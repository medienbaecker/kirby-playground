<?php

/**
 * @var string|null $title
 * @var \Closure $cases Renders the page's cases
 * @var array<string, string> $docs Files of the snippets to document, keyed by snippet name
 * @var array<string, array<array{title: string, url: string, current: bool}>> $sidebar Links, keyed by group heading
 * @var string|null $name Loads `assets/css/playground/<name>.css` and `assets/js/playground/<name>.js`
 */

use Medienbaecker\Playground\Docblock;
use Medienbaecker\Playground\Playground;

$case      = get('case');
$plugin    = kirby()->plugin('medienbaecker/playground');
$globalCss = asset('assets/css/playground/playground.css');
$ownCss    = $name ? asset("assets/css/playground/{$name}.css") : null;
$ownJs     = $name ? asset("assets/js/playground/{$name}.js") : null;

snippet('playground-ui/head');
?>
<?= css(array_filter([
	$plugin->asset($case !== null ? 'frame.css' : 'playground.css')->url(),
	$globalCss->exists() ? $globalCss->url() : null,
	$ownCss?->exists() ? $ownCss->url() : null,
])) ?>

<?php if ($case !== null): ?>
	<?php Playground::render($cases, $case) ?>
	<?= js(array_filter([
		$plugin->asset('frame.js')->url(),
		$ownJs?->exists() ? $ownJs->url() : null,
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
