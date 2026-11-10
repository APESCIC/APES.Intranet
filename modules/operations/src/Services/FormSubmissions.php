<?php

namespace Modules\Operations\Services;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Operations\Models\Form;
use Modules\Operations\Models\FormField;
use Modules\Operations\Models\FormSubmission;
use Modules\Operations\Support\Csv;
use Modules\Operations\Support\Notifier;
use Modules\Operations\Support\Uploads;
use RuntimeException;
use Throwable;

final class FormSubmissions
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return array<string, mixed>
     */
    public function rules(Form $form): array
    {
        $rules = [];

        foreach ($form->fields as $field) {
            $name = $field->inputName();
            $presence = $field->required ? 'required' : 'nullable';

            $rules[$name] = match ($field->type) {
                'textarea' => [$presence, 'string', 'max:10000'],
                'number' => [$presence, 'numeric'],
                'date' => [$presence, 'date'],
                'select' => [$presence, 'string', Rule::in($field->options ?? [])],
                'checkbox' => $field->required ? ['accepted'] : ['nullable', 'boolean'],
                'photo' => Uploads::photoRules($field->required),
                'signature' => Uploads::signatureRules(),
                default => [$presence, 'string', 'max:1000'],
            };

            // A typed name is the keyboard-friendly alternative to drawing a signature.
            if ($field->type === 'signature') {
                $rules[$name.'_typed'] = $field->required
                    ? ['required_without:'.$name, 'nullable', 'string', 'max:200']
                    : ['nullable', 'string', 'max:200'];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(Form $form): array
    {
        return $form->fields->mapWithKeys(fn (FormField $field) => [
            $field->inputName() => $field->label,
            $field->inputName().'_typed' => $field->label,
        ])->all();
    }

    public function submit(Form $form, User $member, Request $request): FormSubmission
    {
        if (! $form->isPublished()) {
            throw new RuntimeException('This form is not open.');
        }

        $form->loadMissing('fields');
        $data = $request->validate($this->rules($form), [], $this->attributes($form));

        $stored = [];

        try {
            $submission = DB::transaction(function () use ($form, $member, $request, $data, &$stored) {
                $submission = FormSubmission::query()->create([
                    'form_id' => $form->id,
                    'user_id' => $member->getKey(),
                    'routed_to_user_id' => $form->route_to_user_id,
                    'routed_to_role' => $form->route_to_role,
                ]);
                $directory = 'operations/forms/'.$form->id.'/'.$submission->id;

                foreach ($form->fields as $field) {
                    $name = $field->inputName();
                    $value = null;
                    $path = null;

                    if ($field->type === 'photo' && $request->hasFile($name)) {
                        $path = $stored[] = Uploads::storePhoto($request->file($name), $directory);
                    } elseif ($field->type === 'signature' && filled($data[$name] ?? null)) {
                        try {
                            $path = $stored[] = Uploads::storeSignature($data[$name], $directory);
                        } catch (RuntimeException $e) {
                            throw ValidationException::withMessages([$name => $e->getMessage()]);
                        }
                    } elseif ($field->type === 'signature') {
                        $value = $data[$name.'_typed'] ?? null;
                    } elseif ($field->type === 'checkbox') {
                        $value = ! empty($data[$name]) ? 'Yes' : 'No';
                    } else {
                        $value = isset($data[$name]) ? (string) $data[$name] : null;
                    }

                    $submission->answers()->create(['field_id' => $field->id, 'value' => $value, 'file_path' => $path]);
                }

                return $submission;
            });
        } catch (Throwable $e) {
            foreach ($stored as $path) {
                Storage::disk((string) config('operations.uploads.disk'))->delete($path);
            }

            throw $e;
        }

        $this->audit->record('operations.form_submitted', $submission, ['form_id' => $form->id], $member);

        Notifier::send(
            $this->recipients($submission),
            'New form submission: '.$form->title,
            [$member->name.' submitted "'.$form->title.'".'],
            route('operations.forms.submissions.show', $submission),
        );

        return $submission;
    }

    /**
     * @return Collection<int, User>
     */
    public function recipients(FormSubmission $submission): Collection
    {
        $people = collect();

        if ($submission->routed_to_user_id !== null) {
            $people = $people->merge(User::query()->active()->whereKey($submission->routed_to_user_id)->get());
        }

        if ($submission->routed_to_role !== null) {
            $people = $people->merge(User::query()->active()->role($submission->routed_to_role)->get());
        }

        return $people->unique('id')->values();
    }

    public function csv(Form $form): string
    {
        $form->load(['fields', 'submissions.submitter', 'submissions.answers']);

        $headers = array_merge(['Submitted at', 'Submitted by'], $form->fields->pluck('label')->all());
        $rows = $form->submissions->map(function (FormSubmission $submission) use ($form) {
            $answers = $submission->answers->keyBy('field_id');

            return array_merge(
                [$submission->created_at->format('Y-m-d H:i'), $submission->submitter->name ?? 'Former member'],
                $form->fields->map(fn (FormField $field) => $answers->get($field->id)?->file_path !== null
                    ? 'File attached'
                    : $answers->get($field->id)?->value)->all(),
            );
        });

        return Csv::build($headers, $rows);
    }
}
