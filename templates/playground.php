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
		$plugin->asset('frame.js')->url(),
		$componentJs->exists() ? $componentJs->url() : null,
	])) ?>
<?php else: ?>
	<main class="playground-ui">
		<?php if ($index !== false): ?>
			<?php snippet('playground-ui/sidebar', ['current' => $component]) ?>
		<?php endif ?>
		<div class="playground-ui__content">
			<?php if ($index !== false): ?>
				<div class="playground-ui-intro">
					<?php snippet('playground-ui/header', ['title' => $components[$index]['title']]) ?>
					<?php foreach (array_filter(Playground::shows($component), fn ($name) => Snippet::file($name) !== null) as $name): ?>
						<?php snippet('playground-ui/component', [
							'name'     => $name,
							'docblock' => Docblock::fromFile(Snippet::file($name)),
						]) ?>
					<?php endforeach ?>
				</div>
				<?php Playground::render($component) ?>
			<?php elseif ($component): ?>
				<?php snippet('playground-ui/not-found') ?>
			<?php else: ?>
				<?php snippet('playground-ui/overview') ?>
			<?php endif ?>
		</div>
	</main>

	<?= js($plugin->asset('playground.js')->url()) ?>
<?php endif ?>

<?php snippet('playground-ui/bottom') ?>
