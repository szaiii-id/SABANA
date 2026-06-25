<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistance_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('submission_id')->constrained('assistance_submissions')->onDelete('cascade');
            $table->string('image_type');
            $table->string('image_url');
            $table->string('cloud_public_id');
            $table->jsonb('ai_result')->nullable()->after('cloud_public_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistance_evidences');
    }
};