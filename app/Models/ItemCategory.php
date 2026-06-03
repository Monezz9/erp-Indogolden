<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ItemCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (ItemCategory $category): void {
            if (filled($category->slug) || blank($category->name)) {
                return;
            }

            $baseSlug = Str::slug($category->name);
            $slug = $baseSlug;
            $suffix = 2;

            while (self::query()
                ->where('slug', $slug)
                ->when($category->exists, fn ($query) => $query->whereKeyNot($category->getKey()))
                ->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $category->slug = $slug;
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
