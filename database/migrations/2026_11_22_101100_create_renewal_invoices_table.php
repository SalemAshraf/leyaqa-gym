<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Non-charging recurring billing: the scheduler writes a due renewal invoice
 * per subscription cycle; staff collect it manually (recording a normal
 * Payment) or cancel it. Nothing is ever auto-charged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renewal_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('due'); // due | collected | cancelled
            $table->date('due_date');
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subscription_id', 'due_date']);
            $table->index(['gym_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewal_invoices');
    }
};
