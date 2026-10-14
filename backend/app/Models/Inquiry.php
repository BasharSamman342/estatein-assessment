<?php

namespace App\Models;

use App\Enums\InquiryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message sent from the public contact or property inquiry form. Stored only; there is no admin screen.
 */
#[Fillable(['type', 'property_id', 'name', 'email', 'phone', 'message'])]
class Inquiry extends Model
{
    protected function casts(): array
    {
        return [
            'type' => InquiryType::class,
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
