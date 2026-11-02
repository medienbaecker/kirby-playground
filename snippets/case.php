<?php

/**
 * @var string $id
 * @var string $label
 * @var string $html The rendered case
 * @var string|null $accessibility
 * @var array<string, mixed> $attributes
 */

?>
<section <?= attr([...$attributes, 'class' => 'playground-ui-case', 'id' => $id]) ?>>
	<h2 class="playground-ui-case__label"><a href="#<?= esc($id, 'attr') ?>"><?= esc($label) ?></a></h2>
	<?= $html ?>
	<?php if ($accessibility): ?>
		<p class="playground-ui-case__accessibility"><strong>Accessibility:</strong> <?= markdown($accessibility, ['inline' => true]) ?></p>
	<?php endif ?>
	<?php if ($html !== ''): ?>
		<details class="playground-ui-code">
			<summary>HTML</summary>
			<button type="button" class="playground-ui-code__copy">Copy</button>
			<span class="playground-ui-code__status" aria-live="polite"></span>
			<pre><code><?= esc($html) ?></code></pre>
		</details>
	<?php endif ?>
</section>
