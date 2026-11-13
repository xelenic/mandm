<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Promotion name
    |--------------------------------------------------------------------------
    |
    | Shown on the public pages people see after scanning a QR code.
    |
    */

    'name' => env('PROMO_NAME', 'M&M Christmas Gift'),

    /*
    |--------------------------------------------------------------------------
    | Support contact
    |--------------------------------------------------------------------------
    |
    | Optional phone number or email shown to people who need help with a
    | claim (e.g. "077 123 4567" or "gifts@example.com"). Hidden if empty.
    |
    */

    'support_contact' => env('PROMO_SUPPORT_CONTACT'),

    /*
    |--------------------------------------------------------------------------
    | QR code base URL
    |--------------------------------------------------------------------------
    |
    | Gift QR codes point to "{qr_base_url}/r/{code}" and the shared "try
    | again" QR code points to "{qr_base_url}/try-again". Set this to your
    | live website address BEFORE exporting codes for printing; it can't be
    | changed once the packages are printed.
    |
    */

    'qr_base_url' => env('PROMO_QR_BASE_URL', env('APP_URL', 'http://localhost')),

    /*
    |--------------------------------------------------------------------------
    | Gift QR codes
    |--------------------------------------------------------------------------
    |
    | Default number of unique QR codes to generate per gift. Only gift
    | packages get a unique code; all other packages share one "try again"
    | QR code.
    |
    */

    'gifts' => [
        'spa_ceylon' => 200,
        'shangri_la_dinner' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Printed card
    |--------------------------------------------------------------------------
    |
    | The designer's card artwork (a one-page PDF) that every QR code is
    | printed onto, and the square box in that artwork where the QR code
    | goes, in millimetres from the card's top-left corner.
    |
    | Box for "56x85 mm - QR Card - Front - Generate AW.pdf": in the PDF it
    | is the 83.521 pt square outline at x 37.610 pt, y 75.582 pt (from the
    | bottom). If the designer moves the box or adds bleed, update these.
    |
    */

    'card' => [
        'template' => env('PROMO_CARD_TEMPLATE', public_path('git_qr/56x85 mm - QR Card - Front - Generate AW.pdf')),

        'qr_box' => [
            'x' => 13.268,
            'y' => 28.872,
            'size' => 29.464,
        ],

        // White gap between the box outline and the QR code, in mm. Smaller = bigger QR.
        // The artwork's white frame around the box adds ~3.6 mm more white outside the
        // outline, which together give scanners the clear border they need.
        'qr_margin' => env('PROMO_CARD_QR_MARGIN', 1.0),
    ],

];
