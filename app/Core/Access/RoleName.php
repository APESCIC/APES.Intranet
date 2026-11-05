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

    /**
     * What the role lets someone see and do, in plain English.
     */
    public function summary(): string
    {
        return match ($this) {
            self::Staff => 'Uses the intranet as a member of staff.',
            self::Volunteer => 'Uses the intranet as a volunteer.',
            self::Student => 'Uses the intranet as a student, with the student area.',
            self::Supervisor => 'Oversees their own team: sees it, and approves its shifts, swaps, timesheets, checklists and student hours. Cannot see other teams.',
            self::Management => 'Manages teams and rotas, oversees every team, and can see all HR records.',
            self::Hr => 'Can see and look after HR records.',
            self::Legal => 'Can see and look after agreements and legal records.',
            self::Admin => 'Can do everything, including seeing HR and legal records and running the intranet.',
        };
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
