<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Utils;

use ju1ius\FusBup\Exception\IdnException;
use ju1ius\FusBup\Utils\Idn;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdnTest extends TestCase
{
    #[DataProvider('toAsciiProvider')]
    public function testToAscii(array|string $input, string $expected): void
    {
        Assert::assertSame($expected, Idn::toAscii($input));
    }

    public static function toAsciiProvider(): iterable
    {
        yield [['faße', 'de'], 'xn--fae-6ka.de'];
        yield ['faße.de', 'xn--fae-6ka.de'];
        yield ['☕.💩.🤟', 'xn--53h.xn--ls8h.xn--7p9h'];
    }

    #[DataProvider('toAsciiErrorsProvider')]
    public function testToAsciiErrors(string $input): void
    {
        $this->expectException(IdnException::class);
        Idn::toAscii($input);
    }

    public static function toAsciiErrorsProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'leading dot' => ['.foo'];
        yield 'empty label' => ['foo..bar'];
    }

    #[DataProvider('toUnicodeProvider')]
    public function testToUnicode(array|string $input, string $expected): void
    {
        Assert::assertSame($expected, Idn::toUnicode($input));
    }

    public static function toUnicodeProvider(): iterable
    {
        yield [['xn--fae-6ka', 'de'], 'faße.de'];
        yield ['xn--fae-6ka.de', 'faße.de'];
        yield ['xn--53h.xn--ls8h.xn--7p9h', '☕.💩.🤟'];
    }

    #[DataProvider('toUnicodeErrorsProvider')]
    public function testToUnicodeErrors(string $input): void
    {
        $this->expectException(IdnException::class);
        Idn::toUnicode($input);
    }

    public static function toUnicodeErrorsProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'leading dot' => ['.foo'];
        yield 'empty label' => ['foo..bar'];
    }
}
