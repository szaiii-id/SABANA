<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistance_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('citizen_id')->constrained('citizens')->onDelete('cascade');
            $table->foreignUuid('program_id')->constrained('assistance_programs')->onDelete('cascade');
            $table->string('registration_number')->unique();

            $table->char('regency_id', 4);
            $table->char('district_id', 7);
            $table->char('village_id', 10);

            $table->enum('status', [
                'pending',
                'validated',
                'rejected',
                'completed',
                'needs_revision',
                'evaluation_pending',
                'revoked',
            ])->default('pending');

            $table->jsonb('submission_data');
            $table->double('smart_score')->nullable();
            $table->enum('disbursement_method', ['bpd_transfer', 'village_cash']);
            $table->string('bank_account_number')->nullable();
            $table->boolean('needs_data_update')->default(false);
            $table->timestamp('last_submission_date')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['citizen_id', 'program_id', 'status'], 'idx_citizen_program_status');
            $table->index(['program_id', 'status'], 'idx_program_status');
            $table->index(['village_id', 'status'], 'idx_village_status');
            $table->index(['district_id', 'status'], 'idx_district_status');
            $table->index(['regency_id', 'status'], 'idx_regency_status');
            $table->index(['smart_score', 'created_at'], 'idx_smart_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistance_submissions');
    }
};