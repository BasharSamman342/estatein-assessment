<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Builder;

/**
 * "Pricing Range" options of the property search. The middle band includes both ends.
 */
enum PriceRange: string
{
    case Under500k = 'lt500000';
    case From500kTo1m = '500000to1000000';
    case Over1m = 'gt1000000';

    public function apply(Builder $query): void
    {
        $column = $query->qualifyColumn('price');

        match ($this) {
            self::Under500k => $query->where($column, '<', 500_000),
            self::From500kTo1m => $query->whereBetween($column, [500_000, 1_000_000]),
            self::Over1m => $query->where($column, '>', 1_000_000),
        };
    }
}
