<?php

namespace App\Models;

use App\Enums\PropertyType;
use App\Models\Concerns\Publishable;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'title_en', 'title_ar', 'description_en', 'description_ar', 'price', 'currency',
    'location_en', 'location_ar', 'bedrooms', 'bathrooms', 'area_sqft', 'year_built',
    'property_type', 'sort_order', 'is_published',
])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory, Publishable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'USD',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'area_sqft' => 'integer',
            'year_built' => 'integer',
            'property_type' => PropertyType::class,
        ];
    }

    /**
     * @return HasMany<PropertyImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The cover image. If more than one row is ever flagged, the lowest sort_order, then lowest id, wins.
     *
     * @return HasOne<PropertyImage, $this>
     */
    public function coverImage(): HasOne
    {
        return $this->hasOne(PropertyImage::class)->ofMany(
            ['sort_order' => 'min', 'id' => 'min'],
            fn ($query) => $query->where('is_cover', true),
        );
    }

    /**
     * @return HasMany<PropertyFeature, $this>
     */
    public function features(): HasMany
    {
        return $this->hasMany(PropertyFeature::class)->orderBy('sort_order')->orderBy('id');
    }
}
