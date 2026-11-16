<?php

namespace Modules\Learning;

enum QuestionType: string
{
    case Mcq = 'mcq';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';
    case Essay = 'essay';

    public function label(): string
    {
        return match ($this) {
            self::Mcq => 'Multiple choice',
            self::TrueFalse => 'True / false',
            self::ShortAnswer => 'Short answer',
            self::Essay => 'Essay (manual mark)',
        };
    }

    public function needsManualMarkByDefault(): bool
    {
        return $this === self::Essay;
    }
}
