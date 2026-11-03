<?php

namespace Medienbaecker\Playground;

use Closure;
use Kirby\Cms\App;
use ReflectionClass;
use Kirby\Filesystem\Dir;
use Kirby\Template\Snippet;
use Kirby\Toolkit\Str;

class Playground
{
    /** @var array<string, true> */
    private static array $ids = [];

    /** @var array<string>|null */
    private static array|null $calls = null;

    private static int $depth = 0;

    private static int|null $recording = null;

    private static string|null $only = null;

    /**
     * Renders one labelled case with an anchor, a resizable frame that shows it and the component snippets it calls
     * Inside the frame, only the rendered case
     *
     * @param array<string, mixed> $attributes Added to the box around the rendered case, e.g. a surface colour
     */
    public static function case(string $label, Closure $render, string|null $accessibility = null, array $attributes = []): void
    {
        $id = static::id($label);

        mt_srand(crc32($id));

        if (static::$only !== null) {
            if (static::$only === $id) {
                echo '<div ' . attr([...$attributes, 'class' => $attributes === [] ? 'playground-ui-case__render playground-ui-case__render--plain' : 'playground-ui-case__render']) . '>';
                $render();
                echo '</div>';
            }

            mt_srand(crc32("{$id}/next"));
            return;
        }

        static::$calls     = [];
        static::$recording = static::$depth;

        ob_start();

        try {
            $render();
        } finally {
            ob_end_clean();
            $calls = static::$calls;
            static::$calls     = null;
            static::$recording = null;
            mt_srand(crc32("{$id}/next"));
        }

        snippet('playground-ui/case', [
            'id'            => $id,
            'label'         => $label,
            'accessibility' => $accessibility,
            'php'           => implode("\n\n", $calls),
        ]);
    }

    /**
     * Renders one snippet as a case
     *
     * @param array<string, mixed> $props
     * @param array<string, mixed> $attributes Added to the box around the rendered case, e.g. a surface colour
     */
    public static function component(string $label, string $snippet, array $props = [], string|null $accessibility = null, array $attributes = []): void
    {
        static::case($label, fn () => snippet($snippet, $props), $accessibility, $attributes);
    }

    /**
     * Renders a playground page, or only the case with this id for its frame
     * Placeholders are seeded per case, so the frame shows what the page recorded
     */
    public static function render(string $component, string|null $case = null): void
    {
        static::$only = $case;

        mt_srand(crc32($component));

        try {
            snippet("playground/{$component}");
        } finally {
            static::$only = null;
        }
    }

    /**
     * Wraps the snippet component, so a case can show the component snippets it renders
     * Only snippets in the components folder count, and none that another snippet renders
     */
    public static function recordSnippets(): void
    {
        $kirby   = kirby();
        $snippet = $kirby->component('snippet');
        $folder  = $kirby->option('medienbaecker.playground.components');

        $kirby->extend(['components' => [
            'snippet' => function (App $kirby, string|array|null $name, array $data = [], bool $slots = false) use ($snippet, $folder) {
                if (static::$recording === static::$depth && is_string($name) && str_starts_with($name, "{$folder}/")) {
                    $props = array_filter(
                        $data,
                        fn ($value, $key) => array_key_exists($key, $kirby->data) === false || $kirby->data[$key] !== $value,
                        ARRAY_FILTER_USE_BOTH
                    );

                    static::$calls[] = static::php($name, $props);
                }

                static::$depth++;

                try {
                    return $snippet($kirby, $name, $data, $slots);
                } finally {
                    static::$depth--;
                }
            },
        ]]);
    }

