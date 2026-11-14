<?php

namespace App\Core\Access\Console;

use App\Core\Access\AccessMatrix;
use App\Core\Access\Notifications\AccessReviewDue;
use App\Core\Notifications\AlertDispatcher;
use App\Core\Notifications\NotificationCategory;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class RemindAccessReview extends Command
{
    protected $signature = 'access-review:remind';

    protected $description = 'Email admin and HR that the quarterly access review is due';

    public function handle(): int
    {
        $reviewers = User::permission(AccessMatrix::ACCESS_REVIEW_VIEW)->get();

        Notification::send($reviewers, new AccessReviewDue);
        AlertDispatcher::send(
            $reviewers,
            NotificationCategory::AccountSecurity,
            'You have an account alert on the APES Intranet.',
            'Open the intranet to view it.',
            route('admin.access-review.index'),
        );
        $this->info("Reminded {$reviewers->count()} reviewers.");

        return self::SUCCESS;
    }
}
