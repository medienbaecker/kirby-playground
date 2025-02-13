# Kirby Playground

![An overview of different button styles](https://github.com/user-attachments/assets/c33d7d9e-70b9-431f-a811-932a1cd62943)

## Usage

1. Create your tests as snippets in `/site/snippets/playground/`
2. Access your tests at `/playground/[component-name]`

For example, if you have a snippet named `buttons.php`, it will be available at `/playground/buttons`.

## Features

- Custom route handling for `/playground/(:any?)`
- Site method `playgroundLinks()` that scans the playground snippets directory and creates a navigation list
- Components are rendered through virtual pages, without requiring actual page files
- Loads CSS files from `assets/css/playground/[component-name].css` and a global `playground.css`
- Built-in fallback to a component list when accessing non-existent components

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
