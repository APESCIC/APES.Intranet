<?php

namespace App\Core\Access\Http\Controllers;

use App\Core\Access\AccessReview;
use App\Core\Audit\AuditLogger;
use Illuminate\View\View;

class AccessReviewController
{
    public function __invoke(AccessReview $review, AuditLogger $audit): View
    {
        $audit->record('admin.access_review.viewed');

        return view('core.admin.access-review.index', [
            'stacked' => $review->stackedSeniorRoles(),
            'leavers' => $review->leavers(),
            'noGroup' => $review->peopleInNoGroup(),
            'sensitiveViews' => $review->seniorSpecialCategoryViews(),
        ]);
    }
}
