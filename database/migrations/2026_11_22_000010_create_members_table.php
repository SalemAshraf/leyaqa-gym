<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 40);
            $table->string('email')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('birthdate')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->date('joined_at')->nullable();
            $table->uuid('qr_token')->unique();
            $table->timestamps();
            $table->index(['gym_id', 'status']);
            $table->index(['gym_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
