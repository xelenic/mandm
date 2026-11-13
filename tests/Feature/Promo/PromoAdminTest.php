<?php

use App\Enums\ClaimStatus;
use App\Enums\Gift;
use App\Filament\Resources\Claims\ClaimResource;
use App\Filament\Resources\Claims\Pages\EditClaim;
use App\Filament\Resources\Claims\Pages\ListClaims;
use App\Filament\Resources\QrCodes\Pages\ListQrCodes;
use App\Filament\Widgets\PromoStatsOverview;
use App\Models\Claim;
use App\Models\QrCode;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('shows the promo pages to admins', function () {
    QrCode::factory()->count(3)->create();
    Claim::factory()->create();

    $this->get('/admin/qr-codes')->assertOk();
    $this->get('/admin/claims')->assertOk();
    $this->get('/admin/claims/'.Claim::first()->id.'/edit')->assertOk();
});

it('keeps non-admins out of the promo pages and downloads', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin/qr-codes')->assertForbidden();
    $this->get('/admin/claims')->assertForbidden();
    $this->get(URL::temporarySignedRoute('admin.qr-codes.export', now()->addMinute()))->assertForbidden();
    $this->get(URL::temporarySignedRoute('admin.claims.export', now()->addMinute()))->assertForbidden();
});

it('rejects unsigned download links', function () {
    $this->get('/admin/exports/qr-codes')->assertForbidden();
    $this->get('/admin/exports/claims')->assertForbidden();
});

it('generates QR codes from the admin panel', function () {
    Livewire::test(ListQrCodes::class)
        ->assertActionVisible('generate')
        ->assertActionHidden('export')
        ->callAction('generate', data: [
            Gift::SpaCeylon->value => 5,
            Gift::ShangriLaDinner->value => 6,
        ])
        ->assertNotified('11 gift QR codes generated');

    expect(QrCode::count())->toBe(11)
        ->and(QrCode::where('gift', Gift::SpaCeylon)->count())->toBe(5)
        ->and(QrCode::where('gift', Gift::ShangriLaDinner)->count())->toBe(6);

    // Once generated, the button disappears so codes can't be regenerated.
    Livewire::test(ListQrCodes::class)
        ->assertActionHidden('generate')
        ->assertActionVisible('export');
});

it('downloads the print ZIP', function () {
    QrCode::factory()->count(3)->create();

    $response = $this->get(URL::temporarySignedRoute('admin.qr-codes.export', now()->addMinute()));

    $response->assertOk()->assertDownload('qr-codes-for-print.zip');

    // deleteFileAfterSend() only fires on a real download, so clean up here.
    $file = $response->baseResponse->getFile()->getPathname();
    expect($file)->toBeFile();
    unlink($file);
});

it('lists QR codes and can filter by gift', function () {
    $spa = QrCode::factory()->forGift(Gift::SpaCeylon)->count(2)->create();
    $dinner = QrCode::factory()->forGift(Gift::ShangriLaDinner)->count(3)->create();

    Livewire::test(ListQrCodes::class)
        ->assertCanSeeTableRecords($spa->merge($dinner))
        ->filterTable('gift', Gift::SpaCeylon->value)
        ->assertCanSeeTableRecords($spa)
        ->assertCanNotSeeTableRecords($dinner);
});

it('shows a gift QR code preview', function () {
    $qrCode = QrCode::factory()->create();

    Livewire::test(ListQrCodes::class)
        ->mountAction(TestAction::make('showQr')->table($qrCode))
        ->assertMountedActionModalSeeHtml('<svg style="width:100%;height:auto"')
        ->assertMountedActionModalSee($qrCode->url());
});

it('shows the shared try-again QR code', function () {
    Livewire::test(ListQrCodes::class)
        ->mountAction('tryAgainQr')
        ->assertMountedActionModalSeeHtml('<svg style="width:100%;height:auto"')
        ->assertMountedActionModalSee(QrCode::tryAgainUrl());
});

it('lets admins update a claim\'s hand-over status and notes', function () {
    $claim = Claim::factory()->create();

    Livewire::test(EditClaim::class, ['record' => $claim->getRouteKey()])
        ->fillForm([
            'status' => ClaimStatus::VoucherSent->value,
            'notes' => 'Couriered on 20 Dec',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($claim->fresh())
        ->status->toBe(ClaimStatus::VoucherSent)
        ->notes->toBe('Couriered on 20 Dec');
});

it('does not allow claims to be created or deleted in the admin panel', function () {
    $claim = Claim::factory()->create();

    $this->get('/admin/claims/create')->assertNotFound();

    Livewire::test(EditClaim::class, ['record' => $claim->getRouteKey()])
        ->assertActionDoesNotExist('delete');
});

it('downloads claims as CSV, defusing spreadsheet formulas', function () {
    Claim::factory()->create(['name' => '=HYPERLINK("http://evil")', 'phone' => '+94771234567']);

    $response = $this->get(URL::temporarySignedRoute('admin.claims.export', now()->addMinute()));
    $csv = $response->streamedContent();

    $response->assertOk();
    expect($csv)->toContain("'=HYPERLINK")
        ->and($csv)->toContain(',+94771234567,');
});

it('shows promo stats on the dashboard', function () {
    QrCode::factory()->forGift(Gift::ShangriLaDinner)->count(3)->create(['scan_count' => 1]);
    QrCode::factory()->forGift(Gift::SpaCeylon)->count(2)->create();
    Claim::factory()->create(['qr_code_id' => QrCode::factory()->forGift(Gift::SpaCeylon)]);

    Livewire::test(PromoStatsOverview::class)
        ->assertSee('Gift QR codes scanned')
        ->assertSee('3 / 6')
        ->assertSee('Spa Ceylon Gift Vouchers claimed')
        ->assertSee('1 / 3')
        ->assertSee('Waiting for hand-over');
});

it('shows the pending claims count in the navigation', function () {
    Claim::factory()->count(2)->create();
    Claim::factory()->create(['status' => ClaimStatus::Collected]);

    Livewire::test(ListClaims::class)->assertOk();

    expect(ClaimResource::getNavigationBadge())->toBe('2');
});
