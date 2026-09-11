<?php

namespace App\Http\Controllers;

use App\Exports\LibraryAttendanceLogsExport;
use App\Models\LibraryAttendanceFeedback;
use App\Models\LibraryAttendanceLog;
use App\Models\LibraryAttendanceSetting;
use App\Models\LibraryEmployee;
use App\Models\LibraryStudent;
use App\Models\Program;
use App\Models\Student;
use App\Services\LibraryPatronVisitReportService;
use App\Services\LibraryVisitScanService;
use App\Support\PatronNameSearch;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibraryAttendanceController extends Controller
{
    public function scanner(): View
    {
        return view('library.attendance.scanner', [
            'logoutFeedbackEnabled' => $this->feedbackEnabled(),
        ]);
    }

    public function scan(Request $request, LibraryVisitScanService $scanner): JsonResponse
    {
        $validated = $request->validate([
            'qrcode' => ['required', 'string'],
        ]);

        $resolved = $scanner->resolve($validated['qrcode']);
        $student = $resolved['student'];
        $employee = $resolved['employee'];

        if (! $student && ! $employee) {
            return response()->json([
                'type' => 'error',
                'message' => 'Library patron not recognized.',
            ], 404);
        }

        $result = $scanner->record($student, $employee, $request->input('section'));
        $patron = $student ?: $employee;

        return response()->json([
            'type' => $student ? 'student' : 'employee',
            'patron_id' => $patron->id,
            'patron' => [
                'firstname' => $patron->firstname,
                'lastname' => $patron->lastname,
                'profile_picture' => $student?->profile_picture ?? $employee?->formal_picture,
            ],
            'status' => $result['status'],
            'logout_feedback_enabled' => $this->feedbackEnabled(),
            'log' => [
                'scanned_at' => $result['log']->scanned_at->format('Y-m-d h:i:s A'),
            ],
        ]);
    }

    public function logs(Request $request): View
    {
        $tab = $this->resolveLogsTab($request);
        $query = $this->filteredLogsQuery($request, $tab);
        $total = (clone $query)->count();
        $logs = $query->paginate(15)->withQueryString();

        return view('library.attendance.logs', [
            'logs' => $logs,
            'total' => $total,
            'tab' => $tab,
            'programs' => $this->programOptions($tab),
            'years' => $this->yearOptions($tab),
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $tab = $this->resolveLogsTab($request);
        $logs = $this->filteredLogsQuery($request, $tab)->get();
        $pdf = Pdf::loadView('library.attendance.pdf', compact('logs', 'tab'));

        return $pdf->download('library_attendance_logs_'.$tab.'.pdf');
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $tab = $this->resolveLogsTab($request);
        $logs = $this->filteredLogsQuery($request, $tab)->get();

        return Excel::download(
            new LibraryAttendanceLogsExport($logs),
            'library_attendance_logs_'.$tab.'.xlsx'
        );
    }

    public function reports(Request $request, LibraryPatronVisitReportService $visitReports): View
    {
        $tab = $visitReports->resolveScope($request->query('tab'));

        return view('library.attendance.reports_hub', [
            'tab' => $tab,
        ]);
    }

    public function reportsDashboard(Request $request, LibraryPatronVisitReportService $visitReports): View
    {
        $tab = $visitReports->resolveScope($request->query('tab'));
        $only = $request->query('only');
        $from = $request->query('from');
        $to = $request->query('to');
        $programNameByCode = Program::query()->pluck('program_name', 'program_code');

        return view('library.attendance.reports_dashboard', array_merge(
            compact('tab', 'programNameByCode', 'only', 'from', 'to'),
            $visitReports->build($from, $to, $tab)
        ));
    }

    public function reportsExportCsv(Request $request, LibraryPatronVisitReportService $visitReports): StreamedResponse
    {
        $tab = $visitReports->resolveScope($request->query('tab'));

        return $visitReports->streamCsvResponse(
            $request->query('from'),
            $request->query('to'),
            $tab
        );
    }

    public function feedback(Request $request): JsonResponse
    {
        if (! $this->feedbackEnabled()) {
            return response()->json(['success' => false, 'message' => 'Library feedback is disabled.'], 403);
        }

        $validated = $request->validate([
            'student_id' => ['nullable', 'integer', 'exists:library_students,id'],
            'employee_id' => ['nullable', 'integer', 'exists:library_employees,id'],
            'rating' => ['nullable', 'string', 'in:excellent,good,medium,poor,very_bad'],
            'declined' => ['nullable', 'boolean'],
        ]);

        abort_if(empty($validated['student_id']) && empty($validated['employee_id']), 422);

        $declined = (bool) ($validated['declined'] ?? false);

        LibraryAttendanceFeedback::query()->create([
            'student_id' => $validated['student_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'rating' => $declined ? null : ($validated['rating'] ?? null),
            'declined' => $declined,
        ]);

        return response()->json(['success' => true]);
    }

    public function feedbackSettings(): View
    {
        return view('library.attendance.feedback_settings', [
            'enabled' => $this->feedbackEnabled(),
        ]);
    }

    public function updateFeedbackSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        LibraryAttendanceSetting::query()->updateOrCreate(
            ['key' => 'logout_feedback_enabled'],
            ['value' => $validated['enabled'] ? '1' : '0']
        );

        return back()->with('success', 'Library visit feedback settings updated.');
    }

    private function resolveLogsTab(Request $request): string
    {
        return $request->string('tab')->toString() === 'employees' ? 'employees' : 'students';
    }

    private function filteredLogsQuery(Request $request, ?string $tab = null): Builder
    {
        $tab ??= $this->resolveLogsTab($request);
        $isStudents = $tab === 'students';

        return LibraryAttendanceLog::query()
            ->with(['student', 'employee'])
            ->when($isStudents, fn ($query) => $query->whereNotNull('student_id'))
            ->when(! $isStudents, fn ($query) => $query->whereNotNull('employee_id'))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('scanned_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('scanned_at', '<=', $request->to))
            ->when($request->filled('program'), function ($query) use ($request, $isStudents) {
                $program = $request->string('program')->toString();

                if ($isStudents) {
                    $query->whereHas('student', fn ($student) => $student->where('course', $program));

                    return;
                }

                $query->whereHas('employee', function ($employee) use ($program) {
                    $employee->where('program', $program)
                        ->orWhere('department', $program);
                });
            })
            ->when($request->filled('year_level'), function ($query) use ($request, $isStudents) {
                $year = $request->string('year_level')->toString();

                if ($isStudents) {
                    $query->whereHas('student', fn ($student) => $student->where('year', $year));

                    return;
                }

                $query->whereHas('employee', fn ($employee) => $employee->where('year_start_work', $year));
            })
            ->when($request->filled('search'), function ($query) use ($request, $isStudents) {
                $search = trim((string) $request->search);

                $query->where(function (Builder $inner) use ($search, $isStudents) {
                    if ($isStudents) {
                        $inner->whereHas('student', function ($student) use ($search) {
                            PatronNameSearch::apply($student, $search, ['course', 'id_number', 'year', 'qrcode']);
                        });
                    } else {
                        $inner->whereHas('employee', function ($employee) use ($search) {
                            PatronNameSearch::apply($employee, $search, [
                                'program',
                                'department',
                                'employee_id',
                                'designation',
                                'qrcode',
                            ]);
                        });
                    }

                    $inner->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->latest('scanned_at')
            ->latest('id');
    }

    /** @return list<string> */
    private function programOptions(string $tab): array
    {
        if ($tab === 'employees') {
            return LibraryEmployee::query()
                ->whereNotNull('program')
                ->where('program', '!=', '')
                ->distinct()
                ->orderBy('program')
                ->pluck('program')
                ->all();
        }

        return Student::query()
            ->whereNotNull('course')
            ->where('course', '!=', '')
            ->distinct()
            ->orderBy('course')
            ->pluck('course')
            ->all();
    }

    /** @return list<string> */
    private function yearOptions(string $tab): array
    {
        if ($tab === 'employees') {
            return LibraryEmployee::query()
                ->whereNotNull('year_start_work')
                ->where('year_start_work', '!=', '')
                ->distinct()
                ->orderByDesc('year_start_work')
                ->pluck('year_start_work')
                ->all();
        }

        return Student::query()
            ->whereNotNull('year')
            ->where('year', '!=', '')
            ->distinct()
            ->orderBy('year')
            ->pluck('year')
            ->all();
    }

    private function feedbackEnabled(): bool
    {
        $value = LibraryAttendanceSetting::query()
            ->where('key', 'logout_feedback_enabled')
            ->value('value');

        return $value === null || in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
