<?php $name = $page->component()->value() ?: 'button' ?>
<p class="playground-ui-not-found">No playground page <strong><?= esc($name) ?></strong>. Create it at <code>site/snippets/playground/<?= esc($name) ?>.php</code></p>
