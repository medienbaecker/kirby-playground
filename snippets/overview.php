<?php

use Medienbaecker\Playground\Playground;

?>
<div class="playground-ui-overview">
	<?php foreach (Playground::groups() as $group => $components): ?>
		<?php if ($group !== ''): ?>
			<h2 class="playground-ui-overview__group"><?= esc($group) ?></h2>
		<?php endif ?>
		<ul class="playground-ui-overview__list">
			<?php foreach ($components as $component): ?>
				<li><a href="<?= $component['url'] ?>"><?= esc($component['title']) ?></a></li>
			<?php endforeach ?>
		</ul>
	<?php endforeach ?>
</div>
