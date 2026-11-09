<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FineSetting;
use App\Models\Setting;
use App\Services\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CirculationPolicyController extends Controller
{
    public function edit(Request $request): View
    {
        $sublocations = FineSetting::sublocationNames();
        $requested = $request->old('sublocation', $request->query('sublocation'));
        $selected = $this->selectedSublocation($requested, $sublocations);
        $fineSettings = FineSetting::storedFor($selected);
        $termsBase = $fineSettings ?? FineSetting::forSection($selected);

        return view('admin.circulation_policy', [
            'sublocations' => $sublocations,
            'selectedSublocation' => $selected,
            'hasSavedPolicy' => $fineSettings !== null,
            'studentMax' => $termsBase->studentMaxLoans(),
            'employeeUnlimited' => $termsBase->employeeLoansUnlimited(),
            'employeeMax' => $termsBase->employeeMaxLoans(),
            'maxRenewals' => $termsBase->maxRenewals(),
            'reborrowCooldownDays' => $termsBase->reborrowCooldownDays(),
            'reservationHoldDays' => $termsBase->reservationHoldDays(),
            'fineSettings' => $fineSettings,
            'studentTerms' => $termsBase->patronTerms(false),
            'employeeTerms' => $termsBase->patronTerms(true),
        ]);
    }

    public function update(Request $request, AdminActivityLogger $activities): RedirectResponse
    {
        $sublocations = FineSetting::sublocationNames();
        $employeeUnlimited = $request->input('employee_unlimited') === '1';

        $request->validate([
            'sublocation' => ['required', 'string', Rule::in($sublocations)],
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

        $sublocation = (string) $request->input('sublocation');

        FineSetting::create([
            'sublocation' => $sublocation,
            'student_max_loans' => (int) $request->input('student_max'),
            'employee_loans_unlimited' => $employeeUnlimited,
            'employee_max_loans' => (int) ($request->input('employee_max') ?: Setting::DEFAULT_MAX_LOANS_EMPLOYEES),
            'policy_max_renewals' => (int) $request->input('max_renewals'),
            'policy_reborrow_cooldown_days' => (int) $request->input('reborrow_cooldown_days'),
            'policy_reservation_hold_days' => (int) $request->input('reservation_hold_days'),
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
            $sublocation.' borrow limits, renewals, and fine settings changed',
            null,
            route('circulation.policy.edit', ['sublocation' => $sublocation]),
            'bi-sliders',
        );

        return redirect()
            ->route('circulation.policy.edit', ['sublocation' => $sublocation], 303)
            ->with('success', $sublocation.' policy updated successfully.')
            ->withFragment('fines-pane');
    }

    /**
     * @param  list<string>  $sublocations
     */
    private function selectedSublocation(mixed $requested, array $sublocations): string
    {
        $canonical = FineSetting::canonicalSection(is_string($requested) ? $requested : null);

        if ($canonical !== null && in_array($canonical, $sublocations, true)) {
            return $canonical;
        }

        if (in_array('Circulation', $sublocations, true)) {
            return 'Circulation';
        }

        return $sublocations[0] ?? 'Circulation';
    }
}
