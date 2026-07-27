<?php

use App\Http\Middleware\EnsureActiveStaff;
use App\Http\Middleware\EnsureAttendanceAccess;
use App\Http\Middleware\EnsureAttendanceAdmin;
use App\Http\Middleware\EnsureDeveloper;
use App\Http\Middleware\EnsureLibraryAccess;
use App\Http\Middleware\EnsureLibraryAdmin;
use App\Http\Middleware\CheckSanctumAbility;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureActiveStaff::class,
        ]);

        $middleware->alias([
            'attendance.access' => EnsureAttendanceAccess::class,
            'attendance.admin' => EnsureAttendanceAdmin::class,
            'developer' => EnsureDeveloper::class,
            'library.access' => EnsureLibraryAccess::class,
            'library.admin' => EnsureLibraryAdmin::class,
            'super-admin' => EnsureSuperAdmin::class,
            'sanctum.ability' => CheckSanctumAbility::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('attendance:close-stale-ins')
            ->dailyAt('00:05')
            ->timezone('Asia/Manila');

        $schedule->command('backup:run --only-db')
            ->dailyAt('01:00')
            ->timezone('Asia/Manila');

        $schedule->command('backup:clean')
            ->dailyAt('01:30')
            ->timezone('Asia/Manila');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            return redirect()->route('login')->with('error', 'Your session has expired. Please try logging in again.');
        });
    })->create();
