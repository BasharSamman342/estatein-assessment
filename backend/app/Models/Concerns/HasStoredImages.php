<?php

namespace App\Models\Concerns;

use App\Services\ImageStorage;

/**
 * Removes a model's image files from disk once its row is deleted (after commit).
 */
trait HasStoredImages
{
    /**
     * Columns that hold paths on the public disk.
     *
     * @return array<int, string>
     */
    abstract public function imageColumns(): array;

    public static function bootHasStoredImages(): void
    {
        static::deleted(function (self $model) {
            app(ImageStorage::class)->deleteAfterCommit(
                array_map(fn (string $column) => $model->getRawOriginal($column), $model->imageColumns()),
            );
        });
    }
}
