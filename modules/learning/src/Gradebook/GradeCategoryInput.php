<?php

namespace Modules\Learning\Gradebook;

final readonly class GradeCategoryInput
{
    /**
     * @param  list<GradeItemInput>  $items
     */
    public function __construct(
        public string $id,
        public float $weight,
        public array $items,
    ) {}
}
