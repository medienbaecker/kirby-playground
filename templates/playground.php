<?php

use Kirby\Template\Snippet;
use Medienbaecker\Playground\Docblock;
use Medienbaecker\Playground\Playground;

$component    = $page->component()->value();
$components   = Playground::components();

snippet('playground-ui/head');

$plugin       = kirby()->plugin('medienbaecker/playground');
$globalCss    = asset('assets/css/playground/playground.css');
$componentCss = asset("assets/css/playground/{$component}.css");
$componentJs  = asset("assets/js/playground/{$component}.js");
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
		<?php if ($components !== []): ?>
			<script>document.documentElement.toggleAttribute("data-playground-sidebar-hidden", localStorage.getItem("playground-sidebar") === "hidden")</script>
			<?php snippet('playground-ui/sidebar', ['current' => $component]) ?>
		<?php endif ?>
		<div class="playground-ui__content">
			<?php if ($index !== false): ?>
				<div class="playground-ui-intro">
					<?php snippet('playground-ui/header', ['title' => $components[$index]['title']]) ?>
					<?php $names = array_filter(Playground::shows($component), fn ($name) => Snippet::file($name) !== null) ?>
					<?php foreach ($names as $name): ?>
						<?php snippet('playground-ui/component', [
							'name'     => count($names) > 1 ? $name : null,
							'docblock' => Docblock::fromFile(Snippet::file($name)),
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
