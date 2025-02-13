<?php snippet('head') ?>

<?= css([
  'assets/css/playground/playground.css',
  "assets/css/playground/{$page->component()}.css"
]) ?>

<?php
snippet([
  "playground/{$page->component()}",
  'playground/not-found'
])
?>

<?php snippet('bottom') ?>