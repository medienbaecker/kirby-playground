<?php

use Medienbaecker\Playground\Playground;

/**
 * @var string $name Snippet name, e.g. `components/button`
 * @var \Medienbaecker\Playground\Docblock $docblock
 */

$props         = $docblock->props();
$status        = $docblock->tag('status')[0] ?? null;
$accessibility = $docblock->tag('accessibility');
$usage         = Playground::usage($name);

?>
<section class="playground-ui-component">
	<h2 class="playground-ui-component__name">
		<code><?= esc($name) ?></code>
		<?php if ($status): ?>
			<span class="playground-ui-component__status"><?= esc($status) ?></span>
		<?php endif ?>
	</h2>

	<?php if ($docblock->description): ?>
		<div class="playground-ui-component__description"><?= markdown($docblock->description, ['safe' => true]) ?></div>
	<?php endif ?>

	<?php if ($accessibility): ?>
		<h3 class="playground-ui-component__heading">Accessibility</h3>
		<ul class="playground-ui-component__list">
			<?php foreach ($accessibility as $item): ?>
				<li><?= markdown($item, ['inline' => true, 'safe' => true]) ?></li>
			<?php endforeach ?>
		</ul>
	<?php endif ?>

	<?php if ($props): ?>
		<div class="playground-ui-props" role="region" aria-label="Props of <?= esc($name, 'attr') ?>" tabindex="0">
			<table class="playground-ui-props__table">
				<thead>
					<tr>
						<th scope="col">Prop</th>
						<th scope="col">Type</th>
						<th scope="col">Description</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($props as $prop): ?>
						<tr>
							<th scope="row"><code>$<?= preg_replace('/([A-Z])/', '<wbr>$1', esc($prop['name'])) ?></code></th>
							<td><code><?= str_replace('|', '|<wbr>', esc($prop['type'])) ?></code></td>
							<td><?= markdown($prop['description'], ['inline' => true, 'safe' => true]) ?></td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>

	<h3 class="playground-ui-component__heading">Used in</h3>
	<?php if ($usage): ?>
		<ul class="playground-ui-component__list">
			<?php foreach ($usage as $file => $calls): ?>
				<li><code><?= str_replace('/', '/<wbr>', esc($file)) ?></code><?= $calls > 1 ? ", {$calls} calls" : '' ?></li>
			<?php endforeach ?>
		</ul>
	<?php else: ?>
		<p>No template or snippet calls it.</p>
	<?php endif ?>
</section>
