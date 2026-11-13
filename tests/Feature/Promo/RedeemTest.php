<?php

use App\Actions\ClaimGift;
use App\Enums\ClaimStatus;
use App\Enums\Gift;
use App\Exceptions\GiftAlreadyClaimed;
use App\Models\Claim;
use App\Models\QrCode;

beforeEach(function () {
    $this->withoutVite();
});

function validClaim(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nimal Perera',
        'phone' => '077 123 4567',
        'email' => 'nimal@example.com',
        'nic' => '199012345678',
        'consent' => '1',
    ], $overrides);
}

describe('scanning a QR code', function () {
    it('shows a friendly 404 for an unknown code', function () {
        $this->get('/r/NOTAREALCODE')
            ->assertNotFound()
            ->assertSee("We couldn't find this code", false);
    });

    it('shows the try-again screen for the shared QR on packages without a gift', function () {
        $this->get(QrCode::tryAgainUrl())
            ->assertOk()
            ->assertSee('Not a winner this time')
            ->assertSee('Try again with another pack')
            ->assertDontSee('<form', false);
    });

    it('records each scan of a gift QR code', function () {
        $qrCode = QrCode::factory()->create();

        $this->get("/r/{$qrCode->code}")->assertOk();
        $this->get("/r/{$qrCode->code}")->assertOk();

        expect($qrCode->fresh())
            ->scan_count->toBe(2)
            ->first_scanned_at->not->toBeNull();
    });

    it('shows winners their gift and the claim form', function (Gift $gift) {
        $qrCode = QrCode::factory()->forGift($gift)->create();

        $this->get("/r/{$qrCode->code}")
            ->assertOk()
            ->assertSee('Congratulations!')
            ->assertSee($gift->getLabel())
            ->assertSee('Claim my gift');
    })->with(Gift::cases());

    it('accepts the code in lower case', function () {
        $qrCode = QrCode::factory()->create();

        $this->get('/r/'.strtolower($qrCode->code))->assertOk()->assertSee('Claim my gift');
    });
});

describe('claiming a gift', function () {
    it('saves the claim and shows the reference', function () {
        $qrCode = QrCode::factory()->forGift(Gift::ShangriLaDinner)->create();

        $this->post("/r/{$qrCode->code}", validClaim())
            ->assertRedirect("/r/{$qrCode->code}");

        $claim = Claim::sole();

        expect($claim)
            ->qr_code_id->toBe($qrCode->id)
            ->gift->toBe(Gift::ShangriLaDinner)
            ->status->toBe(ClaimStatus::Pending)
            ->name->toBe('Nimal Perera')
            ->phone->toBe('+94771234567')
            ->nic->toBe('199012345678')
            ->reference->toMatch('/^XMAS-[A-Z0-9]{8}$/');

        // Same browser, after the redirect and on refresh: show the reference.
        $this->get("/r/{$qrCode->code}")
            ->assertOk()
            ->assertSee('Your gift is reserved!')
            ->assertSee($claim->reference);
    });

    it('accepts the different phone and NIC formats people type', function (string $phone, string $nic, string $storedNic) {
        $qrCode = QrCode::factory()->create();

        $this->post("/r/{$qrCode->code}", validClaim(['phone' => $phone, 'nic' => $nic]))
            ->assertSessionHasNoErrors();

        expect(Claim::sole())
            ->phone->toBe('+94771234567')
            ->nic->toBe($storedNic);
    })->with([
        ['0771234567', '901234567v', '901234567V'],
        ['+94 77 123 4567', '901234567X', '901234567X'],
        ['94771234567', '1990 1234 5678', '199012345678'],
        ['077-123-4567', '199012345678', '199012345678'],
    ]);

    it('validates the form', function (array $input, string $field) {
        $qrCode = QrCode::factory()->create();

        $this->post("/r/{$qrCode->code}", validClaim($input))
            ->assertSessionHasErrors($field);

        expect(Claim::count())->toBe(0);
    })->with([
        'missing name' => [['name' => ''], 'name'],
        'landline phone' => [['phone' => '0112345678'], 'phone'],
        'short phone' => [['phone' => '07712345'], 'phone'],
        'bad email' => [['email' => 'not-an-email'], 'email'],
        'bad NIC' => [['nic' => '12345'], 'nic'],
        'no consent' => [['consent' => null], 'consent'],
    ]);

    it('lets each winning QR code be claimed only once', function () {
        $qrCode = QrCode::factory()->create();

        $this->post("/r/{$qrCode->code}", validClaim());

        // Someone else with the same code (e.g. a photo of the QR).
        $this->flushSession();
        $this->post("/r/{$qrCode->code}", validClaim(['name' => 'Someone Else', 'email' => 'else@example.com']))
            ->assertRedirect("/r/{$qrCode->code}");

        expect(Claim::count())->toBe(1)
            ->and(Claim::sole()->name)->toBe('Nimal Perera');
    });

    it('never shows the winner\'s details to someone else who scans the code', function () {
        $qrCode = QrCode::factory()->create();
        $this->post("/r/{$qrCode->code}", validClaim());
        $reference = Claim::sole()->reference;

        $this->flushSession();

        $this->get("/r/{$qrCode->code}")
            ->assertOk()
            ->assertSee('This gift has already been claimed')
            ->assertDontSee($reference)
            ->assertDontSee('Nimal')
            ->assertDontSee('4567');
    });

    it('allows the same person to claim several winning packages', function () {
        $first = QrCode::factory()->forGift(Gift::SpaCeylon)->create();
        $second = QrCode::factory()->forGift(Gift::ShangriLaDinner)->create();

        $this->post("/r/{$first->code}", validClaim());
        $this->post("/r/{$second->code}", validClaim());

        expect(Claim::where('nic', '199012345678')->count())->toBe(2);
    });

    it('rejects claims for unknown codes', function () {
        $this->post('/r/NOTAREALCODE', validClaim())->assertNotFound();

        expect(Claim::count())->toBe(0);
    });

    it('does not let the public form set the status or gift', function () {
        $qrCode = QrCode::factory()->forGift(Gift::SpaCeylon)->create();

        $this->post("/r/{$qrCode->code}", validClaim([
            'status' => ClaimStatus::Collected->value,
            'gift' => Gift::ShangriLaDinner->value,
        ]));

        expect(Claim::sole())
            ->status->toBe(ClaimStatus::Pending)
            ->gift->toBe(Gift::SpaCeylon);
    });

    it('reports a double claim from the claim action itself', function () {
        $qrCode = QrCode::factory()->create();
        $details = ['name' => 'A', 'phone' => '+94771234567', 'email' => 'a@example.com', 'nic' => '199012345678'];

        app(ClaimGift::class)->handle($qrCode, $details);

        expect(fn () => app(ClaimGift::class)->handle($qrCode, $details))
            ->toThrow(GiftAlreadyClaimed::class);
    });
});

it('rate limits claim submissions', function () {
    $qrCode = QrCode::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->post("/r/{$qrCode->code}", validClaim(['nic' => 'bad']));
    }

    $this->post("/r/{$qrCode->code}", validClaim(['nic' => 'bad']))->assertTooManyRequests();
});
