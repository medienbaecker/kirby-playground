<?php

namespace Medienbaecker\Playground;

use Kirby\Cms\File;

/**
 * Placeholder image that lives in the media folder instead of a content folder
 */
class VirtualFile extends File
{
	protected string $sourceRoot;

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

	public function mediaToken(): string
	{
		return substr(md5($this->root()), 0, 10);
	}
}
