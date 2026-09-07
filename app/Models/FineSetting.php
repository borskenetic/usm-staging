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
    ];

    public static function current(): ?self
    {
        return Cache::remember('fine-setting:current', now()->addMinutes(30), function () {
            return self::orderByDesc('effective_from')->first();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('fine-setting:current');
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
}
