<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Portal links become hashed-at-rest secrets: the URL a member holds is the
 * only copy of the plaintext token; the database keeps its SHA-256 hash.
 * Existing plaintext tokens are hashed in place (those links keep working).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('members', function (Blueprint $table) {
                $table->string('portal_token', 64)->nullable()->change();
            });
        }

        DB::table('members')->whereNotNull('portal_token')->orderBy('id')->each(function ($row) {
            if (strlen($row->portal_token) !== 64) {
                DB::table('members')->where('id', $row->id)
                    ->update(['portal_token' => hash('sha256', $row->portal_token)]);
            }
        });
    }

    public function down(): void
    {
        // Hashed tokens cannot be reversed back to plaintext by design.
    }
};
