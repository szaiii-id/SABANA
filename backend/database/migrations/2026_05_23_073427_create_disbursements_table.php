<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disbursements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('submission_id')->constrained('assistance_submissions')->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained('assistance_programs');
            $table->foreignUuid('citizen_id')->constrained('citizens');
            $table->decimal('amount', 15, 2);
            $table->date('disbursed_at');
            $table->string('method', 20);
            $table->string('reference_number', 50)->unique();
            $table->foreignUuid('disbursed_by')->constrained('admins');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('submission_id');
            $table->index('reference_number');
            $table->index('disbursed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursements');
    }
};