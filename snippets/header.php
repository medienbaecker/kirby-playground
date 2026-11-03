<?php

/**
 * @var string $title
 */

?>
<header class="playground-ui-header">
	<button type="button" class="playground-ui-header__sidebar" aria-controls="playground-ui-sidebar" aria-expanded="true" aria-label="Sidebar">
		<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><rect x="1.5" y="2.5" width="13" height="11" rx="1.5" fill="none" stroke="currentColor"/><path d="M6 2.5v11" stroke="currentColor"/></svg>
	</button>
	<h1 class="playground-ui-header__title"><?= esc($title) ?></h1>
</header>
