<?php

namespace App\Core\AdminDashboard;

use App\Models\User;

/**
 * Resolves and saves per-admin dashboard layouts, falling back to the shared default.
 */
final class AdminDashboardLayouts
{
    /**
     * @param  list<array{
     *     key: string,
     *     title: string,
     *     body: string,
     *     href: ?string,
     *     permission: string,
     *     module?: string|null,
     *     tone?: string,
     *     icon?: string,
     *     external?: bool,
     *     section?: string,
     *     group?: string,
     *     group_heading?: string,
     *     chart?: string,
     *     preserve_newlines?: bool
     * }>  $available
     * @return list<array{
     *     key: string,
     *     title: string,
     *     body: string,
     *     href: ?string,
     *     permission: string,
     *     module?: string|null,
     *     tone?: string,
     *     icon?: string,
     *     external?: bool,
     *     section?: string,
     *     group?: string,
     *     group_heading?: string,
     *     chart?: string,
     *     preserve_newlines?: bool
     * }>
     */
    public function apply(User $user, array $available): array
    {
        $layout = $this->layoutFor($user);
        if ($layout === null) {
            return $available;
        }

        $byKey = [];
        foreach ($available as $card) {
            $byKey[$card['key']] = $card;
        }

        $hidden = array_flip($layout->hidden_keys ?? []);
        $ordered = [];

        foreach ($layout->ordered_keys as $key) {
            if (isset($byKey[$key]) && ! isset($hidden[$key])) {
                $ordered[] = $byKey[$key];
                unset($byKey[$key]);
            }
        }

        foreach ($byKey as $key => $card) {
            if (! isset($hidden[$key])) {
                $ordered[] = $card;
            }
        }

        return $ordered;
    }

    public function layoutFor(User $user): ?AdminDashboardLayout
    {
        return AdminDashboardLayout::query()->where('user_id', $user->id)->first()
            ?? AdminDashboardLayout::query()->whereNull('user_id')->first();
    }

    public function personalLayout(User $user): ?AdminDashboardLayout
    {
        return AdminDashboardLayout::query()->where('user_id', $user->id)->first();
    }

    public function sharedDefault(): ?AdminDashboardLayout
    {
        return AdminDashboardLayout::query()->whereNull('user_id')->first();
    }

    /**
     * @param  list<string>  $orderedKeys
     * @param  list<string>  $hiddenKeys
     */
    public function savePersonal(User $user, array $orderedKeys, array $hiddenKeys = []): AdminDashboardLayout
    {
        return AdminDashboardLayout::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'ordered_keys' => $orderedKeys,
                'hidden_keys' => $hiddenKeys,
            ],
        );
    }

    public function resetPersonal(User $user): void
    {
        AdminDashboardLayout::query()->where('user_id', $user->id)->delete();
    }

    /**
     * @param  list<string>  $orderedKeys
     * @param  list<string>  $hiddenKeys
     */
    public function saveSharedDefault(array $orderedKeys, array $hiddenKeys = []): AdminDashboardLayout
    {
        $existing = $this->sharedDefault();
        if ($existing === null) {
            return AdminDashboardLayout::query()->create([
                'user_id' => null,
                'ordered_keys' => $orderedKeys,
                'hidden_keys' => $hiddenKeys,
            ]);
        }

        $existing->forceFill([
            'ordered_keys' => $orderedKeys,
            'hidden_keys' => $hiddenKeys,
        ])->save();

        return $existing;
    }

    /**
     * @param  list<array{key: string, title: string}>  $available
     * @return list<array{key: string, title: string}>
     */
    public function hiddenCards(User $user, array $available): array
    {
        $layout = $this->layoutFor($user);
        if ($layout === null || ($layout->hidden_keys ?? []) === []) {
            return [];
        }

        $hidden = array_flip($layout->hidden_keys);

        return array_values(array_filter(
            $available,
            fn (array $card) => isset($hidden[$card['key']]),
        ));
    }
}
