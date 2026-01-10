# Kirby Playground

![An overview of different button styles](https://github.com/user-attachments/assets/c33d7d9e-70b9-431f-a811-932a1cd62943)

## Usage

1. Create your tests as snippets in `/site/snippets/playground/`
2. Access your tests at `/playground/[component-name]`

For example, if you have a snippet named `buttons.php`, it will be available at `/playground/buttons`.

## Features

- Custom route handling for `/playground/(:any?)` that can optionally be restricted to logged-in users
- Components are rendered through virtual pages, without requiring actual page files
- Loads CSS files from `assets/css/playground/[component-name].css` and a global `playground.css`
- Built-in fallback to a component list when accessing non-existent components
- Placeholder helpers for lorem ipsum text and images

## Placeholder Content

Generate placeholder content directly in your playground snippets (or anywhere else) by using the `Playground` class:

```php
use Medienbaecker\Playground;
```

### Text

```php
Playground::text([
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
Playground::text(['words' => 5, 'prefix' => false, 'suffix' => false])
```

This way you will get random words, _not_ starting with Lorem ipsum and _not_ ending with a period.

### Images

Returns virtual Kirby File objects with full thumb support. Images are downloaded from [picsum.photos](https://picsum.photos) and cached locally.

```php
// Seeded image (recommended - cached locally)
Playground::image('hero')  // shorthand
Playground::image(['seed' => 'hero'])
Playground::image(['seed' => 'card', 'width' => 400, 'height' => 300])

// Specific image by picsum ID
Playground::image(['id' => 237])

// Multiple images
Playground::images(6, ['seed' => 'gallery'])  // gallery-0, gallery-1, ...
Playground::images(4, ['width' => 400, 'height' => 300, 'seed' => 'thumbs'])

// Random image (downloads fresh each page load - slow!)
Playground::image()
Playground::images(6)
```

**Use seeds for fast page loads.** Unseeded images download from picsum.photos on every request. Seeded images are cached locally after the first download.

Full thumb support:

```php
Playground::image('hero')->thumb('card')
Playground::image('test')->crop(200, 200)
```

Images are cached in `media/plugins/kirby-playground/source/`. Clear the media folder to re-download.

## Configuration

You can enable authentication to restrict access to logged-in Panel users:

```php
// site/config/config.php
return [
  'medienbaecker.playground.auth' => true
];
```

## Directory Structure

```
assets/
├── css/
│   └── playground/
│       ├── playground.css
│       └── your-component.css
site/
├── snippets/
│   └── playground/
│       └── your-component.php
```
