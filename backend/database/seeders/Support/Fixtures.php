<?php

namespace Database\Seeders\Support;

use Illuminate\Http\UploadedFile;

final class Fixtures
{
    /**
     * Wrap a committed fixture image as an upload, so seeding stores it exactly like
     * an admin upload (random name on the public disk). The fixture file itself is not moved.
     */
    public static function upload(string $relativePath): UploadedFile
    {
        $path = database_path("seeders/fixtures/{$relativePath}");

        return new UploadedFile($path, basename($path), null, null, true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function data(string $name): array
    {
        return require database_path("seeders/data/{$name}.php");
    }
}
