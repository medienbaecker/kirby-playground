<?php

/**
 * @var array<string> $tokens Custom property names
 */

?>
<table class="playground-ui-sizes">
	<thead>
		<tr>
			<th scope="col">Token</th>
			<th scope="col">rem</th>
			<th scope="col">px</th>
			<th scope="col">Size</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($tokens as $token): ?>
			<tr>
				<th scope="row"><code><?= esc($token) ?></code></th>
				<td class="playground-ui-value" data-value="rem" data-token="<?= esc($token, 'attr') ?>"></td>
				<td class="playground-ui-value" data-value="px" data-token="<?= esc($token, 'attr') ?>"></td>
				<td><span class="playground-ui-sizes__square" style="inline-size: var(<?= esc($token, 'attr') ?>); block-size: var(<?= esc($token, 'attr') ?>)"></span></td>
			</tr>
		<?php endforeach ?>
	</tbody>
</table>
