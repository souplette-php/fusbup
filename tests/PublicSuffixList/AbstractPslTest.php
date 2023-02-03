<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\PublicSuffixList;

use ju1ius\FusBup\PublicSuffixList;
use ju1ius\FusBup\Tests\PslTestProvider;
use ju1ius\FusBup\Utils\Idn;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

abstract class AbstractPslTest extends TestCase
{
    abstract protected static function getList(): PublicSuffixList;

    /**
     * @dataProvider isPublicSuffixProvider
     */
    public function testIsPublicSuffix(string $input, bool $expected): void
    {
        $result = static::getList()->isEffectiveTLD($input);
        Assert::assertSame($expected, $result);
    }

    public static function isPublicSuffixProvider(): iterable
    {
        yield from PslTestProvider::isPublic();
    }

    /**
     * @dataProvider getPublicSuffixProvider
     */
    public function testGetPublicSuffix(string $input, string $expected): void
    {
        $result = static::getList()->getEffectiveTLD($input);
        Assert::assertSame($expected, $result);
    }

    /**
     * @dataProvider getPublicSuffixProvider
     */
    public function testSplitPublicSuffix(string $input, string $suffix): void
    {
        [$private, $public] = static::getList()->splitEffectiveTLD($input);
        Assert::assertSame($suffix, $public);
        $inputCanonical = Idn::toUnicode($input);
        $domain = $private ? "{$private}.{$public}" : $public;
        Assert::assertSame($inputCanonical, $domain);
    }

    public static function getPublicSuffixProvider(): iterable
    {
        yield from self::filterPslTests(PslTestProvider::unregistrable(), false);
    }

    /**
     * @dataProvider getRegistrableDomainProvider
     */
    public function testGetRegistrableDomain(string $input, ?string $expected): void
    {
        $result = static::getList()->getRegistrableDomain($input);
        Assert::assertSame($expected, $result);
    }

    /**
     * @dataProvider getRegistrableDomainProvider
     */
    public function testSplitRegistrableDomain(string $input, ?string $expected): void
    {
        $result = static::getList()->splitRegistrableDomain($input);
        if ($expected === null) {
            Assert::assertNull($result);
        } else {
            [$head, $tail] = $result;
            Assert::assertSame($expected, $tail);
            $inputCanonical = Idn::toUnicode($input);
            $domain = $head ? "{$head}.{$tail}" : $tail;
            Assert::assertSame($inputCanonical, $domain);
        }
    }

    public static function getRegistrableDomainProvider(): iterable
    {
        yield from self::filterPslTests(PslTestProvider::registrable());
    }

    /**
     * @dataProvider isCookieDomainAcceptableProvider
     */
    public function testIsCookieDomainAcceptable(string $requestDomain, string $cookieDomain, bool $expected): void
    {
        $result = static::getList()->isCookieDomainAcceptable($requestDomain, $cookieDomain);
        Assert::assertSame($expected, $result);
    }

    /**
     * Test cases ported from:
     * @link https://github.com/rockdaboot/libpsl/blob/master/tests/test-is-cookie-domain-acceptable.c
     */
    public static function isCookieDomainAcceptableProvider(): iterable
    {
        yield ['www.dkg.forgot.his.name', 'www.dkg.forgot.his.name', true];
        yield ['www.dkg.forgot.his.name', 'dkg.forgot.his.name', true];
        yield ['www.dkg.forgot.his.name', 'forgot.his.name', false];
        yield ['www.dkg.forgot.his.name', 'his.name', false];
        yield ['www.dkg.forgot.his.name', 'name', false];
        yield ['www.his.name', 'www.his.name', true];
        yield ['www.his.name', 'his.name', true];
        yield ['www.his.name', 'name', false];
        yield ['www.example.com', 'www.example.com', true];
        yield ['www.example.com', 'wwww.example.com', false];
        yield ['www.example.com', 'example.com', true];
        // not accepted by normalization (PSL rule 'com')
        yield ['www.example.com', 'com', false];
        yield ['www.example.com', 'example.org', false];
        // not accepted by normalization  (PSL rule '*.ar')
        yield ['www.sa.gov.au', 'sa.gov.au', false];
        // PSL exception rule '!educ.ar'
        yield ['www.educ.ar', 'educ.ar', true];
        // RFC6265 5.1.3: Having IP addresses, request and domain IP must be identical
        // IPv4 address, partial match
        yield ['192.1.123.2', '.1.123.2', false];
        // IPv4 address, full match
        yield ['192.1.123.2', '192.1.123.2', true];
        // IPv6 address, full match
        yield ['::1', '::1', true];
        // IPv6 address, partial match
        yield ['2a00:1450:4013:c01::8b', ':1450:4013:c01::8b', false];
        // IPv6 address dotted-quad, full match
        yield ['::ffff:192.1.123.2', '::ffff:192.1.123.2', true];
        // IPv6 address dotted-quad, partial match
        yield ['::ffff:192.1.123.2', '.1.123.2', false];
        //yield [null, '.1.123.2', false];
        //yield ['hiho', null, false];
    }

    private static function filterPslTests(iterable $tests, bool $allowNullResult = true): \Traversable
    {
        $i = 0;
        foreach ($tests as [$input, $expected]) {
            // filter out invalid input and expected errors
            if (str_starts_with($input, '.')) {
                continue;
            }
            if (!$allowNullResult && \is_null($expected)) {
                continue;
            }
            $i++;
            // libpsl returns results in their original form,
            // but we return them in canonicalized unicode form.
            $expected = $expected ? Idn::toUnicode($expected) : null;
            $key = sprintf(
                '#%d %s => %s',
                $i,
                $input,
                var_export($expected, true),
            );
            yield $key => [$input, $expected];
        }
    }
}
