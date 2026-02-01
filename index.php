<?php

use Kirby\Cms\App as Kirby;
use Kirby\Filesystem\Dir;

// Manual installation: load classes directly if Composer autoload isn't available
if (!class_exists('Medienbaecker\Playground\Playground')) {
    require __DIR__ . '/classes/VirtualFile.php';
    require __DIR__ . '/classes/TextGenerator.php';
    require __DIR__ . '/classes/ImageGenerator.php';
    require __DIR__ . '/classes/Playground.php';
}

Kirby::plugin('medienbaecker/playground', [
    'options' => [
        'auth' => false
    ],

    'siteMethods' => [
        'playgroundLinks' => function () {
            $files = Dir::files(kirby()->root('snippets') . '/playground');
            return array_map(function ($file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                return [
                    'url'   => url("playground/{$name}"),
                    'title' => ucwords(str_replace('-', ' ', $name))
                ];
            }, $files);
        }
    ],

    'templates' => [
        'playground' => __DIR__ . '/templates/playground.php'
    ],

    'snippets' => [
        'playground/section'   => __DIR__ . '/snippets/section.php',
        'playground/label'     => __DIR__ . '/snippets/label.php',
        'playground/overview'  => __DIR__ . '/snippets/overview.php',
        'playground/not-found' => __DIR__ . '/snippets/not-found.php'
    ],

    'routes' => require __DIR__ . '/routes.php'
]);

// Backwards compatibility: old namespace → new namespace
class_alias(\Medienbaecker\Playground\Playground::class, 'Medienbaecker\Playground');
class_alias(\Medienbaecker\Playground\VirtualFile::class, 'Medienbaecker\PlaygroundFile');
