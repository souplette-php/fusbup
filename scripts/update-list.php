<?php declare(strict_types=1);

use ju1ius\FusBup\Compiler\DafsaCompiler;
use ju1ius\FusBup\Compiler\Parser\RuleList;
use ju1ius\FusBup\Compiler\PslParser;
use ju1ius\FusBup\Compiler\SuffixTreeCompiler;

require_once __DIR__ . '/../vendor/autoload.php';

const LIST_URL = 'https://publicsuffix.org/list/public_suffix_list.dat';

$dataFile = new \SplFileObject($argv[1] ?? LIST_URL);
$ast = (new PslParser())->parse($dataFile);

$rootDir = dirname(__DIR__);
compileTree($ast, "{$rootDir}/src/Resources/psl.php");
compileDafsa($ast, "{$rootDir}/src/Resources/psl.dafsa");

exit(0);


function compileTree(RuleList $rules, string $path): void
{
    $code = (new SuffixTreeCompiler())->compile($rules);
    file_put_contents($path, $code);
}

function compileDafsa(RuleList $rules, string $path): void
{
    $graph = (new DafsaCompiler())->compile($rules, true);
    file_put_contents($path, $graph);
}
