<?php

/**
 * @var string|null $title
 * @var \Closure $cases Renders the page's cases
 * @var array<string, string> $docs Files of the snippets to document, keyed by snippet name
 * @var array<string, array<array{title: string, url: string, current: bool}>> $sidebar Links, keyed by group heading
 * @var string|null $component Loads `assets/css/playground/<component>.css` and `assets/js/playground/<component>.js`
 */

use Medienbaecker\Playground\Docblock;
use Medienbaecker\Playground\Playground;

$case      = get('case');
$plugin    = kirby()->plugin('medienbaecker/playground');
$versioned = function (string $path) {
	$asset = asset($path);

	return $asset->exists() ? $asset->url() . '?v=' . $asset->modified() : null;
};

snippet('playground-ui/head');
?>
<?= css(array_filter([
	$plugin->asset($case !== null ? 'frame.css' : 'playground.css')->url(),
	$versioned('assets/css/playground/playground.css'),
	$component ? $versioned("assets/css/playground/{$component}.css") : null,
])) ?>

<?php if ($case !== null): ?>
	<?php Playground::render($cases, $case) ?>
	<?= js(array_filter([
		$plugin->asset('frame.js')->url(),
		$component ? $versioned("assets/js/playground/{$component}.js") : null,
	])) ?>
<?php else: ?>
	<main class="playground-ui">
		<?php if ($sidebar !== []): ?>
			<?php snippet('playground-ui/sidebar', ['groups' => $sidebar]) ?>
		<?php endif ?>
		<div class="playground-ui__content">
			<?php if ($title !== null): ?>
				<div class="playground-ui-intro">
					<h1 class="playground-ui-intro__title"><?= esc($title) ?></h1>
					<?php foreach ($docs as $snippet => $file): ?>
						<?php snippet('playground-ui/component', [
							'name'     => count($docs) > 1 ? $snippet : null,
							'docblock' => Docblock::fromFile($file),
						]) ?>
					<?php endforeach ?>
				</div>
			<?php endif ?>
			<?php Playground::render($cases) ?>
		</div>
	</main>

	<?= js($plugin->asset('playground.js')->url()) ?>
<?php endif ?>

<?php snippet('playground-ui/bottom') ?>
