<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->string('actor_type', 20);   // 'admin', 'citizen', 'system'
            $table->uuid('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 50)->nullable();
            
            $table->string('module', 50);       
            $table->string('action', 50);        
            $table->string('action_label', 100); 
            
            $table->string('target_type', 50)->nullable();
            $table->uuid('target_id')->nullable();
            $table->string('target_name')->nullable();
            
            $table->jsonb('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['actor_type', 'actor_id', 'created_at'], 'idx_actor');
            $table->index(['module', 'created_at'], 'idx_module');
            $table->index(['target_type', 'target_id'], 'idx_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};