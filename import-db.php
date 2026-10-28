<?php
/**
 * TEMPORARY DATABASE IMPORT TOOL  --  DELETE AFTER USE
 * Imports every .sql file in ./_import (in name order) using the
 * DB credentials from your .env / config.php.
 * Open:  https://YOURSITE/import-db.php?key=f8abfb7933a88802
 * It deletes itself and the .sql files after a successful import.
 */
const IMPORT_KEY = 'f8abfb7933a88802';

if (!hash_equals(IMPORT_KEY, (string)($_GET['key'] ?? ''))) {
    http_response_code(404);
    exit('Not found');
}

require_once __DIR__ . '/config/config.php';   // gives us $pdo
header('Content-Type: text/html; charset=utf-8');

/** Split SQL text into statements; respects quotes and comments. */
function splitSql(string $sql): array {
    $out = []; $buf = ''; $len = strlen($sql);
    $q = null; // current quote char
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i]; $n = $sql[$i + 1] ?? '';
        if ($q !== null) {
            $buf .= $c;
            if ($c === '\\' && $q !== '`') { $buf .= $n; $i++; continue; }
            if ($c === $q) {
                if ($n === $q) { $buf .= $n; $i++; continue; } // doubled quote
                $q = null;
            }
            continue;
        }
        if ($c === "'" || $c === '"' || $c === '`') { $q = $c; $buf .= $c; continue; }
        if ($c === '-' && $n === '-' && ($sql[$i + 2] ?? ' ') <= ' ') {      // -- comment
            while ($i < $len && $sql[$i] !== "\n") $i++;
            continue;
        }
        if ($c === '#') { while ($i < $len && $sql[$i] !== "\n") $i++; continue; }
        if ($c === '/' && $n === '*') {                                          // /* comment */
            $end = strpos($sql, '*/', $i + 2);
            $i = ($end === false) ? $len : $end + 1;
            continue;
        }
        if ($c === ';') {
            if (trim($buf) !== '') $out[] = trim($buf);
            $buf = '';
            continue;
        }
        $buf .= $c;
    }
    if (trim($buf) !== '') $out[] = trim($buf);
    return $out;
}

$dir   = __DIR__ . '/_import';
$files = glob($dir . '/*.sql') ?: [];
sort($files);

echo '<!doctype html><meta charset="utf-8"><title>DB Import</title>'
   . '<body style="font-family:system-ui;max-width:760px;margin:30px auto;padding:0 16px">'
   . '<h2>GoatPro - Database Import</h2>';

if (!$files) { echo '<p>No .sql files found in <code>_import/</code>.</p>'; exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<p>Database: <b>' . htmlspecialchars(DB_NAME) . '</b></p><p>Files to import, in order:</p><ul>';
    foreach ($files as $f) echo '<li>' . htmlspecialchars(basename($f)) . ' (' . round(filesize($f)/1024) . ' KB)</li>';
    echo '</ul><form method="post"><button style="padding:10px 18px;font-size:16px">Import now</button></form>'
       . '<p style="color:#a00">Running this on a database that already has these tables will show "already exists" errors (harmless).</p>';
    exit;
}

$ok = 0; $errors = [];
foreach ($files as $f) {
    $sql = file_get_contents($f);
    foreach (splitSql($sql) as $stmt) {
        try { $pdo->exec($stmt); $ok++; }
        catch (PDOException $e) {
            $errors[] = basename($f) . ': ' . $e->getMessage() . ' --> ' . substr(preg_replace('/\s+/', ' ', $stmt), 0, 90);
        }
    }
}
echo "<p><b>Done.</b> Statements OK: $ok &nbsp; Errors: " . count($errors) . '</p>';
if ($errors) {
    echo '<pre style="background:#fee;padding:10px;white-space:pre-wrap">' . htmlspecialchars(implode("\n\n", $errors)) . '</pre>';
    echo '<p>Files were NOT deleted. Send me the errors above.</p>';
    exit;
}

// success: remove the SQL files and this script
foreach ($files as $f) @unlink($f);
@rmdir($dir);
$deleted = @unlink(__FILE__);
echo '<p style="color:green"><b>Import successful.</b> ' . ($deleted
     ? 'This tool deleted itself and the SQL files.'
     : 'PLEASE DELETE import-db.php and the _import folder manually.') . '</p>';
echo '<p><a href="' . htmlspecialchars(BASE_URL) . '/login.php">Go to login</a></p>';
