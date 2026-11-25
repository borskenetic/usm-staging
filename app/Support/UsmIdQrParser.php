<?php

namespace App\Support;

/**
 * Parses the wildly inconsistent USM physical student-ID QR payloads.
 *
 * Observed formats (and counting — ARO changes these):
 * - Tab:        "21-11048\tDECIE C. GANAO\tABPhilo\tDecember 5, 2002"
 * - ID-first:   "26-78714,KRISTINE MAE B. ONDOY,BSRT,1ST SEMESTER A.Y. 2026-2027"
 * - Name-first: "MERRYL JOANNA JOYCE T. GONZALES,BSN,,June 13, 2003"
 * - Name-only:  "MUHAIMA L. UGKA SR.,BIT,,"
 * - Multiline:  "23-69408\nSheryn Mae Capillo\nABPsych"
 * - Pantas:     "S-00004516"
 */
class UsmIdQrParser
{
    /**
     * @return array{
     *     student_no: ?string,
     *     full_name: ?string,
     *     course: ?string,
     *     birth_or_validity: ?string,
     *     parts: list<string>
     * }
     */
    public function parse(string $raw): array
    {
        $raw = trim(str_replace("\r", '', $raw));

        $parts = $this->splitParts($raw);
        $studentNo = $this->extractStudentNumber($raw);

        // Prefer an ID that appears as its own field.
        foreach ($parts as $part) {
            $fromPart = $this->extractStudentNumber($part);
            if ($fromPart !== null && $this->looksLikeStandaloneId($part)) {
                $studentNo = $fromPart;
                break;
            }
        }

        [$fullName, $course, $extra] = $this->classifyParts($parts, $studentNo);

        return [
            'student_no' => $studentNo,
            'full_name' => $fullName,
            'course' => $course,
            'birth_or_validity' => $extra,
            'parts' => $parts,
        ];
    }

    /**
     * Collapse a printed full name into a stable comparison key.
     * Strips punctuation, middle initials, and JR/SR/II/III/IV suffixes.
     */
    public function normalizeName(string $fullName): string
    {
        $name = strtoupper($fullName);
        $name = str_replace(['Ñ', 'ñ'], ['N', 'N'], $name);
        $name = preg_replace('/\b(JR|SR|II|III|IV)\.?(\s|$)/', ' ', $name) ?? $name;
        $name = preg_replace('/[^A-Z\s]/', '', $name) ?? '';
        $name = preg_replace('/\b[A-Z]\b/', '', $name) ?? '';

        return preg_replace('/\s+/', '', $name) ?? '';
    }

    /**
     * Normalize a course/program code for soft comparison (ABPhilo ≈ ABPhilos).
     */
    public function normalizeCourse(?string $course): ?string
    {
        if ($course === null || trim($course) === '') {
            return null;
        }

        $value = strtoupper($course);
        $value = preg_replace('/[^A-Z0-9]/', '', $value) ?? '';

        return $value !== '' ? $value : null;
    }

