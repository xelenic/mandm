<?php

use App\Actions\ClaimGift;
use App\Enums\ClaimStatus;
use App\Enums\Gift;
use App\Exceptions\GiftAlreadyClaimed;
use App\Models\Claim;
use App\Models\QrCode;
use App\Support\LuckyDrawImage;

beforeEach(function () {
    $this->withoutVite();
});

function details(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Nimal',
        'last_name' => 'Perera',
        'phone' => '077 123 4567',
        'terms' => '1',
    ], $overrides);
}

/** Screens 5 → 6 → submit, as a winner would go through them. */
function claimViaScreens($test, QrCode $qrCode, array $overrides = []): void
{
    $test->post("/r/{$qrCode->code}/details", details($overrides))->assertRedirect("/r/{$qrCode->code}/review");
    $test->post("/r/{$qrCode->code}")->assertRedirect("/r/{$qrCode->code}/received");
}

describe('gift card flow (screens 1 → 2 → 3/4 → 5 → 6 → 8)', function () {
    it('opens the menu with Claim Your Prize leading to the lucky draw', function () {
        $qrCode = QrCode::factory()->create();

        $this->get("/r/{$qrCode->code}")
            ->assertOk()
            ->assertSee('Claim your Prize')
            ->assertSee('href="'.route('lucky.draw', $qrCode->code).'"', false)
            ->assertSee('Campaign Details')
            ->assertSee('How to Join') // the pop-up "Campaign Details" opens
            ->assertSee('Terms &amp; Conditions', false)
            ->assertSee('Winners Gallery');
    });

    it('shows Enter Lucky Draw, leading to the result', function () {
        $qrCode = QrCode::factory()->create();

        $this->get("/r/{$qrCode->code}/draw")
            ->assertOk()
            ->assertSee('Enter for a chance to win a gift voucher.')
            ->assertSee('One entry per card.')
            ->assertSee('href="'.route('lucky.result', $qrCode->code).'"', false);
    });

    it('reveals the right prize', function (Gift $gift, string $image, string $amount, string $name) {
        $qrCode = QrCode::factory()->forGift($gift)->create();

        $this->get("/r/{$qrCode->code}/result")
            ->assertOk()
            ->assertSee("{$image}.webp")
            ->assertSeeInOrder(['Congrats!', 'You won a', $amount, $name])
            ->assertSee('href="'.route('lucky.details', $qrCode->code).'"', false);
    })->with([
        [Gift::SpaCeylon, 'voucher-spa-ceylon', 'Rs. 3,000', 'Spa Ceylon Gift Certificate'],
        [Gift::CinnamonLifeDinner, 'voucher-cinnamon-life', 'Rs. 10,300', 'Cinnamon Life Gift Voucher'],
    ]);

    it('records a scan when the card is opened', function () {
        $qrCode = QrCode::factory()->create();

        $this->get("/r/{$qrCode->code}");
        $this->get("/r/{$qrCode->code}");

        expect($qrCode->fresh())
            ->scan_count->toBe(2)
            ->first_scanned_at->not->toBeNull();
    });

    it('accepts the code in lower case', function () {
        $qrCode = QrCode::factory()->create();

        $this->get('/r/'.strtolower($qrCode->code).'/result')->assertOk()->assertSee('Congrats!');
    });

    it('lets a winner review their details, go back to change them, and submit', function () {
        $qrCode = QrCode::factory()->forGift(Gift::CinnamonLifeDinner)->create();

        $this->get("/r/{$qrCode->code}/details")
            ->assertOk()
            ->assertSee('Personal Details')
            ->assertSee('First Name')
            ->assertSee('Mobile Number')
            ->assertSee('I agree to the campaign');

        // Screen 5 → 6: nothing is saved yet.
        $this->post("/r/{$qrCode->code}/details", details())->assertRedirect("/r/{$qrCode->code}/review");
        expect(Claim::count())->toBe(0);

        $this->get("/r/{$qrCode->code}/review")
            ->assertOk()
            ->assertSee('Nimal')
            ->assertSee('Perera')
            ->assertSee('077 123 4567')
            ->assertSee('Almost there!')
            ->assertSee('Verify');

        // "Back" keeps what they typed.
        $this->get("/r/{$qrCode->code}/details")->assertSee('value="Nimal"', false);
        $this->post("/r/{$qrCode->code}/details", details(['first_name' => 'Kamal']));

        // Verify → screen 8.
        $this->post("/r/{$qrCode->code}")->assertRedirect("/r/{$qrCode->code}/received");

        $this->get("/r/{$qrCode->code}/received")
            ->assertOk()
            ->assertSee('Details')
            ->assertSee('Received!')
            ->assertSee('Our team will contact you to verify your win');

        expect(Claim::sole())
            ->qr_code_id->toBe($qrCode->id)
            ->gift->toBe(Gift::CinnamonLifeDinner)
            ->status->toBe(ClaimStatus::Pending)
            ->name->toBe('Kamal Perera')
            ->phone->toBe('+94771234567')
            ->email->toBeNull()
            ->nic->toBeNull()
            ->reference->toMatch('/^XMAS-[A-Z0-9]{8}$/');
    });

    it('keeps showing "Details Received" to the winner afterwards, without counting more scans', function () {
        $qrCode = QrCode::factory()->create();
        claimViaScreens($this, $qrCode);

        $this->get("/r/{$qrCode->code}/result")->assertRedirect("/r/{$qrCode->code}/received");
        $this->get("/r/{$qrCode->code}");

        expect($qrCode->fresh()->scan_count)->toBe(0);
    });

    it('sends people back to the form if they skip it', function () {
        $qrCode = QrCode::factory()->create();

        $this->get("/r/{$qrCode->code}/review")->assertRedirect("/r/{$qrCode->code}/details");
        $this->post("/r/{$qrCode->code}")->assertRedirect("/r/{$qrCode->code}/details");
        $this->get("/r/{$qrCode->code}/received")->assertRedirect("/r/{$qrCode->code}/result");

        expect(Claim::count())->toBe(0);
    });
});

