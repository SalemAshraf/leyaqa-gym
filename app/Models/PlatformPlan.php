<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformPlan extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar', 'name_en', 'slug', 'interval', 'price_cents', 'currency', 'max_members', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];
}
