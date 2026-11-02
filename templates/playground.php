<?php

use Kirby\Template\Snippet;
use Medienbaecker\Playground\Docblock;
use Medienbaecker\Playground\Playground;

snippet('playground-ui/head');

$component    = $page->component()->value();
$plugin       = kirby()->plugin('medienbaecker/playground');
$globalCss    = asset('assets/css/playground/playground.css');
$componentCss = asset("assets/css/playground/{$component}.css");
$componentJs  = asset("assets/js/playground/{$component}.js");
$components   = Playground::components();
$index        = array_search($component, array_column($components, 'name'), true);
?>
<?= css(array_filter([
	$plugin->asset('playground.css')->url(),
	$globalCss->exists() ? $globalCss->url() : null,
	$componentCss->exists() ? $componentCss->url() : null,
])) ?>

<?php if ($index !== false): ?>
	<?php $names = Playground::shows($component) ?>
	<?php snippet('playground-ui/header', [
		'title'     => $components[$index]['title'],
		'previous'  => $components[$index - 1] ?? null,
		'next'      => $components[$index + 1] ?? null,
		'files'     => array_map(Playground::path(...), ["playground/{$component}", ...$names]),
	]) ?>
	<?php foreach (array_filter($names, fn ($name) => Snippet::file($name) !== null) as $name): ?>
		<?php snippet('playground-ui/component', [
			'name'     => $name,
			'docblock' => Docblock::fromFile(Snippet::file($name)),
		]) ?>
	<?php endforeach ?>
	<?php snippet("playground/{$component}") ?>
<?php elseif ($component): ?>
	<?php snippet('playground-ui/not-found') ?>
<?php else: ?>
	<?php snippet('playground-ui/overview') ?>
<?php endif ?>

<?= js(array_filter([
	$plugin->asset('playground.js')->url(),
	$componentJs->exists() ? $componentJs->url() : null,
])) ?>

<?php snippet('playground-ui/bottom') ?>
