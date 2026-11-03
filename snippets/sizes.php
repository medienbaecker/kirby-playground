<?php

/**
 * @var array<string> $tokens Custom property names
 */

?>
<table class="playground-ui-sizes">
	<thead>
		<tr>
			<th class="playground-ui-sizes__cell playground-ui-sizes__cell--heading" scope="col">Token</th>
			<th class="playground-ui-sizes__cell playground-ui-sizes__cell--heading" scope="col">rem</th>
			<th class="playground-ui-sizes__cell playground-ui-sizes__cell--heading" scope="col">px</th>
			<th class="playground-ui-sizes__cell playground-ui-sizes__cell--heading" scope="col">Size</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($tokens as $token): ?>
			<tr>
				<th class="playground-ui-sizes__cell" scope="row">
					<code class="playground-ui-sizes__token"><?= esc($token) ?></code>
				</th>
				<td class="playground-ui-sizes__cell playground-ui-sizes__cell--value playground-ui-value" data-value="rem" data-token="<?= esc($token, 'attr') ?>"></td>
				<td class="playground-ui-sizes__cell playground-ui-sizes__cell--value playground-ui-value" data-value="px" data-token="<?= esc($token, 'attr') ?>"></td>
				<td class="playground-ui-sizes__cell playground-ui-sizes__cell--square">
					<span class="playground-ui-sizes__square" style="--playground-ui-sizes-size: var(<?= esc($token, 'attr') ?>)"></span>
				</td>
			</tr>
		<?php endforeach ?>
	</tbody>
</table>
