<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateRegisterModalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('developer') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $color = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        $image = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=1000,max_height=1000'];

        return [
            'register_modal_attendance_logo' => $image,
            'register_modal_library_logo' => $image,
            'register_modal_heading' => ['nullable', 'string', 'max:80'],
            'register_modal_login_label' => ['nullable', 'string', 'max:80'],
            'register_modal_attendance_tab' => ['nullable', 'string', 'max:80'],
            'register_modal_library_tab' => ['nullable', 'string', 'max:80'],
            'register_modal_attendance_welcome_label' => ['nullable', 'string', 'max:80'],
            'register_modal_attendance_portal_name' => ['nullable', 'string', 'max:100'],
            'register_modal_attendance_description' => ['nullable', 'string', 'max:255'],
            'register_modal_attendance_heading' => ['nullable', 'string', 'max:120'],
            'register_modal_attendance_student_label' => ['nullable', 'string', 'max:80'],
            'register_modal_attendance_employee_label' => ['nullable', 'string', 'max:80'],
            'register_modal_attendance_student_submit' => ['nullable', 'string', 'max:120'],
            'register_modal_attendance_employee_submit' => ['nullable', 'string', 'max:120'],
            'register_modal_library_welcome_label' => ['nullable', 'string', 'max:80'],
            'register_modal_library_portal_name' => ['nullable', 'string', 'max:100'],
            'register_modal_library_description' => ['nullable', 'string', 'max:255'],
            'register_modal_library_heading' => ['nullable', 'string', 'max:120'],
            'register_modal_library_student_label' => ['nullable', 'string', 'max:80'],
            'register_modal_library_employee_label' => ['nullable', 'string', 'max:80'],
            'register_modal_library_student_submit' => ['nullable', 'string', 'max:120'],
            'register_modal_library_employee_submit' => ['nullable', 'string', 'max:120'],
            'register_modal_attendance_panel_color' => $color,
            'register_modal_attendance_welcome_portal_color' => $color,
            'register_modal_attendance_description_color' => $color,
            'register_modal_attendance_text_color' => $color,
            'register_modal_attendance_accent_color' => $color,
            'register_modal_attendance_active_role_color' => $color,
            'register_modal_attendance_submit_color' => $color,
            'register_modal_library_panel_color' => $color,
            'register_modal_library_welcome_portal_color' => $color,
            'register_modal_library_description_color' => $color,
            'register_modal_library_text_color' => $color,
            'register_modal_library_accent_color' => $color,
            'register_modal_library_active_role_color' => $color,
            'register_modal_library_submit_color' => $color,
        ];
    }

    protected function prepareForValidation(): void
    {
        $colorFields = [
            'register_modal_attendance_panel_color',
            'register_modal_attendance_welcome_portal_color',
            'register_modal_attendance_description_color',
            'register_modal_attendance_text_color',
            'register_modal_attendance_accent_color',
            'register_modal_attendance_active_role_color',
            'register_modal_attendance_submit_color',
            'register_modal_library_panel_color',
            'register_modal_library_welcome_portal_color',
            'register_modal_library_description_color',
            'register_modal_library_text_color',
            'register_modal_library_accent_color',
            'register_modal_library_active_role_color',
            'register_modal_library_submit_color',
        ];

        foreach ($colorFields as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => strtoupper(trim((string) $this->input($field)))]);
            }
        }

        $textFields = [
            'register_modal_heading',
            'register_modal_login_label',
            'register_modal_attendance_tab',
            'register_modal_library_tab',
            'register_modal_attendance_welcome_label',
            'register_modal_attendance_portal_name',
            'register_modal_attendance_description',
            'register_modal_attendance_heading',
            'register_modal_attendance_student_label',
            'register_modal_attendance_employee_label',
            'register_modal_attendance_student_submit',
            'register_modal_attendance_employee_submit',
            'register_modal_library_welcome_label',
            'register_modal_library_portal_name',
            'register_modal_library_description',
            'register_modal_library_heading',
            'register_modal_library_student_label',
            'register_modal_library_employee_label',
            'register_modal_library_student_submit',
            'register_modal_library_employee_submit',
        ];

        foreach ($textFields as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }
}
