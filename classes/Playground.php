<?php

namespace Medienbaecker\Playground;

use Closure;
use Kirby\Filesystem\Dir;
use Kirby\Template\Snippet;
use Kirby\Toolkit\Str;
use Throwable;

class Playground
{
	/** @var array<string, true> */
	private static array $ids = [];

	private static string|null $component = null;

	/**
	 * Renders one labelled case with an anchor and a resizable frame that shows it
	 * An error in the case shows in its frame instead of breaking the page
	 *
	 * @param array<string, mixed> $attributes Added to the box around the rendered case, e.g. a surface colour
	 */
	public static function case(string $label, Closure $render, array $attributes = []): void
	{
		$level = ob_get_level();
		ob_start();

		try {
			$render();
			$output = ob_get_clean();
		} catch (Throwable $error) {
			while (ob_get_level() > $level) {
				ob_end_clean();
			}

			$output = '<p class="playground-ui-case__error">' . esc($error->getMessage()) . '<br>' . esc($error->getFile()) . ':' . $error->getLine() . '</p>';
		}

		snippet('playground-ui/case', [
			'id'       => static::id($label),
			'label'    => $label,
			'document' => snippet('playground-ui/frame', [
				'render'    => '<div ' . attr([...$attributes, 'class' => 'playground-ui-case__render']) . '>' . $output . '</div>',
				'component' => static::$component,
			], true),
		]);
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
	 * A whole page in the playground's interface, from any template: the cases from `$cases`, and a sidebar
	 * with the pages in `$sidebar`, keyed by group heading
	 *
	 * @param array<string, \Kirby\Cms\Pages> $sidebar
	 */
	public static function page(Closure $cases, array $sidebar = []): void
	{
		$links = [];

		foreach ($sidebar as $heading => $pages) {
			foreach ($pages as $link) {
				$links[$heading][] = [
					'title'   => $link->title()->value(),
					'url'     => $link->url(),
					'current' => $link->is(page()),
				];
			}
		}

		snippet('playground-ui/layout', [
			'title'     => page()->title()->value(),
			'cases'     => $cases,
			'docs'      => [],
			'sidebar'   => $links,
			'component' => null,
		]);
	}

	/**
	 * Renders the cases, each in a frame that loads the component's assets
	 */
	public static function render(Closure $cases, string|null $component = null): void
	{
		static::$component = $component;
		$cases();
	}

	/**
	 * The URL of a site asset with its modified time, so browsers fetch new versions, or `null` if it doesn't exist
	 */
	public static function versioned(string $path): string|null
	{
		$asset = asset($path);

		return $asset->exists() ? $asset->url() . '?v=' . $asset->modified() : null;
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
	 * Sidebar links to every playground page, keyed by group, `Components` for those outside a folder
	 *
	 * @return array<string, array<array{title: string, url: string, current: bool}>>
	 */
	public static function groups(string|null $current = null): array
	{
		$groups = [];

		foreach (static::components() as $component) {
			$groups[$component['group'] ?: 'Components'][] = [
				'title'   => $component['title'],
				'url'     => $component['url'],
				'current' => $component['name'] === $current,
			];
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
