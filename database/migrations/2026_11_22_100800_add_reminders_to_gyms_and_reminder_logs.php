<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            // Off by default: each gym owner opts in. Commercial capability
            // ships configurable-but-off until the owner enables it.
            $table->boolean('reminders_enabled')->default(false)->after('ai_enabled');
            $table->unsignedTinyInteger('reminder_days_before')->default(3)->after('reminders_enabled');
        });

        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('kind'); // expiry | debt
            $table->date('reference_date'); // expiry: ends_at; debt: week start
            $table->string('channel');
            $table->text('body');
            $table->timestamps();

            $table->unique(['member_id', 'kind', 'reference_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn(['reminders_enabled', 'reminder_days_before']);
        });
    }
};
