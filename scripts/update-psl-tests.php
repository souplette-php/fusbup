<?php declare(strict_types=1);

const UPSTREAM_URL = 'https://raw.githubusercontent.com/publicsuffix/list/master/tests/tests.txt';
$tests = readTestCases(UPSTREAM_URL);

$rootDir = dirname(__DIR__);
$outputDir = "{$rootDir}/tests/Resources";

$eTldTests = array_map(function($input) {
    $output = runPsl(['--print-unreg-domain', $input]);
    return [convertValue($input), convertValue($output)];
}, $tests);
writeTestCases("{$outputDir}/etld.json", $eTldTests);

$isETldTests = array_map(function ($input) {
    $output = runPsl(['--is-public-suffix', $input]);
    return [convertValue($input), (bool)$output];
}, $tests);
writeTestCases("{$outputDir}/is-etld.json", $isETldTests);

$eTldPlusOneTests = array_map(function($input) {
    $output = runPsl(['--print-reg-domain', $input]);
    return [convertValue($input), convertValue($output)];
}, $tests);
writeTestCases("{$outputDir}/etld+1.json", $eTldPlusOneTests);

function runPsl(array $args): string
{
    $proc = proc_open(['psl', '--batch', ...$args], [1 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]);
    $status = proc_close($proc);
    if ($status !== 0) {
        exit($status);
    }
    return trim($output);
}

function writeTestCases(string $filename, array $tests): void
{
    $data = json_encode($tests, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    file_put_contents($filename, $data);
}

function readTestCases(string $path): array
{
    $tests = [];
    $lines = new \NoRewindIterator(new \SplFileObject($path));
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '//')) {
            continue;
        }
        [$input,] = explode(' ', $line, 2);
        if ($input === 'null') {
            // null is not an allowed input for this library.
            continue;
        }
        $tests[] = $input;
    }

    return $tests;
}

function convertValue(string $value): ?string
{
    return match ($value) {
        'null', '(null)' => null,
        default => $value,
    };
}
