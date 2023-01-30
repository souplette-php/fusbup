<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa;

final class Node
{
    private static self $sink;

    private function __construct(
        public ?string $value = null,
        public array $children = [],
    ) {
    }

    public static function of(string $value, array $children = []): self
    {
        return new self($value, $children);
    }

    public static function sink(): self
    {
        return self::$sink ??= new self();
    }

    public static function terminal(string $value): self
    {
        return new self($value, [self::sink()]);
    }

    public function isSink(): bool
    {
        return $this === self::sink();
    }

    /**
     * Generates list of unique words from all paths starting from this node.
     * @return string[]
     */
    public function toWords(): array
    {
        if ($this->isSink()) {
            return [''];
        }

        $words = [];
        foreach ($this->children as $child) {
            foreach ($child->toWords() as $word) {
                $words[] = $this->value . $word;
            }
        }

        return array_unique($words, \SORT_STRING);
    }
}
