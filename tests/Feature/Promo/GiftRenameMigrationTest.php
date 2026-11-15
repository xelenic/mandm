<?php

use App\Enums\Gift;
use App\Models\Claim;
use App\Models\QrCode;
use Illuminate\Support\Facades\DB;

it('converts existing Shangri-La codes and claims to Cinnamon Life, and back on rollback', function () {
    $migration = require database_path('migrations/2026_11_15_000000_rename_shangri_la_gift_to_cinnamon_life.php');

    $claim = Claim::factory()->create();
    $spa = QrCode::factory()->forGift(Gift::SpaCeylon)->create();
    DB::table('qr_codes')->where('id', $claim->qr_code_id)->update(['gift' => 'shangri_la_dinner']);
    DB::table('claims')->where('id', $claim->id)->update(['gift' => 'shangri_la_dinner']);

    $migration->up();

    expect($claim->fresh()->gift)->toBe(Gift::CinnamonLifeDinner)
        ->and($claim->qrCode->fresh()->gift)->toBe(Gift::CinnamonLifeDinner)
        ->and($spa->fresh()->gift)->toBe(Gift::SpaCeylon);

    $migration->down();

    expect(DB::table('claims')->where('id', $claim->id)->value('gift'))->toBe('shangri_la_dinner')
        ->and(DB::table('qr_codes')->where('id', $spa->id)->value('gift'))->toBe('spa_ceylon');
});
