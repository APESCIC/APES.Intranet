<?php

namespace Modules\Learning;

enum GradingSchemeType: string
{
    case Points = 'points';
    case Percentage = 'percentage';
    case PassFail = 'pass_fail';

    public function label(): string
    {
        return match ($this) {
            self::Points => 'Points',
            self::Percentage => 'Percentage',
            self::PassFail => 'Pass / fail',
        };
    }
}
