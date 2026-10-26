<?php
// Test a Composer archive through a clean consumer, without development dependencies.
$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/sailthru-package-' . bin2hex(random_bytes(8));
mkdir($temporary, 0700);

function runCommand(array $command, string $directory): void {
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $directory);
    if (!is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Package check command failed: ' . implode(' ', $command));
    }
}

function removeDirectory(string $directory): void {
    foreach (new FilesystemIterator($directory) as $entry) {
        if ($entry->isDir() && !$entry->isLink()) {
            removeDirectory($entry->getPathname());
        } else {
            unlink($entry->getPathname());
        }
    }
    rmdir($directory);
}

try {
    // Build a distributable archive with Composer, then test its contents.
    runCommand(['composer', 'archive', '--format=tar', '--dir=' . $temporary,
        '--file=package', '--no-interaction'], $root);
    $package = $temporary . '/package';
    mkdir($package);
    (new PharData($temporary . '/package.tar'))->extractTo($package);
    $consumer = $temporary . '/consumer';
    mkdir($consumer);
    file_put_contents($consumer . '/composer.json', json_encode([
        'name' => 'sailthru/package-smoke',
        'repositories' => [[
            'type' => 'path', 'url' => $package,
            'options' => ['symlink' => false, 'versions' => [
                'sailthru/sailthru-php5-client' => 'dev-smoke'
            ]]
        ]],
        'require' => ['sailthru/sailthru-php5-client' => 'dev-smoke'],
        'config' => ['allow-plugins' => false]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    runCommand(['composer', 'install', '--no-dev', '--no-scripts', '--no-plugins',
        '--no-interaction', '--prefer-dist'], $consumer);
    runCommand([PHP_BINARY, $root . '/tests/smoke-package.php', $consumer . '/vendor'], $consumer);

    // Optionally retain the exact checked artifact for the existing release process.
    if (isset($argv[1])) {
        $destination = $argv[1];
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        if (!copy($temporary . '/package.tar', $destination . '/sailthru-client.tar')) {
            throw new RuntimeException('Could not retain the checked package archive');
        }
    }
    echo "Built Composer package smoke check passed on PHP " . PHP_VERSION . "\n";
} finally {
    removeDirectory($temporary);
}
