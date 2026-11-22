<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20); // duration | sessions
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('sessions_count')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('freeze_days_allowed')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['gym_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
