<?php

use Kirby\Cms\Media;
use Kirby\Cms\Page;
use Kirby\Exception\NotFoundException;
use Kirby\Http\Response;
use Medienbaecker\Playground\ImageGenerator;
use Medienbaecker\Playground\Playground;
use Medienbaecker\Playground\VirtualFile;

// Routes are scoped to all languages so they resolve under a language prefix.
// Only the language router prepends $language, so single-language sites don't get it.
$language = fn(array &$args) => kirby()->multilang() ? array_shift($args) : null;

return [
    [
        'pattern'  => 'media/playground/(:any)/(:any)',
        'language' => '*',
        'action'   => function (...$args) use ($language) {
            if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
                $this->next();
            }

            $language($args);
            [$hash, $filename] = $args;

            $cacheId = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 32);
            $imageGenerator = new ImageGenerator();
            $sourcePath = $imageGenerator->sourceDir() . "/{$cacheId}.jpg";

            if (!file_exists($sourcePath)) {
                return false;
            }

            $file = new VirtualFile([
                'filename' => "{$cacheId}.jpg",
                'parent'   => site(),
                'root'     => $sourcePath,
                'url'      => url("playground/image/{$cacheId}.jpg"),
            ]);

            // Mirrors Media::link(): clearing the media folder leaves stale thumb URLs
            // whose job file is gone, and an uncaught throw never reaches App::io()
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
        'action'   => function (...$args) use ($language) {
            if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
                $this->next();
            }

            $language($args);
            $cacheId = $args[0];

            $imageGenerator = new ImageGenerator();
            $path = $imageGenerator->sourceDir() . "/{$cacheId}.jpg";

            if (!file_exists($path)) {
                return false;
            }

            return Response::file($path, [
                'headers' => ['Cache-Control' => 'public, max-age=31536000']
            ]);
        }
    ],
    [
        'pattern'  => 'playground/(:all?)',
        'language' => '*',
        'action'   => function (...$args) use ($language) {
            if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
                $this->next();
            }

            $lang = $language($args);
            $component = $args[0] ?? null;
            $components = Playground::components();

            if (!$component && $components !== []) {
                go($components[0]['url']);
            }

            // Playground pages show dynamic content and should never be cached
            kirby()->response()->cache(false);

            $playgroundPage = Page::factory([
                'slug'     => 'playground',
                'template' => 'playground',
                'content'  => [
                    'title' => 'Playground'
                ]
            ]);

            if ($component) {
                if (in_array($component, array_column($components, 'name'), true) === false) {
                    kirby()->response()->code(404);
                }

                $parent = $playgroundPage;

                foreach (array_slice(explode('/', $component), 0, -1) as $folder) {
                    $parent = Page::factory([
                        'slug'     => $folder,
                        'template' => 'playground',
                        'parent'   => $parent,
                    ]);
                }

                $componentPage = Page::factory([
                    'slug'     => basename($component),
                    'template' => 'playground',
                    'parent'   => $parent,
                    'content'  => [
                        'title'     => "Playground: {$component}",
                        'component' => $component
                    ]
                ]);
                return site()->visit($componentPage, $lang?->code());
            } else {
                return site()->visit($playgroundPage, $lang?->code());
            }
        }
    ]
];
