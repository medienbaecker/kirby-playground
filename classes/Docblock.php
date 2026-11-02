<?php

namespace Medienbaecker\Playground;

use Kirby\Toolkit\Str;

class Docblock
{
    /**
     * @param string $description Everything between the summary paragraph and the first tag
     * @param array<array{0: string, 1: string}> $tags Name and value of every tag, in file order
     */
    public function __construct(
        public readonly string $description = '',
        public readonly array $tags = []
    ) {
    }

    /**
     * Parses the first docblock in a file
     */
    public static function fromFile(string $path): static
    {
        if (preg_match('!/\*\*(.*?)\*/!s', file_get_contents($path), $match) !== 1) {
            return new static();
        }

        $lines = array_map(
            fn ($line) => rtrim(preg_replace('/^\s*\*?\s?/', '', $line)),
            explode("\n", $match[1])
        );

        $text = [];
        $tags = [];

        foreach ($lines as $line) {
            if (preg_match('/^@([\w-]+)\s*(.*)$/', $line, $tag) === 1) {
                $tags[] = [$tag[1], $tag[2]];
            } elseif ($tags !== [] && $line !== '') {
                $tags[array_key_last($tags)][1] .= ' ' . trim($line);
            } elseif ($tags === []) {
                $text[] = $line;
            }
        }

        return new static(trim(Str::after(trim(implode("\n", $text)), "\n\n")), $tags);
    }

    /**
     * All values of one tag
     *
     * @return array<string>
     */
    public function tag(string $name): array
    {
        return array_values(array_map(
            fn ($tag) => $tag[1],
            array_filter($this->tags, fn ($tag) => $tag[0] === $name)
        ));
    }

    /**
     * The `@var` tags as props
     *
     * @return array<array{name: string, type: string, description: string}>
     */
    public function props(): array
    {
        $props = [];

        foreach ($this->tag('var') as $value) {
            if (preg_match('/^(.*?)\s*\$(\w+)\s*(.*)$/', $value, $match) === 1) {
                $props[] = [
                    'name'        => $match[2],
                    'type'        => $match[1],
                    'description' => $match[3],
                ];
            }
        }

        return $props;
    }
}
