<?php

namespace App\Core\Access;

use App\Core\Audit\AuditLogger;
use App\Core\Auth\Exceptions\FreshSignInRequired;
use App\Core\Auth\FreshSignIn;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The one way modules open HR, special category and legal records. It checks
 * the matrix gate and a fresh sign-in (for every role, admin included), and
 * writes the audit entries the groups decision requires.
 */
final class SensitiveData
{
    public const DBS = 'dbs';

    public const HEALTH = 'health';

    public function __construct(private readonly AuditLogger $audit, private readonly Request $request) {}

    public function openHrRecord(Model $record): void
    {
        $this->authorize('viewHrRecord');

        if ($this->viewerIsAdminOrManagement()) {
            $this->audit->record('hr.record.viewed', $record);
        }
    }

    /**
     * DBS and health data are UK GDPR special category data: every view is
     * audited, whoever opens it.
     */
    public function openSpecialCategory(Model $record, string $category): void
    {
        $this->authorize('viewSpecialCategory');
        $this->audit->record('hr.special_category.viewed', $record, ['category' => $category]);
    }

    public function openLegalRecord(Model $record): void
    {
        $this->authorize('viewLegalRecord');

        if ($this->viewerIsAdminOrManagement()) {
            $this->audit->record('legal.record.viewed', $record);
        }
    }

    private function authorize(string $ability): void
    {
        Gate::authorize($ability);

        if (! (new FreshSignIn($this->request->session()))->isFresh()) {
            throw new FreshSignInRequired('A recent Cloudron sign-in is needed to open this record.');
        }
    }

    private function viewerIsAdminOrManagement(): bool
    {
        $user = $this->request->user();

        return $user instanceof User && $user->hasAnyRole([RoleName::Admin->value, RoleName::Management->value]);
    }
}
