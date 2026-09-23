<?php
/**
 * Veronica user JSON database endpoint.
 *
 * Served by Apache (mod_php) at /{VIBE_PREFIX}-veronica/api/users.
 * Access control (HTTP Basic Auth) is enforced by the Apache <Location>
 * block generated at container start - this script never runs unauthenticated
 * through normal routing.
 *
 * File schema = Veronica UI-native user map:
 *   {"alias": {"pin": "sha256:...", "createdAt": 123, "admin": true}, ...}
 *
 * GET  -> 200 + users map ({} if database does not exist yet)
 * PUT  -> validates + atomically replaces the database, returns {"ok":true}
 * else -> 405
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$file = getenv('VERONICA_USERS_FILE');
if ($file === false || $file === '') {
    $file = '/data/veronica-users.json';
}

function fail(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

/**
 * @return array<string,mixed>
 */
function readUsers(string $file): array
{
    if (!is_file($file)) {
        return [];
    }
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    return $data;
}

/**
 * @param mixed $data
 */
function validateUsers($data): bool
{
    if (!is_array($data)) {
        return false;
    }
    foreach ($data as $alias => $record) {
        if (!is_string($alias) || $alias === '' || !is_array($record)) {
            return false;
        }
        $pin = $record['pin'] ?? null;
        if (!is_string($pin) || $pin === '') {
            return false;
        }
    }
    return true;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    echo json_encode(readUsers($file), JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'PUT') {
    $body = file_get_contents('php://input');
    if ($body === false || trim($body) === '') {
        fail(400, 'empty body');
    }
    $data = json_decode($body, true);
    if (!validateUsers($data)) {
        fail(422, 'invalid users map: expected object of {alias: {pin: string, ...}}');
    }

    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0770, true)) {
        fail(500, 'cannot create data directory');
    }

    // Serialize writers via a lock file, then atomic replace (tmp + rename).
    $lock = fopen($file . '.lock', 'c');
    if ($lock === false) {
        fail(500, 'cannot open lock file');
    }
    if (!flock($lock, LOCK_EX)) {
        fclose($lock);
        fail(500, 'cannot acquire lock');
    }
    $tmp = $file . '.' . getmypid() . '.tmp';
    $json = json_encode($data, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($tmp, $json . "\n") === false) {
        flock($lock, LOCK_UN);
        fclose($lock);
        fail(500, 'cannot write database');
    }
    chmod($tmp, 0660);
    if (!rename($tmp, $file)) {
        @unlink($tmp);
        flock($lock, LOCK_UN);
        fclose($lock);
        fail(500, 'cannot replace database');
    }
    flock($lock, LOCK_UN);
    fclose($lock);

    echo json_encode(['ok' => true]);
    exit;
}

fail(405, 'method not allowed');
