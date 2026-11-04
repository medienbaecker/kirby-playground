<?php

use Kirby\Cms\App as Kirby;

load([
	'Medienbaecker\Playground\Docblock'       => 'classes/Docblock.php',
	'Medienbaecker\Playground\Playground'     => 'classes/Playground.php',
	'Medienbaecker\Playground\VirtualFile'    => 'classes/VirtualFile.php',
	'Medienbaecker\Playground\TextGenerator'  => 'classes/TextGenerator.php',
	'Medienbaecker\Playground\ImageGenerator' => 'classes/ImageGenerator.php',
	'Medienbaecker\Playground\Placeholder'    => 'classes/Placeholder.php',
], __DIR__);

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
		'playground-ui/component' => __DIR__ . '/snippets/component.php',
		'playground-ui/case'      => __DIR__ . '/snippets/case.php',
		'playground-ui/not-found' => __DIR__ . '/snippets/not-found.php',
		'playground-ui/swatches'  => __DIR__ . '/snippets/swatches.php',
		'playground-ui/sizes'     => __DIR__ . '/snippets/sizes.php',
		'playground-ui/type'      => __DIR__ . '/snippets/type.php',
		'playground-ui/sidebar'   => __DIR__ . '/snippets/sidebar.php',
		'playground-ui/layout'    => __DIR__ . '/snippets/layout.php'
	],

	'routes' => require __DIR__ . '/routes.php'
]);
