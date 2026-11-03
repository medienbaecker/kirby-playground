<?php

/**
 * @var array<string> $tokens Custom property names
 */

?>
<div class="playground-ui-typeset">
	<?php foreach ($tokens as $token): ?>
		<div class="playground-ui-typeset__item">
			<p class="playground-ui-typeset__kicker">
				<code class="playground-ui-typeset__token"><?= esc($token) ?></code>
				<span class="playground-ui-typeset__value playground-ui-value" data-value="px" data-property="font-size" data-token="<?= esc($token, 'attr') ?>"></span>
			</p>
			<p class="playground-ui-typeset__sample" style="--playground-ui-typeset-size: var(<?= esc($token, 'attr') ?>)">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt</p>
		</div>
	<?php endforeach ?>
</div>