describe('the contact form', function () {
    it('accepts the mobile number formats people type', function (string $phone) {
        $qrCode = QrCode::factory()->create();

        claimViaScreens($this, $qrCode, ['phone' => $phone]);

        expect(Claim::sole()->phone)->toBe('+94771234567');
    })->with(['0771234567', '+94 77 123 4567', '94771234567', '077-123-4567', '771234567']);

    it('validates every field', function (array $input, string $field) {
        $qrCode = QrCode::factory()->create();

        $this->from("/r/{$qrCode->code}/details")
            ->post("/r/{$qrCode->code}/details", details($input))
            ->assertRedirect("/r/{$qrCode->code}/details")
            ->assertSessionHasErrors($field);

        $this->get("/r/{$qrCode->code}/review")->assertRedirect("/r/{$qrCode->code}/details");
    })->with([
        'missing first name' => [['first_name' => ''], 'first_name'],
        'missing last name' => [['last_name' => ' '], 'last_name'],
        'landline' => [['phone' => '0112345678'], 'phone'],
        'too short' => [['phone' => '07712345'], 'phone'],
        'terms not agreed' => [['terms' => null], 'terms'],
    ]);

    it('does not let the form set the status or gift', function () {
        $qrCode = QrCode::factory()->forGift(Gift::SpaCeylon)->create();

        claimViaScreens($this, $qrCode, ['status' => ClaimStatus::Collected->value, 'gift' => Gift::CinnamonLifeDinner->value]);

        expect(Claim::sole())
            ->status->toBe(ClaimStatus::Pending)
            ->gift->toBe(Gift::SpaCeylon);
    });
});

