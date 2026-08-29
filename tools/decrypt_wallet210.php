<?php
require __DIR__ . '/../vendor/autoload.php';
use Illuminate\Encryption\Encrypter;
$dotenv = __DIR__ . '/../.env';
if (!file_exists($dotenv)) { echo "NO_ENV\n"; exit(1); }
$env = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$vars = [];
foreach ($env as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    $p = explode('=', $line, 2);
    if (count($p) === 2) $vars[trim($p[0])] = trim($p[1]);
}
$rawKey = $vars['APP_KEY'] ?? '';
if (strpos($rawKey, 'base64:') === 0) {
    $key = base64_decode(substr($rawKey, 7));
} else {
    $key = $rawKey;
}
$cipher = 'AES-256-CBC';
$enc = new Encrypter($key, $cipher);
$payload = 'eyJpdiI6ImNrRTdGVzIyMkdZMU5EOUxRR0VMcXc9PSIsInZhbHVlIjoiZVUxUGV6bkFmUzNWbzZ6STkvUlFjbjNKTWFiSzRsQWQ3L3FBWGJuWXJsWDFsN0JDYUZoOUdoU1dINEQ5LzYrSUhEbWNKaEJYNG16ekxJNXk0ZGQxTllJYTdQMlRXVFIzTU4rMnZGZHN3eXM9IiwibWFjIjoiNjEyMDI2YjE0YjAwNDkwMWViMzQ0ODJhZDcyY2ZkYTRiNmUzOGQyM2MzYWI3MDUyZmE3NDI1OGIyN2NjMGNjMyIsInRhZyI6IiJ9';
try{
    $decrypted = $enc->decryptString($payload);
    if (is_string($decrypted)) {
        echo "DECRYPT_OK_LEN:" . strlen($decrypted) . "\n";
        echo "DECRYPT_OK_HEX:" . bin2hex($decrypted) . "\n";
        echo "DECRYPT_OK_RAW:" . $decrypted . "\n";
    } else {
        var_dump($decrypted);
    }
} catch (Throwable $e) {
    echo "DECRYPT_ERROR:" . $e->getMessage() . "\n";
}
