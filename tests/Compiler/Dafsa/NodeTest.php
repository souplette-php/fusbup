<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa;

use ju1ius\FusBup\Compiler\Dafsa\Node;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class NodeTest extends TestCase
{
    public function testNodeCharacterCannotBeEmpty(): void
    {
        $this->expectException(\LogicException::class);
        Node::of('');
    }

    public function testNodeWithoutParentCannotBeFork(): void
    {
        $node = Node::of('a');
        Assert::assertFalse($node->isFork());
    }
}
