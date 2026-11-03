<?php

namespace Medienbaecker\Playground;

use Closure;

class TextGenerator
{
	private const LOREM = 'dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum';

	/**
	 * Random lorem ipsum text (HTML)
	 */
	public static function text(array $options = []): string
	{
		$prefix = ($options['prefix'] ?? 'Lorem ipsum ') ?: '';
		$suffix = ($options['suffix'] ?? '.') ?: '';

		$words = explode(' ', static::LOREM);
		shuffle($words);
		$words = array_slice($words, 0, max(1, static::amount($options['words'] ?? 20)));

		for ($index = rand(6, 12); $index < count($words) - 1; $index += rand(6, 12)) {
			$words[$index] .= '.';
			$words[$index + 1] = ucfirst($words[$index + 1]);
		}

		if ($prefix === '') {
			$words[0] = ucfirst($words[0]);
		}

		static::wrap($words, $options['links'] ?? false, fn ($word) => '<a href="#">' . $word . '</a>');
		static::wrap($words, $options['bold'] ?? false, fn ($word) => '<strong>' . $word . '</strong>');
		static::wrap($words, $options['italic'] ?? false, fn ($word) => '<em>' . $word . '</em>');

		$text       = $prefix . implode(' ', $words) . $suffix;
		$paragraphs = static::amount($options['paragraphs'] ?? false);

		if ($paragraphs === 1) {
			return '<p>' . $text . '</p>';
		}

		if ($paragraphs > 1) {
			$sentences = array_filter(preg_split('/(?<=\.)\s+/', $text));
			$chunks    = array_chunk($sentences, (int) ceil(count($sentences) / $paragraphs));

			return implode("\n", array_map(fn ($chunk) => '<p>' . implode(' ', $chunk) . '</p>', $chunks));
		}

		return $text;
	}

	/**
	 * Wraps random words that are not wrapped yet
	 */
	private static function wrap(array &$words, mixed $amount, Closure $wrapper): void
	{
		$amount = min(static::amount($amount), count($words));

		if ($amount <= 0) {
			return;
		}

		foreach ((array) array_rand($words, $amount) as $index) {
			if (str_contains($words[$index], '<')) {
				continue;
			}

			$period        = str_ends_with($words[$index], '.') ? '.' : '';
			$words[$index] = $wrapper(rtrim($words[$index], '.')) . $period;
		}
	}

	/**
	 * A number from `true` (1), `false` (0), an int or a `[min, max]` range
	 */
	private static function amount(mixed $value): int
	{
		return match (true) {
			$value === true  => 1,
			is_int($value)   => $value,
			is_array($value) => rand($value[0], $value[1]),
			default          => 0,
		};
	}
}
