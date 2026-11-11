<?php

namespace App\Core\Navigation;

final readonly class NavGroup
{
    /**
     * @param  list<NavItem>  $items
     * @param  ?string  $note  A short help line shown under the group's links.
     * @param  array<string, string>  $noteLinks  Link label => URL, shown after the note.
     */
    public function __construct(
        public string $key,
        public string $heading,
        public array $items,
        public ?string $note = null,
        public array $noteLinks = [],
    ) {}

    /**
     * @param  list<NavItem>  $items
     */
    public function withItems(array $items): self
    {
        return new self($this->key, $this->heading, $items, $this->note, $this->noteLinks);
    }
}
