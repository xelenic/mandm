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
        'cinnamon_life_dinner' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | QR code colour
    |--------------------------------------------------------------------------
    |
    | CMYK percentages: cyan, magenta, yellow, black. The default is the
    | chocolate brown used in the card artwork (RGB #461000). It is mostly
    | black ink, so the code stays sharp in print. Keep it dark: scanners
    | need strong contrast against the white background.
    |
    */

    'qr_color_cmyk' => array_map('intval', explode(',', (string) env('PROMO_QR_COLOR_CMYK', '0,77,100,73'))),

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
        // bottom left: same baseline, same distance from the edge. The try-again
        // card has no code of its own, so it gets no text.
        'code_text' => [
            'enabled' => env('PROMO_CARD_CODE_TEXT', true),
            'prefix' => 'MM-',
            // FPDF font definition made from public/StintUltraCondensed-Regular.ttf.
            // For another font: cd resources/fonts && php ../../vendor/setasign/fpdf/makefont/makefont.php /path/to/Font.ttf
            'font' => resource_path('fonts/StintUltraCondensed-Regular.json'),
            'size' => 10.5,      // pt; capitals about 2.5 mm tall
            'right' => 3.08,     // mm from the card's right edge to the end of the text
            'baseline' => 82.81, // mm from the card's top
        ],
    ],

];
