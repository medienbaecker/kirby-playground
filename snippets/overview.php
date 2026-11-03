<?php

use Medienbaecker\Playground\Playground;

$coverage = Playground::coverage();
$notes    = [];
$groups   = Playground::groups();

foreach ($coverage as $component) {
	if ($component['page'] === null) {
		$groups[''][] = ['title' => Playground::title(basename($component['name'])), 'url' => null, 'name' => $component['name']];
	} elseif ($component['docblock'] === false || $component['status'] !== null) {
		$notes[$component['page']['url']] = $component['docblock'] ? $component['status'] : 'no docblock';
	}
}

usort($groups[''], fn ($a, $b) => strcasecmp($a['title'], $b['title']));

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
				<?php if ($component['url'] === null): ?>
					<li class="playground-ui-overview__item playground-ui-overview__item--missing"><span><?= esc($component['title']) ?></span><small>no page</small></li>
				<?php else: ?>
					<li class="playground-ui-overview__item"><a href="<?= $component['url'] ?>"><?= esc($component['title']) ?><?php if (isset($notes[$component['url']])): ?><small><?= esc($notes[$component['url']]) ?></small><?php endif ?></a></li>
				<?php endif ?>
			<?php endforeach ?>
		</ul>
	<?php endforeach ?>
</div>
