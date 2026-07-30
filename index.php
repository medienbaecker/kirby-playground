<?php

use Kirby\Cms\App as Kirby;
use Kirby\Filesystem\Dir;

// Manual installation: load classes directly if Composer autoload isn't available
if (!class_exists('Medienbaecker\Playground\Placeholder')) {
    require __DIR__ . '/classes/VirtualFile.php';
    require __DIR__ . '/classes/TextGenerator.php';
    require __DIR__ . '/classes/ImageGenerator.php';
    require __DIR__ . '/classes/Placeholder.php';
}

Kirby::plugin('medienbaecker/playground', [
    'options' => [
        'auth' => false
    ],

    'siteMethods' => [
        'playgroundLinks' => function () {
            $files = Dir::files(kirby()->root('snippets') . '/playground');
            $files = array_filter($files, fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'php');

            return array_map(function ($file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                return [
                    'url'   => url("playground/{$name}"),
                    'title' => ucwords(str_replace('-', ' ', $name))
                ];
            }, array_values($files));
        }
    ],

    'templates' => [
        'playground' => __DIR__ . '/templates/playground.php'
    ],

    'snippets' => [
        'playground-ui/head'      => __DIR__ . '/snippets/head.php',
        'playground-ui/bottom'    => __DIR__ . '/snippets/bottom.php',
        'playground-ui/overview'  => __DIR__ . '/snippets/overview.php',
        'playground-ui/not-found' => __DIR__ . '/snippets/not-found.php'
    ],

    'routes' => require __DIR__ . '/routes.php'
]);
