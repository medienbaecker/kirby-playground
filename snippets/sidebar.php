<?php

/**
 * @var string $current Name of the page shown
 */

use Medienbaecker\Playground\Playground;

?>
<div class="playground-ui-sidebar">
	<button type="button" class="playground-ui-sidebar__toggle" aria-controls="playground-ui-sidebar" aria-expanded="true" aria-label="Sidebar">
		<svg viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="M6 3l5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
	</button>
	<nav class="playground-ui-sidebar__nav" id="playground-ui-sidebar" aria-label="Playground pages">
		<p class="playground-ui-sidebar__title">Playground</p>
		<?php $groups = Playground::groups() ?>
		<?php foreach ($groups as $group => $components): ?>
			<?php if (count($groups) > 1): ?>
				<h2 class="playground-ui-sidebar__group"><?= esc($group === '' ? 'Components' : $group) ?></h2>
			<?php endif ?>
			<ul class="playground-ui-sidebar__list">
				<?php foreach ($components as $component): ?>
					<li><a <?= attr(['href' => $component['url'], 'aria-current' => $component['name'] === $current ? 'page' : null]) ?>><?= esc($component['title']) ?></a></li>
				<?php endforeach ?>
			</ul>
		<?php endforeach ?>
	</nav>
</div>
