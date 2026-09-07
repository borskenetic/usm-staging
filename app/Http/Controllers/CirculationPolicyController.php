<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FineSetting;
use App\Models\Setting;
use App\Services\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CirculationPolicyController extends Controller
{
    public function edit(): View
    {
        $fineSettings = FineSetting::current();
        $termsBase = $fineSettings ?? new FineSetting(FineSetting::defaultAttributes());

        return view('admin.circulation_policy', [
            'studentMax' => Setting::maxLoansForStudents(),
            'employeeUnlimited' => Setting::employeeLoansUnlimited(),
            'employeeMax' => Setting::maxLoansForEmployees(),
            'maxRenewals' => Setting::maxRenewalsPerLoan(),
            'reborrowCooldownDays' => Setting::reborrowCooldownDays(),
            'reservationHoldDays' => Setting::reservationHoldDays(),
            'fineSettings' => $fineSettings,
            'studentTerms' => $termsBase->patronTerms(false),
            'employeeTerms' => $termsBase->patronTerms(true),
        ]);
    }

    public function update(Request $request, AdminActivityLogger $activities): RedirectResponse
    {
        $employeeUnlimited = $request->input('employee_unlimited') === '1';

        $request->validate([
            'student_max' => 'required|integer|min:1|max:100',
            'employee_unlimited' => 'required|in:0,1',
            'employee_max' => $employeeUnlimited ? 'nullable|integer|min:1|max:100' : 'required|integer|min:1|max:100',
            'max_renewals' => 'required|integer|min:0|max:50',
            'reborrow_cooldown_days' => 'required|integer|min:0|max:365',
            'reservation_hold_days' => 'required|integer|min:1|max:365',
            'student_fine_per_day' => 'required|numeric|min:0',
            'student_max_fine' => 'nullable|numeric|min:0',
            'student_grace_period_days' => 'required|integer|min:0',
            'student_loan_duration_days' => 'required|integer|min:1|max:365',
            'employee_fine_per_day' => 'required|numeric|min:0',
            'employee_max_fine' => 'nullable|numeric|min:0',
            'employee_grace_period_days' => 'required|integer|min:0',
            'employee_loan_duration_days' => 'required|integer|min:1|max:365',
        ]);

        Setting::setBorrowLimits(
            (int) $request->input('student_max'),
            $employeeUnlimited,
            (int) ($request->input('employee_max') ?: Setting::DEFAULT_MAX_LOANS_EMPLOYEES)
        );

        Setting::setLoanPolicies(
            (int) $request->input('max_renewals'),
            (int) $request->input('reborrow_cooldown_days')
        );

        Setting::setReservationHoldDays((int) $request->input('reservation_hold_days'));

        FineSetting::create([
            'fine_per_day' => $request->student_fine_per_day,
            'max_fine' => $request->student_max_fine,
            'grace_period_days' => $request->student_grace_period_days,
            'loan_duration_days' => $request->student_loan_duration_days,
            'student_fine_per_day' => $request->student_fine_per_day,
            'student_max_fine' => $request->student_max_fine,
            'student_grace_period_days' => $request->student_grace_period_days,
            'student_loan_duration_days' => $request->student_loan_duration_days,
            'employee_fine_per_day' => $request->employee_fine_per_day,
            'employee_max_fine' => $request->employee_max_fine,
            'employee_grace_period_days' => $request->employee_grace_period_days,
            'employee_loan_duration_days' => $request->employee_loan_duration_days,
            'effective_from' => now(),
        ]);

        FineSetting::clearCache();
        \App\Models\BookLog::forgetCachedFineSettings();

        $activities->log(
            'library',
            'settings.circulation_policy',
            'Circulation policy updated',
            'Borrow limits, renewals, and fine settings changed',
            null,
            route('circulation.policy.edit'),
            'bi-sliders',
        );

        return redirect()
            ->route('circulation.policy.edit', [], 303)
            ->with('success', 'Circulation policy updated successfully.')
            ->withFragment('fines-pane');
    }
}
