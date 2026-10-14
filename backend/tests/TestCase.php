<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * The database the app uses for development. Tests must never run against it,
     * because RefreshDatabase drops every table.
     */
    private const DEVELOPMENT_DATABASE = 'estatein';

    /**
     * Runs before the testing traits, so the check happens before RefreshDatabase migrates anything.
     * It also catches a cached config (php artisan config:cache) that ignores phpunit.xml.
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($database === self::DEVELOPMENT_DATABASE) {
            throw new RuntimeException(
                "Refusing to run tests against the development database [{$database}]. ".
                'Run `php artisan config:clear` and check DB_DATABASE in phpunit.xml.'
            );
        }

        return parent::setUpTraits();
    }

    protected function actingAsAdmin(): User
    {
        return Sanctum::actingAs(User::factory()->create());
    }

    /**
     * An upload whose bytes are $content, whatever its name and client MIME type claim.
     *
     * UploadedFile::fake() reports a MIME type derived from the file name, so it cannot
     * prove that validation inspects the real content. This one is detected by finfo.
     */
    protected function uploadWithContent(string $name, string $content, string $clientMimeType): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $clientMimeType, null, true);
    }
}
