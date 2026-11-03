<?php

namespace Medienbaecker\Playground;

use Kirby\Cms\Files;

class Placeholder
{
	/**
	 * Random lorem ipsum text (HTML)
	 *
	 * @param array $options {
	 *   words: int|[min,max] - word count (default: 20)
	 *   paragraphs: false|true|int|[min,max] - wrap in <p> (default: false)
	 *   links: false|true|int|[min,max] - insert links (default: false)
	 *   bold: false|true|int|[min,max] - insert bold text (default: false)
	 *   italic: false|true|int|[min,max] - insert italic text (default: false)
	 *   prefix: string - text before lorem ipsum (default: 'Lorem ipsum ')
	 *   suffix: string - text after lorem ipsum (default: '.')
	 * }
	 */
	public static function text(array $options = []): string
	{
		return TextGenerator::text($options);
	}

	/**
	 * Virtual placeholder image as Kirby File object
	 *
	 * @param array|string $options Seed string or options array {
	 *   id: int - specific picsum image ID
	 *   seed: string - seed for consistent random image
	 *   width: int - image width (default: 1200)
	 *   height: int - image height (default: 800)
	 *   content: array - file content fields (alt, caption, etc.)
	 * }
	 */
	public static function image(array|string $options = []): VirtualFile
	{
		return ImageGenerator::image($options);
	}

	/**
	 * Multiple placeholder images as Files collection
	 *
	 * @param int $count Number of images to generate
	 * @param array $options {
	 *   seed: string - base seed (each image appends -0, -1, etc.)
	 *   width: int - image width (default: 1200)
	 *   height: int - image height (default: 800)
	 *   content: array - file content fields (alt, caption, etc.)
	 * }
	 */
	public static function images(int $count = 3, array $options = []): Files
	{
		return ImageGenerator::images($count, $options);
	}
}
