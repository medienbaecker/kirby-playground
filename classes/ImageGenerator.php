<?php

namespace Medienbaecker\Playground;

use Kirby\Cms\Files;
use Kirby\Filesystem\F;
use Kirby\Http\Remote;

class ImageGenerator
{
    /**
     * Get source directory for cached images
     */
    public function sourceDir(): string
    {
        return kirby()->root('media') . '/plugins/kirby-playground/source';
    }

    /**
     * Virtual placeholder image as Kirby File object
     */
    public function generate(array|string $options = []): VirtualFile
    {
        if (is_string($options)) {
            $options = ['seed' => $options];
        }

        $id = $options['id'] ?? null;
        $seed = $options['seed'] ?? null;
        $width = max(100, min(2000, $options['width'] ?? 1200));
        $height = max(100, min(2000, $options['height'] ?? 800));
        $content = array_merge(['alt' => 'Placeholder image'], $options['content'] ?? []);

        // Generate cache ID based on what makes this image unique
        if ($id !== null) {
            $cacheId = md5("id:{$id}:{$width}:{$height}");
        } elseif ($seed !== null) {
            $cacheId = md5("seed:{$seed}:{$width}:{$height}");
        } else {
            $cacheId = md5(uniqid('', true));
        }

        $path = $this->sourceDir() . "/{$cacheId}.jpg";

        if (!file_exists($path)) {
            $this->fetchImage($cacheId, $width, $height, $seed, $id);
        }

        return new VirtualFile([
            'filename' => "{$cacheId}.jpg",
            'parent'   => site(),
            'root'     => $path,
            'url'      => url("playground/image/{$cacheId}.jpg"),
            'content'  => $content
        ]);
    }

    /**
     * Multiple placeholder images as Files collection
     */
    public function generateMultiple(int $count = 3, array $options = []): Files
    {
        $files = [];
        $baseSeed = $options['seed'] ?? null;

        for ($i = 0; $i < $count; $i++) {
            $opts = $options;
            unset($opts['id']);

            if ($baseSeed !== null) {
                $opts['seed'] = "{$baseSeed}-{$i}";
            }

            $files[] = $this->generate($opts);
        }

        return new Files($files);
    }

    /**
     * Download image from picsum.photos and cache it
     */
    private function fetchImage(string $cacheId, int $width, int $height, ?string $seed = null, ?int $id = null): void
    {
        $dir = $this->sourceDir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \Exception('Cannot create cache directory: ' . $dir);
        }

        if ($id !== null) {
            $url = "https://picsum.photos/id/{$id}/{$width}/{$height}";
        } elseif ($seed !== null) {
            $url = "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
        } else {
            $url = "https://picsum.photos/{$width}/{$height}";
        }

        $response = Remote::get($url, ['timeout' => 10]);

        if ($response->code() !== 200) {
            throw new \Exception(
                "Failed to download from picsum.photos (HTTP {$response->code()}). " .
                    "Check your internet connection."
            );
        }

        $content = $response->content();
        if (empty($content)) {
            throw new \Exception('Empty response from picsum.photos');
        }

        F::write($dir . "/{$cacheId}.jpg", $content);
    }
}
