<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Filesystem\Dir;

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
      'pattern' => 'playground/(:any?)',
      'action'  => function ($component = null) {
        if (kirby()->option('medienbaecker.playground.auth') && !kirby()->user()) {
          $this->next();
        }

        $page = new Page([
          'slug'     => $component ?? 'playground',
          'template' => 'playground',
          'content'  => [
            'title' => $component ? "Playground: {$component}" : 'Playground',
            'component' => $component
          ]
        ]);

        return site()->visit($page);
      }
    ]
  ]
]);
