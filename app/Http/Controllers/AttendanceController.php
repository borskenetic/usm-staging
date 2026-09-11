<?php

namespace App\Http\Controllers;

use App\Models\LibraryAttendanceSetting;
use App\Models\LibraryStudent;
use App\Models\Setting;
use App\Services\LibraryVisitScanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function showScanner()
    {
        return view('attendance.scan', [
            'logoutFeedbackEnabled' => $this->libraryFeedbackEnabled(),
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

    /**
     * Official kiosk scanner: Library patrons → library_attendance_logs.
     */
    public function scan(Request $request, LibraryVisitScanService $scanner): JsonResponse
    {
        $request->validate(['qrcode' => 'required|string']);

        $resolved = $scanner->resolve($request->qrcode);
        $student = $resolved['student'];
        $employee = $resolved['employee'];

        if (! $student && ! $employee) {
            return response()->json([
                'type' => 'error',
                'message' => 'RFID not recognized.',
            ]);
        }

        $result = $scanner->record($student, $employee, $request->input('section'));

        if ($student) {
            $this->maybeSendScanSms($student, $result['status']);

            return response()->json([
                'type' => 'student',
                'student_id' => $student->id,
                'student' => [
                    'firstname' => $student->firstname,
                    'lastname' => $student->lastname,
                    'profile_picture' => $student->profile_picture,
                ],
                'status' => $result['status'],
                'logout_feedback_enabled' => $this->libraryFeedbackEnabled(),
                'log' => [
                    'scanned_at' => $result['log']->scanned_at->format('Y-m-d h:i:s A'),
                ],
            ]);
        }

        return response()->json([
            'type' => 'employee',
            'employee_id' => $employee->id,
            'employee' => [
                'firstname' => $employee->firstname,
                'lastname' => $employee->lastname,
                'profile_picture' => $employee->formal_picture,
            ],
            'status' => $result['status'],
            'logout_feedback_enabled' => $this->libraryFeedbackEnabled(),
            'log' => [
                'scanned_at' => $result['log']->scanned_at->format('Y-m-d h:i:s A'),
            ],
        ]);
    }

    private function maybeSendScanSms(LibraryStudent $student, string $status): void
    {
        if (empty($student->mobile_number)) {
            return;
        }

        $template = Setting::where('key', 'scan_sms')->value('value')
            ?? 'Hello {name}, you scanned {status} at the library at {time}.';

        $message = str_replace(
            ['{name}', '{status}', '{time}'],
            [
                trim($student->firstname.' '.$student->lastname),
                $status,
                Carbon::now('Asia/Manila')->format('h:i A'),
            ],
            $template
        );

        app(SMSController::class)->sendDirect(
            $student->mobile_number,
            $message
        );
    }

    private function libraryFeedbackEnabled(): bool
    {
        $value = LibraryAttendanceSetting::query()
            ->where('key', 'logout_feedback_enabled')
            ->value('value');

        if ($value === null) {
            return true;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
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
