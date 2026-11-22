<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->string('status', 20)->default('active'); // active | frozen | expired | cancelled
            $table->unsignedInteger('sessions_remaining')->nullable();
            $table->unsignedInteger('freeze_days_used')->default(0);
            $table->decimal('price', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['gym_id', 'status']);
            $table->index(['gym_id', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
