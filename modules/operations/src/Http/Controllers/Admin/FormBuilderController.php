<?php

namespace Modules\Operations\Http\Controllers\Admin;

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Operations\Models\Form;
use Modules\Operations\Models\FormField;
use Modules\Operations\Services\FormSubmissions;

class FormBuilderController
{
    public function index(): View
    {
        return view('operations::admin.forms.index', [
            'forms' => Form::query()->withCount(['fields', 'submissions'])->orderByDesc('updated_at')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $form = Form::query()->create($this->validated($request) + [
            'status' => Form::STATUS_DRAFT,
            'created_by' => $request->user()->getKey(),
        ]);
        $audit->record('operations.form_created', $form, ['title' => $form->title]);

        return redirect()->route('admin.operations.forms.show', $form)->with('status', 'Draft form created. Add its fields, then publish it.');
    }

    public function show(Form $form): View
    {
        return view('operations::admin.forms.show', [
            'form' => $form->load(['fields', 'routeToUser'])->loadCount('submissions'),
            'members' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'roles' => RoleName::cases(),
        ]);
    }

    public function update(Request $request, Form $form, AuditLogger $audit): RedirectResponse
    {
        $form->update($this->validated($request));
        $audit->record('operations.form_updated', $form, ['title' => $form->title]);

        return back()->with('status', 'Form saved.');
    }

    public function addField(Request $request, Form $form): RedirectResponse
    {
        abort_if($form->isPublished(), 422, 'Unpublish the form before changing its fields.');

        $data = $request->validate([
            'label' => ['required', 'string', 'max:200'],
            'type' => ['required', Rule::in(array_keys(FormField::TYPES))],
            'required' => ['nullable', 'boolean'],
            'options' => ['required_if:type,select', 'nullable', 'string', 'max:2000'],
        ]);

        $options = $data['type'] === 'select'
            ? array_values(array_filter(array_map('trim', explode(',', (string) $data['options']))))
            : null;

        $form->fields()->create([
            'label' => $data['label'],
            'type' => $data['type'],
            'required' => (bool) ($data['required'] ?? false),
            'options' => $options,
            'sort_order' => (int) $form->fields()->max('sort_order') + 10,
        ]);

        return back()->with('status', 'Field added.');
    }

    public function removeField(Form $form, FormField $field): RedirectResponse
    {
        abort_unless((int) $field->form_id === (int) $form->id, 404);
        abort_if($form->isPublished() || $form->submissions()->exists(), 422, 'Fields with submissions cannot be removed.');

        $field->delete();

        return back()->with('status', 'Field removed.');
    }

    public function publish(Request $request, Form $form, AuditLogger $audit): RedirectResponse
    {
        $publish = $request->boolean('publish');

        if ($publish && ($form->fields()->doesntExist() || ($form->route_to_user_id === null && $form->route_to_role === null))) {
            return back()->withErrors(['publish' => 'Add at least one field and choose who receives submissions before publishing.']);
        }

        $form->forceFill([
            'status' => $publish ? Form::STATUS_PUBLISHED : Form::STATUS_DRAFT,
            'published_at' => $publish ? now() : $form->published_at,
        ])->save();
        $audit->record($publish ? 'operations.form_published' : 'operations.form_unpublished', $form, ['title' => $form->title]);

        return back()->with('status', $publish ? 'Form published. Members can fill it in now.' : 'Form unpublished.');
    }

    public function export(Form $form, FormSubmissions $submissions, AuditLogger $audit): Response
    {
        $audit->record('operations.form_exported', $form, ['submissions' => $form->submissions()->count()]);

        return response($submissions->csv($form), 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="form-'.$form->id.'-submissions.csv"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'route_to_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'route_to_role' => ['nullable', Rule::in(array_map(fn (RoleName $r) => $r->value, RoleName::cases()))],
        ]);

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'route_to_user_id' => $data['route_to_user_id'] ?? null,
            'route_to_role' => $data['route_to_role'] ?? null,
        ];
    }
}
