<?php

namespace Modules\Operations\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Operations\Models\Form;
use Modules\Operations\Models\FormAnswer;
use Modules\Operations\Models\FormSubmission;
use Modules\Operations\OperationsPermissions;
use Modules\Operations\Services\FormSubmissions;
use Modules\Operations\Support\Uploads;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormController
{
    public function index(Request $request): View
    {
        return view('operations::forms.index', [
            'forms' => Form::query()->where('status', Form::STATUS_PUBLISHED)->orderBy('title')->get(),
            'mine' => FormSubmission::query()->with('form')->where('user_id', $request->user()->getKey())->latest()->limit(20)->get(),
            'inboxCount' => FormSubmission::query()->routedTo($request->user())->count(),
        ]);
    }

    public function show(Form $form): View
    {
        abort_unless($form->isPublished(), 404);

        return view('operations::forms.fill', ['form' => $form->load('fields')]);
    }

    public function submit(Request $request, Form $form, FormSubmissions $submissions): RedirectResponse
    {
        abort_unless($form->isPublished(), 404);

        try {
            $submission = $submissions->submit($form, $request->user(), $request);
        } catch (RuntimeException $e) {
            return back()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()->route('operations.forms.submissions.show', $submission)->with('status', 'Thank you. Your form has been sent to '.$form->routingLabel().'.');
    }

    public function inbox(Request $request): View
    {
        return view('operations::forms.inbox', [
            'submissions' => FormSubmission::query()
                ->with(['form', 'submitter'])
                ->routedTo($request->user())
                ->latest()
                ->paginate(30),
        ]);
    }

    public function submission(Request $request, FormSubmission $submission): View
    {
        $this->authoriseView($request, $submission);

        return view('operations::forms.submission', [
            'submission' => $submission->load(['form.fields', 'submitter', 'answers']),
        ]);
    }

    public function file(Request $request, FormAnswer $answer): StreamedResponse
    {
        $this->authoriseView($request, $answer->submission);
        abort_if($answer->file_path === null, 404);

        return Uploads::response($answer->file_path);
    }

    private function authoriseView(Request $request, FormSubmission $submission): void
    {
        $user = $request->user();

        abort_unless(
            (int) $submission->user_id === (int) $user->getKey()
                || $submission->isRoutedTo($user)
                || $user->can(OperationsPermissions::FORMS_MANAGE),
            403,
        );
    }
}
