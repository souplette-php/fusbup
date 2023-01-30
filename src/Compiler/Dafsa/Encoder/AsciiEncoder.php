<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa\Encoder;

use ju1ius\FusBup\Compiler\ByteArray;
use ju1ius\FusBup\Compiler\Dafsa\Node;
use ju1ius\FusBup\Compiler\Dafsa\Optimization\TopologicalSort;

/**
 * @internal
 */
final class AsciiEncoder implements EncoderInterface
{
    private \SplObjectStorage $offsets;

    public function encode(array $nodes): array
    {
        $output = [];
        $this->offsets = new \SplObjectStorage();
        $sorted = (new TopologicalSort())->process($nodes);
        foreach (array_reverse($sorted) as $node) {
            $current = \count($output);
            if ($this->shouldEncodePrefix($node, $current)) {
                array_push($output, ...$this->encodePrefix($node));
            } else {
                array_push($output, ...$this->encodeLinks($node->children, $current));
                array_push($output, ...$this->encodeLabel($node));
            }
            $this->offsets[$node] = \count($output);
        }

        array_push($output, ...$this->encodeLinks($nodes, \count($output)));

        return array_reverse($output);
    }

    /**
     * Encodes a list of children as one, two or three byte offsets.
     *
     * @param Node[] $children
     * @param int $current
     * @return int[]
     */
    private function encodeLinks(array $children, int $current): array
    {
        assert($children);
        if ($children[0]->isSink()) {
            // This is an <end_label> node and no links follow such nodes
            assert(\count($children) === 1);
            return [];
        }

        $guess = 3 * \count($children);
        // todo: check sort order
        $sortKey = fn($n) => -$this->offsets[$n];
        usort($children, fn($a, $b) => $sortKey($a) <=> $sortKey($b));

        while (true) {
            $offset = $current + $guess;
            $buf = [];
            foreach ($children as $child) {
                $last = \count($buf);
                $distance = $offset - $this->offsets[$child];
                assert($distance > 0 && $distance < (1 << 21));
                if ($distance < (1 << 6)) {
                    // A 6-bit offset: "s0xxxxxx"
                    $buf[] = $distance;
                } elseif ($distance < (1 << 13)) {
                    // A 13-bit offset: "s10xxxxxxxxxxxxx"
                    $buf[] = 0x40 | ($distance >> 8);
                    $buf[] = $distance & 0xFF;
                } else {
                    // A 21-bit offset: "s11xxxxxxxxxxxxxxxxxxxxx"
                    $buf[] = 0x60 | ($distance >> 16);
                    $buf[] = ($distance >> 8) & 0xFF;
                    $buf[] = $distance & 0xFF;
                }
                // Distance in first link is relative to following record.
                // Distance in other links are relative to previous link.
                $offset -= $distance;
            }
            if ($guess === \count($buf)) {
                break;
            }
            $guess = \count($buf);
        }
        // Set most significant bit to mark end of links in this node.
        $buf[$last] |= (1 << 7);
        return array_reverse($buf);
    }

    /**
     * Encodes a node label as a list of bytes with a trailing high byte >0x80.
     * @param Node $node
     * @return int[]
     */
    private function encodeLabel(Node $node): array
    {
        $bytes = $this->encodePrefix($node);
        // Set most significant bit to mark end of label in this node.
        $bytes[0] |= (1 << 7);
        return $bytes;
    }

    private function shouldEncodePrefix(Node $node, int $currentOffset): bool
    {
        if (\count($node->children) !== 1) {
            return false;
        }

        $child = $node->children[0];
        return (
            !$child->isSink()
            && $this->offsets->contains($child)
            && $this->offsets[$child] === $currentOffset
        );
    }

    /**
     * Encodes a node label as a list of bytes without a trailing high byte.
     * This method encodes a node if there is exactly one child
     * and the child follows immediately after so that no jump is needed.
     * This label will then be a prefix to the label in the child node.
     *
     * @param Node $node
     * @return int[]
     */
    private function encodePrefix(Node $node): array
    {
        assert($node->value);
        return array_reverse(ByteArray::fromString($node->value));
    }
}
