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
        Schema::create('test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_generation_request_id')->constrained('test_generation_requests')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('preconditions')->nullable();
            $table->json('steps');
            $table->text('expected_result');
            $table->enum('priority', ['Low', 'Medium', 'High']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_cases');
    }
};
