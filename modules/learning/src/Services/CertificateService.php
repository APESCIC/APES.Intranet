<?php

namespace Modules\Learning\Services;

use App\Core\Notifications\AlertDispatcher;
use App\Core\Notifications\NotificationCategory;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;
use Modules\Learning\Models\Certificate;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseCompletion;
use Modules\Learning\Models\Enrolment;
use Modules\Learning\Models\LessonProgress;

class CertificateService
{
    public function __construct(
        private readonly EnrolmentService $enrolments,
    ) {}

    public function issueForCompletion(User $learner, Course $course, CourseCompletion $completion): Certificate
    {
        $expiresOn = null;
        if ($course->certificate_renew_months !== null && $course->certificate_renew_months > 0) {
            $expiresOn = now()->startOfDay()->addMonths($course->certificate_renew_months)->toDateString();
        }

        $existing = Certificate::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->first();

        $verifyCode = $existing !== null ? $existing->verify_code : $this->uniqueVerifyCode();

        return Certificate::query()->updateOrCreate(
            [
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ],
            [
                'course_completion_id' => $completion->id,
                'verify_code' => $verifyCode,
                'cpd_hours' => $course->cpd_hours,
                'issued_at' => now(),
                'expires_on' => $expiresOn,
                'expiry_reminded_at' => null,
                'revoked_at' => null,
            ],
        );
    }

    public function findByVerifyCode(string $code): ?Certificate
    {
        $normalised = strtoupper(trim($code));

        return Certificate::query()
            ->with(['user', 'course'])
            ->where('verify_code', $normalised)
            ->first();
    }

    public function pdf(Certificate $certificate): string
    {
        $certificate->loadMissing(['user', 'course']);

        $html = view('learning::certificates.pdf', [
            'certificate' => $certificate,
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    public function handleExpiryReminders(int $days = 30): int
    {
        $days = max(1, $days);
        $today = now()->startOfDay();
        $until = $today->copy()->addDays($days);

        $due = Certificate::query()
            ->with('user')
            ->whereNull('revoked_at')
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '>=', $today->toDateString())
            ->whereDate('expires_on', '<=', $until->toDateString())
            ->where(function ($query) use ($today) {
                $query->whereNull('expiry_reminded_at')
                    ->orWhereDate('expiry_reminded_at', '<', $today->toDateString());
            })
            ->get();

        foreach ($due as $certificate) {
            if ($certificate->user === null) {
                continue;
            }

            AlertDispatcher::send(
                [$certificate->user],
                NotificationCategory::Announcements,
                'Your learning certificate is due to expire',
                'A course certificate on the APES Intranet will expire soon. Open My learning to retrain before it lapses.',
                route('learning.mine'),
            );

            $certificate->forceFill(['expiry_reminded_at' => now()])->save();
        }

        return $due->count();
    }

    public function expireAndReenrol(): int
    {
        $today = now()->startOfDay()->toDateString();

        $expired = Certificate::query()
            ->with(['user', 'course'])
            ->whereNull('revoked_at')
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '<', $today)
            ->get();

        $count = 0;

        foreach ($expired as $certificate) {
            $this->revokeAndReset($certificate);
            $count++;
        }

        return $count;
    }

    public function revokeAndReset(Certificate $certificate): void
    {
        $learner = $certificate->user;
        $course = $certificate->course;

        $certificate->forceFill(['revoked_at' => now()])->save();

        if ($learner === null || $course === null) {
            return;
        }

        CourseCompletion::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->delete();

        $lessonIds = $course->sections()
            ->with('lessons')
            ->get()
            ->flatMap(fn ($section) => $section->lessons->pluck('id'));

        if ($lessonIds->isNotEmpty()) {
            LessonProgress::query()
                ->where('user_id', $learner->id)
                ->whereIn('lesson_id', $lessonIds)
                ->delete();
        }

        $enrolment = Enrolment::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->first();

        if ($enrolment === null) {
            $this->enrolments->enrolManual($course, $learner, $learner);
        } else {
            $enrolment->update([
                'status' => Enrolment::STATUS_ACTIVE,
                'ended_at' => null,
            ]);
        }

        AlertDispatcher::send(
            [$learner],
            NotificationCategory::Announcements,
            'Your learning certificate has expired',
            'A course certificate on the APES Intranet has expired. You have been re-enrolled so you can complete the course again. No grade details are included in this notice.',
            route('learning.mine'),
        );
    }

    private function uniqueVerifyCode(): string
    {
        do {
            $code = Str::upper(Str::random(12));
        } while (Certificate::query()->where('verify_code', $code)->exists());

        return $code;
    }
}