    /**
     * Guess lastname = last significant token of a printed full name.
     */
    public function guessLastName(string $fullName): ?string
    {
        $name = strtoupper($fullName);
        $name = preg_replace('/\b(JR|SR|II|III|IV)\.?(\s|$)/', ' ', $name) ?? $name;
        $name = preg_replace('/[^A-Z\s]/', ' ', $name) ?? '';
        $tokens = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));

        // Drop trailing single-letter initials.
        while ($tokens !== [] && strlen((string) end($tokens)) === 1) {
            array_pop($tokens);
        }

        if ($tokens === []) {
            return null;
        }

        return (string) end($tokens);
    }

    /**
     * @return list<string>
     */
    private function splitParts(string $raw): array
    {
        if (str_contains($raw, "\t")) {
            return array_values(array_filter(array_map('trim', explode("\t", $raw)), fn ($p) => $p !== ''));
        }

        if (str_contains($raw, "\n")) {
            return array_values(array_filter(array_map('trim', explode("\n", $raw)), fn ($p) => $p !== ''));
        }

        // Comma payloads often embed dates ("June 13, 2003") — keep empty slots
        // so "NAME,COURSE,," still yields a clear course field.
        return array_map('trim', explode(',', $raw));
    }

    /**
     * @param  list<string>  $parts
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function classifyParts(array $parts, ?string $studentNo): array
    {
        $meaningful = array_values(array_filter($parts, fn ($p) => $p !== ''));

        if ($meaningful === []) {
            return [null, null, null];
        }

        $fullName = null;
        $course = null;
        $extra = null;

        // Drop standalone ID field(s).
        $withoutId = array_values(array_filter(
            $meaningful,
            fn ($p) => ! $this->looksLikeStandaloneId($p)
        ));

        foreach ($withoutId as $part) {
            if ($this->looksLikePersonName($part)) {
                $fullName ??= $part;
            } elseif ($this->looksLikeCourse($part)) {
                $course ??= $part;
            } elseif ($this->looksLikeDateOrValidity($part)) {
                $extra ??= $part;
            } elseif ($fullName === null) {
                $fullName = $part;
            } elseif ($course === null) {
                $course = $part;
            } else {
                $extra ??= $part;
            }
        }

        // If ID-first comma format left name in parts[1] but classifier missed it.
        if ($fullName === null && $studentNo !== null) {
            foreach ($withoutId as $part) {
                if (! $this->looksLikeCourse($part) && ! $this->looksLikeDateOrValidity($part)) {
                    $fullName = $part;
                    break;
                }
            }
        }

        return [$fullName, $course, $extra];
    }

    public function extractStudentNumber(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        // Pantas-issued patron codes.
        if (preg_match('/\b(S-\d{5,})\b/i', $value, $matches)) {
            return strtoupper($matches[1]);
        }

        // YY-##### or YYYY-##### anywhere in the payload.
        if (preg_match('/\b(\d{2,4}-\d{3,})\b/', $value, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function looksLikeStandaloneId(string $part): bool
    {
        $part = trim($part);

        return (bool) preg_match('/^(S-\d{5,}|\d{2,4}-\d{3,})$/i', $part);
    }

    private function looksLikePersonName(string $part): bool
    {
        if ($this->looksLikeStandaloneId($part) || $this->looksLikeDateOrValidity($part)) {
            return false;
        }

        // Names are mostly letters/spaces/periods; usually contain a space or suffix.
        if (! preg_match('/[A-Za-z]/', $part)) {
            return false;
        }

        if ($this->looksLikeCourse($part) && ! str_contains($part, ' ')) {
            return false;
        }

        return (bool) preg_match('/[A-Za-z].*[A-Za-z]/', $part)
            && ! preg_match('/^\d/', $part);
    }

    private function looksLikeCourse(string $part): bool
    {
        $part = trim($part);
        if ($part === '' || str_contains($part, ' ')) {
            // Allow short spaced programs like "AB Philo" rarely; most are compact codes.
            if (! preg_match('/^(BS|AB|BA|BE|B|D|M|PhD)/i', $part)) {
                return false;
            }
        }

        // Compact program codes: BSN, BEED, ABPhilo, BIndTech, BSE - Libungan, etc.
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9\/\- ]{1,30}$/', $part)
            && ! $this->looksLikeDateOrValidity($part)
            && strlen(preg_replace('/[^A-Za-z]/', '', $part) ?? '') <= 24;
    }

    private function looksLikeDateOrValidity(string $part): bool
    {
        if (preg_match('/\b(SEMESTER|A\.?Y\.?|VALID)\b/i', $part)) {
            return true;
        }

        return (bool) preg_match('/\b(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:t(?:ember)?)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\b/i', $part)
            || (bool) preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}$/', $part);
    }
}
