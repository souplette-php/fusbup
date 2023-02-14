<?php declare(strict_types=1);

namespace Souplette\FusBup\Tests\Loader;

use PHPUnit\Framework\TestCase;
use Souplette\FusBup\Exception\LoaderException;
use Souplette\FusBup\Loader\DafsaLoader;
use Souplette\FusBup\Tests\ResourceHelper;

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
