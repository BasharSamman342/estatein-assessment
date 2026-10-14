<?php

namespace App\Models;

use App\Models\Concerns\HasStoredImages;
use Database\Factories\PropertyImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['alt_en', 'alt_ar', 'is_cover', 'sort_order'])]
class PropertyImage extends Model
{
    /** @use HasFactory<PropertyImageFactory> */
    use HasFactory, HasStoredImages;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_cover' => false,
        'sort_order' => 0,
    ];

    public function imageColumns(): array
    {
        return ['path'];
    }

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
