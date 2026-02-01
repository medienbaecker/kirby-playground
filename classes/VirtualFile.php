<?php

namespace Medienbaecker\Playground;

use Kirby\Cms\File;
use Kirby\Filesystem\F;

/**
 * Virtual File for playground images with thumb support.
 * Overrides root/media paths since files live in media cache, not content folder.
 */
class VirtualFile extends File
{
    protected string|null $customRoot = null;

    public function __construct(array $props)
    {
        $this->customRoot = $props['root'] ?? null;
        parent::__construct($props);
    }

    public function root(): string|null
    {
        return $this->customRoot;
    }

    public function mediaDir(): string
    {
        return kirby()->root('media') . '/playground/' . $this->mediaHash();
    }

    public function mediaUrl(?string $filename = null): string
    {
        $base = url('media/playground/' . $this->mediaHash());
        return $filename ? $base . '/' . $filename : $base;
    }

    public function mediaHash(): string
    {
        return $this->mediaToken() . '-' . $this->modifiedFile();
    }

    public function mediaToken(): string
    {
        return substr(md5($this->root()), 0, 10);
    }

    protected function modifiedFile(): int
    {
        // Use content hash instead of mtime (stable for downloaded files)
        return crc32(F::read($this->root()));
    }
}
