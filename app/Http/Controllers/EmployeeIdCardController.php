<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\LibraryIdCardService;
use ZipArchive;

/**
 * Faculty & staff ID cards — same layout and templates as student IDs.
 */
class EmployeeIdCardController extends Controller
{
    public function __construct(
        private readonly LibraryIdCardService $idCardService,
    ) {}

    public function front($id)
    {
        $employee = Employee::findOrFail($id);

        return $this->idCardService->frontImageForEmployee($employee)->response('png');
    }

    public function back($id)
    {
        $employee = Employee::findOrFail($id);

        return $this->idCardService->backImageForEmployee($employee)->response('png');
    }

    public function download($id)
    {
        $employee = Employee::findOrFail($id);

        $front = (string) $this->idCardService->frontImageForEmployee($employee)->encode('png');
        $back = (string) $this->idCardService->backImageForEmployee($employee)->encode('png');

        $zipPath = storage_path("app/temp_emp_id_{$id}.zip");
        $frontPath = storage_path("app/emp_front_{$id}.png");
        $backPath = storage_path("app/emp_back_{$id}.png");

        file_put_contents($frontPath, $front);
        file_put_contents($backPath, $back);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFile($frontPath, "{$employee->lastname}_{$employee->firstname}_front.png");
            $zip->addFile($backPath, "{$employee->lastname}_{$employee->firstname}_back.png");
            $zip->close();
        }

        unlink($frontPath);
        unlink($backPath);

        return response()->download($zipPath, "{$employee->lastname}_{$employee->firstname}_ID.zip")
            ->deleteFileAfterSend(true);
    }
}
