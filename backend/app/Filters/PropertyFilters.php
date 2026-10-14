<?php

namespace App\Filters;

use App\Enums\Locale;
use App\Enums\PriceRange;
use App\Enums\PropertyType;
use App\Enums\SizeRange;
use App\Enums\YearRange;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies the public property search (already validated) to a query. Filters combine with AND.
 * "locale" picks which title and location columns q and location look at.
 */
class PropertyFilters
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        $locale = Locale::tryFrom($this->filters['locale'] ?? '') ?? Locale::English;

        if (filled($term = $this->filters['q'] ?? null)) {
            // The column collation (utf8mb4_unicode_ci) makes LIKE case-insensitive.
            $query->where($query->qualifyColumn($locale->column('title')), 'like', '%'.self::escapeLike($term).'%');
        }

        if (filled($location = $this->filters['location'] ?? null)) {
            // Exact and case-sensitive: the collation alone would treat "Dubai" and "dubai" as equal.
            $query->whereRaw($query->qualifyColumn($locale->column('location')).' = ? collate utf8mb4_bin', [$location]);
        }

        if ($type = PropertyType::tryFrom($this->filters['type'] ?? '')) {
            $query->where($query->qualifyColumn('property_type'), $type);
        }

        PriceRange::tryFrom($this->filters['price_range'] ?? '')?->apply($query);
        SizeRange::tryFrom($this->filters['size_range'] ?? '')?->apply($query);
        YearRange::tryFrom($this->filters['year_range'] ?? '')?->apply($query);

        return $query;
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
