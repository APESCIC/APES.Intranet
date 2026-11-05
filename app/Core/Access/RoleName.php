<?php

namespace App\Core\Access;

/**
 * Account types (staff, volunteer, student) say who someone is. Permission
 * roles (supervisor, management, HR, legal, admin) say what they can do, on
 * top of an account type.
 */
enum RoleName: string
{
    case Staff = 'staff';
    case Volunteer = 'volunteer';
    case Student = 'student';
    case Supervisor = 'supervisor';
    case Management = 'management';
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
        return [self::Admin, self::Hr, self::Legal, self::Management, self::Supervisor, self::Staff, self::Volunteer, self::Student];
    }

    /**
     * @return list<self>
     */
    public static function accountTypes(): array
    {
        return [self::Staff, self::Volunteer, self::Student];
    }

    public function isAccountType(): bool
    {
        return in_array($this, self::accountTypes(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Volunteer => 'Volunteer',
            self::Student => 'Student',
            self::Supervisor => 'Supervisor',
            self::Management => 'Management',
            self::Hr => 'HR',
            self::Legal => 'Legal',
            self::Admin => 'Admin',
        };
    }
}
