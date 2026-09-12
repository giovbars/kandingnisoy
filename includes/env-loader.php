<?php
/**
 * Minimal .env loader — no Composer package needed.
 * Reads KEY=VALUE lines from a .env file and injects them into
 * getenv()/$_ENV, skipping blank lines and lines starting with #.
 *
 * Include this once (e.g. at the top of config.php or index.php),
 * before any code that calls getenv('OPENWEATHER_API_KEY') etc.
 */
function loadEnv(string $path): void {
    if (!is_file($path)) {
        return; // .env not present (e.g. on a server using real env vars) — that's fine
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2));

        // Strip surrounding quotes if present
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        // Don't override real environment variables that are already set
        if (getenv($name) === false) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}