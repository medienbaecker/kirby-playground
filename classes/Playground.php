<?php

namespace Medienbaecker\Playground;

use Closure;
use Kirby\Filesystem\Dir;
use Kirby\Toolkit\Str;

class Playground
{
    /** @var array<string, true> */
    private static array $ids = [];

    /**
     * Renders one labelled case with an anchor and its HTML
     */
    public static function case(string $label, Closure $render, string|null $accessibility = null): void
    {
        ob_start();

        try {
            $result = $render();
        } finally {
            $html = trim(ob_get_clean() . (is_string($result ?? null) ? $result : ''));
        }

        snippet('playground-ui/case', [
            'id'            => static::id($label),
            'label'         => $label,
            'html'          => $html,
            'accessibility' => $accessibility,
        ]);
    }

    /**
     * Every playground snippet, root files first, then grouped by folder
     *
     * @return array<array{name: string, title: string, group: string, url: string}>
     */
    public static function components(): array
    {
        $root  = kirby()->root('snippets') . '/playground';
        $files = is_dir($root) ? Dir::index($root, true) : [];
        $files = array_filter($files, fn ($file) => pathinfo($file, PATHINFO_EXTENSION) === 'php');

        $components = array_map(function ($file) {
            $name   = substr($file, 0, -4);
            $folder = dirname($name);

            return [
                'name'  => $name,
                'title' => static::title(basename($name)),
                'group' => $folder === '.' ? '' : static::title($folder),
                'url'   => static::url($name),
            ];
        }, $files);

        usort($components, fn ($a, $b) => [$a['group'], $a['title']] <=> [$b['group'], $b['title']]);

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
        $n    = 1;

        while (isset(static::$ids[$id])) {
            $id = $base . '-' . ++$n;
        }

        static::$ids[$id] = true;

        return $id;
    }

    private static function title(string $name): string
    {
        return ucfirst(str_replace(['-', '/'], [' ', ' / '], $name));
    }
}
