<?php

namespace Medienbaecker\Playground;

use Closure;
use ReflectionClass;
use Kirby\Filesystem\Dir;
use Kirby\Template\Snippet;
use Kirby\Toolkit\Str;

class Playground
{
    /** @var array<string, true> */
    private static array $ids = [];

    private static string|null $only = null;

    /** @var array<string, mixed>|null */
    private static array|null $props = null;

    private static string|null $isolated = null;

    /**
     * Renders one labelled case with an anchor and its HTML
     *
     * @param array<string, mixed> $attributes Added to the box around the rendered case, e.g. a surface colour
     */
    public static function case(string $label, Closure $render, string|null $accessibility = null, array $attributes = []): void
    {
        $id = static::id($label);

        if (static::$only !== null && static::$only !== $id) {
            return;
        }

        ob_start();

        try {
            $render();
        } finally {
            $html = trim(ob_get_clean());
        }

        static::output([
            'id'            => $id,
            'label'         => $label,
            'html'          => $html,
            'accessibility' => $accessibility,
            'attributes'    => $attributes,
            'php'           => null,
            'controls'      => [],
        ]);
    }

    /**
     * Renders one snippet as a case, with the PHP call that renders it and a form for its props
     *
     * @param array<string, mixed> $props
     * @param array<string, mixed> $attributes Added to the box around the rendered case, e.g. a surface colour
     */
    public static function component(string $label, string $snippet, array $props = [], string|null $accessibility = null, array $attributes = []): void
    {
        $id = static::id($label);

        if (static::$only !== null && static::$only !== $id) {
            return;
        }

        $controls = static::controls($snippet, $props);

        if (static::$props !== null) {
            $editable = array_flip(array_column($controls, 'name'));
            $passed   = array_intersect_key(static::$props, $editable);
            $props    = array_replace(array_diff_key($props, array_diff_key($editable, $passed)), $passed);
            $controls = static::controls($snippet, $props);
        }

        static::output([
            'id'            => $id,
            'label'         => $label,
            'html'          => trim(snippet($snippet, $props, true)),
            'accessibility' => $accessibility,
            'attributes'    => $attributes,
            'php'           => static::php($snippet, $props),
            'controls'      => $controls,
        ]);
    }

    /**
     * Renders a playground page and returns only the case with this id, its editable props replaced by `$props`
     * Placeholders are seeded with the id, so the case looks the same on every request
     *
     * @param array<string, mixed>|null $props
     */
    public static function isolate(string $component, string $id, array|null $props = null): string|null
    {
        static::$only  = $id;
        static::$props = $props;

        mt_srand(crc32("{$component}#{$id}"));

        ob_start();

        try {
            snippet("playground/{$component}");
        } finally {
            ob_end_clean();
            static::$only  = null;
            static::$props = null;
        }

        return static::$isolated;
    }

    /**
     * Every playground snippet of the site and of plugins, root files first, then grouped by folder
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

        usort($components, fn ($a, $b) => [$a['group'], $a['title']] <=> [$b['group'], $b['title']]);

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
     * A form field for each `@var` prop with a type it can edit: text, true or false, number or a list of values
     *
     * @param array<string, mixed> $props
     * @return array<array{name: string, input: string, value: mixed, options: array<string>}>
     */
    private static function controls(string $snippet, array $props): array
    {
        $file     = Snippet::file($snippet);
        $controls = [];

        foreach ($file === null ? [] : Docblock::fromFile($file)->props() as $prop) {
            $value    = $props[$prop['name']] ?? null;
            $types    = array_map('trim', explode('|', ltrim($prop['type'], '?')));
            $types    = array_values(array_diff($types, ['null']));
            $options  = array_map(fn ($type) => trim($type, "'\""), $types);

            $input = match (true) {
                is_object($value) || is_array($value)                            => null,
                $types === ['string']                                            => 'text',
                $types === ['bool']                                              => 'boolean',
                $types === ['int'], $types === ['float']                         => 'number',
                $types !== [] && preg_grep('/^([\'"]).*\1$/', $types) === $types => 'select',
                default                                                          => null,
            };

            if ($input !== null) {
                $controls[] = [
                    'name'    => $prop['name'],
                    'input'   => $input,
                    'value'   => $value,
                    'options' => $input === 'select' ? $options : [],
                ];
            }
        }

        return $controls;
    }

    /**
     * Echoes a rendered case, or keeps it when only one case is wanted
     *
     * @param array<string, mixed> $data
     */
    private static function output(array $data): void
    {
        $html = snippet('playground-ui/case', $data, true);

        if (static::$only !== null) {
            static::$isolated = $html;
        } else {
            echo $html;
        }
    }

    /**
     * The `snippet()` call that renders a component case
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
