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

    /**
     * Most senior first, for showing one role per person.
     *
     * @return list<self>
     */
    public static function byPrecedence(): array
    {
        return [self::Admin, self::Hr, self::Legal, self::Manager, self::Staff, self::Volunteer, self::Student];
    }

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
