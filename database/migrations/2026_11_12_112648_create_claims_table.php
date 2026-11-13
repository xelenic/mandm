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
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            // Unique: a QR code can be claimed exactly once, even under concurrent requests.
            $table->foreignId('qr_code_id')->unique()->constrained()->restrictOnDelete();
            $table->string('reference')->unique();
            $table->string('gift')->index();
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->string('nic');
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
