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
        Schema::create('citizens', function (Blueprint $table) {
            $table->id();
            $table->string('nik',16)->unique();
            $table->string('family_card_number',16);
            $table->string('full_name');
            $table->string('whatsapp_number',20);
            $table->string('pin');
            $table->string('temporary_pin',6)->nullable();
            $table->timestamp('temporary_pin_expired_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index('nik', 'whatsapp_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citizens');
    }
};
