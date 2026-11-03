<?php

/**
 * @var string $id
 * @var string $label
 * @var string $html The rendered case
 * @var string|null $accessibility
 * @var array<string, mixed> $attributes Added to the box around the rendered case
 * @var string|null $php The `snippet()` call of a component case
 * @var array $controls Form fields for the props of a component case
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
	<?php if ($controls): ?>
		<details class="playground-ui-controls">
			<summary>Props</summary>
			<form class="playground-ui-controls__form">
				<?php foreach ($controls as $control): ?>
					<?php $field = "props-{$id}-{$control['name']}" ?>
					<label class="playground-ui-controls__name" for="<?= esc($field, 'attr') ?>"><code>$<?= esc($control['name']) ?></code></label>
					<?php if ($control['input'] === 'select'): ?>
						<select id="<?= esc($field, 'attr') ?>" name="<?= esc($control['name'], 'attr') ?>">
							<option value="">Default</option>
							<?php foreach ($control['options'] as $option): ?>
								<option <?= attr(['value' => $option, 'selected' => $control['value'] === $option]) ?>><?= esc($option) ?></option>
							<?php endforeach ?>
						</select>
					<?php elseif ($control['input'] === 'boolean'): ?>
						<select id="<?= esc($field, 'attr') ?>" name="<?= esc($control['name'], 'attr') ?>" data-type="boolean">
							<option value="">Default</option>
							<option <?= attr(['value' => 'true', 'selected' => $control['value'] === true]) ?>>true</option>
							<option <?= attr(['value' => 'false', 'selected' => $control['value'] === false]) ?>>false</option>
						</select>
					<?php else: ?>
						<input <?= attr([
							'type'  => $control['input'],
							'id'    => $field,
							'name'  => $control['name'],
							'value' => $control['value'],
							'step'  => $control['input'] === 'number' ? 'any' : null,
						]) ?>>
					<?php endif ?>
				<?php endforeach ?>
			</form>
		</details>
	<?php endif ?>
	<?php if ($php): ?>
		<details class="playground-ui-code">
			<summary>PHP</summary>
			<button type="button" class="playground-ui-code__copy">Copy</button>
			<span class="playground-ui-code__status" aria-live="polite"></span>
			<pre><code class="playground-ui-code__php"><?= esc($php) ?></code></pre>
		</details>
	<?php endif ?>
	<?php if ($html !== ''): ?>
		<details class="playground-ui-code">
			<summary>HTML</summary>
			<button type="button" class="playground-ui-code__copy">Copy</button>
			<span class="playground-ui-code__status" aria-live="polite"></span>
			<pre><code class="playground-ui-code__html"><?= esc($html) ?></code></pre>
		</details>
	<?php endif ?>
</section>
