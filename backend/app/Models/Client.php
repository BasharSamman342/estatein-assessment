<?php

namespace App\Models;

use App\Models\Concerns\HasStoredImages;
use App\Models\Concerns\Publishable;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * "Our Valued Clients": a company name and its logo.
 */
#[Fillable(['name_en', 'name_ar', 'sort_order', 'is_published'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, HasStoredImages, Publishable;

    public function imageColumns(): array
    {
        return ['logo_path'];
    }
}
