# Kirby Playground

![An overview of different button styles](https://github.com/user-attachments/assets/c33d7d9e-70b9-431f-a811-932a1cd62943)

A Kirby plugin that shows your snippets in test cases at `/playground`.

## Usage

Put playground snippets in `site/snippets/playground/`. A snippet `buttons.php` is shown at `/playground/buttons`, and `/playground` opens the first page.

Subfolders become groups in the sidebar: `form/input.php` is listed under "Form" at `/playground/form/input`. A `base` folder comes first, for pages that are not components, like typography or spacing. The sidebar on the right lists every page and can be collapsed.

## Cases

`Playground::case()` takes a label and a closure that renders the case. Each case gets an anchor and its own iframe, loaded at `?case=<id>`, so media queries react to the frame's width. Drag its right edge to resize it; the width shows next to the label.

```php
use Medienbaecker\Playground\Playground;

Playground::case('Long label', fn () => snippet('components/button', ['label' => 'Lorem ipsum dolor sit amet']));

// Attributes go on the box around the case, e.g. to set a background
Playground::case('On purple', fn () => snippet('components/button'), attributes: ['data-background' => 'purple-700']);
```

A case that renders one snippet can use `Playground::component()`:

```php
Playground::component('Large', 'components/button', ['label' => 'Lorem ipsum', 'size' => 'large']);
```

Two custom properties set the space around a case. Set them in `assets/css/playground/playground.css` for all cases, or on one case through its attributes:

```css
.playground-ui-case__render {
  --playground-case-padding-block: 5rem;
  --playground-case-padding-inline: var(--spacing-inline);
}
```

```php
Playground::case('Flush', fn () => snippet('components/hero'), attributes: ['style' => '--playground-case-padding-inline: 0']);
```

## Component docs

A playground page documents `components/<page name>` when that snippet exists, so `playground/button.php` shows the docs of `components/button.php`. To document other snippets, or several, name them with `@component` tags:

```php
<?php

/**
 * @component components/button
 */
```

The page then shows that snippet's docblock above the cases: the description and a table of its `@var` props.

```php
<?php

/**
 * Button Component
 *
 * Shown as the description, Markdown allowed.
 *
 * @var string|null $label Shown in the props table
 */
```

The first paragraph is the summary and is not shown. A one-line `/** @var Page $page */` hint before the docblock is skipped.

## Tokens

Three helpers show custom properties with their computed values. Each takes a label and the property names in the order to show them:

```php
Playground::swatches('Purple', ['--brand-purple-100', '--brand-purple-300', '--brand-purple-700']);
Playground::swatches('Roles on purple', ['--color-text', '--color-accent'], attributes: ['data-tone' => 'dark']);
Playground::sizes('Spacing', ['--spacing-xs', '--spacing-s', '--spacing-m']);
Playground::type('Font sizes', ['--font-size-s', '--font-size-base', '--font-size-l']);
```

## Placeholder content

The `Placeholder` class makes text and images for playground snippets, or anywhere else:

```php
use Medienbaecker\Playground\Placeholder;
```

### Text

```php
Placeholder::text([
    // word count (default: 20)
    'words' => 50,
    'words' => [30, 60],

    // wrap in <p> tags (default: false)
    'paragraphs' => false,
    'paragraphs' => true,
    'paragraphs' => 3,
    'paragraphs' => [2, 5],

    // insert random <a> tags (default: false)
    'links' => false,
    'links' => true,
    'links' => 2,
    'links' => [1, 3],

    // insert random <strong> tags (default: false)
    'bold' => false,
    'bold' => true,
    'bold' => 1,
    'bold' => [1, 2],

    // insert random <em> tags (default: false)
    'italic' => false,
    'italic' => true,
    'italic' => 1,
    'italic' => [1, 3],

    // start with (default: 'Lorem ipsum ')
    'prefix' => 'Lorem ipsum ',
    'prefix' => false,

    // end with (default: '.')
    'suffix' => '.',
    'suffix' => false,
])
```

For names or titles, turn off the prefix and suffix to get random words without "Lorem ipsum" and the period:

```php
Placeholder::text(['words' => 5, 'prefix' => false, 'suffix' => false])
```

### Images

Images come from [picsum.photos](https://picsum.photos) as Kirby file objects, so thumbs work:

```php
Placeholder::image('hero')  // shorthand for ['seed' => 'hero']
Placeholder::image(['seed' => 'card', 'width' => 400, 'height' => 300])
Placeholder::image(['id' => 237])  // picsum image ID
Placeholder::image(['seed' => 'hero', 'content' => ['alt' => 'Hero image', 'caption' => Placeholder::text(['words' => 10])]])

Placeholder::images(6, ['seed' => 'gallery'])  // gallery-0, gallery-1, ...

Placeholder::image('hero')->thumb('card')
Placeholder::image('test')->crop(200, 200)
```

Images with a seed or ID are downloaded once and cached in `media/playground-source/`; delete that folder to download them again. Images without either, like `Placeholder::image()` or `Placeholder::images(6)`, are downloaded on every request, which is slow.

## Assets

The playground loads these files when they exist:

```
assets/
├── css/
│   └── playground/
│       ├── playground.css
│       └── buttons.css
└── js/
    └── playground/
        └── buttons.js
```

`playground.css` is loaded on every playground page. `buttons.css` is loaded on `/playground/buttons` and its cases, `buttons.js` only in its cases.

## Configuration

To restrict the playground to logged-in users:

```php
// site/config/config.php
return [
  'medienbaecker.playground.auth' => true
];
```

Playground pages are wrapped in `snippet('head')` and `snippet('bottom')`. If your snippets have other names, override `site/snippets/playground-ui/head.php` and `bottom.php`. The other `playground-ui/*` snippets can be overridden the same way: `sidebar`, `header`, `component`, `case`, `not-found`, `swatches`, `sizes` and `type`.
