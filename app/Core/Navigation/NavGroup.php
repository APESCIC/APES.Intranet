<?php

namespace App\Core\Navigation;

final readonly class NavGroup
{
    /**
     * @param  list<NavItem>  $items
     */
    public function __construct(
        public string $key,
        public string $heading,
        public array $items,
    ) {}
}
