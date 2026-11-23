<?php

namespace App\Core\Navigation;

final readonly class NavGroup
{
    /**
     * @param  list<NavItem>  $items
     * @param  ?string  $note  A short help line shown under the group's links.
     * @param  array<string, string>  $noteLinks  Link label => URL, shown after the note.
     * @param  ?string  $foldInto  Merge this group's items into another group by key and hide it.
     * @param  ?string  $presentAs  Top-bar heading override for this group.
     */
    public function __construct(
        public string $key,
        public string $heading,
        public array $items,
        public ?string $note = null,
        public array $noteLinks = [],
        public ?string $icon = null,
        public ?string $foldInto = null,
        public ?string $presentAs = null,
    ) {}

    public function sectionIcon(): string
    {
        if ($this->icon !== null) {
            return $this->icon;
        }

        return $this->items[0]->icon;
    }

    /**
     * @param  list<NavItem>  $items
     */
    public function withItems(array $items): self
    {
        return new self(
            $this->key,
            $this->heading,
            $items,
            $this->note,
            $this->noteLinks,
            $this->icon,
            $this->foldInto,
            $this->presentAs,
        );
    }

    public function withHeading(string $heading): self
    {
        return new self(
            $this->key,
            $heading,
            $this->items,
            $this->note,
            $this->noteLinks,
            $this->icon,
            $this->foldInto,
            $this->presentAs,
        );
    }
}
