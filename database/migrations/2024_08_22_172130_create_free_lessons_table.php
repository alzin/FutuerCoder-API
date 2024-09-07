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
        Schema::create('free_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('userId')->constrained('guest_users')->onDelete('cascade');
            $table->foreignId('courseId')->constrained('cources')->onDelete('cascade');
            $table->foreignId('sessionTime')->constrained('cources_times')->onDelete('cascade');
            $table->text('meetUrl');
            $table->text('eventId');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('free_lessons');
    }
};
