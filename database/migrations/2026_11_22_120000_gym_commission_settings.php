<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reversible coach-commission proposal: configuration only - off and 0%
// by default, creates no financial obligation and pays nobody.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->boolean('commission_enabled')->default(false);
            $table->decimal('pt_commission_percent', 5, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn(['commission_enabled', 'pt_commission_percent']);
        });
    }
};
