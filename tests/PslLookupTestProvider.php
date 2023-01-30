<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests;

use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\Parser\RuleType;

final class PslLookupTestProvider
{
    public static function splitCases(): iterable
    {
        yield 'no match uses * as default' => [
            [new Rule('a.b'), new Rule('b.c')],
            'foo.bar',
            [['foo'], ['bar']]
        ];
        yield 'single non-ambiguous match' => [
            [new Rule('a.b'), new Rule('b.c')],
            'foo.bar.b.c',
            [['foo', 'bar'], ['b', 'c']],
        ];
        yield 'labels are canonicalized' => [
            [new Rule('aérôpört.ci')],
            'VAMOS.AL.AÉRÔPÖRT.CI',
            [['vamos', 'al'], ['xn--arprt-bsa2fra', 'ci']],
        ];
        yield 'several matches' => [
            [new Rule('uk'), new Rule('co.uk')],
            'a.b.co.uk',
            [['a', 'b'], ['co', 'uk']],
        ];
        yield 'several matches, rule order is irrelevant' => [
            [new Rule('co.uk'), new Rule('uk')],
            'a.b.co.uk',
            [['a', 'b'], ['co', 'uk']],
        ];
        yield 'wildcard rule' => [
            [new Rule('com', RuleType::Wildcard)],
            'a.b.com',
            [['a'], ['b', 'com']],
        ];
        yield 'wildcard rule when nothing matches *' => [
            [new Rule('foo.com', RuleType::Wildcard)],
            'foo.com',
            [[], ['foo', 'com']],
        ];
        yield 'exclusion rule wins over wildcard' => [
            [new Rule('test', RuleType::Wildcard), new Rule('www.test', RuleType::Exception)],
            'www.test',
            [['www'], ['test']],
        ];
        yield 'exclusion rule' => [
            [
                new Rule('com'),
                new Rule('yep.com', RuleType::Wildcard),
                new Rule('nope.yep.com', RuleType::Exception),
            ],
            'nope.yep.com',
            [['nope'], ['yep', 'com']],
        ];
    }
}
