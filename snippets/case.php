<?php

/**
 * @var string $id
 * @var string $label
 * @var string $html The rendered case
 * @var string|null $accessibility
 * @var array<string, mixed> $attributes Added to the box around the rendered case
 * @var string $php The `snippet()` calls that render the case
 */

?>
<section class="playground-ui-case" id="<?= esc($id, 'attr') ?>">
	<div class="playground-ui-case__header">
		<h2 class="playground-ui-case__label"><a href="#<?= esc($id, 'attr') ?>"><?= esc($label) ?></a></h2>
	</div>
	<div <?= attr([...$attributes, 'class' => $attributes === [] ? 'playground-ui-case__render playground-ui-case__render--plain' : 'playground-ui-case__render']) ?>><?= $html ?></div>
	<?php if ($accessibility): ?>
		<p class="playground-ui-case__accessibility"><strong>Accessibility:</strong> <?= markdown($accessibility, ['inline' => true, 'safe' => true]) ?></p>
	<?php endif ?>
	<?php if ($php): ?>
		<details class="playground-ui-code">
			<summary>PHP</summary>
			<button type="button" class="playground-ui-code__copy">Copy</button>
			<span class="playground-ui-code__status" aria-live="polite"></span>
			<pre><code class="playground-ui-code__php"><?= esc($php) ?></code></pre>
		</details>
	<?php endif ?>
</section>
