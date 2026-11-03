<?php

/**
 * @var string|null $name Snippet name, e.g. `components/button`, shown when a page documents more than one
 * @var \Medienbaecker\Playground\Docblock $docblock
 */

$props         = $docblock->props();
$status        = $docblock->tag('status')[0] ?? null;
$accessibility = $docblock->tag('accessibility');
$type          = function (string $type): string {
	$type = trim($type);

	return match (true) {
		$type === 'null'                           => '<span class="playground-ui-type__null">null</span>',
		preg_match('/^([\'"]).*\1$/', $type) === 1 => '<span class="playground-ui-type__value">' . esc(trim($type, '\'"')) . '</span>',
		ctype_upper(ltrim($type, '\\')[0] ?? '')   => '<abbr class="playground-ui-type__class" title="' . esc(ltrim($type, '\\'), 'attr') . '">' . esc(basename(str_replace('\\', '/', $type))) . '</abbr>',
		default                                    => '<span class="playground-ui-type__builtin">' . esc($type) . '</span>',
	};
};

?>
<section class="playground-ui-component">
	<?php if ($name || $status): ?>
		<h2 class="playground-ui-component__name">
			<?php if ($name): ?>
				<code><?= esc($name) ?></code>
			<?php endif ?>
			<?php if ($status): ?>
				<span class="playground-ui-component__status"><?= esc($status) ?></span>
			<?php endif ?>
		</h2>
	<?php endif ?>

	<?php if ($docblock->description): ?>
		<div class="playground-ui-component__description"><?= markdown($docblock->description, ['safe' => true, 'breaks' => false]) ?></div>
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
		<div class="playground-ui-props">
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
							<th scope="row"><code class="playground-ui-props__name">$<?= preg_replace('/([A-Z])/', '<wbr>$1', esc($prop['name'])) ?></code></th>
							<td><code class="playground-ui-type"><?= implode('<span class="playground-ui-type__pipe">|</span><wbr>', array_map($type, explode('|', preg_replace('/^\?(.*)$/', '$1|null', $prop['type'])))) ?></code></td>
							<td><?= markdown($prop['description'], ['inline' => true, 'safe' => true]) ?></td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
</section>
