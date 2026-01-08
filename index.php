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
            $links = array_map(function ($file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                $title = ucwords(str_replace('-', ' ', $name));
                return "<li><a href='" . url("playground/{$name}") . "'>{$title}</a></li>";
            }, $files);

            return '<ul class="playground-nav">' . implode('', $links) . '</ul>';
        }
    ],

    'templates' => [
        'playground' => __DIR__ . '/templates/playground.php'
    ],

    'snippets' => [
        'playground/not-found' => __DIR__ . '/snippets/not-found.php'
    ],

    'routes' => [
        [
            'pattern' => 'media/playground/(:any)/(:any)/(:any)',
            'action'  => function (string $variant, string $hash, string $filename) {
                $variants = ['landscape', 'portrait', 'square'];
                if (!in_array($variant, $variants)) {
                    return false;
                }
                $file = \Medienbaecker\Playground::image($variant);
                return \Kirby\Cms\Media::thumb($file, $hash, $filename);
            }
        ],
        [
            'pattern' => 'playground/(:any).jpg',
            'action'  => function ($variant) {
                $variants = ['landscape', 'portrait', 'square'];
                if (!in_array($variant, $variants)) {
                    return false;
                }
                $path = __DIR__ . "/assets/{$variant}.jpg";
                return new Response(
                    file_get_contents($path),
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
