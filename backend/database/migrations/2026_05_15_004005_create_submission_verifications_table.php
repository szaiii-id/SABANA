<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('submission_id')
                  ->constrained('assistance_submissions')
                  ->cascadeOnDelete();

            $table->foreignUuid('admin_id')
                  ->nullable()
                  ->constrained('admins')
                  ->nullOnDelete();

            $table->string('admin_name')->nullable();

            $table->enum('action_type', [
                'approved',
                'rejected',
                'revision_requested',
                'unvalidated',
                'completed',
                'evaluation_triggered',
                'evaluation_approved',
                'evaluation_revoked',
            ]);

            $table->text('notes')->nullable();

            $table->jsonb('revision_items')->nullable();

            $table->timestamps();

            $table->index(['submission_id', 'action_type']);
            $table->index(['admin_id', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_verifications');
    }
};