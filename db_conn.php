<?php
/**
 * Simple .env loader for small projects.
 * Parses KEY=VALUE lines into $_ENV if not already set.
 */
function loadEnv(string $path)
{
    if (! file_exists($path)) {
        trigger_error(".env file not found at $path", E_USER_WARNING);
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        [$key, $val] = array_map('trim', explode('=', $line, 2));
        // strip surrounding quotes
        $val = preg_replace('/^([\'"])(.*)\1$/', '$2', $val);
        if (! isset($_ENV[$key])) {
            $_ENV[$key] = $val;
        }
    }
}

// 1) Load .env from project root
loadEnv(__DIR__ . '/.env');

// 2) Pull credentials from $_ENV
$servername = $_ENV['DB_HOST']   ?? 'localhost';
$username   = $_ENV['DB_USER']   ?? 'root';
$password   = $_ENV['DB_PASS']   ?? '';
$dbname     = $_ENV['DB_NAME']   ?? '';

// 3) Connect
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}