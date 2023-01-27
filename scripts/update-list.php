<?php declare(strict_types=1);

use ju1ius\FusBup\Compiler\PslCompiler;
use ju1ius\FusBup\Parser\PslParser;

require_once __DIR__ . '/../vendor/autoload.php';

const LIST_URL = 'https://publicsuffix.org/list/public_suffix_list.dat';

$dataFile = new \SplFileObject(LIST_URL);
$ast = (new PslParser())->parse($dataFile);
$code = (new PslCompiler())->compileToFile($ast);

$rootDir = dirname(__DIR__);
file_put_contents("{$rootDir}/src/Resources/psl.php", $code);

exit(0);
