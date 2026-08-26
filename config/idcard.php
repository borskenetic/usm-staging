<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ID card "Valid until" text
    |--------------------------------------------------------------------------
    |
    | Printed on the reverse under the "Valid until:" bar.
    | Override via IDCARD_VALID_UNTIL in .env when the semester changes.
    |
    */
    'valid_until' => env('IDCARD_VALID_UNTIL', '2ND SEMESTER 2025-2026'),

];
