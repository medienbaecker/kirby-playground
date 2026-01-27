<div class="playground-overview">
	<ul>
		<?php foreach (site()->playgroundLinks() as $link): ?>
			<li><a href="<?= $link['url'] ?>"><?= $link['title'] ?></a></li>
		<?php endforeach ?>
	</ul>
</div>
