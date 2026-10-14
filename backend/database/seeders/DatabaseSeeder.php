<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Faq;
use App\Models\Property;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Services\ImageStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Folders on the public disk that only seeded or uploaded CMS images live in.
     */
    private const IMAGE_DIRECTORIES = ['properties', 'testimonials', 'team', 'clients'];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        // Seeding twice would duplicate every section, so sample content only goes into empty tables.
        if ($this->contentExists()) {
            if (! app()->runningUnitTests()) {
                $this->command?->warn('Sample content already exists; skipped. Run `php artisan migrate:fresh --seed` to start over.');
            }

            return;
        }

        // The tables are empty, so any image files left in these folders belong to no row
        // (for example from an earlier migrate:fresh --seed). Clear them out.
        foreach (self::IMAGE_DIRECTORIES as $directory) {
            Storage::disk(ImageStorage::DISK)->deleteDirectory($directory);
        }

        $this->call([
            TestimonialSeeder::class,
            FaqSeeder::class,
            TeamMemberSeeder::class,
            ClientSeeder::class,
            PropertySeeder::class,
        ]);
    }

    private function contentExists(): bool
    {
        foreach ([Testimonial::class, Faq::class, TeamMember::class, Client::class, Property::class] as $model) {
            if ($model::query()->exists()) {
                return true;
            }
        }

        return false;
    }
}
