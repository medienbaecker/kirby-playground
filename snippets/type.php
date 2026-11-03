<?php

/**
 * @var array<string> $tokens Custom property names
 */

?>
<div class="playground-ui-type">
	<?php foreach ($tokens as $token): ?>
		<div class="playground-ui-type__item">
			<p class="playground-ui-type__kicker"><code><?= esc($token) ?></code> <span class="playground-ui-value" data-value="px" data-property="font-size" data-token="<?= esc($token, 'attr') ?>"></span></p>
			<p class="playground-ui-type__sample" style="font-size: var(<?= esc($token, 'attr') ?>)">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt</p>
		</div>
	<?php endforeach ?>
</div>
