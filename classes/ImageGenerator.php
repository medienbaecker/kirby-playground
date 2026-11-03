<?php

namespace Medienbaecker\Playground;

use Exception;
use Kirby\Cms\Files;
use Kirby\Filesystem\F;
use Kirby\Http\Remote;

class ImageGenerator
{
    /**
     * Folder of the downloaded source images
     */
    public static function sourceDir(): string
    {
        return kirby()->root('media') . '/playground-source';
    }

    /**
     * Placeholder image from picsum.photos, downloaded on first use
     */
    public static function image(array|string $options = []): VirtualFile
    {
        if (is_string($options)) {
            $options = ['seed' => $options];
        }

        $id     = $options['id'] ?? null;
        $seed   = $options['seed'] ?? null;
        $width  = max(100, min(2000, $options['width'] ?? 1200));
        $height = max(100, min(2000, $options['height'] ?? 800));

        if ($id !== null) {
            $cacheId = md5("id:{$id}:{$width}:{$height}");
            $path    = "id/{$id}/";
        } elseif ($seed !== null) {
            $cacheId = md5("seed:{$seed}:{$width}:{$height}");
            $path    = "seed/{$seed}/";
        } else {
            $cacheId = md5((string) mt_rand());
            $path    = '';
        }

        $file = static::file($cacheId, ['alt' => 'Placeholder image', ...($options['content'] ?? [])]);

        if (!$file->exists()) {
            $response = Remote::get("https://picsum.photos/{$path}{$width}/{$height}", ['timeout' => 10]);

            if ($response->code() !== 200) {
                throw new Exception("Failed to download from picsum.photos (HTTP {$response->code()}). Check your internet connection.");
            }

            F::write($file->root(), $response->content());
        }

        return $file;
    }

    /**
     * Placeholder images, each with its own seed
     */
    public static function images(int $count = 3, array $options = []): Files
    {
        $seed  = $options['seed'] ?? null;
        $files = [];

        for ($index = 0; $index < $count; $index++) {
            $files[] = static::image([
                ...$options,
                'id'   => null,
                'seed' => $seed === null ? null : "{$seed}-{$index}",
            ]);
        }

        return new Files($files);
    }

    /**
     * The source image with this cache id as a Kirby File object
     */
    public static function file(string $cacheId, array $content = []): VirtualFile
    {
        return new VirtualFile([
            'filename' => "{$cacheId}.jpg",
            'parent'   => site(),
            'root'     => static::sourceDir() . "/{$cacheId}.jpg",
            'url'      => url("playground/image/{$cacheId}.jpg"),
            'content'  => $content,
        ]);
    }
}
