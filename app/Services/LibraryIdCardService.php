<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\Student;
use App\Support\PublicAssetPath;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Carbon\Carbon;
use Intervention\Image\Facades\Image;
use Intervention\Image\Image as InterventionImage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

final class LibraryIdCardService
{
    public function frontPngForStudent(Student $student): string
    {
        return $this->encodePng($this->composeFront([
            'photo' => $student->profile_picture,
            'full_name' => trim("{$student->firstname} {$student->lastname}"),
            'subtitle' => $student->course,
            'id_number' => $student->id_number,
        ]));
    }

    public function backPngForStudent(Student $student): string
    {
        return $this->encodePng($this->composeBack([
            'qrcode' => (string) $student->qrcode,
            'signature' => $student->student_signature,
            'emergency_person' => $student->emergency_person,
            'emergency_relationship' => $student->emergency_relationship,
            'emergency_number' => $student->emergency_number,
            'birth_date' => $student->birthday,
        ]));
    }

    public function frontImageForStudent(Student $student): InterventionImage
    {
        return $this->composeFront([
            'photo' => $student->profile_picture,
            'full_name' => trim("{$student->firstname} {$student->lastname}"),
            'subtitle' => $student->course,
            'id_number' => $student->id_number,
        ]);
    }

    public function backImageForStudent(Student $student): InterventionImage
    {
        return $this->composeBack([
            'qrcode' => (string) $student->qrcode,
            'signature' => $student->student_signature,
            'emergency_person' => $student->emergency_person,
            'emergency_relationship' => $student->emergency_relationship,
            'emergency_number' => $student->emergency_number,
            'birth_date' => $student->birthday,
        ]);
    }

    public function frontImageForEmployee(Employee $employee): InterventionImage
    {
        $subtitle = $employee->department
            ?: $employee->program
            ?: $employee->designation
            ?: $employee->position;

        return $this->composeFront([
            'photo' => $employee->formal_picture,
            'full_name' => trim("{$employee->firstname} {$employee->lastname}"),
            'subtitle' => $subtitle,
            'id_number' => $employee->employee_id ?: $employee->employee_number,
        ]);
    }

    public function backImageForEmployee(Employee $employee): InterventionImage
    {
        return $this->composeBack([
            'qrcode' => $employee->qrcode ?: ('E-'.$employee->id),
            'signature' => $employee->employee_signature,
            'emergency_person' => $employee->emergency_contact_name,
            'emergency_relationship' => $employee->emergency_contact_relationship,
            'emergency_number' => $employee->emergency_contact_number,
            'birth_date' => $employee->birth_date,
        ]);
    }

    /**
     * @param  array{photo:?string,full_name:string,subtitle:?string,id_number:?string}  $data
     */
    public function composeFront(array $data): InterventionImage
    {
        $img = $this->idCardTemplate('front');

        $photoPath = PublicAssetPath::resolve($data['photo'] ?? null);
        if ($photoPath) {
            $profile = Image::make($photoPath)->resize(1045, 1045);
            $img->insert($profile, 'center', 5, -390);
        }

        $fontPath = public_path('fonts/arial.ttf');

        $img->text($data['full_name'], 1100, 2090, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(150);
            $font->color('#000');
            $font->align('center');
            $font->valign('top');
        });

        if (! empty($data['subtitle'])) {
            $img->text(trim($data['subtitle']), 1100, 2355, function ($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(150);
                $font->color('#000');
                $font->align('center');
                $font->valign('top');
            });
        }

        if (! empty($data['id_number'])) {
            $idNumber = trim($data['id_number']);
            $idFontSize = 100;
            foreach ([[-2, 0], [2, 0], [0, -2], [0, 2], [-2, -2], [-2, 2], [2, -2], [2, 2]] as [$ox, $oy]) {
                $img->text($idNumber, 1090 + $ox, 1890 + $oy, function ($font) use ($fontPath, $idFontSize) {
                    $font->file($fontPath);
                    $font->size($idFontSize);
                    $font->color('#000');
                    $font->align('center');
                    $font->valign('top');
                });
            }
        }

