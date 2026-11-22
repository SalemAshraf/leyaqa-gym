<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal access becomes a two-step model: the staff-shared link carries a
 * single-use bootstrap token which is exchanged once for a bounded session
 * (web cookie or API bearer, 30 days, revoked on rotation). Raw reusable
 * secrets no longer sit in URLs or host access logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_portal_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('label')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_portal_sessions');
    }
};
