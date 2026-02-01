<?php

use Kirby\Cms\Media;
use Kirby\Cms\Page;
use Kirby\Http\Response;
use Medienbaecker\Playground\ImageGenerator;
use Medienbaecker\Playground\VirtualFile;

return [
    [
        'pattern' => 'media/playground/(:any)/(:any)',
        'action'  => function (string $hash, string $filename) {
            if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
                $this->next();
            }

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

            return Media::thumb($file, $hash, $filename);
        }
    ],
    [
        'pattern' => 'playground/image/(:any).jpg',
        'action'  => function (string $cacheId) {
            if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
                $this->next();
            }

            $imageGenerator = new ImageGenerator();
            $path = $imageGenerator->sourceDir() . "/{$cacheId}.jpg";

            if (!file_exists($path)) {
                return false;
            }

            return new Response(
                \Kirby\Filesystem\F::read($path),
                'image/jpeg',
                200,
                ['Cache-Control' => 'public, max-age=31536000']
            );
        }
    ],
    [
        'pattern' => 'playground/(:any?)',
        'action'  => function ($component = null) {
            if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
                $this->next();
            }

            $playgroundPage = new Page([
                'slug'     => 'playground',
                'template' => 'playground',
                'content'  => [
                    'title' => 'Playground'
                ]
            ]);

            if ($component) {
                $componentPage = new Page([
                    'slug'     => $component,
                    'template' => 'playground',
                    'parent'   => $playgroundPage,
                    'content'  => [
                        'title'     => "Playground: {$component}",
                        'component' => $component
                    ]
                ]);
                return site()->visit($componentPage);
            } else {
                return site()->visit($playgroundPage);
            }
        }
    ]
];
