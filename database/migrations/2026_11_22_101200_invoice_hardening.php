<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hardening: renewal generation is opt-in per gym; collections capture the
 * actual method/amount; cancellations keep an audit trail; payments link
 * back to their renewal invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->boolean('renewal_invoices_enabled')->default(false)->after('portal_link_days');
        });
        Schema::table('renewal_invoices', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 2)->nullable()->after('amount');
            $table->string('paid_method')->nullable()->after('paid_amount');
            $table->timestamp('cancelled_at')->nullable()->after('collected_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('renewal_invoice_id')->nullable()->after('subscription_id')->constrained('renewal_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('renewal_invoice_id');
        });
        Schema::table('renewal_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['paid_amount', 'paid_method', 'cancelled_at']);
        });
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn('renewal_invoices_enabled');
        });
    }
};
