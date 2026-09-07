<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FineSetting;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CirculationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_library_admin_can_view_and_save_circulation_policy(): void
    {
        $admin = $this->staffUser('library_admin');

        $this->actingAs($admin)
            ->get(route('circulation.policy.edit'))
            ->assertOk()
            ->assertSee('Circulation Policy')
            ->assertSee('Borrow Limits')
            ->assertSee('Save policy');

        $response = $this->actingAs($admin)->post(route('circulation.policy.update'), [
            'student_max' => 6,
            'employee_unlimited' => '0',
            'employee_max' => 12,
            'max_renewals' => 2,
            'reborrow_cooldown_days' => 5,
            'reservation_hold_days' => 3,
            'student_fine_per_day' => 10,
            'student_max_fine' => 200,
            'student_grace_period_days' => 1,
            'student_loan_duration_days' => 8,
            'employee_fine_per_day' => 5,
            'employee_max_fine' => 100,
            'employee_grace_period_days' => 2,
            'employee_loan_duration_days' => 14,
        ]);

        $response->assertRedirect();
        $this->assertStringStartsWith(
            route('circulation.policy.edit'),
            $response->headers->get('Location')
        );

        $this->assertSame(6, Setting::maxLoansForStudents());
        $this->assertSame(12, Setting::maxLoansForEmployees());
        $this->assertFalse(Setting::employeeLoansUnlimited());
        $this->assertSame(2, Setting::maxRenewalsPerLoan());
        $this->assertSame(5, Setting::reborrowCooldownDays());
        $this->assertSame(3, Setting::reservationHoldDays());

        $fine = FineSetting::query()->latest('id')->firstOrFail();
        $this->assertSame(10.0, (float) $fine->student_fine_per_day);
        $this->assertSame(8, (int) $fine->student_loan_duration_days);
        $this->assertSame(14, (int) $fine->employee_loan_duration_days);
        $this->assertSame(8, $fine->studentLoanDurationDays());
        $this->assertSame(14, $fine->employeeLoanDurationDays());
    }

    public function test_old_fines_edit_redirects_to_circulation_policy(): void
    {
        $admin = $this->staffUser('library_admin');

        $this->actingAs($admin)
            ->get('/admin/fines')
            ->assertRedirect('/admin/circulation-policy');
    }

    public function test_library_staff_cannot_access_circulation_policy(): void
    {
        $staff = $this->staffUser('library_staff');

        $this->actingAs($staff)
            ->get(route('circulation.policy.edit'))
            ->assertForbidden();
    }

    public function test_checkout_helpers_read_saved_borrow_limits(): void
    {
        Setting::setBorrowLimits(4, false, 9);
        Setting::setLoanPolicies(1, 10);

        $this->assertSame(4, \App\Http\Controllers\BookController::maxConcurrentLoansPerStudent());
        $this->assertSame(1, \App\Http\Controllers\BookController::maxRenewalsPerLoan());
        $this->assertSame(10, \App\Http\Controllers\BookController::reborrowCooldownDays());
    }

    private function staffUser(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
