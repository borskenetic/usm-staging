<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\LibraryIdCardService;
use ZipArchive;

class IdCardController extends Controller
{
    public function __construct(
        private readonly LibraryIdCardService $idCardService,
    ) {}

    public function front($id)
    {
        $student = Student::findOrFail($id);

        return $this->idCardService->frontImageForStudent($student)->response('png');
    }

    public function back($id)
    {
        $student = Student::findOrFail($id);

        return $this->idCardService->backImageForStudent($student)->response('png');
    }

    public function download($id)
    {
        $student = Student::findOrFail($id);

        $front = $this->idCardService->frontPngForStudent($student);
        $back = $this->idCardService->backPngForStudent($student);

        $zipPath = storage_path("app/temp_id_{$id}.zip");
        $frontPath = storage_path("app/front_{$id}.png");
        $backPath = storage_path("app/back_{$id}.png");

        file_put_contents($frontPath, $front);
        file_put_contents($backPath, $back);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFile($frontPath, "{$student->lastname}_{$student->firstname}_front.png");
            $zip->addFile($backPath, "{$student->lastname}_{$student->firstname}_back.png");
            $zip->close();
        }

        unlink($frontPath);
        unlink($backPath);

        return response()->download($zipPath, "{$student->lastname}_{$student->firstname}_ID.zip")
            ->deleteFileAfterSend(true);
    }
}
