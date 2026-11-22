<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone', 40)->nullable();
            $table->timestamps();
            $table->unique(['gym_id', 'name']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('gym_id')->constrained()->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('gym_id')->constrained()->nullOnDelete();
        });

        Schema::table('check_ins', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('member_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', fn (Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('members', fn (Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::dropIfExists('branches');
    }
};
