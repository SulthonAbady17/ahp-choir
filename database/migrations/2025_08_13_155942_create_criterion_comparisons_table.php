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
        Schema::create('criterion_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_left_id')->constrained('criteria')->onDelete('cascade');
            $table->foreignId('criterion_right_id')->constrained('criteria')->onDelete('cascade');
            $table->float('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criterion_comparisons');
    }
};
