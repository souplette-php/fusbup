<?php declare(strict_types=1);

use ju1ius\FusBup\Compiler\PslCompiler;
use ju1ius\FusBup\Parser\PslParser;

require_once __DIR__ . '/../vendor/autoload.php';

$rootDir = dirname(__DIR__);
$dataFile = new \SplFileObject("{$rootDir}/data/public_suffix_list.dat");
$ast = (new PslParser())->parse($dataFile);
$code = (new PslCompiler())->compileToFile($ast);

file_put_contents("{$rootDir}/src/Resources/psl.php", $code);
