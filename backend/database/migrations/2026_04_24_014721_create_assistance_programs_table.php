<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistance_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->jsonb('criteria')->nullable();
            $table->jsonb('ai_config')->nullable();
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('quota_total')->nullable();
            $table->decimal('benefit_amount', 15, 2)->nullable();
            $table->string('banner_url')->nullable();
            $table->string('banner_public_id')->nullable();
            $table->enum('status', ['draft', 'active', 'closed', 'completed'])->default('draft');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistance_programs');
    }
};