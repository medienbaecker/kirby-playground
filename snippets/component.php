<?php

/**
 * @var string|null $name Snippet name, e.g. `components/button`, shown when a page documents more than one
 * @var \Medienbaecker\Playground\Docblock $docblock
 */

$props = $docblock->props();
$type  = function (string $type): string {
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
	<?php if ($name): ?>
		<h2 class="playground-ui-component__name">
			<code class="playground-ui-component__snippet"><?= esc($name) ?></code>
		</h2>
	<?php endif ?>

	<?php if ($docblock->description): ?>
		<div class="playground-ui-component__description"><?= markdown($docblock->description, ['safe' => true, 'breaks' => false]) ?></div>
	<?php endif ?>

	<?php if ($props): ?>
		<div class="playground-ui-props">
			<table class="playground-ui-props__table">
				<colgroup>
					<col class="playground-ui-props__column playground-ui-props__column--name">
					<col class="playground-ui-props__column playground-ui-props__column--type">
					<col class="playground-ui-props__column">
				</colgroup>
				<thead>
					<tr>
						<th class="playground-ui-props__cell playground-ui-props__cell--heading" scope="col">Prop</th>
						<th class="playground-ui-props__cell playground-ui-props__cell--heading" scope="col">Type</th>
						<th class="playground-ui-props__cell playground-ui-props__cell--heading" scope="col">Description</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($props as $prop): ?>
						<tr class="playground-ui-props__row">
							<th class="playground-ui-props__cell" scope="row">
								<code class="playground-ui-props__name">$<?= preg_replace('/([A-Z])/', '<wbr>$1', esc($prop['name'])) ?></code>
							</th>
							<td class="playground-ui-props__cell">
								<code class="playground-ui-props__type"><?= implode('<span class="playground-ui-type__pipe">|</span><wbr>', array_map($type, explode('|', preg_replace('/^\?(.*)$/', '$1|null', $prop['type'])))) ?></code>
							</td>
							<td class="playground-ui-props__cell"><?= markdown($prop['description'], ['inline' => true, 'safe' => true]) ?></td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
</section>
