<?php

namespace App\Http\Controllers;

use App\Models\AttendanceProgram;
use App\Models\Program;
use App\Services\Auth\ModuleAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(private readonly ModuleAccessService $moduleAccess) {}

    public function showLogin()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', [
            'attendancePrograms' => AttendanceProgram::query()->orderBy('program_name')->get(),
            'libraryPrograms' => Program::query()->orderBy('program_name')->get(),
            'workStartYears' => range((int) date('Y'), 1980),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user?->loadMissing('roles');

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->with('auth_modal', 'login')
                    ->with('error', 'This account is inactive. Please contact an administrator.');
            }

            try {
                $module = $this->moduleAccess->defaultModule($user);
                $request->session()->put('active_module', $module);

                return redirect()->route($this->moduleAccess->dashboardRouteForModule($user, $module));
            } catch (\InvalidArgumentException) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->with('auth_modal', 'login')
                    ->with('error', 'No dashboard is available for this account.');
            }
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->with('auth_modal', 'login')
            ->withErrors([
                'email' => 'Invalid credentials.',
            ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
