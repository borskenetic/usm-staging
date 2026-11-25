<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttendanceStudent;
use App\Models\LibraryAttendanceSetting;
use App\Models\LibraryStudent;
use App\Services\LibraryVisitScanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryAttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_scanner_records_independent_library_visit_logs(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 10:00:00', 'Asia/Manila'));

        $student = LibraryStudent::query()->create([
            'id_number' => 'LIB-001',
            'qrcode' => 'LIB-QR-001',
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
        ]);

        $firstScan = $this->postJson('/library/attendance/scanner', [
            'qrcode' => 'LIB-QR-001',
            'section' => 'Reading Area',
        ]);

        $firstScan->assertOk()
            ->assertJsonPath('type', 'student')
            ->assertJsonPath('patron_id', $student->id)
            ->assertJsonPath('status', 'IN')
            ->assertJsonPath('duplicate', false);

        $this->assertDatabaseHas('library_attendance_logs', [
            'student_id' => $student->id,
            'employee_id' => null,
            'status' => 'IN',
            'section' => 'Reading Area',
        ]);
        $this->assertDatabaseCount('attendance_logs', 0);

        Carbon::setTestNow(Carbon::parse('2026-11-25 10:00:00', 'Asia/Manila')
            ->addSeconds(LibraryVisitScanService::SCAN_COOLDOWN_SECONDS + 1));

        $secondScan = $this->postJson('/library/attendance/scanner', [
            'qrcode' => 'LIB-QR-001',
        ]);

        $secondScan->assertOk()
            ->assertJsonPath('status', 'OUT')
            ->assertJsonPath('duplicate', false);
        $this->assertDatabaseHas('library_attendance_logs', [
            'student_id' => $student->id,
            'status' => 'OUT',
        ]);

        Carbon::setTestNow();
    }

    public function test_library_scanner_ignores_repeat_scans_within_cooldown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 09:00:00', 'Asia/Manila'));

        $student = LibraryStudent::query()->create([
            'id_number' => '26-09267',
            'qrcode' => 'S-00002124',
            'firstname' => 'Wilbert',
            'lastname' => 'Capillo',
            'course' => 'BSABE',
            'year' => '1st Year',
        ]);

        $this->postJson('/library/attendance/scanner', [
            'qrcode' => '26-09267,WILBERT D. CAPILLO,BSABE,1ST SEMESTER A.Y. 2026-2027',
        ])->assertOk()->assertJsonPath('status', 'IN');

        // Same-second / rapid rescans must not flip IN → OUT.
        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-11-25 09:00:00', 'Asia/Manila')->addSeconds($i + 1));

            $this->postJson('/library/attendance/scanner', [
                'qrcode' => '26-09267,WILBERT D. CAPILLO,BSABE,1ST SEMESTER A.Y. 2026-2027',
            ])->assertOk()
                ->assertJsonPath('status', 'IN')
                ->assertJsonPath('duplicate', true)
                ->assertJsonPath('patron_id', $student->id);
        }

        $this->assertDatabaseCount('library_attendance_logs', 1);

        Carbon::setTestNow();
    }

    public function test_library_scanner_resolves_usm_physical_id_qr_payload(): void
    {
        $student = LibraryStudent::query()->create([
            'id_number' => '26-78714',
            'qrcode' => 'S-00004516',
            'firstname' => 'Kristine Mae',
            'lastname' => 'Ondoy',
            'course' => 'BSRT',
            'year' => '1st Year',
            'normalized_name' => 'KRISTINEMAEONDOY',
        ]);

        $this->postJson('/library/attendance/scanner', [
            'qrcode' => '26-78714,KRISTINE MAE B. ONDOY,BSRT,1ST SEMESTER A.Y. 2026-2027',
        ])->assertOk()
            ->assertJsonPath('patron_id', $student->id)
            ->assertJsonPath('patron.firstname', 'Kristine Mae')
            ->assertJsonPath('patron.lastname', 'Ondoy')
            ->assertJsonPath('status', 'IN');
    }

    public function test_library_scanner_resolves_tab_separated_and_name_only_usm_qrs(): void
    {
        $tabStudent = LibraryStudent::query()->create([
            'id_number' => '21-11048',
            'qrcode' => 'S-TAB-001',
            'firstname' => 'Decie',
            'lastname' => 'Ganao',
            'course' => 'ABPhilos',
            'normalized_name' => 'DECIEGANAO',
        ]);

        $nameOnly = LibraryStudent::query()->create([
            'id_number' => '22-61500',
            'qrcode' => 'S-NAME-001',
            'firstname' => 'Merryl Joanna Joyce',
            'lastname' => 'Gonzales',
            'course' => 'BSN',
            'normalized_name' => 'MERRYLJOANNAJOYCEGONZALES',
        ]);

        $suffix = LibraryStudent::query()->create([
            'id_number' => '24-51861',
            'qrcode' => 'S-NAME-002',
            'firstname' => 'Muhaima',
            'lastname' => 'Ugka',
            'course' => 'BIndTech',
            'normalized_name' => 'MUHAIMAUGKA',
        ]);

        $typo = LibraryStudent::query()->create([
            'id_number' => '23-44338',
            'qrcode' => 'S-NAME-003',
            'firstname' => 'Venice Jade',
            'lastname' => 'Fortez',
            'course' => 'BSE',
            'normalized_name' => 'VENICEJADEFORTEZ',
        ]);

        $this->postJson('/library/attendance/scanner', [
            'qrcode' => "21-11048\tDECIE C. GANAO\tABPhilo\tDecember 5, 2002",
        ])->assertOk()->assertJsonPath('patron_id', $tabStudent->id);

        $this->postJson('/library/attendance/scanner', [
            'qrcode' => 'MERRYL JOANNA JOYCE T. GONZALES,BSN,,June 13, 2003',
        ])->assertOk()->assertJsonPath('patron_id', $nameOnly->id);

        $this->postJson('/library/attendance/scanner', [
            'qrcode' => 'MUHAIMA L. UGKA SR.,BIT,,',
        ])->assertOk()->assertJsonPath('patron_id', $suffix->id);

        // ARO printed FROTEZ; DB has Fortez.
        $this->postJson('/library/attendance/scanner', [
            'qrcode' => 'VENICE JADE A. FROTEZ,BSE,,',
        ])->assertOk()->assertJsonPath('patron_id', $typo->id);
    }

    public function test_library_scanner_does_not_accept_attendance_only_patrons(): void
    {
        AttendanceStudent::query()->create([
            'student_id' => 'ATT-001',
            'qrcode' => 'ATT-QR-001',
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
        ]);

        $response = $this->postJson('/library/attendance/scanner', [
            'qrcode' => 'ATT-QR-001',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('message', 'Library patron not recognized.');
        $this->assertDatabaseCount('library_attendance_logs', 0);
    }

    public function test_library_feedback_setting_controls_feedback_submission(): void
    {
        $student = LibraryStudent::query()->create([
            'id_number' => 'LIB-002',
            'qrcode' => 'LIB-QR-002',
            'firstname' => 'Katherine',
            'lastname' => 'Johnson',
        ]);

        $this->postJson('/library/attendance/feedback', [
            'student_id' => $student->id,
            'rating' => 'excellent',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('library_attendance_feedbacks', [
            'student_id' => $student->id,
            'rating' => 'excellent',
            'declined' => false,
        ]);

        LibraryAttendanceSetting::query()->create([
            'key' => 'logout_feedback_enabled',
            'value' => '0',
        ]);

        $this->postJson('/library/attendance/feedback', [
            'student_id' => $student->id,
            'rating' => 'good',
        ])->assertForbidden();
    }
}
