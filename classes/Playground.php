<?php

namespace Medienbaecker\Playground;

use Closure;
use Kirby\Filesystem\Dir;
use Kirby\Template\Snippet;
use Kirby\Toolkit\Str;

class Playground
{
	/** @var array<string, true> */
	private static array $ids = [];

	private static string|null $only = null;

	private static string $frame = '';

	/**
	 * Renders one labelled case with an anchor and a resizable frame that shows it
	 * Inside the frame, only the rendered case
	 *
	 * @param array<string, mixed> $attributes Added to the box around the rendered case, e.g. a surface colour
	 */
	public static function case(string $label, Closure $render, array $attributes = []): void
	{
		$id = static::id($label);

		if (static::$only === null) {
			snippet('playground-ui/case', [
				'id'    => $id,
				'label' => $label,
			]);

			return;
		}

		if (static::$only === $id) {
			ob_start();
			$render();
			static::$frame = '<div ' . attr([...$attributes, 'class' => 'playground-ui-case__render']) . '>' . ob_get_clean() . '</div>';
		}
	}

	/**
	 * Renders one snippet as a case
	 *
	 * @param array<string, mixed> $props
	 * @param array<string, mixed> $attributes Added to the box around the rendered case, e.g. a surface colour
	 */
	public static function component(string $label, string $snippet, array $props = [], array $attributes = []): void
	{
		static::case($label, fn () => snippet($snippet, $props), $attributes);
	}

	/**
	 * A case with a swatch for each colour token: its colour, name and value
	 *
	 * @param array<string> $tokens Custom property names, e.g. `--color-accent`
	 * @param array<string, mixed> $attributes Added to the box around the rendered case, e.g. a surface colour
	 */
	public static function swatches(string $label, array $tokens, array $attributes = []): void
	{
		static::case($label, fn () => snippet('playground-ui/swatches', ['tokens' => $tokens]), $attributes);
	}

	/**
	 * A case with a table row for each length token: its name, value and a square of that size
	 *
	 * @param array<string> $tokens Custom property names, e.g. `--spacing-m`
	 */
	public static function sizes(string $label, array $tokens): void
	{
		static::case($label, fn () => snippet('playground-ui/sizes', ['tokens' => $tokens]));
	}

	/**
	 * A case with a line of text for each font size token, its name and value above it
	 *
	 * @param array<string> $tokens Custom property names, e.g. `--font-size-l`
	 */
	public static function type(string $label, array $tokens): void
	{
		static::case($label, fn () => snippet('playground-ui/type', ['tokens' => $tokens]));
	}

	/**
	 * Renders a playground page, or only the case with this id for its frame
	 */
	public static function render(string $component, string|null $case = null): void
	{
		if ($case === null) {
			snippet("playground/{$component}");
			return;
		}

		static::$only = $case;

		ob_start();
		snippet("playground/{$component}");
		ob_end_clean();

		echo static::$frame ?: '<div class="playground-ui-case__render">No case with the id <code>' . esc($case) . '</code>.</div>';
	}

	/**
	 * Every playground snippet of the site and of plugins: the `base` folder first, then root files, then the other folders
	 *
	 * @return array<array{name: string, title: string, group: string, url: string}>
	 */
	public static function components(): array
	{
		$root  = kirby()->root('snippets') . '/playground';
		$files = is_dir($root) ? Dir::index($root, true) : [];
		$names = [];

		foreach ($files as $file) {
			if (str_ends_with($file, '.php')) {
				$names[] = substr($file, 0, -4);
			}
		}

		foreach (array_keys(kirby()->extensions('snippets')) as $name) {
			if (str_starts_with($name, 'playground/')) {
				$names[] = Str::after($name, 'playground/');
			}
		}

		$components = array_map(function ($name) {
			$folder = dirname($name);

			return [
				'name'  => $name,
				'title' => static::title(basename($name)),
				'group' => $folder === '.' ? '' : static::title($folder),
				'url'   => (kirby()->language()?->url() ?? kirby()->url()) . "/playground/{$name}",
			];
		}, array_unique($names));

		usort($components, fn ($a, $b) => [$a['group'] !== 'Base', $a['group'], strtr($a['title'], ' ', '~')] <=> [$b['group'] !== 'Base', $b['group'], strtr($b['title'], ' ', '~')]);

		return $components;
	}

	/**
	 * Components keyed by group, `''` for those outside a folder
	 *
	 * @return array<string, array>
	 */
	public static function groups(): array
	{
		$groups = [];

		foreach (static::components() as $component) {
			$groups[$component['group']][] = $component;
		}

		return $groups;
	}

	/**
	 * Files of the snippets a playground page documents, keyed by snippet name:
	 * those named in its `@component` tags, or without tags `components/<page name>`
	 *
	 * @return array<string, string>
	 */
	public static function snippets(string $component): array
	{
		$names = Docblock::fromFile(Snippet::file("playground/{$component}"))->tag('component') ?: ['components/' . basename($component)];
		$files = [];

		foreach ($names as $name) {
			$file = Snippet::file($name);

			if ($file !== null) {
				$files[$name] = $file;
			}
		}

		return $files;
	}

	private static function id(string $label): string
	{
		$base   = Str::slug($label) ?: 'case';
		$id     = $base;
		$number = 1;

		while (isset(static::$ids[$id])) {
			$id = $base . '-' . ++$number;
		}

		static::$ids[$id] = true;

		return $id;
	}

	private static function title(string $name): string
	{
		return ucfirst(str_replace(['-', '/'], [' ', ' / '], $name));
	}
}
