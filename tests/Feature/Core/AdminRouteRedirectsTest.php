<?php

use App\Core\Access\RoleName;
use Database\Seeders\HrPermissionsSeeder;
use Database\Seeders\Microsoft365PermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Helpdesk;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(HrPermissionsSeeder::class);
    $this->seed(Microsoft365PermissionsSeeder::class);
    Helpdesk::seed();
});

it('permanently redirects legacy module admin URLs to admin slash slug paths', function (string $legacy, string $target) {
    $admin = Helpdesk::member(RoleName::Admin);

    $this->actingAs($admin)->get($legacy)->assertRedirect($target);
})->with([
    'people 2FA status' => ['/admin/2fa-status', '/admin/people/2fa-status'],
    'helpdesk topics' => ['/admin/help-desk/topics', '/admin/helpdesk/topics'],
    'Microsoft 365 links' => ['/admin/m365-links', '/admin/microsoft-365/links'],
]);

it('permanently redirects legacy help desk topic edit URLs', function () {
    $admin = Helpdesk::member(RoleName::Admin);
    $topic = Helpdesk::category('devices');

    $this->actingAs($admin)
        ->get('/admin/help-desk/topics/'.$topic->id)
        ->assertRedirect('/admin/helpdesk/topics/'.$topic->id);
});
