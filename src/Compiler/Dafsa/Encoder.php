<?php declare(strict_types=1);

namespace Souplette\FusBup\Compiler\Dafsa;

use Souplette\FusBup\Compiler\Utils\ByteArray;

/**
 * @internal
 */
final class Encoder
{
    private \SplObjectStorage $offsets;

    public function encode(Dafsa $dafsa): array
    {
        $output = [];
        $this->offsets = new \SplObjectStorage();
        $sorted = (new TopologicalSort())->process($dafsa);
        foreach (array_reverse($sorted) as $node) {
            $current = \count($output);
            if ($this->shouldEncodePrefix($node, $current)) {
                array_push($output, ...$this->encodePrefix($node));
            } else {
                array_push($output, ...$this->encodeLinks($node, $current));
                array_push($output, ...$this->encodeLabel($node));
            }
            $this->offsets[$node] = \count($output);
        }

        array_push($output, ...$this->encodeLinks($dafsa->rootNode, \count($output)));

        return array_reverse($output);
    }

    /**
     * Encodes a list of children as one, two or three byte offsets.
     *
     * @param Node $node
     * @param int $current
     * @return int[]
     */
    private function encodeLinks(Node $node, int $current): array
    {
        assert($node->children);
        $children = $node->children;
        if (reset($children)->isSink) {
            // This is an <end_label> node and no links follow such nodes
            return [];
        }

        $guess = 3 * \count($children);
        $sortKey = fn($n) => -$this->offsets[$n];
        usort($children, fn($a, $b) => $sortKey($a) <=> $sortKey($b));

        $last = 0;
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

        $child = reset($node->children);
        return (
            !$child->isSink
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
        assert($node->char !== '');
        return array_reverse(ByteArray::fromString($node->char));
    }
}
