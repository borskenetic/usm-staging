<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\PendingEmployee;
use App\Models\PendingFaculty;
use App\Models\PendingStudent;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendingFacultyController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'faculty');
        $activeTab = in_array($tab, ['students', 'employees', 'faculty'], true)
            ? $tab
            : 'faculty';

        $pendingEmployees = PendingEmployee::with('role')->latest()->get();
        $pendingStudents = PendingStudent::with('role')->latest()->paginate(10)->withQueryString();
        $pendingFaculty = PendingFaculty::query()->latest()->get();

        return view('pending.index', compact(
            'pendingStudents',
            'pendingEmployees',
            'pendingFaculty',
            'activeTab'
        ));
    }

    public function approve(int $id, AdminActivityLogger $activities)
    {
        DB::beginTransaction();

        try {
            $pending = PendingFaculty::query()->findOrFail($id);

            if (Faculty::query()->where('employee_id', $pending->employee_id)->exists()) {
                DB::rollBack();

                return back()->with('error', 'A faculty account with this employee ID already exists.');
            }

            $faculty = Faculty::query()->create([
                'employee_id' => $pending->employee_id,
                'lastname' => $pending->lastname,
                'firstname' => $pending->firstname,
                'middle_initial' => $pending->middle_initial,
                'email' => $pending->email,
                'department' => $pending->department,
                'designation' => $pending->designation,
                'birthday' => $pending->birthday,
                'mobile_number' => $pending->mobile_number,
                'profile_picture' => $pending->profile_picture,
                'account_status' => 'Active',
                'password' => null,
                'password_setup_completed' => false,
                'force_password_reset' => true,
            ]);

            $pending->delete();
            $activities->log(
                'library',
                'faculty.approved',
                'Teaching faculty approved',
                $faculty->employee_id,
                $faculty
            );

            DB::commit();

            return back()->with('success', 'Teaching faculty approved. They can sign in with their employee ID (default password is birthday YYYYMMDD).');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    public function reject(int $id, AdminActivityLogger $activities)
    {
        $pending = PendingFaculty::query()->findOrFail($id);
        $employeeId = $pending->employee_id;
        $pending->delete();
        $activities->log('library', 'faculty.rejected', 'Teaching faculty rejected', $employeeId);

        return back()->with('success', 'Teaching faculty registration rejected.');
    }
}
