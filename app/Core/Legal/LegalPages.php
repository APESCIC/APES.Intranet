<?php

namespace App\Core\Legal;

use App\Core\Legal\Exceptions\PublishedLegalVersionException;
use App\Core\Support\HtmlSanitizer;
use App\Models\LegalPage;
use App\Models\LegalPageVersion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class LegalPages
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function findBySlug(string $slug): ?LegalPage
    {
        return LegalPage::query()->where('slug', $slug)->first();
    }

    public function currentVersion(LegalPage $page, ?Carbon $at = null): ?LegalPageVersion
    {
        return $page->currentVersion($at);
    }

    public function bodyWithHeadingIds(string $html): string
    {
        return preg_replace_callback(
            '/<h2\b([^>]*)>(.*?)<\/h2>/is',
            function (array $match): string {
                $label = trim(strip_tags($match[2]));
                $id = Str::slug($label);
                $attrs = $match[1];
                if ($id === '' || str_contains($attrs, 'id=')) {
                    return $match[0];
                }

                return '<h2'.$attrs.' id="'.e($id).'">'.$match[2].'</h2>';
            },
            $html,
        ) ?? $html;
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function contentsNavigation(string $html): array
    {
        if (! preg_match_all('/<h2\b[^>]*>(.*?)<\/h2>/is', $html, $matches)) {
            return [];
        }

        $items = [];
        foreach ($matches[1] as $heading) {
            $label = trim(html_entity_decode(strip_tags($heading), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($label === '') {
                continue;
            }

            $items[] = [
                'id' => Str::slug($label),
                'label' => $label,
            ];
        }

        return $items;
    }

    public function createDraft(LegalPage $page, string $body, ?string $changeSummary = null, bool $materialChange = false): LegalPageVersion
    {
        $number = (int) $page->versions()->max('number') + 1;

        return $page->versions()->create([
            'number' => $number,
            'body' => $this->sanitizer->clean($body),
            'change_summary' => $changeSummary,
            'status' => LegalPageVersionStatus::Draft,
            'material_change' => $materialChange,
        ]);
    }

    public function updateDraft(LegalPageVersion $version, string $body, ?string $changeSummary = null): void
    {
        if ($version->status === LegalPageVersionStatus::Published) {
            throw new PublishedLegalVersionException;
        }

        $version->update([
            'body' => $this->sanitizer->clean($body),
            'change_summary' => $changeSummary,
        ]);
    }

    public function publish(
        LegalPageVersion $version,
        ?User $publisher = null,
        ?Carbon $inEffectAt = null,
    ): void {
        if ($version->status !== LegalPageVersionStatus::Draft) {
            return;
        }

        $version->update([
            'status' => LegalPageVersionStatus::Published,
            'published_at' => now(),
            'published_by' => $publisher?->id,
            'in_effect_at' => $inEffectAt,
        ]);
    }

    /**
     * @return list<LegalPageVersion>
     */
    public function publishedVersions(LegalPage $page, ?Carbon $at = null): array
    {
        $at ??= now();

        return $page->versions()
            ->where('status', LegalPageVersionStatus::Published)
            ->where(function ($query) use ($at) {
                $query->whereNull('in_effect_at')->orWhere('in_effect_at', '<=', $at);
            })
            ->orderByDesc('number')
            ->get()
            ->all();
    }
}
