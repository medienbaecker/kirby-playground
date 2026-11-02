<?php

use Kirby\Cms\App as Kirby;

// Manual installation: load classes directly if Composer autoload isn't available
if (!class_exists('Medienbaecker\Playground\Placeholder')) {
    require __DIR__ . '/classes/Docblock.php';
    require __DIR__ . '/classes/Playground.php';
    require __DIR__ . '/classes/VirtualFile.php';
    require __DIR__ . '/classes/TextGenerator.php';
    require __DIR__ . '/classes/ImageGenerator.php';
    require __DIR__ . '/classes/Placeholder.php';
}

Kirby::plugin('medienbaecker/playground', [
    'options' => [
        'auth' => false
    ],

    'templates' => [
        'playground' => __DIR__ . '/templates/playground.php'
    ],

    'snippets' => [
        'playground-ui/head'      => __DIR__ . '/snippets/head.php',
        'playground-ui/bottom'    => __DIR__ . '/snippets/bottom.php',
        'playground-ui/overview'  => __DIR__ . '/snippets/overview.php',
        'playground-ui/header'    => __DIR__ . '/snippets/header.php',
        'playground-ui/component' => __DIR__ . '/snippets/component.php',
        'playground-ui/case'      => __DIR__ . '/snippets/case.php',
        'playground-ui/not-found' => __DIR__ . '/snippets/not-found.php'
    ],

    'routes' => require __DIR__ . '/routes.php'
]);
