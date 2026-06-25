<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_assistance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('admin_name');         
            $table->string('admin_role');         
            
            $table->foreignUuid('citizen_id')->constrained('citizens')->cascadeOnDelete();
            
            $table->foreignUuid('submission_id')->constrained('assistance_submissions')->cascadeOnDelete();
            
            $table->foreignUuid('program_id')->constrained('assistance_programs')->cascadeOnDelete();
            
            $table->string('action', 50);        
            
            $table->jsonb('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index('admin_id');
            $table->index('citizen_id');
            $table->index('program_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_assistance_logs');
    }
};