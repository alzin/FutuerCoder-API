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
        Schema::create('cources_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courseId')->constrained('cources')->onDelete('cascade');
            $table->date('SessionTimings')->nullable();
            $table->integer('studentsCount')->default(0);
            $table->time('startTime');
            $table->time('endTime');
            $table->timestamps();
            // Create a composite unique index to prevent duplicate startTime for the same date
            $table->unique(['courseId', 'SessionTimings', 'startTime']);

        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cources_times');
    }
};
