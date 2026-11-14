<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishlink\WebPush\MessageSentReport;
use NotificationChannels\WebPush\PushSubscription;
use NotificationChannels\WebPush\ReportHandler;
use NotificationChannels\WebPush\WebPushMessage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function sampleSubscriptionPayload(string $endpoint = 'https://push.example.test/endpoint/abc'): array
{
    return [
        'endpoint' => $endpoint,
        'keys' => [
            'p256dh' => 'BNcRdreALSVUFxm0i0YCnymtfXEEXVxqRnPoQpN9xq-bGmpb6S0u8r3OQKQKQYKd',
            'auth' => 'tBHIQiVF51eXQuPTqKwK4g',
        ],
        'contentEncoding' => 'aes128gcm',
    ];
}

it('lets a signed-in person store a push subscription against their account', function () {
    $user = User::factory()->create()->assignRole('staff');

    $this->actingAs($user)
        ->postJson(route('push-subscriptions.store'), sampleSubscriptionPayload())
        ->assertCreated()
        ->assertJson(['status' => 'subscribed']);

    expect($user->fresh()->pushSubscriptions)->toHaveCount(1)
        ->and($user->fresh()->pushSubscriptions->first()->endpoint)
        ->toBe('https://push.example.test/endpoint/abc');
});

it('rejects guests who try to store a push subscription', function () {
    $this->postJson(route('push-subscriptions.store'), sampleSubscriptionPayload())
        ->assertUnauthorized();
});

it('removes a push subscription for the signed-in person', function () {
    $user = User::factory()->create()->assignRole('staff');
    $user->updatePushSubscription(
        'https://push.example.test/endpoint/abc',
        'BNcRdreALSVUFxm0i0YCnymtfXEEXVxqRnPoQpN9xq-bGmpb6S0u8r3OQKQKQYKd',
        'tBHIQiVF51eXQuPTqKwK4g',
        'aes128gcm',
    );

    $this->actingAs($user)
        ->deleteJson(route('push-subscriptions.destroy'), [
            'endpoint' => 'https://push.example.test/endpoint/abc',
        ])
        ->assertOk()
        ->assertJson(['status' => 'unsubscribed']);

    expect($user->fresh()->pushSubscriptions)->toHaveCount(0);
});

it('exposes the VAPID public key to signed-in people', function () {
    $user = User::factory()->create()->assignRole('staff');

    $this->actingAs($user)
        ->getJson(route('push.vapid-public-key'))
        ->assertOk()
        ->assertJsonStructure(['publicKey'])
        ->assertJsonPath('publicKey', config('webpush.vapid.public_key'));
});

it('does not expose the VAPID private key', function () {
    $user = User::factory()->create()->assignRole('staff');

    $response = $this->actingAs($user)->getJson(route('push.vapid-public-key'));
    $body = $response->getContent();

    expect($body)->not->toContain((string) config('webpush.vapid.private_key'))
        ->and($body)->not->toContain('privateKey')
        ->and($body)->not->toContain('private_key');
});

it('drops an expired push subscription when the push service returns gone', function () {
    $user = User::factory()->create()->assignRole('staff');
    $subscription = $user->updatePushSubscription(
        'https://push.example.test/endpoint/expired',
        'BNcRdreALSVUFxm0i0YCnymtfXEEXVxqRnPoQpN9xq-bGmpb6S0u8r3OQKQKQYKd',
        'tBHIQiVF51eXQuPTqKwK4g',
        'aes128gcm',
    );

    $request = Mockery::mock(RequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(410);
    $response->shouldReceive('getReasonPhrase')->andReturn('Gone');
    $response->shouldReceive('getBody->getContents')->andReturn('');

    $report = new MessageSentReport($request, $response, false, 'Gone');
    $message = (new WebPushMessage)->title('Test')->body('Generic notice.');

    app(ReportHandler::class)->handleReport($report, $subscription, $message);

    expect(PushSubscription::query()->count())->toBe(0)
        ->and($user->fresh()->pushSubscriptions)->toHaveCount(0);
});

it('serves the push-only service worker at the site root', function () {
    $script = file_get_contents(public_path('sw.js'));

    expect(public_path('sw.js'))->toBeFile()
        ->and($script)->toContain("addEventListener('push'")
        ->and($script)->toContain('showNotification')
        ->and($script)->toContain('pushsubscriptionchange')
        ->and($script)->not->toContain('caches.open')
        ->and($script)->not->toContain('cache.put')
        ->and(file_get_contents(resource_path('js/app.js')))->toContain('registerPushServiceWorker')
        ->and(file_get_contents(resource_path('js/app.js')))->not->toContain('subscribeToPush()')
        ->and(file_get_contents(resource_path('js/push.js')))->toContain('Notification.requestPermission');
});
