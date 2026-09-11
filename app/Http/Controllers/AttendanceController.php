<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use App\Models\AttendanceLog;
use App\Models\AttendanceStudent;
use App\Models\Setting;
use App\Services\AttendanceSessionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function showScanner()
    {
        return view('attendance.scan', [
            'logoutFeedbackEnabled' => Setting::logoutFeedbackEnabled(),
        ]);
    }

    public function feedbackSettings()
    {
        return view('attendance.feedback_settings', [
            'enabled' => Setting::logoutFeedbackEnabled(),
        ]);
    }

    public function updateFeedbackSettings(Request $request)
    {
        $request->validate([
            'enabled' => 'required|in:0,1',
        ]);

        Setting::setLogoutFeedbackEnabled($request->input('enabled') === '1');

        return back()->with(
            'success',
            $request->input('enabled') === '1'
                ? 'Logout feedback is now enabled on the attendance scanner.'
                : 'Logout feedback is now disabled on the attendance scanner.'
        );
    }

    private function parseQr($raw)
    {
        $raw = trim(str_replace("\r", '', $raw));

        // Case 3: multiline format
        if (str_contains($raw, "\n")) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $raw))));

            return [
                'student_no' => $lines[0] ?? null,
                'full_name' => $lines[1] ?? null,
                'course' => $lines[2] ?? null,
            ];
        }

        // Otherwise comma-separated
        $parts = array_map('trim', explode(',', $raw));

        // If first part looks like student number (20-80556)
        if (preg_match('/^\d{2}-\d+$/', $parts[0] ?? '')) {
            return [
                'student_no' => $parts[0] ?? null,
                'full_name' => $parts[1] ?? null,
                'course' => $parts[2] ?? null,
            ];
        }

        // Format 2 (no student number)
        return [
            'student_no' => null,
            'full_name' => $parts[0] ?? null,
            'course' => $parts[1] ?? null,
        ];
    }

    public function scan(Request $request): JsonResponse
    {
        $request->validate(['qrcode' => 'required|string']);

        $token = trim(str_replace("\r", '', $request->qrcode));

        // Same lookup rule as Library visit scanner: QR code or ID number.
        // Still Attendance-domain patrons only (never library patrons).
        $student = AttendanceStudent::query()
            ->where(function ($query) use ($token) {
                $query->where('qrcode', $token)
                    ->orWhere('student_id', $token);
            })
            ->first();

        $employee = $student ? null : AttendanceEmployee::query()
            ->where(function ($query) use ($token) {
                $query->where('qrcode', $token)
                    ->orWhere('employee_id', $token)
                    ->orWhere('employee_number', $token);
            })
            ->first();

        // Legacy printed QR payloads (multiline / comma / normalized name).
        if (! $student && ! $employee) {
            $parsed = $this->parseQr($request->qrcode);

            if ($parsed['student_no']) {
                $student = AttendanceStudent::query()
                    ->where('student_id', $parsed['student_no'])
                    ->first();
            }

            if (! $student && $parsed['full_name']) {
                $qrName = strtoupper($parsed['full_name']);
                $qrName = preg_replace('/[^A-Z\s]/', '', $qrName);
                $qrName = preg_replace('/\b[A-Z]\b/', '', $qrName);
                $qrName = preg_replace('/\s+/', '', $qrName);

                $student = AttendanceStudent::query()
                    ->where('normalized_name', $qrName)
                    ->first();

                if (! $student) {
                    $employee = AttendanceEmployee::query()
                        ->where('normalized_name', $qrName)
                        ->first();
                }
            }
        }

        if ($student) {
            return $this->recordStudentScan($student);
        }

        if ($employee) {
            return $this->recordEmployeeScan($employee);
        }

        return response()->json([
            'type' => 'error',
            'message' => 'RFID not recognized.',
        ]);
    }

    private function recordStudentScan(AttendanceStudent $student): JsonResponse
    {
        $sessions = app(AttendanceSessionService::class);
        $sessions->closeStaleOpenInForStudent($student);

        $lastLog = AttendanceLog::query()
            ->where('student_id', $student->id)
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->first();

        $newStatus = ($lastLog && $sessions->isInStatus($lastLog->status)) ? 'OUT' : 'IN';

        $log = AttendanceLog::query()->create([
            'student_id' => $student->id,
            'status' => $newStatus,
            'scanned_at' => Carbon::now('Asia/Manila'),
        ]);

        if (! empty($student->mobile_number)) {
            $template = Setting::where('key', 'scan_sms')->value('value')
                ?? 'Hello {name}, you scanned {status} at the library at {time}.';

            $message = str_replace(
                ['{name}', '{status}', '{time}'],
                [
                    trim($student->firstname.' '.$student->lastname),
                    $newStatus,
                    Carbon::now('Asia/Manila')->format('h:i A'),
                ],
                $template
            );

            app(SMSController::class)->sendDirect(
                $student->mobile_number,
                $message
            );
        }

        return response()->json([
            'type' => 'student',
            'student_id' => $student->id,
            'student' => [
                'firstname' => $student->firstname,
                'lastname' => $student->lastname,
                'profile_picture' => $student->profile_picture,
            ],
            'status' => $newStatus,
            'logout_feedback_enabled' => Setting::logoutFeedbackEnabled(),
            'log' => [
                'scanned_at' => $log->scanned_at->format('Y-m-d h:i:s A'),
            ],
        ]);
    }

    private function recordEmployeeScan(AttendanceEmployee $employee): JsonResponse
    {
        $sessions = app(AttendanceSessionService::class);
        $sessions->closeStaleOpenInForEmployee($employee);

        $lastLog = AttendanceLog::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->first();

        $newStatus = ($lastLog && $sessions->isInStatus($lastLog->status)) ? 'OUT' : 'IN';

        $log = AttendanceLog::query()->create([
            'employee_id' => $employee->id,
            'status' => $newStatus,
            'scanned_at' => Carbon::now('Asia/Manila'),
        ]);

        return response()->json([
            'type' => 'employee',
            'employee_id' => $employee->id,
            'employee' => [
                'firstname' => $employee->firstname,
                'lastname' => $employee->lastname,
                'profile_picture' => $employee->formal_picture,
            ],
            'status' => $newStatus,
            'logout_feedback_enabled' => false,
            'log' => [
                'scanned_at' => $log->scanned_at->format('Y-m-d h:i:s A'),
            ],
        ]);
    }

    // Show the change video page
    public function showChangeVideo()
    {
        return view('attendance.change_video');
    }

    // Handle video upload
    public function uploadVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4|max:512000', // 500MB
        ]);

        $video = $request->file('video');
        $filename = 'area51_product_slideshow.mp4'; // overwrite existing
        $destination = public_path('videos');

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $video->move($destination, $filename);

        return redirect()->route('attendance.changeVideo')->with('success', 'Video uploaded successfully!');
    }
}
