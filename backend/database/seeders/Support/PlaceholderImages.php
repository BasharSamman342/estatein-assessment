<?php

namespace Database\Seeders\Support;

use GdImage;
use RuntimeException;

/**
 * Draws the placeholder seed images in the Estatein dark palette with GD.
 *
 * The design's real photos were not exported, so these stand in for them. They are generated
 * once (php artisan estatein:fixtures) and the output is kept in database/seeders/fixtures/,
 * so seeding works offline. Swap in exported Figma images with the same file names to use those.
 */
final class PlaceholderImages
{
    private const BACKGROUND = [20, 20, 20];

    private const PANEL = [38, 38, 38];

    private const PURPLE = [112, 59, 247];

    private const LAVENDER = [166, 133, 250];

    private const WHITE = [255, 255, 255];

    private const MUTED = [153, 153, 153];

    /** Sky tints, one per photo, so the gallery images are easy to tell apart. */
    private const SKIES = [
        [70, 40, 160], [30, 60, 120], [110, 50, 90], [40, 90, 110], [90, 40, 140], [120, 70, 50],
        [50, 50, 130], [80, 30, 110], [30, 80, 90], [100, 60, 130], [60, 40, 100], [130, 60, 110],
    ];

    /**
     * @return array<int, string> the files written
     */
    public static function generate(string $directory, int $properties = 12, int $people = 7, int $logos = 7): array
    {
        mt_srand(2026);
        $written = [];

        for ($i = 1; $i <= $properties; $i++) {
            $written[] = self::save(self::propertyPhoto($i), "{$directory}/properties/property-".self::number($i).'.jpg');
        }

        for ($i = 1; $i <= $people; $i++) {
            $written[] = self::save(self::portrait($i, 400, 400), "{$directory}/avatars/avatar-".self::number($i).'.jpg');
            $written[] = self::save(self::portrait($i + 3, 600, 700), "{$directory}/team/member-".self::number($i).'.jpg');
            $written[] = self::save(self::logo($i), "{$directory}/clients/client-".self::number($i).'.png');
        }

        return $written;
    }

    private static function propertyPhoto(int $index): GdImage
    {
        [$width, $height] = [1200, 800];
        $image = imagecreatetruecolor($width, $height);
        $sky = self::SKIES[($index - 1) % count(self::SKIES)];

        self::verticalGradient($image, 0, (int) ($height * 0.72), $sky, self::BACKGROUND);
        self::verticalGradient($image, (int) ($height * 0.72), $height, self::PANEL, self::BACKGROUND);

        // Alternate a house, a tower and a low commercial block.
        match ($index % 3) {
            1 => self::house($image, $width, $height),
            2 => self::tower($image, $width, $height),
            default => self::block($image, $width, $height),
        };

        self::text($image, 'ESTATEIN', 48, 48, 4, self::WHITE);
        self::text($image, 'PLACEHOLDER PHOTO '.self::number($index), 48, $height - 72, 3, self::MUTED);

        return $image;
    }

    private static function house(GdImage $image, int $width, int $height): void
    {
        $ground = (int) ($height * 0.72);
        [$left, $right, $top] = [(int) ($width * 0.28), (int) ($width * 0.72), (int) ($height * 0.42)];

        imagefilledrectangle($image, $left, $top, $right, $ground, self::color($image, self::PANEL));
        imagefilledpolygon($image, [$left - 40, $top, ($left + $right) / 2, $top - 150, $right + 40, $top], self::color($image, self::PURPLE));

        foreach ([0.36, 0.58] as $x) {
            $windowLeft = (int) ($width * $x);
            imagefilledrectangle($image, $windowLeft, $top + 50, $windowLeft + 90, $top + 130, self::color($image, self::LAVENDER));
        }

        $doorLeft = (int) ($width * 0.475);
        imagefilledrectangle($image, $doorLeft, $ground - 150, $doorLeft + 60, $ground, self::color($image, self::BACKGROUND));
    }