describe('claiming only once', function () {
    it('lets each gift card be claimed only once', function () {
        $qrCode = QrCode::factory()->create();
        claimViaScreens($this, $qrCode);

        // Someone else with the same card (e.g. a photo of the QR code).
        $this->flushSession();
        $this->post("/r/{$qrCode->code}/details", details(['first_name' => 'Someone']))->assertRedirect("/r/{$qrCode->code}/result");
        $this->post("/r/{$qrCode->code}")->assertRedirect("/r/{$qrCode->code}/details");
        $this->get("/r/{$qrCode->code}/details")->assertRedirect("/r/{$qrCode->code}/result");

        expect(Claim::count())->toBe(1)
            ->and(Claim::sole()->name)->toBe('Nimal Perera');
    });

    it('never shows the winner\'s details to someone else', function () {
        $qrCode = QrCode::factory()->create();
        claimViaScreens($this, $qrCode);

        $this->flushSession();

        $this->get("/r/{$qrCode->code}/result")
            ->assertOk()
            ->assertSee('Already Claimed')
            ->assertDontSee('Nimal')
            ->assertDontSee('4567')
            ->assertDontSee(Claim::sole()->reference);
    });

    it('handles two people submitting at the same moment', function () {
        $qrCode = QrCode::factory()->create();
        $this->post("/r/{$qrCode->code}/details", details());

        // Someone else claims between this person's review and submit.
        Claim::factory()->create(['qr_code_id' => $qrCode->id]);

        $this->post("/r/{$qrCode->code}")->assertRedirect("/r/{$qrCode->code}/result");
        $this->get("/r/{$qrCode->code}/result")->assertSee('Already Claimed');

        expect(Claim::count())->toBe(1);
    });

    it('allows the same person to claim several winning cards', function () {
        claimViaScreens($this, QrCode::factory()->forGift(Gift::SpaCeylon)->create());
        claimViaScreens($this, QrCode::factory()->forGift(Gift::CinnamonLifeDinner)->create());

        expect(Claim::where('phone', '+94771234567')->count())->toBe(2);
    });

    it('reports a double claim from the claim action itself', function () {
        $qrCode = QrCode::factory()->create();
        $details = ['name' => 'A B', 'phone' => '+94771234567', 'email' => null, 'nic' => null];

        app(ClaimGift::class)->handle($qrCode, $details);

        expect(fn () => app(ClaimGift::class)->handle($qrCode, $details))->toThrow(GiftAlreadyClaimed::class);
    });
});

describe('try-again card flow (screens 1 → 2 → 7)', function () {
    it('goes from the menu through Enter Lucky Draw to "Thanks for joining in"', function () {
        $this->get('/try-again')
            ->assertOk()
            ->assertSee('href="'.route('lucky.try-again.draw').'"', false);

        $this->get('/try-again/draw')
            ->assertOk()
            ->assertSee('href="'.route('lucky.try-again.result').'"', false);

        $this->get('/try-again/result')
            ->assertOk()
            ->assertSee('joining in!')
            ->assertSee("This entry didn't win a voucher.", false)
            ->assertSee('character-waving.webp')
            ->assertSee('href="'.route('redeem.try-again').'"', false);
    });
});

describe('typing the code (home page, no card scanned)', function () {
    beforeEach(function () {
        $this->qrCode = QrCode::factory()->create(['code' => 'QNTU8RJF7YCH']);
    });

    it('shows the menu, with Claim Your Prize leading to the code box', function () {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('lucky.enter-code').'"', false);

        $this->get('/draw')
            ->assertOk()
            ->assertSee('Type the code printed at the bottom right of your card')
            ->assertSee('action="'.route('redeem.search').'"', false);
    });

    it('opens the prize, however the code is typed', function (string $typed) {
        $this->post('/draw', ['code' => $typed])->assertRedirect('/r/QNTU8RJF7YCH/result');

        expect($this->qrCode->fresh()->scan_count)->toBe(1);
    })->with([
        'as printed' => ['MM-QNTU8RJF7YCH'],
        'without the prefix' => ['QNTU8RJF7YCH'],
        'lower case' => ['mm-qntu8rjf7ych'],
        'prefix without dash' => ['MMQNTU8RJF7YCH'],
        'with spaces' => [' MM - QNTU 8RJF 7YCH '],
    ]);

    it('finds a code that itself starts with MM', function () {
        QrCode::factory()->create(['code' => 'MMAB23CD45EF']);

        $this->post('/draw', ['code' => 'MM-MMAB23CD45EF'])->assertRedirect('/r/MMAB23CD45EF/result');
    });

    it('opens "Thanks for joining in" for the code on the try-again card', function (string $typed) {
        $this->post('/draw', ['code' => $typed])->assertRedirect('/try-again/result');
    })->with(['MM-M3RRYXM4S', 'M3RRYXM4S', 'mm m3rry xm4s']);

    it('explains when a code is not found, keeping what was typed', function () {
        $this->from('/draw')
            ->post('/draw', ['code' => 'MM-WRONGCODE99'])
            ->assertRedirect('/draw')
            ->assertSessionHasErrors(['code' => "We couldn't find this code. Please check it against your card and try again."])
            ->assertSessionHasInput('code', 'MM-WRONGCODE99');

        expect($this->qrCode->fresh()->scan_count)->toBe(0);
    });

    it('asks for a code when the box is empty', function () {
        $this->from('/draw')
            ->post('/draw', ['code' => ''])
            ->assertSessionHasErrors(['code' => 'Please enter the code printed on your card.']);
    });

    it('stops anyone trying codes in bulk', function () {
        foreach (range(1, 10) as $attempt) {
            $this->post('/draw', ['code' => "WRONG{$attempt}"]);
        }

        $this->post('/draw', ['code' => 'MM-QNTU8RJF7YCH'])->assertTooManyRequests();
    });
});

