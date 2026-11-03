<?php

/**
 * @var string $title
 * @var array|null $previous
 * @var array|null $next
 * @var array<string> $files Paths shown in the header
 */

use Medienbaecker\Playground\Playground;

?>
<header class="playground-ui-header">
	<nav class="playground-ui-header__nav" aria-label="Playground">
		<a href="<?= Playground::url() ?>">Playground</a>
		<?php if ($previous || $next): ?>
			<span class="playground-ui-header__siblings">
				<?php if ($previous): ?>
					<a href="<?= $previous['url'] ?>" rel="prev"><span aria-hidden="true">←</span> <?= esc($previous['title']) ?></a>
				<?php endif ?>
				<?php if ($next): ?>
					<a href="<?= $next['url'] ?>" rel="next"><?= esc($next['title']) ?> <span aria-hidden="true">→</span></a>
				<?php endif ?>
			</span>
		<?php endif ?>
	</nav>
	<h1 class="playground-ui-header__title"><?= esc($title) ?></h1>
	<p class="playground-ui-header__axe" aria-live="polite" data-script="<?= esc(kirby()->plugin('medienbaecker/playground')->asset('axe.min.js')->url(), 'attr') ?>"></p>
	<ul class="playground-ui-header__files">
		<?php foreach ($files as $file): ?>
			<li><code><?= str_replace('/', '/<wbr>', esc($file)) ?></code></li>
		<?php endforeach ?>
	</ul>
</header>
