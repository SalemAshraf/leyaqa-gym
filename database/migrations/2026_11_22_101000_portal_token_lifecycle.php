<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Portal-link lifecycle: links expire portal_link_days after issue
 * (default 90, 0 = never; owner-configurable in Settings). Existing
 * tokens count as issued at their member's creation so old links do not
 * all die at once on deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('portal_token_issued_at')->nullable()->after('portal_token');
        });
        Schema::table('gyms', function (Blueprint $table) {
            $table->unsignedSmallInteger('portal_link_days')->default(90)->after('reminder_days_before');
        });

        DB::table('members')->whereNotNull('portal_token')->whereNull('portal_token_issued_at')
            ->update(['portal_token_issued_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('portal_token_issued_at');
        });
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn('portal_link_days');
        });
    }
};
