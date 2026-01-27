<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Filesystem\Dir;
use Kirby\Http\Response;

require_once __DIR__ . '/Playground.php';
require_once __DIR__ . '/PlaygroundFile.php';

Kirby::plugin('medienbaecker/playground', [
    'options' => [
        'auth' => false
    ],

    'siteMethods' => [
        'playgroundLinks' => function () {
            $files = Dir::files(kirby()->root('snippets') . '/playground');
            return array_map(function ($file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                return [
                    'url'   => url("playground/{$name}"),
                    'title' => ucwords(str_replace('-', ' ', $name))
                ];
            }, $files);
        }
    ],

    'templates' => [
        'playground' => __DIR__ . '/templates/playground.php'
    ],

    'snippets' => [
        'playground/section'   => __DIR__ . '/snippets/section.php',
        'playground/label'     => __DIR__ . '/snippets/label.php',
        'playground/overview'  => __DIR__ . '/snippets/overview.php',
        'playground/not-found' => __DIR__ . '/snippets/not-found.php'
    ],

    'routes' => [
        [
            'pattern' => 'media/playground/(:any)/(:any)',
            'action'  => function (string $hash, string $filename) {
                if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
                    $this->next();
                }

                // Extract cache ID (32-char MD5 hash) from thumb filename
                $cacheId = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 32);

                $sourcePath = \Medienbaecker\Playground::sourceDir() . "/{$cacheId}.jpg";
                if (!file_exists($sourcePath)) {
                    return false;
                }

                $file = new \Medienbaecker\PlaygroundFile([
                    'filename' => "{$cacheId}.jpg",
                    'parent'   => site(),
                    'root'     => $sourcePath,
                    'url'      => url("playground/image/{$cacheId}.jpg"),
                ]);

                return \Kirby\Cms\Media::thumb($file, $hash, $filename);
            }
        ],
        [
            'pattern' => 'playground/image/(:any).jpg',
            'action'  => function (string $cacheId) {
                if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
                    $this->next();
                }

                $path = \Medienbaecker\Playground::sourceDir() . "/{$cacheId}.jpg";
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
                        'parent' => $playgroundPage,
                        'content'  => [
                            'title' => "Playground: {$component}",
                            'component' => $component
                        ]
                    ]);
                    return site()->visit($componentPage);
                } else {
                    return site()->visit($playgroundPage);
                }
            }
        ]
    ]
]);
