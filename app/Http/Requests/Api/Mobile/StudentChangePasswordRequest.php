<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Mobile;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StudentChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $student = $this->user();

            if (! $student instanceof Student) {
                return;
            }

            $password = $validator->getValue('password');

            // Reject if the password matches the derived default (birthdate YYYYMMDD)
            if ($student->birthday) {
                $defaultPassword = Carbon::parse($student->birthday)->format('Ymd');
                if ($password === $defaultPassword) {
                    $validator->errors()->add(
                        'password',
                        'The new password cannot be the same as your default password. Please choose something different.'
                    );
                }
            }

            // Reject if password has no letter or no number
            if (! preg_match('/[A-Za-z]/', $password) || ! preg_match('/[0-9]/', $password)) {
                $validator->errors()->add(
                    'password',
                    'The password must contain at least one letter and one number.'
                );
            }
        });
    }
}