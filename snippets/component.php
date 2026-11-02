<?php

/**
 * @var string $name Snippet name, e.g. `components/button`
 * @var \Medienbaecker\Playground\Docblock $docblock
 */

$sections = [
	'use'           => 'When to use',
	'avoid'         => 'When not to use',
	'accessibility' => 'Accessibility',
];
$props  = $docblock->props();
$status = $docblock->tag('status')[0] ?? null;

?>
<section class="playground-ui-component">
	<h2 class="playground-ui-component__name">
		<code><?= esc($name) ?></code>
		<?php if ($status): ?>
			<span class="playground-ui-component__status"><?= esc($status) ?></span>
		<?php endif ?>
	</h2>

	<?php if ($docblock->description): ?>
		<div class="playground-ui-component__description"><?= markdown($docblock->description) ?></div>
	<?php endif ?>

	<?php foreach ($sections as $tag => $heading): ?>
		<?php if ($items = $docblock->tag($tag)): ?>
			<h3 class="playground-ui-component__heading"><?= $heading ?></h3>
			<ul class="playground-ui-component__list">
				<?php foreach ($items as $item): ?>
					<li><?= markdown($item, ['inline' => true]) ?></li>
				<?php endforeach ?>
			</ul>
		<?php endif ?>
	<?php endforeach ?>

	<?php if ($props): ?>
		<div class="playground-ui-props" role="region" aria-label="Props of <?= esc($name, 'attr') ?>" tabindex="0">
			<table class="playground-ui-props__table">
				<thead>
					<tr><th scope="col">Prop</th><th scope="col">Type</th><th scope="col">Description</th></tr>
				</thead>
				<tbody>
					<?php foreach ($props as $prop): ?>
						<tr>
							<th scope="row"><code>$<?= preg_replace('/([A-Z])/', '<wbr>$1', esc($prop['name'])) ?></code></th>
							<td><code><?= str_replace('|', '|<wbr>', esc($prop['type'])) ?></code></td>
							<td><?= markdown($prop['description'], ['inline' => true]) ?></td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
</section>
