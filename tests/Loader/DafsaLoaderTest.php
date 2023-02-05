<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Loader;

use ju1ius\FusBup\Exception\LoaderException;
use ju1ius\FusBup\Loader\DafsaLoader;
use ju1ius\FusBup\Tests\ResourceHelper;
use PHPUnit\Framework\TestCase;

final class DafsaLoaderTest extends TestCase
{
    public function testExceptionOnInvalidHeader(): void
    {
        ResourceHelper::tmp(__METHOD__, function(string $tmp) {
            file_put_contents($tmp, "NOT_A_DAFSA!   \nxxxxx");
            $this->expectException(LoaderException::class);
            (new DafsaLoader($tmp))->load();
        });
    }
}
