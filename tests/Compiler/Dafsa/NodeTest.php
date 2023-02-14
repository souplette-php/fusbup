<?php declare(strict_types=1);

namespace Souplette\FusBup\Tests\Compiler\Dafsa;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Souplette\FusBup\Compiler\Dafsa\Node;

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
