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
        Schema::create('assistance_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('citizen_id')->constrained('citizens')->onDelete('cascade');
            $table->foreignUuid('program_id')->constrained('assistance_programs')->onDelete('cascade');
            $table->string('registration_number')->unique()->index();
            
            $table->char('regency_id', 4)->index(); 
            $table->char('district_id', 7)->index();
            $table->char('village_id', 10)->index();

            $table->enum('status', ['draft', 'pending', 'validated', 'rejected', 'completed'])->default('pending');
            $table->jsonb('submission_data'); 

            $table->double('saw_score')->nullable()->index();

            $table->enum('disbursement_method', ['bpd_transfer', 'village_cash']);
            $table->string('bank_account_number')->nullable();
            
            $table->boolean('needs_data_update')->default(false);
            $table->timestamp('last_submission_date')->useCurrent();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance_submissions');
    }
};
