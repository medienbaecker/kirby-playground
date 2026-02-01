<?php

namespace Medienbaecker\Playground;

class TextGenerator
{
    private const LOREM = 'dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum';

    /**
     * Random lorem ipsum text (HTML)
     */
    public function generate(array $options = []): string
    {
        $wordCount = max(1, self::resolveCount($options['words'] ?? 20, 20));
        $paragraphCount = self::resolveCount($options['paragraphs'] ?? false, 0);
        $prefix = $options['prefix'] ?? 'Lorem ipsum ';
        $suffix = $options['suffix'] ?? '.';
        if (!$prefix) $prefix = '';
        if (!$suffix) $suffix = '';

        // Pick random words from pool
        $loremWords = explode(' ', self::LOREM);
        shuffle($loremWords);
        $text = array_slice($loremWords, 0, $wordCount);

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
