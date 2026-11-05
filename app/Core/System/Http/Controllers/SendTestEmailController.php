<?php

namespace App\Core\System\Http\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\System\Notifications\TestEmail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Sends a test email to the signed-in admin. There is deliberately no address
 * field: the recipient is always the person who pressed the button.
 */
class SendTestEmailController
{
    public const PER_HOUR = 3;

    public function __invoke(Request $request, AuditLogger $audit): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $key = 'test-email:'.$admin->getKey();

        if (RateLimiter::tooManyAttempts($key, self::PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return redirect()->route('admin.system.index')
                ->with('error', 'You can send '.self::PER_HOUR." test emails an hour. Try again in {$minutes} minutes.");
        }

        RateLimiter::hit($key, 3600);

        try {
            Notification::sendNow($admin, new TestEmail);
        } catch (Throwable $exception) {
            Log::warning('Test email failed', ['exception' => $exception::class]);
            $audit->record('admin.system.test_email', $admin, ['result' => 'failed']);

            return redirect()->route('admin.system.index')
                ->with('error', 'The test email could not be sent. Check the Sendmail status above.');
        }

        $audit->record('admin.system.test_email', $admin, ['result' => 'sent']);

        return redirect()->route('admin.system.index')
            ->with('status', "Test email sent to {$admin->email}. It should arrive in a minute or two.");
    }
}
