<?php

namespace Modules\Hr\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\Access\SensitiveData;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Hr\Models\IdentityCheck;

class IdentityCheckController
{
    public function report(Request $request, SensitiveData $sensitive): View
    {
        abort_unless($request->user()?->can(AccessMatrix::HR_SPECIAL_CATEGORY_VIEW), 403);

        $checks = IdentityCheck::query()
            ->with('user')
            ->orderByRaw('expires_on is null desc')
            ->orderBy('expires_on')
            ->get();

        foreach ($checks as $check) {
            $sensitive->openSpecialCategory($check, $check->check_type === IdentityCheck::TYPE_DBS ? SensitiveData::DBS : 'right_to_work');
        }

        $missingTypes = [];
        $people = User::query()->orderBy('name')->get();
        foreach ($people as $person) {
            foreach ([IdentityCheck::TYPE_DBS, IdentityCheck::TYPE_RIGHT_TO_WORK] as $type) {
                if (! $checks->contains(fn (IdentityCheck $c) => $c->user_id === $person->id && $c->check_type === $type)) {
                    $missingTypes[] = ['user' => $person, 'check_type' => $type];
                }
            }
        }

        $expired = $checks->filter(fn (IdentityCheck $c) => $c->isExpired());
        $expiring = $checks->filter(fn (IdentityCheck $c) => $c->expiresWithinDays(30));

        return view('hr::checks.report', [
            'checks' => $checks,
            'expired' => $expired,
            'expiring' => $expiring,
            'missing' => $missingTypes,
            'canEdit' => $this->canEdit($request->user()),
        ]);
    }

    public function store(Request $request, User $user, SensitiveData $sensitive, AuditLogger $audit): RedirectResponse
    {
        abort_unless($this->canEdit($request->user()), 403);

        $data = $request->validate([
            'check_type' => ['required', Rule::in([IdentityCheck::TYPE_DBS, IdentityCheck::TYPE_RIGHT_TO_WORK])],
            'label' => ['nullable', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:100'],
            'checked_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date'],
            'document_url' => ['nullable', 'url', 'max:500'],
        ]);

        $check = IdentityCheck::query()->create([
            ...$data,
            'user_id' => $user->id,
        ]);

        $sensitive->openSpecialCategory($check, $data['check_type'] === IdentityCheck::TYPE_DBS ? SensitiveData::DBS : 'right_to_work');
        $audit->record('hr.identity_check.created', $check, [
            'member_id' => $user->id,
            'check_type' => $check->check_type,
        ]);

        return redirect()
            ->route('hr.records.show', $user)
            ->with('status', 'Identity check saved (link only — no scan stored).');
    }

    private function canEdit(?User $actor): bool
    {
        return $actor !== null && $actor->hasAnyRole([RoleName::Hr->value, RoleName::Admin->value]);
    }
}
