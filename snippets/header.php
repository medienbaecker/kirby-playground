<?php

/**
 * @var string $title
 * @var array|null $previous
 * @var array|null $next
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
	<p class="playground-ui-header__axe" aria-live="polite">Checking accessibility…</p>
</header>
