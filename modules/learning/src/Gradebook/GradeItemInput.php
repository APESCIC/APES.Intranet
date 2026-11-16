<?php

namespace Modules\Learning\Gradebook;

/**
 * Pure input for one grade item in the calculation engine.
 *
 * @phpstan-type AttemptList list<float|null>
 */
final readonly class GradeItemInput
{
    /**
     * @param  list<float|null>  $attempts  chronological scores (null = not scored); used with attemptPolicy
     * @param  'present'|'missing'|'zero'|'exempt'  $status  overrides attempts when not present
     * @param  'best'|'latest'|'average'  $attemptPolicy
     * @param  'missing'|'zero_after_due'  $missingPolicy
     */
    public function __construct(
        public string $id,
        public float $maxPoints,
        public array $attempts = [],
        public string $status = 'present',
        public string $attemptPolicy = 'latest',
        public string $missingPolicy = 'missing',
        public bool $duePassed = false,
        public ?float $resitScore = null,
    ) {}
}
