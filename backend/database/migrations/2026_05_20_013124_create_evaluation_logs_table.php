<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('submission_id')
                ->constrained('assistance_submissions')
                ->cascadeOnDelete();
            
            $table->foreignUuid('new_submission_id')
                ->nullable()
                ->constrained('assistance_submissions')
                ->nullOnDelete();
            
            $table->foreignUuid('program_id')->constrained('assistance_programs');
            $table->foreignUuid('citizen_id')->constrained('citizens');
            $table->string('village_id', 10)->nullable();
            $table->string('district_id', 7)->nullable();
            $table->string('regency_id', 4)->nullable();
            
            $table->string('status', 20)->default('triggered');
            
            $table->jsonb('old_data')->nullable();
            
            $table->text('decision_notes')->nullable();
            
            $table->string('triggered_by', 50)->default('system');
            $table->timestamp('triggered_at')->useCurrent();
            
            $table->foreignUuid('decided_by')
                ->nullable()
                ->constrained('admins')
                ->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            
            $table->timestamps();
            
            $table->index('status');
            $table->index('submission_id');
            $table->index('citizen_id');
            $table->index('village_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_logs');
    }
};