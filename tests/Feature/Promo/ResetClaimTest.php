<?php

use App\Enums\ClaimStatus;
use App\Filament\Resources\QrCodes\Pages\ListQrCodes;
use App\Models\Claim;
use App\Models\QrCode;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

    // Scanned three times, then claimed.
    $this->qrCode = QrCode::factory()->create([
        'scan_count' => 3,
        'first_scanned_at' => now()->subDay(),
        'last_scanned_at' => now()->subHour(),
    ]);
    $this->claim = Claim::factory()->create(['qr_code_id' => $this->qrCode->id, 'name' => 'Nimal Perera']);
});

it('resets the claim and the scan status, so the code is back to new', function () {
    Log::spy();

    Livewire::test(ListQrCodes::class)
        ->callAction(TestAction::make('resetClaim')->table($this->qrCode), data: ['reason' => 'Test claim before launch'])
        ->assertHasNoActionErrors()
        ->assertNotified("Claim reset on {$this->qrCode->serial}");

    expect(Claim::count())->toBe(0)
        ->and($this->qrCode->fresh())
        ->claim->toBeNull()
        ->scan_count->toBe(0)               // "Not scanned" in the admin list
        ->first_scanned_at->toBeNull()
        ->last_scanned_at->toBeNull();

    Log::shouldHaveReceived('warning')->once()->with('Gift claim reset', Mockery::on(
        fn (array $context) => $context['reference'] === $this->claim->reference
            && $context['qr_serial'] === $this->qrCode->serial
            && $context['scans_before_reset'] === 3
            && $context['reason'] === 'Test claim before launch'
            && $context['by'] === $this->admin->email
            && ! in_array('Nimal Perera', $context, true) // no personal details in the log
    ));

    // Scanning it again starts counting from scratch.
    $this->withoutVite()->get("/r/{$this->qrCode->code}")->assertOk();
    expect($this->qrCode->fresh()->scan_count)->toBe(1);

    // A new winner can now claim it through the lucky draw screens.
    $this->withoutVite()->flushSession();
    $this->post("/r/{$this->qrCode->code}/details", [
        'first_name' => 'Kamal', 'last_name' => 'Silva', 'phone' => '0771234567', 'terms' => '1',
    ])->assertRedirect("/r/{$this->qrCode->code}/review");
    $this->post("/r/{$this->qrCode->code}")->assertRedirect("/r/{$this->qrCode->code}/received");

    expect(Claim::sole()->name)->toBe('Kamal Silva');
});

it('needs a reason', function () {
    Livewire::test(ListQrCodes::class)
        ->callAction(TestAction::make('resetClaim')->table($this->qrCode), data: ['reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    expect(Claim::count())->toBe(1);
});

it('only offers a reset on codes that have been claimed', function () {
    $unclaimed = QrCode::factory()->create();

    Livewire::test(ListQrCodes::class)
        ->assertActionVisible(TestAction::make('resetClaim')->table($this->qrCode))
        ->assertActionHidden(TestAction::make('resetClaim')->table($unclaimed));
});

it('shows the claim being reset, and warns when the voucher was already sent or collected', function (ClaimStatus $status, bool $warns) {
    $this->claim->forceFill(['status' => $status])->save();

    $test = Livewire::test(ListQrCodes::class)
        ->mountAction(TestAction::make('resetClaim')->table($this->qrCode))
        ->assertMountedActionModalSee($this->claim->reference)
        ->assertMountedActionModalSee('Nimal Perera');

    $warns
        ? $test->assertMountedActionModalSee('Resetting means the same gift could be claimed twice')
        : $test->assertMountedActionModalDontSee('claimed twice');
})->with([
    'pending' => [ClaimStatus::Pending, false],
    'voucher sent' => [ClaimStatus::VoucherSent, true],
    'collected' => [ClaimStatus::Collected, true],
]);

it('keeps non-admins out', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin/qr-codes')->assertForbidden();

    expect(Claim::count())->toBe(1);
});
