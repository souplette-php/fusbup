<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa;

final class Dafsa implements \IteratorAggregate
{
    public Node $rootNode;
    public Node $sinkNode;

    public function __construct()
    {
        $this->rootNode = Node::source();
        $this->sinkNode = Node::sink();
    }

    /**
     * @param string[] $words
     */
    public static function of(array $words): self
    {
        $dafsa = new self();
        foreach ($words as $word) {
            $dafsa->append($word);
        }

        return $dafsa;
    }

    public function append(string $word): void
    {
        $machine = new AppendStateMachine($word, $this->rootNode, $this->sinkNode);
        $machine->run();
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->rootNode->children as $child) {
            yield $child;
        }
    }
}
