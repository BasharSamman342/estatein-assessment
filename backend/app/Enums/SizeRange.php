<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Builder;

/**
 * "Property Size" options of the property search, compared to area_sqft. The middle band includes both ends.
 */
enum SizeRange: string
{
    case Under1500 = 'lt1500';
    case From1500To3000 = '1500to3000';
    case Over3000 = 'gt3000';

    public function apply(Builder $query): void
    {
        $column = $query->qualifyColumn('area_sqft');

        match ($this) {
            self::Under1500 => $query->where($column, '<', 1500),
            self::From1500To3000 => $query->whereBetween($column, [1500, 3000]),
            self::Over3000 => $query->where($column, '>', 3000),
        };
    }
}
