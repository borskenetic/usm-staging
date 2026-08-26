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

/**
 * USM student/employee ID card layout (templates: 556×874).
 */
final class LibraryIdCardService
{
    public function frontPngForStudent(Student $student): string
    {
        return $this->encodePng($this->frontImageForStudent($student));
    }

    public function backPngForStudent(Student $student): string
    {
        return $this->encodePng($this->backImageForStudent($student));
    }

    public function frontImageForStudent(Student $student): InterventionImage
    {
        return $this->composeFront([
            'photo' => $student->profile_picture,
            'full_name' => $this->formatIdCardName(
                $student->firstname,
                $student->lastname,
                $student->middle_initial
            ),
            'subtitle' => $student->course,
            'id_number' => $student->id_number,
        ]);
    }

    public function backImageForStudent(Student $student): InterventionImage
    {
        return $this->composeBack([
            'qrcode' => (string) ($student->qrcode ?: $student->id_number ?: ('S-'.$student->id)),
            'signature' => $student->student_signature,
            'emergency_person' => $student->emergency_person,
            'emergency_address' => $student->emergency_address ?: $student->address,
            'emergency_number' => $student->emergency_number,
            'birth_date' => $student->birthday,
            'valid_until' => config('idcard.valid_until'),
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
            'full_name' => $this->formatIdCardName(
                $employee->firstname,
                $employee->lastname,
                $employee->middle_initial
            ),
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
            'emergency_address' => $employee->emergency_address ?: $employee->address,
            'emergency_number' => $employee->emergency_contact_number,
            'birth_date' => $employee->birth_date,
            'valid_until' => config('idcard.valid_until'),
        ]);
    }

    /**
     * @param  array{photo:?string,full_name:string,subtitle:?string,id_number:?string}  $data
     */
    public function composeFront(array $data): InterventionImage
    {
        $img = $this->idCardTemplate('front');

        // Yellow photo placeholder on template: x=254–527, y=52–370 (274×319)
        $photoPath = PublicAssetPath::resolve($data['photo'] ?? null);
        if ($photoPath) {
            $profile = Image::make($photoPath)->fit(259, 259);
            $img->insert($profile, 'top-left', 268, 112);
        }

        // Green ID number under university seal (left of photo)
        if (! empty($data['id_number'])) {
            $this->drawIdCardText($img, trim((string) $data['id_number']), 130, 340, 35, '#00A651', 'center', 'top', true);
        }

        // Name + program/department in yellow lower band
        if (! empty($data['full_name'])) {
            $this->drawIdCardText($img, $data['full_name'], 278, 645, 40, '#000000', 'center', 'top', true);
        }

        if (! empty($data['subtitle'])) {
            $this->drawIdCardText($img, trim((string) $data['subtitle']), 278, 690, 35, '#000000', 'center', 'top', true);
        }

        return $img;
    }

    /**
     * @param  array{
     *     qrcode:string,
     *     signature:?string,
     *     emergency_person:?string,
     *     emergency_address:?string,
     *     emergency_number:?string,
     *     birth_date:?string|\DateTimeInterface|null,
     *     valid_until:?string
     * }  $data
     */
    public function composeBack(array $data): InterventionImage
    {
        $img = $this->idCardTemplate('back');

        // Emergency contact under top black header (y ~60–175)
        $ey = 80;
        if (! empty($data['emergency_person'])) {
            $this->drawIdCardText($img, (string) $data['emergency_person'], 278, $ey, 15, '#000', 'center', 'top', true);
            $ey += 27;
        }
        if (! empty($data['emergency_address'])) {
            $this->drawIdCardText($img, (string) $data['emergency_address'], 278, $ey, 10, '#000', 'center', 'top', false);
            $ey += 25;
        }
        if (! empty($data['emergency_number'])) {
            $this->drawIdCardText($img, (string) $data['emergency_number'], 278, $ey, 14, '#000', 'center', 'top', true);
        }

        if (! empty($data['birth_date'])) {
            try {
                $formattedDate = Carbon::parse($data['birth_date'])->format('F j, Y');
                $this->drawIdCardText($img, $formattedDate, 195, 198, 12, '#000', 'left', 'top', true);
            } catch (\Throwable) {
                // Skip invalid dates from legacy dumps (e.g. 0000-00-00).
            }
        }

        $qrImage = Image::make($this->generateQrPng((string) $data['qrcode'], 160));
        $img->insert($qrImage, 'top-left', 40, 475);

        $signaturePath = PublicAssetPath::resolve($data['signature'] ?? null);
        if ($signaturePath) {
            $signature = Image::make($signaturePath)->resize(380, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->insert($signature, 'top-left', 200, 555);
        }

        if (! empty($data['valid_until'])) {
            $this->drawIdCardText($img, (string) $data['valid_until'], 278, 820, 15, '#000', 'center', 'top', true);
        }

        return $img;
    }

    private function idCardTemplate(string $side): InterventionImage
    {
        $side = $side === 'back' ? 'back' : 'front';
        $relative = "images/id_templates/{$side}.png";
        $path = public_path($relative);

        if (! is_file($path)) {
            abort(500, "USM ID template missing: public/{$relative}");
        }

        return Image::make($path);
    }

    private function formatIdCardName(?string $firstname, ?string $lastname, ?string $middleInitial = null): string
    {
        $first = trim((string) $firstname);
        $last = trim((string) $lastname);
        $mi = trim((string) $middleInitial);

        if ($mi !== '') {
            $mi = rtrim($mi, '.').'.';
            $name = trim("{$first} {$mi} {$last}");
        } else {
            $name = trim("{$first} {$last}");
        }

        return mb_strtoupper($name);
    }

    private function idCardFont(bool $bold = true): string
    {
        $candidates = $bold
            ? [
                public_path('fonts/arialbd.ttf'),
                public_path('fonts/Arial Bold.ttf'),
                'C:/Windows/Fonts/arialbd.ttf',
                'C:/Windows/Fonts/ARIALBD.TTF',
            ]
            : [
                public_path('fonts/arial.ttf'),
                public_path('fonts/Arial.ttf'),
                'C:/Windows/Fonts/arial.ttf',
                'C:/Windows/Fonts/ARIAL.TTF',
            ];

        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return public_path('fonts/arial.ttf');
    }

    private function drawIdCardText(
        $img,
        string $text,
        int $x,
        int $y,
        int $size,
        string $color = '#000',
        string $align = 'center',
        string $valign = 'top',
        bool $bold = true
    ): void {
        $fontPath = $this->idCardFont($bold);
        $hasBoldFile = $bold && (
            str_contains(strtolower($fontPath), 'arialbd')
            || str_contains(strtolower($fontPath), 'bold')
        );

        if ($bold && ! $hasBoldFile) {
            foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$ox, $oy]) {
                $img->text($text, $x + $ox, $y + $oy, function ($font) use ($fontPath, $size, $color, $align, $valign) {
                    $font->file($fontPath);
                    $font->size($size);
                    $font->color($color);
                    $font->align($align);
                    $font->valign($valign);
                });
            }
        }

        $img->text($text, $x, $y, function ($font) use ($fontPath, $size, $color, $align, $valign) {
            $font->file($fontPath);
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
    private function generateQrPng(string $payload, int $size = 160): string
    {
        if ($payload === '') {
            $payload = 'UNKNOWN';
        }

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
