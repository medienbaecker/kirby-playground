<?php $plugin = kirby()->plugin('medienbaecker/playground') ?>
<?= css($plugin->asset('tokens.css')->url()) ?>
<div class="playground-ui-tokens"></div>
<?= js($plugin->asset('tokens.js')->url()) ?>
