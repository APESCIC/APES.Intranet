<?php

namespace App\Core\AdminDashboard;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Email match for the organisation Director (not a Spatie role).
 */
final class DirectorGate
{
    public function allows(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $director = (string) config('intranet.director_email', '');
        if ($director === '') {
            return false;
        }

        return Str::lower((string) $user->email) === Str::lower($director);
    }
}
