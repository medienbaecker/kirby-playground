<?php

namespace Medienbaecker\Playground;

use Kirby\Cms\File;
use Kirby\Filesystem\F;

/**
 * Placeholder image that lives in the media folder instead of a content folder
 */
class VirtualFile extends File
{
    protected string $sourceRoot;
    protected string|null $mediaHash = null;

    public function __construct(array $props)
    {
        $this->sourceRoot = $props['root'];
        parent::__construct($props);
    }

    public function root(): string
    {
        return $this->sourceRoot;
    }

    public function mediaDir(): string
    {
        return kirby()->root('media') . '/playground/' . $this->mediaHash();
    }

    public function mediaUrl(string|null $filename = null): string
    {
        return url('media/playground/' . $this->mediaHash() . '/' . ($filename ?? $this->filename()));
    }

    public function mediaHash(): string
    {
        return $this->mediaHash ??= $this->mediaToken() . '-' . $this->modifiedFile();
    }

    public function mediaToken(): string
    {
        return substr(md5($this->root()), 0, 10);
    }

    protected function modifiedFile(): int
    {
        return crc32(F::read($this->root()));
    }
}
