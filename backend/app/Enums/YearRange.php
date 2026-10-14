<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Builder;

/**
 * "Build Year" options of the property search, compared to year_built. The middle band includes both ends.
 */
enum YearRange: string
{
    case Before2000 = 'pre2000';
    case From2000To2015 = '2000to2015';
    case After2015 = 'post2015';

    public function apply(Builder $query): void
    {
        $column = $query->qualifyColumn('year_built');

        match ($this) {
            self::Before2000 => $query->where($column, '<', 2000),
            self::From2000To2015 => $query->whereBetween($column, [2000, 2015]),
            self::After2015 => $query->where($column, '>', 2015),
        };
    }
}
