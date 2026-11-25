<?php
// Router tylko do podglądu lokalnego (php -S). W produkcji użyj nginx.
$uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

if (strpos($uri, '/status') === 0) {
    require __DIR__ . '/index.php';
    return true;
}

$static = strpos($uri, '/tracking/') === 0
    || in_array($uri, ['/favicon.ico', '/favicon.svg', '/favicon-32.png', '/apple-touch-icon.png'], true);

if ($static) {
    $file = realpath(__DIR__ . '/public' . $uri);
    $base = realpath(__DIR__ . '/public');
    if ($file && $base && strpos($file, $base) === 0 && is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $types = [
            'css' => 'text/css',
            'woff2' => 'font/woff2',
            'woff' => 'font/woff',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'ico' => 'image/x-icon',
        ];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($file);
        return true;
    }
    http_response_code(404);
    return true;
}

return false;
