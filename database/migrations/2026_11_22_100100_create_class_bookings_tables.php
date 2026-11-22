<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('coach')->nullable();
            $table->unsignedTinyInteger('weekday'); // 0 = Sunday .. 6 = Saturday
            $table->string('start_time', 5); // HH:MM, gym-local
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedSmallInteger('capacity')->default(20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['gym_id', 'weekday', 'is_active']);
        });

        Schema::create('class_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gym_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->date('class_date');
            $table->string('status', 20)->default('booked'); // booked | cancelled
            $table->timestamps();
            $table->unique(['gym_class_id', 'member_id', 'class_date']);
            $table->index(['gym_id', 'class_date', 'status']);
        });

        // New permission must reach existing installs, not only fresh seeds.
        \App\Support\Permissions::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('class_bookings');
        Schema::dropIfExists('gym_classes');
    }
};