        return $img;
    }

    /**
     * @param  array{
     *     qrcode:string,
     *     signature:?string,
     *     emergency_person:?string,
     *     emergency_relationship:?string,
     *     emergency_number:?string,
     *     birth_date:?string|\DateTimeInterface|null
     * }  $data
     */
    public function composeBack(array $data): InterventionImage
    {
        $img = $this->idCardTemplate('back');

        $qrImage = Image::make($this->generateQrPng((string) $data['qrcode'], 900));
        $img->insert($qrImage, 'top-left', 655, 435);

        $signaturePath = PublicAssetPath::resolve($data['signature'] ?? null);
        if ($signaturePath) {
            $signature = Image::make($signaturePath)->resize(500, 600);
            $img->insert($signature, 'center', -30, 1200);
        }

        if (! empty($data['emergency_person'])) {
            $this->drawIdCardText($img, $data['emergency_person'], 1100, 1650, 100, '#000');
        }
        if (! empty($data['emergency_relationship'])) {
            $this->drawIdCardText($img, $data['emergency_relationship'], 1100, 1750, 100, '#000');
        }
        if (! empty($data['emergency_number'])) {
            $this->drawIdCardText($img, $data['emergency_number'], 1100, 1850, 100, '#000');
        }

        if (! empty($data['birth_date'])) {
            $formattedDate = Carbon::parse($data['birth_date'])->format('m-d-Y');
            $this->drawIdCardText($img, $formattedDate, 3000, 800, 300, '#000');
        }

        return $img;
    }

    private function idCardTemplate(string $side): InterventionImage
    {
        $path = PublicAssetPath::resolve("images/id_templates/{$side}.png")
            ?? base_path("images/id_templates/{$side}.png");

        return Image::make($path);
    }

    private function drawIdCardText($img, $text, $x, $y, $size, $color = '#000', $align = 'center', $valign = 'top'): void
    {
        $fontPathBold = public_path('fonts/arialbd.ttf');
        $fontPathRegular = public_path('fonts/arial.ttf');

        if (file_exists($fontPathBold)) {
            $img->text($text, $x, $y, function ($font) use ($fontPathBold, $size, $color, $align, $valign) {
                $font->file($fontPathBold);
                $font->size($size);
                $font->color($color);
                $font->align($align);
                $font->valign($valign);
            });

            return;
        }

        foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$ox, $oy]) {
            $img->text($text, $x + $ox, $y + $oy, function ($font) use ($fontPathRegular, $size, $color, $align, $valign) {
                $font->file($fontPathRegular);
                $font->size($size);
                $font->color($color);
                $font->align($align);
                $font->valign($valign);
            });
        }

        $img->text($text, $x, $y, function ($font) use ($fontPathRegular, $size, $color, $align, $valign) {
            $font->file($fontPathRegular);
            $font->size($size);
            $font->color($color);
            $font->align($align);
            $font->valign($valign);
        });
    }

    private function encodePng(InterventionImage $img): string
    {
        return (string) $img->encode('png');
    }

    /**
     * Prefer Imagick-backed SimpleQrCode when available; otherwise draw with GD.
     */
    private function generateQrPng(string $payload, int $size = 900): string
    {
        if (extension_loaded('imagick')) {
            return (string) QrCode::format('png')
                ->size($size)
                ->margin(0)
                ->generate($payload);
        }

        $qrCode = Encoder::encode($payload, ErrorCorrectionLevel::L());
        $matrix = $qrCode->getMatrix();
        $moduleCount = $matrix->getWidth();
        $scale = max(1, intdiv($size, $moduleCount));
        $pixelSize = $moduleCount * $scale;

        $image = imagecreatetruecolor($pixelSize, $pixelSize);
        if ($image === false) {
            throw new \RuntimeException('Unable to allocate QR image.');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $pixelSize, $pixelSize, $white);

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $image,
                        $x * $scale,
                        $y * $scale,
                        (($x + 1) * $scale) - 1,
                        (($y + 1) * $scale) - 1,
                        $black,
                    );
                }
            }
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
