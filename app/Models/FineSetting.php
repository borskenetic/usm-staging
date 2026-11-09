<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FineSetting extends Model
{
    public const DEFAULT_LOAN_DURATION_DAYS = 7;

    protected $table = 'library_fine_settings';

    protected $fillable = [
        'fine_per_day',
        'max_fine',
        'grace_period_days',
        'loan_duration_days',
        'student_fine_per_day',
        'student_max_fine',
        'student_grace_period_days',
        'student_loan_duration_days',
        'employee_fine_per_day',
        'employee_max_fine',
        'employee_grace_period_days',
        'employee_loan_duration_days',
        'effective_from',
        'sublocation',
        'student_max_loans',
        'employee_max_loans',
        'employee_loans_unlimited',
        'policy_max_renewals',
        'policy_reborrow_cooldown_days',
        'policy_reservation_hold_days',
    ];

    /** @return list<string> */
    public static function defaultSublocationNames(): array
    {
        return [
            'Circulation',
            'Filipiniana',
            'Reference',
            'Periodicals',
            'Fiction',
            'Reserve',
        ];
    }

    /**
     * Catalog 852 ‡c options, plus any sublocation already saved on a book.
     *
     * @return list<string>
     */
    public static function sublocationNames(): array
    {
        $fallback = self::defaultSublocationNames();
        $field = MarcField::findForTagSubfield('852', 'c');

        if (! $field) {
            return $fallback;
        }

        $names = $field->mergedSelectOptions('section');

        return $names !== [] ? $names : $fallback;
    }

    public static function canonicalSection(?string $section): ?string
    {
        $trimmed = trim((string) $section);
        if ($trimmed === '') {
            return null;
        }

        foreach (self::sublocationNames() as $name) {
            if (strcasecmp($name, $trimmed) === 0) {
                return $name;
            }
        }

        return $trimmed;
    }

    public static function current(): ?self
    {
        return Cache::remember('fine-setting:current:global', now()->addMinutes(30), function () {
            return self::query()
                ->whereNull('sublocation')
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first();
        });
    }

    public static function storedFor(string $sublocation): ?self
    {
        $name = self::canonicalSection($sublocation);
        if ($name === null) {
            return null;
        }

        $cached = Cache::remember(
            self::sectionCacheKey($name),
            now()->addMinutes(30),
            fn () => self::query()
                ->where('sublocation', $name)
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first()
        );

        return $cached instanceof self ? $cached : null;
    }

    /**
     * Saved policy for this shelving location, or the shared defaults when none has been saved yet.
     */
    public static function forSection(?string $section): self
    {
        $name = self::canonicalSection($section);
        if ($name !== null) {
            $stored = self::storedFor($name);
            if ($stored) {
                return $stored;
            }
        }

        return self::draftFromGlobal($name);
    }

    public static function clearCache(): void
    {
        Cache::forget('fine-setting:current');
        Cache::forget('fine-setting:current:global');

        foreach (self::sublocationNames() as $name) {
            Cache::forget(self::sectionCacheKey($name));
        }

        BookLog::forgetCachedFineSettings();
    }

    public static function sectionCacheKey(string $sublocation): string
    {
        return 'fine-setting:section:'.md5($sublocation);
    }

    public static function draftFromGlobal(?string $sublocation): self
    {
        $draft = self::currentOrDefault()->replicate();
        $draft->sublocation = $sublocation;
        $draft->student_max_loans = Setting::maxLoansForStudents();
        $draft->employee_max_loans = Setting::maxLoansForEmployees();
        $draft->employee_loans_unlimited = Setting::employeeLoansUnlimited();
        $draft->policy_max_renewals = Setting::maxRenewalsPerLoan();
        $draft->policy_reborrow_cooldown_days = Setting::reborrowCooldownDays();
        $draft->policy_reservation_hold_days = Setting::reservationHoldDays();

        return $draft;
    }

    /**
     * @param  iterable<int, Book>  $books
     */
    public static function capacityErrorForBooks(int $studentId, iterable $books): ?string
    {
        /** @var array<string, array{policy: self, count: int}> $groups */
        $groups = [];

        foreach ($books as $book) {
            if (! $book instanceof Book) {
                continue;
            }

            $policy = self::forSection($book->section);
            $key = $policy->sublocation ?? '';
            $groups[$key]['policy'] = $policy;
            $groups[$key]['count'] = ($groups[$key]['count'] ?? 0) + 1;
        }

        foreach ($groups as $group) {
            $message = $group['policy']->loanCapacityMessage($studentId, $group['count']);
            if ($message !== null) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sublocationPolicyPayload(int $studentId): array
    {
        $rows = [];

        foreach (self::sublocationNames() as $name) {
            $policy = self::forSection($name);
            $current = BookLog::countActiveLoansForStudentInSection($studentId, $name);
            $max = $policy->studentMaxLoans();
            $terms = $policy->patronTerms(false);

            $rows[] = [
                'sublocation' => $name,
                'max_active_loans' => $max,
                'current_active_loans' => $current,
                'remaining_loans' => max(0, $max - $current),
                'max_renewals' => $policy->maxRenewals(),
                'reborrow_cooldown_days' => $policy->reborrowCooldownDays(),
                'reservation_hold_days' => $policy->reservationHoldDays(),
                'loan_duration_days' => $policy->studentLoanDurationDays(),
                'grace_period_days' => (int) $terms->grace_period_days,
                'fine_per_day' => (float) $terms->fine_per_day,
            ];
        }

        return $rows;
    }

    public static function currentOrDefault(): self
    {
        return self::current() ?? new self(self::defaultAttributes());
    }

    /** @return array<string, mixed> */
    public static function defaultAttributes(): array
    {
        $finePerDay = (float) config('circulation.fine_per_day', 5.00);
        $maxFine = config('circulation.max_fine', 500.00);
        $grace = (int) config('circulation.grace_period_days', 0);
        $loanDays = (int) config('circulation.loan_duration_days', self::DEFAULT_LOAN_DURATION_DAYS);

        return [
            'fine_per_day' => $finePerDay,
            'max_fine' => $maxFine,
            'grace_period_days' => $grace,
            'loan_duration_days' => $loanDays,
            'student_fine_per_day' => $finePerDay,
            'student_max_fine' => $maxFine,
            'student_grace_period_days' => $grace,
            'student_loan_duration_days' => $loanDays,
            'employee_fine_per_day' => $finePerDay,
            'employee_max_fine' => $maxFine,
            'employee_grace_period_days' => $grace,
            'employee_loan_duration_days' => $loanDays,
            'effective_from' => now()->toDateString(),
        ];
    }

    /**
     * @return object{fine_per_day: float, max_fine: ?float, grace_period_days: int, loan_duration_days: int}
     */
    public function patronTerms(bool $isEmployee): object
    {
        if ($isEmployee) {
            return (object) [
                'fine_per_day' => (float) ($this->employee_fine_per_day ?? $this->fine_per_day ?? 0),
                'max_fine' => $this->employee_max_fine ?? $this->max_fine,
                'grace_period_days' => (int) ($this->employee_grace_period_days ?? $this->grace_period_days ?? 0),
                'loan_duration_days' => (int) ($this->employee_loan_duration_days ?? $this->loan_duration_days ?? self::DEFAULT_LOAN_DURATION_DAYS),
            ];
        }

        return (object) [
            'fine_per_day' => (float) ($this->student_fine_per_day ?? $this->fine_per_day ?? 0),
            'max_fine' => $this->student_max_fine ?? $this->max_fine,
            'grace_period_days' => (int) ($this->student_grace_period_days ?? $this->grace_period_days ?? 0),
            'loan_duration_days' => (int) ($this->student_loan_duration_days ?? $this->loan_duration_days ?? self::DEFAULT_LOAN_DURATION_DAYS),
        ];
    }

    public function studentLoanDurationDays(): int
    {
        return $this->patronTerms(false)->loan_duration_days;
    }

    public function employeeLoanDurationDays(): int
    {
        return $this->patronTerms(true)->loan_duration_days;
    }

    public function studentMaxLoans(): int
    {
        $value = $this->attributes['student_max_loans'] ?? null;

        if ($value === null || $value === '') {
            return Setting::maxLoansForStudents();
        }

        $max = (int) $value;

        return $max > 0 ? $max : Setting::maxLoansForStudents();
    }

    public function employeeMaxLoans(): int
    {
        $value = $this->attributes['employee_max_loans'] ?? null;

        if ($value === null || $value === '') {
            return Setting::maxLoansForEmployees();
        }

        $max = (int) $value;

        return $max > 0 ? $max : Setting::maxLoansForEmployees();
    }

    public function employeeLoansUnlimited(): bool
    {
        $value = $this->attributes['employee_loans_unlimited'] ?? null;

        if ($value === null || $value === '') {
            return Setting::employeeLoansUnlimited();
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public function maxRenewals(): int
    {
        $value = $this->attributes['policy_max_renewals'] ?? null;

        if ($value === null || $value === '') {
            return Setting::maxRenewalsPerLoan();
        }

        $max = (int) $value;

        return $max >= 0 ? $max : Setting::maxRenewalsPerLoan();
    }

    public function reborrowCooldownDays(): int
    {
        $value = $this->attributes['policy_reborrow_cooldown_days'] ?? null;

        if ($value === null || $value === '') {
            return Setting::reborrowCooldownDays();
        }

        $days = (int) $value;

        return $days >= 0 ? $days : Setting::reborrowCooldownDays();
    }

    public function reservationHoldDays(): int
    {
        $value = $this->attributes['policy_reservation_hold_days'] ?? null;

        if ($value === null || $value === '') {
            return Setting::reservationHoldDays();
        }

        $days = (int) $value;

        return $days > 0 ? $days : Setting::reservationHoldDays();
    }

    public function loanCapacityMessage(int $studentId, int $adding = 1): ?string
    {
        $current = BookLog::countActiveLoansForStudentInSection($studentId, $this->sublocation);
        $max = $this->studentMaxLoans();

        if ($current + $adding <= $max) {
            return null;
        }

        $label = $this->sublocation ?: 'no sublocation';

        return 'Checkout blocked: patron may have at most '.$max.' '.$label.' books on loan at a time (including room use).';
    }
}
