<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nip', 18)->unique()->comment('Employee Identification Number (18 digits)');
            $table->string('name');
            $table->string('password');
            
            $table->enum('role', [
                'super_admin',     
                'regency_admin',   
                'district_admin',  
                'village_officer'  
            ])->default('village_officer');
            
            $table->char('regency_id', 4)->nullable();
            $table->foreign('regency_id')
                  ->references('id')
                  ->on('regencies')
                  ->onDelete('set null');
                  
            $table->char('district_id', 7)->nullable();
            $table->foreign('district_id')
                  ->references('id')
                  ->on('districts')
                  ->onDelete('set null');
                  
            $table->char('village_id', 10)->nullable();
            $table->foreign('village_id')
                  ->references('id')
                  ->on('villages')
                  ->onDelete('set null');
            
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['role', 'regency_id']); 
            $table->index(['role', 'district_id']); 
            $table->index(['role', 'village_id']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};