describe('other screens', function () {
    it('shows a friendly "code not found" screen with a way back', function () {
        $this->get('/r/NOTAREALCODE')
            ->assertNotFound()
            ->assertSee("We couldn't find this code")
            ->assertSee('Back to Menu')
            ->assertSee('href="'.route('home').'"', false);
    });

    it('opens "How to Join" for Campaign Details, on the menu the person came from, without counting a scan', function () {
        $qrCode = QrCode::factory()->create();
        $this->get("/r/{$qrCode->code}");

        $this->get('/campaign')->assertRedirect(route('redeem.show', $qrCode->code).'?how-to-join=1');

        $this->get(route('redeem.show', $qrCode->code).'?how-to-join=1')
            ->assertOk()
            ->assertSeeInOrder(['How to Join', 'Step 1 :-', 'Advent Calendar Box', 'Step 2 :-', 'redeem your prize instantly'])
            ->assertSee('dialog.showModal();', false); // opens straight away

        expect($qrCode->fresh()->scan_count)->toBe(1);
    });

    it('shows the menu information pages, returning to the menu the person came from', function (string $url, string $title) {
        $qrCode = QrCode::factory()->create();
        $this->get("/r/{$qrCode->code}");

        $this->get($url)
            ->assertOk()
            ->assertSee($title, false)
            ->assertSee('href="'.route('redeem.show', $qrCode->code).'"', false);
    })->with([
        ['/terms', 'Terms &amp; Conditions'],
        ['/winners', 'Winners Gallery'],
    ]);

    it('shows the full Terms & Conditions, with a tappable privacy link and enquiry number', function () {
        $this->get('/terms')
            ->assertOk()
            ->assertSeeInOrder([
                '1. Organiser and eligibility',
                'Mars International India Private Limited',
                '2. Campaign Period',
                '01 December 2026',
                '01 January 2027',
                '3. How to Enter',
                'Only one entry per card is permitted.',
                '4. Results and Prize Claims',
                'within seven working days of winner confirmation',
                '5. Personal Information and Enquiries',
            ], false)
            ->assertSee('href="https://www.mars.com/privacy"', false)
            ->assertSee('href="tel:+94706335650"', false)
            ->assertSee('+94 70 63 35 650')
            // The text scrolls inside the panel and can be scrolled with the keyboard.
            ->assertSee('tabindex="0" role="region" aria-label="Terms and Conditions text"', false)
            ->assertSee('lucky-scroll', false);
    });

    it('versions image addresses, so a replaced image is never served from an old cache', function () {
        $version = filemtime(public_path('images/lucky-draw/logo.webp'));

        expect(LuckyDrawImage::url('logo'))->toEndWith("/images/lucky-draw/logo.webp?v={$version}")
            ->and(LuckyDrawImage::url('no-such-image'))->toEndWith('/images/lucky-draw/no-such-image.webp');

        $this->get('/')->assertSee("logo.webp?v={$version}", false);
    });

    it('shows the footer and the logo on every screen', function () {
        $this->get('/')
            ->assertSee('© 2026 Mars or Affiliates')
            ->assertSee('images/lucky-draw/logo.webp');
    });

    it('rate limits contact form submissions', function () {
        $qrCode = QrCode::factory()->create();

        foreach (range(1, 10) as $attempt) {
            $this->post("/r/{$qrCode->code}/details", details(['phone' => 'bad']));
        }

        $this->post("/r/{$qrCode->code}/details", details(['phone' => 'bad']))->assertTooManyRequests();
    });
});

it('prints MM-M3RRYXM4S on the try-again card, unless code text is switched off', function () {
    expect(QrCode::tryAgainCardText())->toBe('MM-M3RRYXM4S');

    config(['promo.card.code_text.enabled' => false]);

    expect(QrCode::tryAgainCardText())->toBeNull();
});
