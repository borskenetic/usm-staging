<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use App\Models\AttendancePendingEmployee;
use App\Models\AttendancePendingStudent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttendanceRegistrationController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('patron.register', ['service' => 'attendance']);
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:255'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date'],
            'educational_level' => ['nullable', 'string', 'max:255'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'course' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'emergency_person' => ['nullable', 'string', 'max:255'],
            'emergency_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_number' => ['nullable', 'string', 'max:20'],
            'emergency_address' => ['nullable', 'string'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
            'student_signature' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $this->storeUploadedImage(
                $request,
                'profile_picture',
                'images/attendance/profile_pictures',
                'profile'
            );
        }

        $validated['student_signature'] = $this->storeBase64Image(
            $validated['student_signature'] ?? null,
            'images/attendance/student_signatures',
            'student_sig'
        );

        AttendancePendingStudent::query()->create($validated);

        return back()
            ->with('auth_modal', 'register')
            ->with('auth_service', 'attendance')
            ->with('auth_type', 'student')
            ->with('success', 'Attendance student registration submitted.');
    }

    public function storeEmployee(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:255'],
            'employee_number' => ['nullable', 'string', 'max:255'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:10'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'sex' => ['nullable', 'string', 'max:20'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:20'],
            'emergency_address' => ['nullable', 'string'],
            'formal_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
            'employee_signature' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('formal_picture')) {
            $validated['formal_picture'] = $this->storeUploadedImage(
                $request,
                'formal_picture',
                'images/attendance/formal_pictures',
                'formal'
            );
        }

        $validated['employee_signature'] = $this->storeBase64Image(
            $validated['employee_signature'] ?? null,
            'images/attendance/employee_signatures',
            'employee_sig'
        );

        $validated['qrcode'] = $this->nextAttendanceEmployeeCode();

        AttendancePendingEmployee::query()->create($validated);

        return back()
            ->with('auth_modal', 'register')
            ->with('auth_service', 'attendance')
            ->with('auth_type', 'employee')
            ->with('success', 'Attendance employee registration submitted.');
    }

    private function storeUploadedImage(Request $request, string $field, string $directory, string $prefix): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        $filename = time().'_'.$prefix.'_'.preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $destination = public_path($directory);

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);

        return $directory.'/'.$filename;
    }

    private function storeBase64Image(?string $dataUrl, string $directory, string $prefix): ?string
    {
        if (empty($dataUrl) || ! str_starts_with($dataUrl, 'data:')) {
            return $dataUrl;
        }

        [$meta, $contents] = explode(',', $dataUrl, 2);
        $extension = preg_match('/data:image\/(jpeg|jpg)/i', $meta) ? 'jpg' : 'png';
        $filename = time().'_'.$prefix.'.'.$extension;
        $destination = public_path($directory);

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        file_put_contents($destination.DIRECTORY_SEPARATOR.$filename, base64_decode($contents));

        return $directory.'/'.$filename;
    }

    private function nextAttendanceEmployeeCode(): string
    {
        $lastPendingCode = AttendancePendingEmployee::query()
            ->whereNotNull('qrcode')
            ->orderByDesc('id')
            ->value('qrcode');
        $lastActiveCode = AttendanceEmployee::query()
            ->whereNotNull('qrcode')
            ->orderByDesc('id')
            ->value('qrcode');

        $lastNumber = collect([$lastPendingCode, $lastActiveCode])
            ->filter(fn (?string $code): bool => $code !== null && str_starts_with($code, 'AE-'))
            ->map(fn (string $code): int => (int) Str::after($code, 'AE-'))
            ->max() ?? 0;

        return 'AE-'.str_pad((string) ($lastNumber + 1), 8, '0', STR_PAD_LEFT);
    }
}
