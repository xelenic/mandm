<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            // Printed reference for the print run (e.g. "MM-0001"). Says nothing about the gift.
            $table->string('serial')->unique();
            // Secret, random code inside the QR. Unguessable so nobody can hunt for winners.
            $table->string('code')->unique();
            // Every stored code is a winning code. Packages without a gift all carry
            // one shared "try again" QR instead (see QrCode::tryAgainUrl()).
            $table->string('gift')->index();
            $table->unsignedInteger('scan_count')->default(0);
            $table->timestamp('first_scanned_at')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
