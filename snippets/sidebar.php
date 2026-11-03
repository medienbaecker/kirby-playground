<?php

/**
 * @var string $current Name of the page shown
 */

use Medienbaecker\Playground\Playground;

?>
<nav class="playground-ui-sidebar" id="playground-ui-sidebar" aria-label="Playground pages">
	<a class="playground-ui-sidebar__home" href="<?= Playground::url() ?>">Playground</a>
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
