<?php

namespace App\Core\Legal;

use App\Core\Access\AccessMatrix;
use App\Models\LegalAcceptance;
use App\Models\LegalPage;
use App\Models\LegalPageVersion;
use App\Models\User;
use Illuminate\Support\Collection;

final class AcceptanceReport
{
    public function __construct(
        private readonly TermsAcceptance $termsAcceptance,
        private readonly LegalPages $legalPages,
    ) {}

    /**
     * @return array{page: LegalPage|null, version: LegalPageVersion|null}
     */
    public function termsContext(): array
    {
        $page = $this->termsAcceptance->termsPage();
        $version = $page !== null ? $this->legalPages->currentVersion($page) : null;

        return [
            'page' => $page,
            'version' => $version,
        ];
    }

    /**
     * @return array{
     *     page: LegalPage|null,
     *     version: LegalPageVersion|null,
     *     rows: list<array<string, mixed>>,
     *     accepted_count: int,
     *     pending_count: int
     * }
     */
    public function build(string $status = 'all'): array
    {
        ['page' => $page, 'version' => $version] = $this->termsContext();

        $users = User::query()
            ->active()
            ->permission(AccessMatrix::INTRANET_ACCESS)
            ->orderBy('name')
            ->get();

        $acceptancesByUser = $this->acceptancesForVersion($version, $users);

        $rows = [];
        $acceptedCount = 0;
        $pendingCount = 0;

        foreach ($users as $user) {
            $acceptance = $version !== null ? $acceptancesByUser->get($user->id) : null;
            $isAccepted = $acceptance !== null;

            if ($isAccepted) {
                $acceptedCount++;
            } else {
                $pendingCount++;
            }

            $statusKey = $isAccepted ? 'accepted' : 'pending';

            if ($status === 'accepted' && ! $isAccepted) {
                continue;
            }

            if ($status === 'pending' && $isAccepted) {
                continue;
            }

            $rowVersion = null;
            if ($isAccepted) {
                $rowVersion = $version->number;
            }

            $rows[] = [
                'user' => $user,
                'status' => $isAccepted ? 'Accepted' : 'Not yet',
                'status_key' => $statusKey,
                'accepted_at' => $acceptance instanceof LegalAcceptance ? $acceptance->accepted_at : null,
                'version_number' => $rowVersion,
            ];
        }

        return [
            'page' => $page,
            'version' => $version,
            'rows' => $rows,
            'accepted_count' => $acceptedCount,
            'pending_count' => $pendingCount,
        ];
    }

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<int, LegalAcceptance>
     */
    private function acceptancesForVersion(?LegalPageVersion $version, Collection $users): Collection
    {
        if ($version === null || $users->isEmpty()) {
            return collect();
        }

        return LegalAcceptance::query()
            ->where('legal_page_version_id', $version->id)
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->keyBy('user_id');
    }
}