    private static function tower(GdImage $image, int $width, int $height): void
    {
        $ground = (int) ($height * 0.72);
        [$left, $right, $top] = [(int) ($width * 0.38), (int) ($width * 0.62), (int) ($height * 0.12)];

        imagefilledrectangle($image, $left, $top, $right, $ground, self::color($image, self::PANEL));
        imagefilledrectangle($image, $left, $top, $right, $top + 16, self::color($image, self::PURPLE));

        for ($y = $top + 50; $y < $ground - 40; $y += 52) {
            for ($x = $left + 26; $x < $right - 40; $x += 56) {
                $lit = mt_rand(0, 3) > 0;
                imagefilledrectangle($image, $x, $y, $x + 30, $y + 28, self::color($image, $lit ? self::LAVENDER : self::BACKGROUND));
            }
        }
    }

    private static function block(GdImage $image, int $width, int $height): void
    {
        $ground = (int) ($height * 0.72);
        [$left, $right, $top] = [(int) ($width * 0.18), (int) ($width * 0.82), (int) ($height * 0.38)];

        imagefilledrectangle($image, $left, $top, $right, $ground, self::color($image, self::PANEL));
        imagefilledrectangle($image, $left, $top, $right, $top + 40, self::color($image, self::PURPLE));

        for ($x = $left + 30; $x < $right - 80; $x += 120) {
            imagefilledrectangle($image, $x, $top + 80, $x + 90, $ground - 20, self::color($image, self::LAVENDER));
        }
    }

    private static function portrait(int $index, int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        $tint = self::SKIES[($index - 1) % count(self::SKIES)];

        self::verticalGradient($image, 0, $height, $tint, self::BACKGROUND);

        $centerX = intdiv($width, 2);
        $headSize = (int) ($width * 0.36);
        imagefilledellipse($image, $centerX, (int) ($height * 0.42), $headSize, $headSize, self::color($image, self::LAVENDER));
        imagefilledellipse($image, $centerX, $height, (int) ($width * 0.8), (int) ($height * 0.62), self::color($image, self::PURPLE));

        return $image;
    }

    private static function logo(int $index): GdImage
    {
        [$width, $height] = [480, 200];
        $image = imagecreatetruecolor($width, $height);

        imagefilledrectangle($image, 0, 0, $width, $height, self::color($image, self::PANEL));
        imagefilledellipse($image, 100, 100, 110, 110, self::color($image, $index % 2 ? self::PURPLE : self::LAVENDER));
        imagefilledrectangle($image, 75, 75, 125, 125, self::color($image, self::PANEL));
        self::text($image, 'CLIENT '.self::number($index), 185, 78, 3, self::WHITE);

        return $image;
    }

    /**
     * @param  array{int, int, int}  $from
     * @param  array{int, int, int}  $to
     */
    private static function verticalGradient(GdImage $image, int $top, int $bottom, array $from, array $to): void
    {
        $span = max(1, $bottom - $top);
        $width = imagesx($image);

        for ($y = $top; $y < $bottom; $y++) {
            $ratio = ($y - $top) / $span;
            $color = array_map(fn (int $a, int $b) => (int) round($a + ($b - $a) * $ratio), $from, $to);
            imageline($image, 0, $y, $width, $y, self::color($image, $color));
        }
    }

    /**
     * GD's built-in fonts are tiny bitmaps, so the text is drawn small and scaled up.
     *
     * @param  array{int, int, int}  $rgb
     */
    private static function text(GdImage $image, string $text, int $x, int $y, int $scale, array $rgb): void
    {
        $font = 5;
        $small = imagecreate(imagefontwidth($font) * strlen($text), imagefontheight($font));
        $transparent = imagecolorallocate($small, 255, 0, 255);
        imagecolortransparent($small, $transparent);
        imagestring($small, $font, 0, 0, $text, imagecolorallocate($small, ...$rgb));

        imagecopyresized($image, $small, $x, $y, 0, 0, imagesx($small) * $scale, imagesy($small) * $scale, imagesx($small), imagesy($small));
    }

    /**
     * @param  array{int, int, int}  $rgb
     */
    private static function color(GdImage $image, array $rgb): int
    {
        return imagecolorallocate($image, ...$rgb);
    }

    private static function save(GdImage $image, string $path): string
    {
        if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true)) {
            throw new RuntimeException("Cannot create directory for [{$path}].");
        }

        str_ends_with($path, '.png') ? imagepng($image, $path, 9) : imagejpeg($image, $path, 82);

        return $path;
    }

    private static function number(int $index): string
    {
        return str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    }
}
