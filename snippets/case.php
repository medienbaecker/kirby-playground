<?php

/**
 * @var string $id
 * @var string $label
 */

?>
<section class="playground-ui-case" id="<?= esc($id, 'attr') ?>">
	<div class="playground-ui-case__header">
		<h2 class="playground-ui-case__label">
			<a class="playground-ui-case__link" href="#<?= esc($id, 'attr') ?>"><?= esc($label) ?></a>
		</h2>
		<span class="playground-ui-case__width"></span>
	</div>
	<div class="playground-ui-case__frame">
		<iframe class="playground-ui-case__iframe" src="?case=<?= esc($id, 'attr') ?>" title="<?= esc($label, 'attr') ?>"></iframe>
		<div class="playground-ui-case__handle"></div>
	</div>
</section>
