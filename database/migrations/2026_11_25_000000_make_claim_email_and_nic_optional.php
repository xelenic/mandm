<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The lucky draw design's contact form asks only for first name, last name
 * and mobile number, so email and NIC are no longer required. Existing
 * values are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('nic')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('nic')->nullable(false)->change();
        });
    }
};
