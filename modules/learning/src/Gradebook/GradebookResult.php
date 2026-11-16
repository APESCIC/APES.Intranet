<?php

namespace Modules\Learning\Gradebook;

final readonly class GradebookResult
{
    /**
     * @param  array<string, string>  $categoryPercentsUnrounded  category id => percent string (bcmath)
     */
    public function __construct(
        public string $coursePercentUnrounded,
        public string $coursePercentRounded,
        public array $categoryPercentsUnrounded,
        public bool $passed,
    ) {}
}
