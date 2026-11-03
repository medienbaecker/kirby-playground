<?php

use Kirby\Cms\Media;
use Kirby\Cms\Page;
use Kirby\Exception\NotFoundException;
use Kirby\Filesystem\F;
use Kirby\Http\Response;
use Medienbaecker\Playground\ImageGenerator;
use Medienbaecker\Playground\Playground;

$shiftLanguage = fn (array &$args) => kirby()->multilang() ? array_shift($args) : null;

return [
	[
		'pattern'  => 'media/playground/(:any)/(:any)',
		'language' => '*',
		'action'   => function (...$args) use ($shiftLanguage) {
			if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
				$this->next();
			}

			$shiftLanguage($args);
			[$hash, $filename] = $args;

			$file = ImageGenerator::file(substr(F::name($filename), 0, 32));

			if (!$file->exists()) {
				return false;
			}

			try {
				return Media::thumb($file, $hash, $filename);
			} catch (NotFoundException) {
				return false;
			}
		}
	],
	[
		'pattern'  => 'playground/image/(:any).jpg',
		'language' => '*',
		'action'   => function (...$args) use ($shiftLanguage) {
			if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
				$this->next();
			}

			$shiftLanguage($args);
			$file = ImageGenerator::file($args[0]);

			if (!$file->exists()) {
				return false;
			}

			return Response::file($file->root(), [
				'headers' => ['Cache-Control' => 'public, max-age=31536000']
			]);
		}
	],
	[
		'pattern'  => 'playground/(:all?)',
		'language' => '*',
		'action'   => function (...$args) use ($shiftLanguage) {
			if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
				$this->next();
			}

			$language   = $shiftLanguage($args);
			$component  = $args[0] ?? null;
			$components = Playground::components();

			if (!$component && $components !== []) {
				go($components[0]['url']);
			}

			kirby()->response()->cache(false);

			$page = Page::factory([
				'slug'     => 'playground',
				'template' => 'playground',
				'content'  => [
					'title' => 'Playground'
				]
			]);

			if ($component) {
				if (!in_array($component, array_column($components, 'name'), true)) {
					kirby()->response()->code(404);
				}

				foreach (array_slice(explode('/', $component), 0, -1) as $folder) {
					$page = Page::factory([
						'slug'     => $folder,
						'template' => 'playground',
						'parent'   => $page,
					]);
				}

				$page = Page::factory([
					'slug'     => basename($component),
					'template' => 'playground',
					'parent'   => $page,
					'content'  => [
						'title'     => "Playground: {$component}",
						'component' => $component
					]
				]);
			}

			return site()->visit($page, $language?->code());
		}
	]
];
