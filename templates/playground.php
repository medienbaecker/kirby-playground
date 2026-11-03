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
$case         = get('case');
?>
<?= css(array_filter([
	$plugin->asset('playground.css')->url(),
	$globalCss->exists() ? $globalCss->url() : null,
	$componentCss->exists() ? $componentCss->url() : null,
])) ?>

<?php if ($index !== false && $case !== null): ?>
	<?php Playground::render($component, $case) ?>
	<?= js(array_filter([
		$plugin->asset('axe.min.js')->url(),
		$componentJs->exists() ? $componentJs->url() : null,
	])) ?>
<?php else: ?>
	<?php if ($index !== false): ?>
		<?php Playground::recordSnippets() ?>
		<?php snippet('playground-ui/header', [
			'title'    => $components[$index]['title'],
			'previous' => $components[$index - 1] ?? null,
			'next'     => $components[$index + 1] ?? null,
		]) ?>
		<?php foreach (array_filter(Playground::shows($component), fn ($name) => Snippet::file($name) !== null) as $name): ?>
			<?php snippet('playground-ui/component', [
				'name'     => $name,
				'docblock' => Docblock::fromFile(Snippet::file($name)),
			]) ?>
		<?php endforeach ?>
		<?php Playground::render($component) ?>
	<?php elseif ($component): ?>
		<?php snippet('playground-ui/not-found') ?>
	<?php else: ?>
		<?php snippet('playground-ui/overview') ?>
	<?php endif ?>

	<script type="importmap"><?= json_encode(['imports' => [
		dirname($plugin->asset('microlighter/microlighter.min.js')->url()) . '/grammars/php.js' => $plugin->asset('microlighter/grammars/php.js')->url(),
	]], JSON_UNESCAPED_SLASHES) ?></script>
	<?= js($plugin->asset('microlighter/microlighter.min.js')->url(), ['type' => 'module']) ?>
	<?= js($plugin->asset('playground.js')->url()) ?>
<?php endif ?>

<?php snippet('playground-ui/bottom') ?>
