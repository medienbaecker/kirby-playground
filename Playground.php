<?php

namespace Medienbaecker;

use Kirby\Cms\Files;
use Kirby\Filesystem\F;
use Kirby\Http\Remote;

class Playground
{
    private const LOREM = 'dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum';

    /**
     * Random lorem ipsum text (HTML)
     *
     * @param array $options {
     *   words: int|[min,max] - word count (default: 20)
     *   paragraphs: false|true|int|[min,max] - wrap in <p> (default: false)
     *   links: false|true|int|[min,max] - insert links (default: false)
     *   bold: false|true|int|[min,max] - insert bold text (default: false)
     *   italic: false|true|int|[min,max] - insert italic text (default: false)
     *   prefix: string - text before lorem ipsum (default: 'Lorem ipsum ')
     *   suffix: string - text after lorem ipsum (default: '.')
     * }
     */
    public static function text(array $options = []): string
    {
        $wordCount = max(1, self::resolveCount($options['words'] ?? 20, 20));
        $paragraphCount = self::resolveCount($options['paragraphs'] ?? false, 0);
        $prefix = $options['prefix'] ?? 'Lorem ipsum ';
        $suffix = $options['suffix'] ?? '.';
        if (!$prefix) $prefix = '';
        if (!$suffix) $suffix = '';

        // Generate and shuffle words
        $loremWords = explode(' ', self::LOREM);
        $text = [];
        for ($i = 0; $i < $wordCount; $i++) {
            $text[] = $loremWords[$i % count($loremWords)];
        }
        shuffle($text);

        // Insert random sentence breaks
        for ($i = rand(6, 12); $i < count($text) - 1; $i += rand(6, 12)) {
            $text[$i] .= '.';
            if (isset($text[$i + 1])) {
                $text[$i + 1] = ucfirst($text[$i + 1]);
            }
        }

        // Capitalize first word (unless prefix)
        $text[0] = $prefix ? lcfirst($text[0]) : ucfirst($text[0]);

        // Apply formatting
        self::wrapWords($text, $options['links'] ?? false, fn($w) => '<a href="#">' . $w . '</a>');
        self::wrapWords($text, $options['bold'] ?? false, fn($w) => '<strong>' . $w . '</strong>', true);
        self::wrapWords($text, $options['italic'] ?? false, fn($w) => '<em>' . $w . '</em>', true);

        $result = $prefix . implode(' ', $text) . $suffix;

        // Wrap in paragraphs
        if ($paragraphCount === 1) {
            return '<p>' . $result . '</p>';
        } elseif ($paragraphCount > 1) {
            $sentences = array_filter(preg_split('/(?<=\.)\s+/', $result));
            $perParagraph = (int) max(1, ceil(count($sentences) / $paragraphCount));
            $chunks = array_chunk($sentences, $perParagraph);
            return implode("\n", array_map(fn($p) => '<p>' . implode(' ', $p) . '</p>', $chunks));
        }

        return $result;
    }

    /**
     * Get source directory for cached images
     */
    public static function sourceDir(): string
    {
        return \kirby()->root('media') . '/plugins/kirby-playground/source';
    }

    /**
     * Virtual placeholder image as Kirby File object
     *
     * @param array|string $options Seed string or options array {
     *   id: int - specific picsum image ID
     *   seed: string - seed for consistent random image
     *   width: int - image width (default: 1200)
     *   height: int - image height (default: 800)
     * }
     */
    public static function image(array|string $options = []): PlaygroundFile
    {
        if (is_string($options)) {
            $options = ['seed' => $options];
        }

        $id = $options['id'] ?? null;
        $seed = $options['seed'] ?? null;
        $width = max(100, min(2000, $options['width'] ?? 1200));
        $height = max(100, min(2000, $options['height'] ?? 800));

        // Generate cache ID based on what makes this image unique
        if ($id !== null) {
            $cacheId = md5("id:{$id}:{$width}:{$height}");
        } elseif ($seed !== null) {
            $cacheId = md5("seed:{$seed}:{$width}:{$height}");
        } else {
            $cacheId = md5(uniqid('', true));
        }

        $path = self::sourceDir() . "/{$cacheId}.jpg";

        if (!file_exists($path)) {
            self::fetchImage($cacheId, $width, $height, $seed, $id);
        }

        return new PlaygroundFile([
            'filename' => "{$cacheId}.jpg",
            'parent'   => \site(),
            'root'     => $path,
            'url'      => \url("playground/image/{$cacheId}.jpg"),
            'content'  => [
                'alt' => 'Placeholder image',
                'focus' => '50% 50%'
            ]
        ]);
    }

    /**
     * Multiple placeholder images as Files collection
     */
    public static function images(int $count = 3, array $options = []): Files
    {
        $files = [];
        $baseSeed = $options['seed'] ?? null;

        for ($i = 0; $i < $count; $i++) {
            $opts = $options;
            unset($opts['id']); // id doesn't make sense for multiple images

            if ($baseSeed !== null) {
                $opts['seed'] = "{$baseSeed}-{$i}";
            }

            $files[] = self::image($opts);
        }

        return new Files($files);
    }

    /**
     * Download image from picsum.photos and cache it
     */
    private static function fetchImage(string $cacheId, int $width, int $height, ?string $seed = null, ?int $id = null): void
    {
        $dir = self::sourceDir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \Exception('Cannot create cache directory: ' . $dir);
        }

        // Build URL based on what's provided (id takes precedence)
        if ($id !== null) {
            $url = "https://picsum.photos/id/{$id}/{$width}/{$height}";
        } elseif ($seed !== null) {
            $url = "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
        } else {
            $url = "https://picsum.photos/{$width}/{$height}";
        }

        $response = Remote::get($url, ['timeout' => 10]);

        if ($response->code() !== 200) {
            throw new \Exception(
                "Failed to download from picsum.photos (HTTP {$response->code()}). " .
                    "Check your internet connection."
            );
        }

        $content = $response->content();
        if (empty($content)) {
            throw new \Exception('Empty response from picsum.photos');
        }

        F::write($dir . "/{$cacheId}.jpg", $content);
    }

    /**
     * Wrap random words with a tag
     */
    private static function wrapWords(array &$text, mixed $count, callable $wrapper, bool $skipExisting = false): void
    {
        $count = self::resolveCount($count, 0);
        if ($count <= 0) return;

        $count = min($count, count($text));
        $positions = (array) array_rand($text, $count);

        foreach ($positions as $pos) {
            if ($skipExisting && str_contains($text[$pos], '<')) continue;
            $word = rtrim($text[$pos], '.');
            $hadPeriod = str_ends_with($text[$pos], '.');
            $text[$pos] = $wrapper($word) . ($hadPeriod ? '.' : '');
        }
    }

    /**
     * Resolve count from various input types
     */
    private static function resolveCount(mixed $value, int $default): int
    {
        return match (true) {
            $value === false => 0,
            $value === true => 1,
            is_array($value) && count($value) === 2 => rand($value[0], $value[1]),
            is_int($value) => $value,
            default => $default
        };
    }
}
