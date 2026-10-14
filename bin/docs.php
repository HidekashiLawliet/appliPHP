<?php

/**
 * Generates the PHPDoc of src/ into docs/api: `composer docs`.
 *
 * phpDocumentor fails when its own path or the project path contains spaces
 * (e.g. "C:\Program Files\php" or "appli MVC PHP"). This script therefore
 * copies the sources to a temporary folder, runs phpDocumentor there, then
 * copies the generated HTML back. The phar is downloaded on first use.
 */

declare(strict_types=1);

const PHAR_URL = 'https://github.com/phpDocumentor/phpDocumentor/releases/download/v3.10.0/phpDocumentor.phar';

$root = dirname(__DIR__);
$phar = $root . '/tools/phpDocumentor.phar';
$target = $root . '/docs/api';

/**
 * Copies a directory recursively, skipping the given sub-folders.
 *
 * @param list<string> $skip Folder names to leave out.
 */
function copyDirectory(string $from, string $to, array $skip = []): void
{
    if (!is_dir($to) && !mkdir($to, 0777, true)) {
        throw new RuntimeException("Impossible de creer {$to}");
    }
    foreach (scandir($from) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..' || in_array($entry, $skip, true)) {
            continue;
        }
        $source = $from . '/' . $entry;
        is_dir($source) ? copyDirectory($source, $to . '/' . $entry) : copy($source, $to . '/' . $entry);
    }
}

/**
 * Deletes a directory and its content.
 */
function removeDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            $path = $dir . '/' . $entry;
            is_dir($path) ? removeDirectory($path) : unlink($path);
        }
    }
    rmdir($dir);
}

if (!is_file($phar)) {
    echo "Telechargement de phpDocumentor...\n";
    if (!is_dir(dirname($phar))) {
        mkdir(dirname($phar), 0777, true);
    }
    if (!copy(PHAR_URL, $phar)) {
        fwrite(STDERR, "Echec du telechargement de " . PHAR_URL . "\n");
        exit(1);
    }
}

$work = sys_get_temp_dir() . '/tpak-phpdoc-' . bin2hex(random_bytes(4));
try {
    copyDirectory($root . '/src', $work . '/src', ['Views']);
    copy($phar, $work . '/phpDocumentor.phar');

    $process = proc_open(
        [PHP_BINARY, 'phpDocumentor.phar', 'run', '-d', 'src', '-t', 'api', '--cache-folder', 'cache',
            '--title', 'Touche pas au klaxon', '--visibility', 'public,protected', '--no-progress'],
        [STDIN, STDOUT, STDERR],
        $pipes,
        $work
    );
    if ($process === false || proc_close($process) !== 0) {
        throw new RuntimeException('phpDocumentor a echoue.');
    }

    removeDirectory($target);
    copyDirectory($work . '/api', $target);
    echo "Documentation generee dans docs/api/index.html\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
} finally {
    removeDirectory($work);
}
