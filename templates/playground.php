<?php snippet('head') ?>

<?php $componentCss = asset("assets/css/playground/{$page->component()}.css") ?>
<?= css(array_filter([
	'assets/css/playground/playground.css',
	F::exists($componentCss->root()) ? $componentCss->url() : null,
])) ?>

<?php snippet(["playground/{$page->component()}", 'playground/overview']) ?>

<?php snippet('bottom') ?>
