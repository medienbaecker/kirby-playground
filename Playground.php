<?php

namespace Medienbaecker;

use Kirby\Cms\Files;

class Playground
{
    private const IMAGE_VARIANTS = ['landscape', 'portrait', 'square'];
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
     * Virtual placeholder image as Kirby File object
     */
    public static function image(string $variant = 'landscape', array $content = []): PlaygroundFile
    {
        $variant = in_array($variant, self::IMAGE_VARIANTS) ? $variant : 'landscape';

        return new PlaygroundFile([
            'filename' => "playground-{$variant}.jpg",
            'parent'   => \site(),
            'root'     => __DIR__ . "/assets/{$variant}.jpg",
            'url'      => \url("playground/{$variant}.jpg"),
            'content'  => array_merge([
                'alt' => 'Placeholder image',
                'focus' => '50% 50%'
            ], $content)
        ]);
    }

    /**
     * Multiple placeholder images as Files collection
     */
    public static function images(int|array $options = 3): Files
    {
        if (is_int($options)) {
            $options = ['count' => $options];
        }

        $count = $options['count'] ?? 3;
        $variants = $options['variants'] ?? self::IMAGE_VARIANTS;
        $content = $options['content'] ?? [];

        $files = [];
        for ($i = 0; $i < $count; $i++) {
            $files[] = self::image($variants[$i % count($variants)], $content);
        }

        return new Files($files);
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
