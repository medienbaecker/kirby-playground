<?php

use Medienbaecker\Playground\Docblock;
use Medienbaecker\Playground\Playground;

$component    = $page->component()->value();
$components   = Playground::components();
$index        = array_search($component, array_column($components, 'name'), true);
$case         = get('case');
$plugin       = kirby()->plugin('medienbaecker/playground');
$globalCss    = asset('assets/css/playground/playground.css');
$componentCss = asset("assets/css/playground/{$component}.css");
$componentJs  = asset("assets/js/playground/{$component}.js");

snippet('playground-ui/head');
?>
<?= css(array_filter([
	$plugin->asset($index !== false && $case !== null ? 'frame.css' : 'playground.css')->url(),
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
		<?php if ($components !== []): ?>
			<?php snippet('playground-ui/sidebar', ['current' => $component]) ?>
		<?php endif ?>
		<div class="playground-ui__content">
			<?php if ($index !== false): ?>
				<div class="playground-ui-intro">
					<h1 class="playground-ui-intro__title"><?= esc($components[$index]['title']) ?></h1>
					<?php $snippets = Playground::snippets($component) ?>
					<?php foreach ($snippets as $name => $file): ?>
						<?php snippet('playground-ui/component', [
							'name'     => count($snippets) > 1 ? $name : null,
							'docblock' => Docblock::fromFile($file),
						]) ?>
					<?php endforeach ?>
				</div>
				<?php Playground::render($component) ?>
			<?php else: ?>
				<?php snippet('playground-ui/not-found') ?>
			<?php endif ?>
		</div>
	</main>

	<?= js($plugin->asset('playground.js')->url()) ?>
<?php endif ?>

<?php snippet('playground-ui/bottom') ?>
