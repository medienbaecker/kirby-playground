<?php snippet('playground-ui/head') ?>

<?php
$component    = $page->component()->value();
$globalCss    = asset('assets/css/playground/playground.css');
$componentCss = asset("assets/css/playground/{$component}.css");
$componentJs  = asset("assets/js/playground/{$component}.js");
?>
<?= css(array_filter([
	$globalCss->exists() ? $globalCss->url() : null,
	$componentCss->exists() ? $componentCss->url() : null,
])) ?>

<?php if ($component): ?>
	<?php snippet(["playground/{$component}", 'playground-ui/not-found']) ?>
<?php else: ?>
	<?php snippet('playground-ui/overview') ?>
<?php endif ?>

<?= js(array_filter([
	$componentJs->exists() ? $componentJs->url() : null,
])) ?>

<?php snippet('playground-ui/bottom') ?>
