<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Operations\Mail\OperationsNoticeMail;
use Modules\Operations\Models\Form;
use Modules\Operations\Models\FormAnswer;
use Modules\Operations\Models\FormSubmission;
use Tests\Support\Operations;

uses(RefreshDatabase::class);

beforeEach(function () {
    Operations::seedRoles();
    Mail::fake();
    Storage::fake('local');
    $this->admin = Operations::member(RoleName::Admin);
    $this->member = Operations::member();
    $this->hr = Operations::member(RoleName::Hr, ['email' => 'hr@example.test', 'team' => 'Office']);
});

function incidentForm(): Form
{
    $form = Form::query()->create(['title' => 'Incident report', 'status' => Form::STATUS_DRAFT, 'route_to_role' => RoleName::Hr->value]);
    $form->fields()->createMany([
        ['label' => 'What happened?', 'type' => 'textarea', 'required' => true, 'sort_order' => 10],
        ['label' => 'Area', 'type' => 'select', 'required' => true, 'options' => ['Reptiles', 'Birds'], 'sort_order' => 20],
        ['label' => 'Photo', 'type' => 'photo', 'required' => true, 'sort_order' => 30],
        ['label' => 'Signature', 'type' => 'signature', 'required' => true, 'sort_order' => 40],
        ['label' => 'First aid given', 'type' => 'checkbox', 'required' => false, 'sort_order' => 50],
    ]);
    $form->forceFill(['status' => Form::STATUS_PUBLISHED, 'published_at' => now()])->save();

    return $form->load('fields');
}

function pngDataUrl(): string
{
    return 'data:image/png;base64,'.base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
}

it('lets an admin build and publish a form', function () {
    $this->actingAs($this->admin)->post(route('admin.operations.forms.store'), ['title' => 'Lost property'])->assertRedirect();
    $form = Form::query()->firstOrFail();

    $this->actingAs($this->admin)->post(route('admin.operations.forms.publish', $form), ['publish' => 1])->assertSessionHasErrors('publish');

    $this->actingAs($this->admin)->post(route('admin.operations.forms.fields.store', $form), ['label' => 'Item', 'type' => 'text', 'required' => 1]);
    $this->actingAs($this->admin)->post(route('admin.operations.forms.fields.store', $form), ['label' => 'Where', 'type' => 'select', 'options' => 'Office, Shop, Car park']);
    $this->actingAs($this->admin)->put(route('admin.operations.forms.update', $form), ['title' => 'Lost property', 'route_to_user_id' => $this->hr->id]);
    $this->actingAs($this->admin)->post(route('admin.operations.forms.publish', $form), ['publish' => 1])->assertSessionHasNoErrors();

    expect($form->refresh()->isPublished())->toBeTrue()
        ->and($form->fields()->pluck('label')->all())->toBe(['Item', 'Where'])
        ->and($form->fields()->where('label', 'Where')->first()->options)->toBe(['Office', 'Shop', 'Car park'])
        ->and(AuditLog::query()->where('event', 'operations.form_published')->exists())->toBeTrue();

    $this->actingAs($this->member)->get(route('operations.forms.index'))->assertOk()->assertSee('Lost property');
    $this->actingAs($this->member)->get(route('admin.operations.forms.index'))->assertForbidden();
});

it('saves a submission with a photo and signature and routes it to the role', function () {
    $form = incidentForm();
    [$what, $area, $photo, $signature] = $form->fields->all();

    $response = $this->actingAs($this->member)->post(route('operations.forms.submit', $form), [
        'field_'.$what->id => 'A tortoise escaped its pen',
        'field_'.$area->id => 'Reptiles',
        'field_'.$photo->id => UploadedFile::fake()->image('pen.jpg', 800, 600),
        'field_'.$signature->id => pngDataUrl(),
    ]);

    $submission = FormSubmission::query()->with('answers')->firstOrFail();
    $response->assertRedirect(route('operations.forms.submissions.show', $submission));

    $files = $submission->answers->whereNotNull('file_path');
    expect($files)->toHaveCount(2)
        ->and($submission->routed_to_role)->toBe(RoleName::Hr->value)
        ->and($submission->answers->firstWhere('field_id', $area->id)->value)->toBe('Reptiles');
    foreach ($files as $answer) {
        Storage::disk('local')->assertExists($answer->file_path);
    }

    Mail::assertSent(OperationsNoticeMail::class, fn (OperationsNoticeMail $mail) => $mail->hasTo('hr@example.test'));
    $this->actingAs($this->hr)->get(route('operations.forms.inbox'))->assertOk()->assertSee('Incident report');
    $this->actingAs($this->hr)->get(route('operations.forms.submissions.show', $submission))->assertOk()->assertSee('A tortoise escaped its pen');
    $this->actingAs($this->hr)->get(route('operations.forms.files.show', $files->first()))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

    $outsider = Operations::member(attributes: ['team' => 'Office']);
    $this->actingAs($outsider)->get(route('operations.forms.submissions.show', $submission))->assertForbidden();
    $this->actingAs($outsider)->get(route('operations.forms.files.show', $files->first()))->assertForbidden();
});

it('enforces required fields', function () {
    $form = incidentForm();
    [$what, $area, $photo, $signature] = $form->fields->all();

    $this->actingAs($this->member)->post(route('operations.forms.submit', $form), [
        'field_'.$area->id => 'Fish',
    ])->assertSessionHasErrors(['field_'.$what->id, 'field_'.$area->id, 'field_'.$photo->id, 'field_'.$signature->id.'_typed']);

    expect(FormSubmission::query()->count())->toBe(0);
});

it('accepts a typed name instead of a drawn signature and rejects files that are not photos', function () {
    $form = incidentForm();
    [$what, $area, $photo, $signature] = $form->fields->all();
    $base = ['field_'.$what->id => 'Fell over', 'field_'.$area->id => 'Birds', 'field_'.$signature->id.'_typed' => 'Alex Example'];

    $this->actingAs($this->member)->post(route('operations.forms.submit', $form), $base + [
        'field_'.$photo->id => UploadedFile::fake()->create('notes.svg', 4, 'image/svg+xml'),
    ])->assertSessionHasErrors('field_'.$photo->id);

    $this->actingAs($this->member)->post(route('operations.forms.submit', $form), $base + [
        'field_'.$photo->id => UploadedFile::fake()->image('floor.png'),
    ])->assertRedirect();

    expect(FormAnswer::query()->where('field_id', $signature->id)->value('value'))->toBe('Alex Example');
});

it('exports submissions to CSV for admins', function () {
    $form = incidentForm();
    [$what, $area, $photo, $signature] = $form->fields->all();
    $this->actingAs($this->member)->post(route('operations.forms.submit', $form), [
        'field_'.$what->id => '=HYPERLINK("x")',
        'field_'.$area->id => 'Birds',
        'field_'.$photo->id => UploadedFile::fake()->image('a.jpg'),
        'field_'.$signature->id.'_typed' => 'Alex Example',
    ]);

    $csv = $this->actingAs($this->admin)->get(route('admin.operations.forms.export', $form))->assertOk()->getContent();
    $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));

    expect($rows[0])->toBe(['Submitted at', 'Submitted by', 'What happened?', 'Area', 'Photo', 'Signature', 'First aid given'])
        ->and($rows[1][2])->toBe("'=HYPERLINK(\"x\")")
        ->and($rows[1][4])->toBe('File attached')
        ->and($rows[1][6])->toBe('No');
});
