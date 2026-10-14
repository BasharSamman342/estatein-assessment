<?php

namespace App\Enums;

/**
 * The two content languages. CMS columns are paired as <name>_en / <name>_ar.
 */
enum Locale: string
{
    case English = 'en';
    case Arabic = 'ar';

    public function column(string $name): string
    {
        return "{$name}_{$this->value}";
    }
}
