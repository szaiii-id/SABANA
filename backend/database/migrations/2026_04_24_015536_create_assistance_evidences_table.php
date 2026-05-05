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
        Schema::create('assistance_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('submission_id')->constrained('assistance_submissions')->onDelete('cascade');
            $table->string('image_type'); // Menjadi String (bisa diisi 'rapor', 'foto_usaha', dll)
            $table->string('image_url');
            $table->string('cloud_public_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance_evidences');
    }
};
