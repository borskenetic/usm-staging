<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Branding stylesheet (per school / per subdomain)
    |--------------------------------------------------------------------------
    |
    | Point this to a file under /public (served via asset()).
    | Example: BRANDING_CSS=branding/usm.css
    |
    */
    'css_path' => env('BRANDING_CSS', 'branding/branding.css'),

    /*
    |--------------------------------------------------------------------------
    | Asset Optimization
    |--------------------------------------------------------------------------
    |
    | Controls compression quality and resize behavior for uploaded branding
    | images (banners, logos, etc.). The AssetOptimizer service applies these
    | values after every file upload.
    |
    */
    'optimization' => [
        'jpeg_quality' => 85,
        'png_compression' => 7,
        'webp_quality' => 80,
        'strip_exif' => true,
        'dimensions' => [
            'banner' => ['max_width' => 4000, 'max_height' => 2000],
            'logo' => ['max_width' => 1000, 'max_height' => 1000],
        ],
    ],

    'defaults' => [
        'banner_path' => 'images/Bannernew.jpg',
        'opac_banner_path' => 'images/Bannernew.jpg',
        'opac_logo_path' => 'img/usm_logo_1954.png',
        'opac_default_book_cover_path' => 'images/defaultBook.png',
        'sidebar_logo_path' => 'img/usm_logo_1954.png',
        'login_modal_logo_path' => 'img/usm_logo_1954.png',
        'register_modal_attendance_logo_path' => 'img/usm_logo_1954.png',
        'register_modal_library_logo_path' => 'img/usm_logo_1954.png',
        'sidebar_brand_name' => 'USM Library',
        'sidebar_brand_subtitle' => 'KEPLRC Portal',
        'login_modal_welcome_label' => 'Welcome to',
        'login_modal_portal_name' => 'USM Library Portal',
        'login_modal_description' => 'Sign in to access the University of Southern Mindanao Library system.',
        'login_modal_sign_in_heading' => 'Sign in to your account',
        'login_modal_email_placeholder' => 'staff@usm.edu.ph',
        'login_modal_password_placeholder' => 'Enter password',
        'register_modal_heading' => 'Register',
        'register_modal_login_label' => 'Login',
        'register_modal_attendance_tab' => 'Attendance',
        'register_modal_library_tab' => 'Library',
        'register_modal_attendance_welcome_label' => 'Register for',
        'register_modal_attendance_portal_name' => 'USM Attendance',
        'register_modal_attendance_description' => 'Create your attendance record. Students and employees can log school attendance once approved.',
        'register_modal_attendance_heading' => 'Attendance Registration',
        'register_modal_attendance_student_label' => 'Student',
        'register_modal_attendance_employee_label' => 'Employee',
        'register_modal_attendance_student_submit' => 'Submit Student Registration',
        'register_modal_attendance_employee_submit' => 'Submit Employee Registration',
        'register_modal_library_welcome_label' => 'Register for',
        'register_modal_library_portal_name' => 'USM Library',
        'register_modal_library_description' => 'Apply for library access. A librarian reviews each request before your library ID is issued.',
        'register_modal_library_heading' => 'Library Registration',
        'register_modal_library_student_label' => 'Student',
        'register_modal_library_employee_label' => 'Faculty & Staff',
        'register_modal_library_student_submit' => 'Submit Student Registration',
        'register_modal_library_employee_submit' => 'Submit Faculty & Staff Registration',
        'sidebar_brand_text_color' => '#0F172A',
        'primary_color' => '#1F4EA7',
        'secondary_color' => '#2E7D32',
        'accent_color' => '#FFD700',
        'sidebar_background_color' => '#FFFFFF',
        'sidebar_text_color' => '#0F172A',
        'sidebar_active_color' => '#2E7D32',
        'sidebar_hover_background_color' => '#F1F5F9',
        'sidebar_hover_text_color' => '#0F172A',
        'button_color' => '#1F4EA7',
        'sidebar_footer_background_color' => '#FFFFFF',
        'table_header_color' => '#F1F5F9',
        'table_header_text_color' => '#475569',
        'table_border_color' => '#E2E8F0',
        'table_hover_color' => '#EFF6FF',
        'login_modal_left_background_color' => '#1F4EA7',
        'login_modal_welcome_portal_color' => '#FFFFFF',
        'login_modal_description_color' => '#DBEAFE',
        'login_modal_background_color' => '#FFFFFF',
        'login_modal_form_background_color' => '#FFFFFF',
        'login_modal_form_border_color' => '#DCE3EE',
        'login_modal_text_color' => '#172033',
        'login_modal_button_color' => '#2E7D32',
        'register_modal_attendance_panel_color' => '#2E7D32',
        'register_modal_attendance_welcome_portal_color' => '#FFFFFF',
        'register_modal_attendance_description_color' => '#FFFFFF',
        'register_modal_attendance_text_color' => '#172033',
        'register_modal_attendance_accent_color' => '#FFD700',
        'register_modal_attendance_active_role_color' => '#2E7D32',
        'register_modal_attendance_submit_color' => '#2E7D32',
        'register_modal_library_panel_color' => '#1F4EA7',
        'register_modal_library_welcome_portal_color' => '#FFFFFF',
        'register_modal_library_description_color' => '#FFFFFF',
        'register_modal_library_text_color' => '#172033',
        'register_modal_library_accent_color' => '#1F4EA7',
        'register_modal_library_active_role_color' => '#2E7D32',
        'register_modal_library_submit_color' => '#1F4EA7',
    ],
];
