<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizens', function (Blueprint $table) {
            $table->uuid('id')->primary(); 
            $table->string('nik', 16)->unique();
            $table->string('family_card_number', 16);
            $table->string('full_name');
            $table->string('whatsapp_number', 20);
            $table->boolean('is_verified')->default(false); 
            $table->string('pin');
            $table->string('temporary_pin', 255)->nullable(); 
            $table->timestamp('temporary_pin_expired_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index(['nik', 'whatsapp_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizens');
    }
};