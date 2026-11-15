<?php

use App\Filament\Resources\QrCodes\Pages\ListQrCodes;
use App\Models\Claim;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

beforeEach(function () {
    QrCode::factory()->count(3)->create(['scan_count' => 1]);
    Claim::factory()->count(2)->create();
});

describe('admin panel', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    });

    it('deletes every QR code and claim once DELETE is typed', function () {
        Log::spy();

        Livewire::test(ListQrCodes::class)
            ->assertActionVisible('clearAll')
            ->callAction('clearAll', data: ['confirmation' => 'DELETE'])
            ->assertHasNoActionErrors()
            ->assertNotified('All promo data cleared');

        expect(QrCode::count())->toBe(0)
            ->and(Claim::count())->toBe(0);

        Log::shouldHaveReceived('warning')->once()->with('Promo data cleared', Mockery::on(
            fn (array $context) => $context['qr_codes'] === 5 && $context['claims'] === 2 && $context['by'] === $this->admin->email
        ));

        // Fresh start: Generate is back, Clear and Export are gone.
        Livewire::test(ListQrCodes::class)
            ->assertActionVisible('generate')
            ->assertActionHidden('export')
            ->assertActionHidden('clearAll');
    });

    it('deletes nothing unless DELETE is typed exactly', function (?string $typed) {
        Livewire::test(ListQrCodes::class)
            ->callAction('clearAll', data: ['confirmation' => $typed])
            ->assertHasActionErrors(['confirmation']);

        expect(QrCode::count())->toBe(5)
            ->and(Claim::count())->toBe(2);
    })->with([
        'empty' => [null],
        'lower case' => ['delete'],
        'something else' => ['yes'],
    ]);

    it('shows what will be deleted', function () {
        Livewire::test(ListQrCodes::class)
            ->mountAction('clearAll')
            ->assertMountedActionModalSee('5 QR codes (3 already scanned)')
            ->assertMountedActionModalSee('2 gift claims')
            ->assertMountedActionModalSee('Download the claims CSV first');
    });
});

it('keeps non-admins out', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin/qr-codes')->assertForbidden();

    expect(QrCode::count())->toBe(5);
});

describe('command line', function () {
    it('deletes everything once DELETE is typed', function () {
        $this->artisan('app:clear-promo-data')
            ->expectsQuestion('Type DELETE to confirm', 'DELETE')
            ->expectsOutputToContain('Deleted 5 QR codes and 2 claims')
            ->assertSuccessful();

        expect(QrCode::count())->toBe(0)
            ->and(Claim::count())->toBe(0);
    });

    it('deletes nothing for any other answer', function () {
        $this->artisan('app:clear-promo-data')
            ->expectsQuestion('Type DELETE to confirm', 'yes')
            ->assertFailed();

        expect(QrCode::count())->toBe(5)
            ->and(Claim::count())->toBe(2);
    });

    it('can skip the question with --force', function () {
        $this->artisan('app:clear-promo-data', ['--force' => true])->assertSuccessful();

        expect(QrCode::count())->toBe(0);
    });
});
