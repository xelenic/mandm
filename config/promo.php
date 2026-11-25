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

    'name' => env('PROMO_NAME', "M&M's Christmas Lucky Draw"),

    // Footer on every lucky draw screen.
    'copyright' => env('PROMO_COPYRIGHT', '© 2026 Mars or Affiliates'),

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
        'cinnamon_life_dinner' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | QR code colour
    |--------------------------------------------------------------------------
    |
    | Colour of the QR code and the code text on the cards (and of the bare
    | QR images), as RGB hex. #461100 is a chocolate brown from the artwork.
    | Note: the artwork's "* Terms & Conditions Apply" text is printed with
    | the brand spot ink MI_BROWN_8_009 instead, so it can print slightly
    | differently. Keep it dark: scanners need strong contrast against white.
    |
    */

    'qr_color' => env('PROMO_QR_COLOR', '#461100'),

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

        // White gap between the box edge and the QR code, in mm. 0 = the QR code fills
        // the box exactly; the artwork's white frame around the box is the clear
        // border scanners need. Scanned 100% in testing, and better than smaller sizes
        // in poor conditions. This relies on remove_box_outline: the outline touching
        // the QR code made most scans fail.
        'qr_margin' => env('PROMO_CARD_QR_MARGIN', 0.0),

        // Hide the thin outline the designer drew around the box to mark the QR
        // position. The artwork file itself is not changed.
        'remove_box_outline' => env('PROMO_CARD_REMOVE_BOX_OUTLINE', true),

        // Each gift card's code, printed at the bottom right (e.g. "MM-QNTU8RJF7YCH")
        // in the QR colour. It mirrors the "* Terms & Conditions Apply" text at the
        // bottom left: same baseline, same distance from the edge.
        'code_text' => [
            'enabled' => env('PROMO_CARD_CODE_TEXT', true),
            'prefix' => 'MM-',
            // Printed on the shared try-again card (with the prefix: "MM-M3RRYXM4S").
            // Typing it in the home page search opens the try-again page. Keep it a
            // different length from gift codes (12 characters) so they never clash.
            'try_again_code' => env('PROMO_TRY_AGAIN_CODE', 'M3RRYXM4S'),
            // FPDF font definition made from public/StintUltraCondensed-Regular.ttf.
            // For another font: cd resources/fonts && php ../../vendor/setasign/fpdf/makefont/makefont.php /path/to/Font.ttf
            'font' => resource_path('fonts/StintUltraCondensed-Regular.json'),
            'size' => 10.5,      // pt; capitals about 2.5 mm tall
            'right' => 3.08,     // mm from the card's right edge to the end of the text
            'baseline' => 82.81, // mm from the card's top
        ],
    ],

];
