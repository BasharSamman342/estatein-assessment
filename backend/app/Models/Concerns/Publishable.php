<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared behaviour of the CMS tables: an is_published flag and a manual sort_order.
 */
trait Publishable
{
    public function initializePublishable(): void
    {
        $this->mergeCasts([
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // Mirror the column defaults so a freshly created model can be serialized without a refresh.
        $this->attributes += ['is_published' => false, 'sort_order' => 0];
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_published'), true);
    }

    /**
     * The order every list uses, public and admin: sort_order, then id.
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy($this->qualifyColumn('sort_order'))->orderBy($this->qualifyColumn('id'));
    }
}