    /**
     * Every playground snippet of the site and of plugins: the `base` folder first, then root files, then the other folders
     *
     * @return array<array{name: string, title: string, group: string, url: string}>
     */
    public static function components(): array
    {
        $root    = kirby()->root('snippets') . '/playground';
        $files   = is_dir($root) ? Dir::index($root, true) : [];
        $files   = array_filter($files, fn ($file) => pathinfo($file, PATHINFO_EXTENSION) === 'php');
        $names   = array_map(fn ($file) => substr($file, 0, -4), $files);
        $plugins = array_filter(array_keys(kirby()->extensions('snippets')), fn ($name) => str_starts_with($name, 'playground/'));
        $names   = array_unique([...$names, ...array_map(fn ($name) => Str::after($name, 'playground/'), $plugins)]);

        $components = array_map(function ($name) {
            $folder = dirname($name);

            return [
                'name'  => $name,
                'title' => static::title(basename($name)),
                'group' => $folder === '.' ? '' : static::title($folder),
                'url'   => static::url($name),
            ];
        }, $names);

        usort($components, fn ($a, $b) => [$a['group'] !== 'Base', $a['group'], $a['title']] <=> [$b['group'] !== 'Base', $b['group'], $b['title']]);

        return $components;
    }

    /**
     * Every snippet in the components folder, with the playground page that shows it and what its docblock has
     *
     * @return array<array{name: string, page: array|null, docblock: bool, status: string|null}>
     */
    public static function coverage(): array
    {
        $folder = kirby()->option('medienbaecker.playground.components');
        $root   = kirby()->root('snippets') . '/' . $folder;
        $files  = is_dir($root) ? Dir::index($root, true) : [];
        $pages  = [];

        foreach (static::components() as $component) {
            foreach (static::shows($component['name']) as $name) {
                $pages[$name] ??= $component;
            }
        }

        foreach (static::components() as $component) {
            $pages["{$folder}/" . basename($component['name'])] = $component;
        }

        $coverage = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $name     = $folder . '/' . substr($file, 0, -4);
            $docblock = Docblock::fromFile("{$root}/{$file}");

            $coverage[] = [
                'name'     => $name,
                'page'     => $pages[$name] ?? null,
                'docblock' => $docblock->description !== '' || $docblock->tags !== [],
                'status'   => $docblock->tag('status')[0] ?? null,
            ];
        }

        usort($coverage, fn ($a, $b) => $a['name'] <=> $b['name']);

        return $coverage;
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
     * Snippets a playground page names in its `@component` tags
     *
     * @return array<string>
     */
    public static function shows(string $component): array
    {
        $file = Snippet::file("playground/{$component}");

        return $file === null ? [] : array_map('trim', Docblock::fromFile($file)->tag('component'));
    }

    /**
     * URL of a playground page in the current language
     */
    public static function url(string $name = ''): string
    {
        $base = kirby()->language()?->url() ?? kirby()->url();

        return rtrim("{$base}/playground/{$name}", '/');
    }

    /**
     * The `snippet()` call of a component snippet
     *
     * @param array<string, mixed> $props
     */
    private static function php(string $snippet, array $props): string
    {
        return $props === [] ? "snippet('{$snippet}')" : "snippet('{$snippet}', " . static::export($props) . ')';
    }

    private static function export(mixed $value, int $depth = 0): string
    {
        if (is_object($value)) {
            return '$' . lcfirst((new ReflectionClass($value))->getShortName());
        }

        if (is_array($value) === false) {
            return $value === null ? 'null' : var_export($value, true);
        }

        if ($value === []) {
            return '[]';
        }

        $indent = str_repeat("\t", $depth + 1);
        $list   = array_is_list($value);
        $keys   = array_map(fn ($key) => var_export($key, true), array_keys($value));
        $width  = max(array_map('strlen', $keys));
        $lines  = array_map(
            fn ($key, $item) => $indent . ($list ? '' : str_pad($key, $width) . ' => ') . static::export($item, $depth + 1) . ',',
            $keys,
            $value
        );

        return "[\n" . implode("\n", $lines) . "\n" . str_repeat("\t", $depth) . ']';
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

    /**
     * Readable title of a snippet name, e.g. `Button cards`
     */
    public static function title(string $name): string
    {
        return ucfirst(str_replace(['-', '/'], [' ', ' / '], $name));
    }
}
