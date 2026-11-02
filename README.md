# Kirby Playground

![An overview of different button styles](https://github.com/user-attachments/assets/c33d7d9e-70b9-431f-a811-932a1cd62943)

## Usage

1. Create your tests as snippets in `/site/snippets/playground/`
2. Access your tests at `/playground/[component-name]`

For example, if you have a snippet named `buttons.php`, it will be available at `/playground/buttons`.

Subfolders become groups in the overview: `/site/snippets/playground/form/input.php` is listed under "Form" and available at `/playground/form/input`.

## Features

- Custom route handling for `/playground/(:all?)` that can optionally be restricted to logged-in users
- Components are rendered through virtual pages, without requiring actual page files
- Optionally loads `assets/css/playground/playground.css` and `assets/css/playground/[component-name].css`
- Optionally loads `assets/js/playground/[component-name].js`
- Unknown components return a 404 with a list of the components you do have
- A header with links to the overview and the previous and next component
- Component docs and a props table from docblocks, and cases with their HTML
- Loads its own `playground.css` and `playground.js` for the header, docs and cases
- Placeholder helpers for lorem ipsum text and images

## Cases

Wrap each case in `Playground::case()` to give it a label, an anchor and a panel with its rendered HTML and a Copy button:

```php
use Medienbaecker\Playground\Playground;

Playground::case('Long label', fn () => snippet('components/button', ['label' => 'Lorem ipsum dolor sit amet']));

// With an accessibility note for this case
Playground::case('Icon only', fn () => snippet('components/button', ['iconOnly' => true]), accessibility: 'The label stays readable for screen readers');

// With attributes on the case's wrapper, e.g. to paint it in a surface colour
Playground::case('On purple', fn () => snippet('components/button'), attributes: ['data-background' => 'purple-700']);
```

## Component Docs

Name the components a playground snippet shows in its docblock:

```php
<?php

/**
 * @component components/button
 */
```

Each named component gets a section above the cases, read from its own docblock:

```php
<?php

/**
 * Button Component
 *
 * Shown as the description, Markdown allowed.
 *
 * @status ready
 * @accessibility What to keep in mind, one item per tag
 * @var string|null $label Shown in the props table
 */
```

The first paragraph is the summary and is not shown.

## Placeholder Content

Generate placeholder content directly in your playground snippets (or anywhere else) by using the `Placeholder` class:

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

For names, titles, or other non-sentence content, set `prefix` and `suffix` to `false` (or `null` or `''`):

```php
Placeholder::text(['words' => 5, 'prefix' => false, 'suffix' => false])
```

This way you will get random words, _not_ starting with Lorem ipsum and _not_ ending with a period.

### Images

Returns virtual Kirby File objects with full thumb support. Images are downloaded from [picsum.photos](https://picsum.photos) and cached locally.

```php
// Seeded image (recommended - cached locally)
Placeholder::image('hero')  // shorthand
Placeholder::image(['seed' => 'hero'])
Placeholder::image(['seed' => 'card', 'width' => 400, 'height' => 300])

// Specific image by picsum ID
Placeholder::image(['id' => 237])

// Multiple images
Placeholder::images(6, ['seed' => 'gallery'])  // gallery-0, gallery-1, ...
Placeholder::images(4, ['width' => 400, 'height' => 300, 'seed' => 'thumbs'])

// Random image (downloads fresh each page load - slow!)
Placeholder::image()
Placeholder::images(6)

// With file content (alt, caption, etc.)
Placeholder::image(['seed' => 'hero', 'content' => ['alt' => 'Hero image', 'caption' => Placeholder::text(['words' => 10])]])
```

**Use seeds for fast page loads.** Unseeded images download from picsum.photos on every request. Seeded images are cached locally after the first download.

Full thumb support:

```php
Placeholder::image('hero')->thumb('card')
Placeholder::image('test')->crop(200, 200)
```

Images are cached in `media/playground-source/`. Delete that folder to re-download.

## Configuration

You can enable authentication to restrict access to logged-in Panel users:

```php
// site/config/config.php
return [
  'medienbaecker.playground.auth' => true
];
```

Playground pages are wrapped in `snippet('head')` and `snippet('bottom')`. If your site uses other names, override `site/snippets/playground-ui/head.php` and `bottom.php`. The same works for `playground-ui/overview`, `playground-ui/header`, `playground-ui/component`, `playground-ui/case` and `playground-ui/not-found`.

## Directory Structure

```
assets/
├── css/
│   └── playground/
│       ├── playground.css
│       └── your-component.css
├── js/
│   └── playground/
│       └── your-component.js
site/
├── snippets/
│   └── playground/
│       └── your-component.php
```
