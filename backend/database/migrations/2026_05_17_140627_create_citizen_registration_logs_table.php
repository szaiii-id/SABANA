<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_registration_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('citizen_id')->constrained('citizens')->cascadeOnDelete();
            $table->foreignUuid('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('admin_name')->nullable();
            $table->string('admin_role')->nullable();
            $table->enum('action', [
                'register_with_pin',
                'register_without_pin',
                'resend_pin',
                'update_data',
            ]);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['citizen_id', 'action']);
            $table->index(['admin_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_registration_logs');
    }
};