<?php

namespace App\Http\Controllers;

use App\Models\AttendanceProgram;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicRegistrationController extends Controller
{
    public function choose(Request $request): View
    {
        $service = $request->query('service', session('auth_service', 'attendance'));
        $service = in_array($service, ['attendance', 'library'], true) ? $service : 'attendance';

        $type = $request->query('type', session('auth_type', 'student'));
        $type = in_array($type, ['student', 'employee'], true) ? $type : 'student';

        return view('auth.direct-register', [
            'attendancePrograms' => AttendanceProgram::query()->orderBy('program_name')->get(),
            'libraryPrograms' => Program::query()->orderBy('program_name')->get(),
            'workStartYears' => range((int) date('Y'), 1980),
            'initialService' => $service,
            'initialType' => $type,
        ]);
    }
}
