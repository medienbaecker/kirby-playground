<?php

namespace Medienbaecker\Playground;

use Closure;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Template\Snippet;
use Kirby\Toolkit\Str;

class Playground
{
    /** @var array<string, true> */
    private static array $ids = [];

    /**
     * Renders one labelled case with an anchor and its HTML
     *
     * @param array<string, mixed> $attributes Added to the case's wrapper, e.g. a surface colour
     */
    public static function case(string $label, Closure $render, string|null $accessibility = null, array $attributes = []): void
    {
        ob_start();

        try {
            $render();
        } finally {
            $html = trim(ob_get_clean());
        }

        snippet('playground-ui/case', [
            'id'            => static::id($label),
            'label'         => $label,
            'html'          => $html,
            'accessibility' => $accessibility,
            'attributes'    => $attributes,
        ]);
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
            $pages["{$folder}/" . basename($component['name'])] ??= $component;

            foreach (static::shows($component['name']) as $name) {
                $pages[$name] = $component;
            }
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
     * Path of a snippet's file from the index root, or its name with "(not found)"
     */
    public static function path(string $name): string
    {
        $file = Snippet::file($name);

        if ($file === null) {
            return "{$name} (not found)";
        }

        return Str::after($file, kirby()->root('index') . '/') ?: $file;
    }

    /**
     * Templates and snippets that call a snippet, with the number of calls, playground pages left out
     *
     * @return array<string, int> Path from the index root and calls
     */
    public static function usage(string $name): array
    {
        $pattern = '/snippet\(\s*[\'"]' . preg_quote($name, '/') . '[\'"]/';
        $index   = kirby()->root('index') . '/';
        $usage   = [];

        foreach (['templates', 'snippets'] as $folder) {
            $root = kirby()->root($folder);

            foreach (Dir::index($root, true) as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'php' || ($folder === 'snippets' && str_starts_with($file, 'playground/'))) {
                    continue;
                }

                $calls = preg_match_all($pattern, F::read("{$root}/{$file}"));

                if ($calls > 0) {
                    $usage[Str::after("{$root}/{$file}", $index)] = $calls;
                }
            }
        }

        arsort($usage);

        return $usage;
    }

    /**
     * URL of a playground page in the current language
     */
    public static function url(string $name = ''): string
    {
        $base = kirby()->language()?->url() ?? kirby()->url();

        return rtrim("{$base}/playground/{$name}", '/');
    }

    private static function id(string $label): string
    {
        $base = Str::slug($label) ?: 'case';
        $id   = $base;
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
