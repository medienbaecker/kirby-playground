<?php

use Kirby\Template\Snippet;
use Kirby\Toolkit\Str;
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
$relative     = function (string $name): string {
	$file = Snippet::file($name);

	if ($file === null) {
		return "{$name} (not found)";
	}

	return str_starts_with($file, kirby()->root('index') . '/') ? Str::after($file, kirby()->root('index') . '/') : $file;
};
?>
<?= css(array_filter([
	$plugin->asset('playground.css')->url(),
	$globalCss->exists() ? $globalCss->url() : null,
	$componentCss->exists() ? $componentCss->url() : null,
])) ?>

<?php if ($index !== false): ?>
	<?php $names = array_map(fn ($value) => strtok($value, " \t"), Docblock::fromFile(Snippet::file("playground/{$component}"))->tag('component')) ?>
	<?php snippet('playground-ui/header', [
		'title'     => $components[$index]['title'],
		'previous'  => $components[$index - 1] ?? null,
		'next'      => $components[$index + 1] ?? null,
		'files'     => array_map($relative, ["playground/{$component}", ...$names]),
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
