<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Audit\AuditLogger;
use App\Core\Legal\LegalAuditEvents;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('defines every legal audit event name', function () {
    expect(LegalAuditEvents::all())->toBe([
        LegalAuditEvents::PAGE_DRAFT_SAVED,
        LegalAuditEvents::PAGE_PUBLISHED,
        LegalAuditEvents::PAGE_RESTORED,
        LegalAuditEvents::FOOTER_UPDATED,
        LegalAuditEvents::ACCEPTANCE_EXPORTED,
        LegalAuditEvents::TERMS_ACCEPTED,
    ]);
});

it('records each legal audit event', function (string $event) {
    $actor = User::factory()->create()->assignRole(RoleName::Legal->value);

    app(AuditLogger::class)->record($event, null, ['source' => 'test'], $actor);

    $entry = AuditLog::where('event', $event)->firstOrFail();

    expect($entry->actor_id)->toBe($actor->id)
        ->and($entry->context)->toMatchArray(['source' => 'test']);
})->with(LegalAuditEvents::all());
