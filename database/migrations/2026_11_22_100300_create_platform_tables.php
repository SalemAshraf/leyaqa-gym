<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->string('interval', 10); // monthly | yearly
            $table->unsignedInteger('price_cents')->nullable(); // null = contact sales
            $table->string('currency', 3)->default('EGP');
            $table->unsignedInteger('max_members')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('gyms', function (Blueprint $table) {
            $table->foreignId('platform_plan_id')->nullable()->constrained('platform_plans')->nullOnDelete();
            $table->string('platform_status', 20)->default('trialing'); // trialing | active | past_due | suspended
            $table->date('trial_ends_at')->nullable();
            $table->date('platform_renews_at')->nullable();
        });

        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_plan_id')->nullable()->constrained('platform_plans')->nullOnDelete();
            $table->unsignedInteger('amount_cents')->nullable();
            $table->string('currency', 3)->default('EGP');
            $table->date('period_starts_at');
            $table->date('period_ends_at');
            $table->string('status', 20)->default('pending'); // pending | paid | void
            $table->timestamp('paid_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Default commercial plans: names only, no invented prices (null = contact sales).
        DB::table('platform_plans')->insert([
            ['name_ar' => 'شهري', 'name_en' => 'Monthly', 'slug' => 'monthly', 'interval' => 'monthly', 'price_cents' => null, 'currency' => 'EGP', 'max_members' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name_ar' => 'سنوي', 'name_en' => 'Yearly', 'slug' => 'yearly', 'interval' => 'yearly', 'price_cents' => null, 'currency' => 'EGP', 'max_members' => null, 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Existing gyms keep working: grandfather them as active without a plan.
        DB::table('gyms')->whereNull('platform_status')->orWhere('platform_status', '')->update(['platform_status' => 'active']);
        DB::table('gyms')->where('platform_status', 'trialing')->whereNull('trial_ends_at')->update(['platform_status' => 'active']);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_invoices');
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('platform_plan_id');
            $table->dropColumn(['platform_status', 'trial_ends_at', 'platform_renews_at']);
        });
        Schema::dropIfExists('platform_plans');
    }
};
