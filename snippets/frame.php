<?php

/**
 * @var string $render The rendered case
 * @var string|null $component Loads `assets/css/playground/<component>.css` and `assets/js/playground/<component>.js`
 */

use Medienbaecker\Playground\Playground;

$plugin = kirby()->plugin('medienbaecker/playground');

snippet('playground-ui/head');
?>
<?= css(array_filter([
	$plugin->asset('frame.css')->url(),
	Playground::versioned('assets/css/playground/playground.css'),
	$component ? Playground::versioned("assets/css/playground/{$component}.css") : null,
])) ?>

<?= $render ?>

<?= js(array_filter([
	$plugin->asset('frame.js')->url(),
	$component ? Playground::versioned("assets/js/playground/{$component}.js") : null,
])) ?>

<?php snippet('playground-ui/bottom') ?>
