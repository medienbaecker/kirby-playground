<?php

namespace Medienbaecker;

use Kirby\Cms\File;

/**
 * Virtual File for playground images with thumb support.
 * Overrides root/media paths since files live in plugin assets, not content folder.
 */
class PlaygroundFile extends File
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
        return kirby()->root('media') . '/playground/' . $this->variant() . '/' . $this->mediaHash();
    }

    public function mediaUrl(?string $filename = null): string
    {
        $base = url('media/playground/' . $this->variant() . '/' . $this->mediaHash());
        return $filename ? $base . '/' . $filename : $base;
    }

    public function mediaHash(): string
    {
        return $this->mediaToken() . '-' . filemtime($this->root());
    }

    public function mediaToken(): string
    {
        return substr(md5($this->root()), 0, 10);
    }

    protected function variant(): string
    {
        return str_replace(['playground-', '.jpg'], '', $this->filename());
    }
}
