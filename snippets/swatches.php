<?php

/**
 * @var array<string> $tokens Custom property names
 */

?>
<ul class="playground-ui-swatches">
	<?php foreach ($tokens as $token): ?>
		<li class="playground-ui-swatches__item">
			<span class="playground-ui-swatches__color" style="background-color: var(<?= esc($token, 'attr') ?>)"></span>
			<code><?= esc($token) ?></code>
			<span class="playground-ui-value" data-value="color" data-token="<?= esc($token, 'attr') ?>"></span>
		</li>
	<?php endforeach ?>
</ul>
