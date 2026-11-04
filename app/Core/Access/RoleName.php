<?php

namespace App\Core\Access;

enum RoleName: string
{
    case Staff = 'staff';
    case Volunteer = 'volunteer';
    case Student = 'student';
    case Manager = 'manager';
    case Hr = 'hr';
    case Legal = 'legal';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Volunteer => 'Volunteer',
            self::Student => 'Student',
            self::Manager => 'Manager',
            self::Hr => 'HR',
            self::Legal => 'Legal',
            self::Admin => 'Admin',
        };
    }
}
