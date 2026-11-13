<?php

use App\Services\Print\ContentStreamFilter;

/** Collapse whitespace so tests compare instructions, not formatting. */
function ops(string $content): string
{
    return trim(preg_replace('/\s+/', ' ', $content));
}

it('leaves content outside hidden layers unchanged', function () {
    $content = "q 1 0 0 1 10 20 cm\n0 0 m 5 5 l S\n/Im0 Do\nQ";

    expect(ops(ContentStreamFilter::suppressPainting($content, ['MC1'])))->toBe(ops($content));
});

it('suppresses painting in a hidden layer but keeps graphics state changes', function () {
    // Like the card artwork: Illustrator closes a q from the visible layer inside the hidden one.
    $content = "/OC /MC0 BDC q 0 0 10 10 re W n 1 1 1 rg 0 0 5 5 re f EMC\n"
        .'/OC /MC1 BDC Q 0.275 0.067 0 rg /GS0 gs 0 8.504 158.74 223.937 re f EMC';

    expect(ops(ContentStreamFilter::suppressPainting($content, ['MC1'])))->toBe(
        '/OC /MC0 BDC q 0 0 10 10 re W n 1 1 1 rg 0 0 5 5 re f EMC '
        .'/OC /MC1 BDC Q 0.275 0.067 0 rg /GS0 gs 0 8.504 158.74 223.937 re n EMC'
    );
});

it('drops images, shadings and text drawn in a hidden layer', function () {
    $content = "/OC /Hidden BDC /Im1 Do /Sh1 sh BT /F1 12 Tf (Hi) Tj [(A) 10 (B)] TJ (next) ' ET EMC /Im2 Do";

    expect(ops(ContentStreamFilter::suppressPainting($content, ['Hidden'])))
        ->toBe('/OC /Hidden BDC BT /F1 12 Tf T* ET EMC /Im2 Do');
});

it('handles nested marked content and resumes painting after the hidden layer', function () {
    $content = '/OC /MC1 BDC /Span <</ActualText (x)>> BDC 0 0 1 1 re f EMC 1 1 2 2 re S EMC 3 3 4 4 re f';

    expect(ops(ContentStreamFilter::suppressPainting($content, ['MC1'])))
        ->toBe('/OC /MC1 BDC /Span << /ActualText (x) >> BDC 0 0 1 1 re n EMC 1 1 2 2 re n EMC 3 3 4 4 re f');
});

it('ignores marked content that is not a hidden layer', function () {
    $content = '/Artifact BMC 0 0 1 1 re f EMC /OC /MC0 BDC 1 1 1 1 re f EMC';

    expect(ops(ContentStreamFilter::suppressPainting($content, ['MC1'])))->toBe(ops($content));
});

it('is not confused by operator names inside strings', function () {
    $content = '/OC /MC1 BDC BT (EMC \\) f) Tj ET EMC BT (EMC (nested) f) Tj ET';

    expect(ops(ContentStreamFilter::suppressPainting($content, ['MC1'])))
        ->toBe('/OC /MC1 BDC BT ET EMC BT (EMC (nested) f) Tj ET');
});
