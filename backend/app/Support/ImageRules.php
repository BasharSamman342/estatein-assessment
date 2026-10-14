<?php

namespace App\Support;

/**
 * Validation for every uploaded image. Both "mimes" and "mimetypes" inspect the
 * file's actual content (finfo), so a renamed text or SVG file is rejected.
 */
class ImageRules
{
    /**
     * @return array<int, string>
     */
    public static function upload(): array
    {
        return [
            'file',
            'mimes:'.implode(',', config('estatein.images.mimes')),
            'mimetypes:'.implode(',', config('estatein.images.mimetypes')),
            'max:'.config('estatein.images.max_kilobytes'),
        ];
    }
}
