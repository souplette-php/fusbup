<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa;

use ju1ius\FusBup\Compiler\ByteArray;
use ju1ius\FusBup\Compiler\Dafsa\DafsaCompiler;
use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\Tests\GPerfParser;
use ju1ius\FusBup\Tests\ResourceHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class DafsaCompilerTest extends TestCase
{
    private function toByteArray(array $rules, bool $reverse = false): array
    {
        $output = (new DafsaCompiler())->compile($rules, $reverse);
        return ByteArray::fromDafsa($output);
    }

    /**
     * @dataProvider compileMatchesBytesProvider
     */
    public function testCompileMatchesBytes(array $input, array $expected, bool $reverse = false): void
    {
        $output = self::toByteArray($input, $reverse);
        Assert::assertSame($expected, $output);
    }

    public static function compileMatchesBytesProvider(): iterable
    {
        yield 'two simple overlapping rules' => [
            [Rule::pub('uk'), Rule::pub('co.uk')],
            [0x02, 0x83, 0x63, 0x6f, 0x2e, 0x75, 0x6b, 0x80,],
            false,
        ];
        yield 'two simple overlapping rules, reversed' => [
            [Rule::pub('uk'), Rule::pub('co.uk')],
            [0x81, 0x6b, 0xf5, 0x02, 0x83, 0x2e, 0x6f, 0x63, 0x80],
            true,
        ];
    }

    /**
     * @medium
     * @dataProvider compileMatchesChromiumImplementationProvider
     */
    public function testCompileMatchesChromiumImplementation(string $inputFile, string $dafsaFile): void
    {
        $words = GPerfParser::parse($inputFile);
        $result = (new DafsaCompiler())->compileWords($words);
        $expected = require $dafsaFile;
        Assert::assertSame($expected, ByteArray::fromDafsa($result));
    }

    public static function compileMatchesChromiumImplementationProvider(): iterable
    {
        foreach (ResourceHelper::glob('dafsa/*.gperf') as $inputFile) {
            $testName = basename($inputFile, '.gperf');
            $dafsaFile = ResourceHelper::path("dafsa/{$testName}.php");
            yield $testName => [$inputFile, $dafsaFile];
        }
    }
}
