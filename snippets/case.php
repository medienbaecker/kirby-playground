<?php

/**
 * @var string $id
 * @var string $label
 * @var string|null $accessibility
 */

?>
<section class="playground-ui-case" id="<?= esc($id, 'attr') ?>">
	<div class="playground-ui-case__header">
		<h2 class="playground-ui-case__label"><a href="#<?= esc($id, 'attr') ?>"><?= esc($label) ?></a></h2>
		<span class="playground-ui-case__width"></span>
	</div>
	<div class="playground-ui-case__frame">
		<iframe class="playground-ui-case__iframe" src="?case=<?= esc($id, 'attr') ?>" title="<?= esc($label, 'attr') ?>"></iframe>
		<div class="playground-ui-case__handle" role="separator" aria-orientation="vertical" aria-label="Drag to resize"></div>
	</div>
	<?php if ($accessibility): ?>
		<p class="playground-ui-case__accessibility"><strong>Accessibility:</strong> <?= markdown($accessibility, ['inline' => true, 'safe' => true]) ?></p>
	<?php endif ?>
</section>
