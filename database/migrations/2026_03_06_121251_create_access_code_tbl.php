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
        Schema::create('access_code_tbl', function (Blueprint $table) {
            $table->id();
            $table->string('access_code')->unique();
            $table->integer('status')->default(0);
            $table->timestamp('access_date')->nullable();
            $table->timestamp('access_expired')->nullable();
            $table->string('subscription_plan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_code_tbl');
    }
};
