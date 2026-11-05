<?php

namespace Tests\Fakes;

use App\Core\Access\Contracts\BelongsToTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A rota, timesheet or HR record stand-in that is never saved.
 */
final class FakeTeamRecord extends Model implements BelongsToTeam
{
    /**
     * @param  list<int>  $supervisorIds
     */
    public static function make(int $id, array $supervisorIds = []): self
    {
        $record = new self;
        $record->setAttribute('id', $id);
        $record->supervisorIds = $supervisorIds;

        return $record;
    }

    /** @var list<int> */
    public array $supervisorIds = [];

    public function isSupervisedBy(User $user): bool
    {
        return in_array($user->getKey(), $this->supervisorIds, true);
    }
}
