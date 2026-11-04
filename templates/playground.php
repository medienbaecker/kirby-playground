<?php

use Medienbaecker\Playground\Playground;

$component  = $page->component()->value();
$components = Playground::components();
$index      = array_search($component, array_column($components, 'name'), true);

snippet('playground-ui/layout', [
	'title'     => $index === false ? null : $components[$index]['title'],
	'cases'     => $index === false ? fn () => snippet('playground-ui/not-found') : fn () => snippet("playground/{$component}"),
	'docs'      => $index === false ? [] : Playground::snippets($component),
	'sidebar'   => Playground::groups($component),
	'component' => $component,
]);
