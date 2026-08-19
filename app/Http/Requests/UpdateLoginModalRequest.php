<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLoginModalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('developer') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $color = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'login_modal_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=1000,max_height=1000'],
            'login_modal_welcome_label' => ['nullable', 'string', 'max:80'],
            'login_modal_portal_name' => ['nullable', 'string', 'max:100'],
            'login_modal_description' => ['nullable', 'string', 'max:255'],
            'login_modal_sign_in_heading' => ['nullable', 'string', 'max:120'],
            'login_modal_email_placeholder' => ['nullable', 'string', 'max:120'],
            'login_modal_password_placeholder' => ['nullable', 'string', 'max:120'],
            'login_modal_left_background_color' => $color,
            'login_modal_welcome_portal_color' => $color,
            'login_modal_description_color' => $color,
            'login_modal_background_color' => $color,
            'login_modal_form_background_color' => $color,
            'login_modal_form_border_color' => $color,
            'login_modal_text_color' => $color,
            'login_modal_button_color' => $color,
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['login_modal_left_background_color', 'login_modal_welcome_portal_color', 'login_modal_description_color', 'login_modal_background_color', 'login_modal_form_background_color', 'login_modal_form_border_color', 'login_modal_text_color', 'login_modal_button_color'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => strtoupper(trim((string) $this->input($field)))]);
            }
        }

        foreach (['login_modal_welcome_label', 'login_modal_portal_name', 'login_modal_description', 'login_modal_sign_in_heading', 'login_modal_email_placeholder', 'login_modal_password_placeholder'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }
}
