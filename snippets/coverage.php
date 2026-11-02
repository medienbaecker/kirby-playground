<?php

use Medienbaecker\Playground\Playground;

$components = Playground::coverage();
$gaps       = array_filter($components, fn ($component) => $component['page'] === null || $component['docblock'] === false || $component['status'] === null);
$shown      = count(array_filter($components, fn ($component) => $component['page'] !== null));

?>
<?php if ($gaps): ?>
	<section class="playground-ui-coverage">
		<h2 class="playground-ui-coverage__title">Coverage</h2>
		<p class="playground-ui-coverage__summary"><?= $shown ?> of <?= count($components) ?> components have a playground page.</p>
		<div class="playground-ui-coverage__table" role="region" aria-label="Components with gaps" tabindex="0">
			<table>
				<thead>
					<tr>
						<th scope="col">Component</th>
						<th scope="col">Playground page</th>
						<th scope="col">Docblock</th>
						<th scope="col">Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($gaps as $component): ?>
						<tr>
							<th scope="row"><code><?= str_replace('/', '/<wbr>', esc($component['name'])) ?></code></th>
							<td>
								<?php if ($component['page']): ?>
									<a href="<?= esc($component['page']['url'], 'attr') ?>"><?= esc($component['page']['title']) ?></a>
								<?php else: ?>
									<span class="playground-ui-coverage__missing">None</span>
								<?php endif ?>
							</td>
							<td>
								<?php if ($component['docblock']): ?>
									Yes
								<?php else: ?>
									<span class="playground-ui-coverage__missing">None</span>
								<?php endif ?>
							</td>
							<td>
								<?php if ($component['status']): ?>
									<?= esc($component['status']) ?>
								<?php else: ?>
									<span class="playground-ui-coverage__missing">None</span>
								<?php endif ?>
							</td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</section>
<?php endif ?>
