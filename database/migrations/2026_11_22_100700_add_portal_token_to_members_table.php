<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Separate from qr_token: the QR card is shown at the door and must stay
            // money-free, while the portal link is shared privately with the member.
            $table->uuid('portal_token')->nullable()->unique()->after('qr_token');
        });

        foreach (DB::table('members')->whereNull('portal_token')->pluck('id') as $id) {
            DB::table('members')->where('id', $id)->update(['portal_token' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('portal_token');
        });
    }
};
