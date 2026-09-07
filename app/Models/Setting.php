<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const KEY_LOGOUT_FEEDBACK = 'logout_feedback_enabled';

    public const KEY_MAX_LOANS_STUDENTS = 'max_concurrent_loans_students';

    public const KEY_MAX_LOANS_EMPLOYEES = 'max_concurrent_loans_employees';

    public const KEY_MAX_LOANS_EMPLOYEES_UNLIMITED = 'max_concurrent_loans_employees_unlimited';

    public const KEY_MAX_RENEWALS_PER_LOAN = 'max_renewals_per_loan';

    public const KEY_REBORROW_COOLDOWN_DAYS = 'reborrow_cooldown_days';

    public const KEY_RESERVATION_HOLD_DAYS = 'reservation_hold_days';

    public const DEFAULT_MAX_LOANS_STUDENTS = 5;

    public const DEFAULT_MAX_LOANS_EMPLOYEES = 10;

    public const DEFAULT_MAX_RENEWALS_PER_LOAN = 3;

    public const DEFAULT_REBORROW_COOLDOWN_DAYS = 7;

    public const DEFAULT_RESERVATION_HOLD_DAYS = 7;

    protected $fillable = ['key', 'value'];

    public static function logoutFeedbackEnabled(): bool
    {
        $value = static::where('key', self::KEY_LOGOUT_FEEDBACK)->value('value');

        if ($value === null) {
            return true;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function setLogoutFeedbackEnabled(bool $enabled): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_LOGOUT_FEEDBACK],
            ['value' => $enabled ? '1' : '0']
        );
    }

    public static function maxLoansForStudents(): int
    {
        $value = static::where('key', self::KEY_MAX_LOANS_STUDENTS)->value('value');

        if ($value === null || $value === '') {
            return self::DEFAULT_MAX_LOANS_STUDENTS;
        }

        $max = (int) $value;

        return $max > 0 ? $max : self::DEFAULT_MAX_LOANS_STUDENTS;
    }

    public static function employeeLoansUnlimited(): bool
    {
        $value = static::where('key', self::KEY_MAX_LOANS_EMPLOYEES_UNLIMITED)->value('value');

        if ($value === null) {
            return false;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function maxLoansForEmployees(): int
    {
        $value = static::where('key', self::KEY_MAX_LOANS_EMPLOYEES)->value('value');

        if ($value === null || $value === '') {
            return self::DEFAULT_MAX_LOANS_EMPLOYEES;
        }

        $max = (int) $value;

        return $max > 0 ? $max : self::DEFAULT_MAX_LOANS_EMPLOYEES;
    }

    public static function setBorrowLimits(int $studentMax, bool $employeeUnlimited, int $employeeMax): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_MAX_LOANS_STUDENTS],
            ['value' => (string) $studentMax]
        );

        static::updateOrCreate(
            ['key' => self::KEY_MAX_LOANS_EMPLOYEES_UNLIMITED],
            ['value' => $employeeUnlimited ? '1' : '0']
        );

        static::updateOrCreate(
            ['key' => self::KEY_MAX_LOANS_EMPLOYEES],
            ['value' => (string) $employeeMax]
        );
    }

    public static function maxRenewalsPerLoan(): int
    {
        $value = static::where('key', self::KEY_MAX_RENEWALS_PER_LOAN)->value('value');

        if ($value === null || $value === '') {
            return self::DEFAULT_MAX_RENEWALS_PER_LOAN;
        }

        $max = (int) $value;

        return $max >= 0 ? $max : self::DEFAULT_MAX_RENEWALS_PER_LOAN;
    }

    public static function reborrowCooldownDays(): int
    {
        $value = static::where('key', self::KEY_REBORROW_COOLDOWN_DAYS)->value('value');

        if ($value === null || $value === '') {
            return self::DEFAULT_REBORROW_COOLDOWN_DAYS;
        }

        $days = (int) $value;

        return $days >= 0 ? $days : self::DEFAULT_REBORROW_COOLDOWN_DAYS;
    }

    public static function setLoanPolicies(int $maxRenewals, int $reborrowCooldownDays): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_MAX_RENEWALS_PER_LOAN],
            ['value' => (string) $maxRenewals]
        );

        static::updateOrCreate(
            ['key' => self::KEY_REBORROW_COOLDOWN_DAYS],
            ['value' => (string) $reborrowCooldownDays]
        );
    }

    public static function reservationHoldDays(): int
    {
        $value = static::where('key', self::KEY_RESERVATION_HOLD_DAYS)->value('value');

        if ($value === null || $value === '') {
            return self::DEFAULT_RESERVATION_HOLD_DAYS;
        }

        $days = (int) $value;

        return $days > 0 ? $days : self::DEFAULT_RESERVATION_HOLD_DAYS;
    }

    public static function setReservationHoldDays(int $days): void
    {
        static::updateOrCreate(
            ['key' => self::KEY_RESERVATION_HOLD_DAYS],
            ['value' => (string) max(1, $days)]
        );
    }

    public static function wouldExceedStudentLoanLimit(int $currentLoans, int $adding = 1): bool
    {
        return $currentLoans + $adding > self::maxLoansForStudents();
    }

    public static function wouldExceedEmployeeLoanLimit(int $currentLoans, int $adding = 1): bool
    {
        if (self::employeeLoansUnlimited()) {
            return false;
        }

        return $currentLoans + $adding > self::maxLoansForEmployees();
    }
}
