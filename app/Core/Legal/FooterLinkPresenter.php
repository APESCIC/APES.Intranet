<?php

namespace App\Core\Legal;

use App\Models\FooterLink;

/**
 * Renders a single footer link for the public shell (grouped UI arrives in #447).
 */
final class FooterLinkPresenter
{
    public function __construct(
        private readonly FooterLinks $footerLinks,
    ) {}

    public function anchor(FooterLink $link, ?string $ariaCurrent = null): string
    {
        $href = $this->footerLinks->href($link);
        if ($href === null) {
            return e($link->label);
        }

        $external = $this->footerLinks->isExternal($link);
        $attrs = [
            'href' => $href,
        ];

        if ($ariaCurrent !== null) {
            $attrs['aria-current'] = $ariaCurrent;
        }

        if ($external) {
            $attrs['target'] = '_blank';
            $attrs['rel'] = 'noopener';
        }

        $attrString = collect($attrs)
            ->map(fn ($value, $key) => $key.'="'.e($value).'"')
            ->implode(' ');

        $label = e($link->label);
        $externalHint = $external
            ? '<span class="sr-only"> (opens another site)</span>'
            : '';

        return "<a {$attrString}>{$label}{$externalHint}</a>";
    }
}
