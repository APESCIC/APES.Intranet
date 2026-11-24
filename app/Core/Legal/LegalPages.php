<?php

namespace App\Core\Legal;

use App\Core\Legal\Exceptions\OpenLegalDraftExistsException;
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

    public function draftFor(LegalPage $page): ?LegalPageVersion
    {
        return $page->versions()
            ->where('status', LegalPageVersionStatus::Draft)
            ->orderByDesc('number')
            ->first();
    }

    public function openDraft(LegalPage $page): LegalPageVersion
    {
        $existing = $this->draftFor($page);
        if ($existing !== null) {
            return $existing;
        }

        $latestPublished = $page->versions()
            ->where('status', LegalPageVersionStatus::Published)
            ->orderByDesc('number')
            ->first();

        $body = $latestPublished !== null ? $latestPublished->body : '';

        return $this->createDraft($page, $body);
    }

    public function createDraft(LegalPage $page, string $body, ?string $changeSummary = null, bool $materialChange = false): LegalPageVersion
    {
        if ($this->draftFor($page) !== null) {
            throw new OpenLegalDraftExistsException;
        }

        $number = (int) $page->versions()->max('number') + 1;

        return $page->versions()->create([
            'number' => $number,
            'body' => $this->sanitizer->clean($body),
            'change_summary' => $changeSummary,
            'status' => LegalPageVersionStatus::Draft,
            'material_change' => $materialChange,
        ]);
    }

    public function updateDraft(
        LegalPageVersion $version,
        string $body,
        ?string $changeSummary = null,
        bool $materialChange = false,
        ?Carbon $inEffectAt = null,
    ): void {
        if ($version->status === LegalPageVersionStatus::Published) {
            throw new PublishedLegalVersionException;
        }

        $version->update([
            'body' => $this->sanitizer->clean($body),
            'change_summary' => $changeSummary,
            'material_change' => $materialChange,
            'in_effect_at' => $inEffectAt,
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

    /**
     * @return list<LegalPageVersion>
     */
    public function allVersions(LegalPage $page): array
    {
        return $page->versions()->orderByDesc('number')->get()->all();
    }

    public function restore(LegalPage $page, LegalPageVersion $version): LegalPageVersion
    {
        $existing = $this->draftFor($page);
        if ($existing !== null) {
            $this->updateDraft(
                $existing,
                $version->body,
                $version->change_summary,
                $version->material_change,
            );

            return $existing->fresh();
        }

        return $this->createDraft(
            $page,
            $version->body,
            $version->change_summary,
            $version->material_change,
        );
    }

    /**
     * @return list<array{type: 'equal'|'insert'|'delete', text: string}>
     */
    public function compare(LegalPageVersion $from, LegalPageVersion $to): array
    {
        return $this->diffWords(
            $this->wordsFromHtml($from->body),
            $this->wordsFromHtml($to->body),
        );
    }

    /**
     * @return list<string>
     */
    private function wordsFromHtml(string $html): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
        if ($text === '') {
            return [];
        }

        return preg_split('/\s+/u', $text) ?: [];
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     * @return list<array{type: 'equal'|'insert'|'delete', text: string}>
     */
    private function diffWords(array $a, array $b): array
    {
        $rows = count($a) + 1;
        $cols = count($b) + 1;
        $lcs = array_fill(0, $rows, array_fill(0, $cols, 0));

        for ($i = 1; $i < $rows; $i++) {
            for ($j = 1; $j < $cols; $j++) {
                if ($a[$i - 1] === $b[$j - 1]) {
                    $lcs[$i][$j] = $lcs[$i - 1][$j - 1] + 1;
                } else {
                    $lcs[$i][$j] = max($lcs[$i - 1][$j], $lcs[$i][$j - 1]);
                }
            }
        }

        /** @var list<array{type: 'equal'|'insert'|'delete', text: string}> $result */
        $result = [];
        $i = count($a);
        $j = count($b);

        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $a[$i - 1] === $b[$j - 1]) {
                $result = $this->pushDiffToken($result, 'equal', $a[$i - 1]);
                $i--;
                $j--;
            } elseif ($j > 0 && ($i === 0 || $lcs[$i][$j - 1] >= $lcs[$i - 1][$j])) {
                $result = $this->pushDiffToken($result, 'insert', $b[$j - 1]);
                $j--;
            } else {
                $result = $this->pushDiffToken($result, 'delete', $a[$i - 1]);
                $i--;
            }
        }

        return array_reverse($result);
    }

    /**
     * @param  list<array{type: 'equal'|'insert'|'delete', text: string}>  $result
     * @return list<array{type: 'equal'|'insert'|'delete', text: string}>
     */
    private function pushDiffToken(array $result, string $type, string $word): array
    {
        if ($result !== [] && $result[count($result) - 1]['type'] === $type) {
            $lastIndex = count($result) - 1;
            $result[$lastIndex]['text'] .= ' '.$word;

            return $result;
        }

        $result[] = ['type' => $type, 'text' => $word];

        return $result;
    }
}
