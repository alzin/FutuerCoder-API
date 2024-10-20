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
        Schema::create('cources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('teacher');
            $table->text('description');
            $table->text('imagePath');
            $table->decimal('price', 8, 2); // أو float('price', 8, 2)
            $table->integer('min_age');
            $table->integer('max_age');
            $table->text('course_outline');
            $table->text('payment_url');
            $table->integer('duration_in_session'); 
            $table->date('course_start_date'); 
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cources');
    }
};
