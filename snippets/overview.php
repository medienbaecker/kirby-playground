<?php

use Medienbaecker\Playground\Playground;

$groups = Playground::groups();

?>
<header class="playground-ui-header">
	<h1 class="playground-ui-header__title">Playground</h1>
</header>
<div class="playground-ui-overview">
	<?php foreach ($groups as $group => $components): ?>
		<?php if (count($groups) > 1): ?>
			<h2 class="playground-ui-overview__group"><?= esc($group === '' ? 'Components' : $group) ?></h2>
		<?php endif ?>
		<ul class="playground-ui-overview__list">
			<?php foreach ($components as $component): ?>
				<li class="playground-ui-overview__item"><a href="<?= $component['url'] ?>"><?= esc($component['title']) ?></a></li>
			<?php endforeach ?>
		</ul>
	<?php endforeach ?>
</div>
