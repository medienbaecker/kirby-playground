<?php $name = $page->component()->value() ?>
<?php if ($name): ?>
	<p class="playground-ui-not-found">No playground page <strong><?= esc($name) ?></strong>. Create it at <code>site/snippets/playground/<?= esc($name) ?>.php</code></p>
<?php else: ?>
	<p class="playground-ui-not-found">No playground pages yet. Create one in <code>site/snippets/playground/</code></p>
<?php endif ?>
