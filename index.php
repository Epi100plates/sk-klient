<?php
/**
 * Strona statusu naprawy dla klienta (projekt sk-klient).
 *
 * Router: GET /status/<token>/ -> pobranie JSON z API serwisu -> widok.
 * Aplikacja nie ma bazy danych: jedynym źródłem statusu jest publiczny
 * endpoint serwisu (GET <api_base>/status/<token>/?format=json).
 *
 * Kody odpowiedzi:
 *   200 - strona statusu,
 *   404 - nieprawidłowy lub unieważniony link (ta sama strona komunikatu),
 *   405 - metoda inna niż GET/HEAD,
 *   429 - limit tempa przekazany z API,
 *   503 - API serwisu chwilowo niedostępne.
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$isHead = $method === 'HEAD';
if ($method !== 'GET' && !$isHead) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Dozwolone sa tylko zadania GET i HEAD.';
    exit;
}

header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; font-src 'self'; img-src 'self' data:; base-uri 'none'; form-action 'none'; frame-ancestors 'self'");

/** Token z adresu: /status/<token>/ albo ?t=<token>. */
function klient_token(): string
{
    $param = trim((string) ($_GET['t'] ?? ''));
    if ($param !== '') {
        return $param;
    }
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    if (preg_match('~/status/([^/?#]+)~', $uri, $m)) {
        return $m[1];
    }
    return '';
}

$token = klient_token();
// Token ma ściśle określony format (id.wersja.podpis) - odrzucamy resztę bez pytania API.
if (!preg_match('/^\d{1,10}\.\d{1,12}\.[A-Za-z0-9_-]{43}$/', $token)) {
    http_response_code(404);
    klient_render(null, $isHead);
    exit;
}

$result = klient_fetch_status($token);

if ($result['code'] === 404 || ($result['code'] === 200 && !is_array($result['data']['status'] ?? null))) {
    http_response_code(404);
    klient_render(null, $isHead);
    exit;
}

if ($result['code'] === 429) {
    http_response_code(429);
    header('Retry-After: 60');
    klient_render_unavailable($isHead);
    exit;
}

if ($result['code'] !== 200) {
    http_response_code(503);
    header('Retry-After: 60');
    klient_render_unavailable($isHead);
    exit;
}

http_response_code(200);
klient_render($result['data']['status'], $isHead);

/** Renderuje widok statusu (HTML z views/status.php). */
function klient_render(?array $status, bool $headOnly = false): void
{
    header('Content-Type: text/html; charset=utf-8');
    if ($headOnly) {
        exit;
    }
    $status_refresh = 0;
    require __DIR__ . '/views/status.php';
}

/** Strona 503 - API serwisu nie odpowiada. Osobny komunikat, nie "link nieaktywny". */
function klient_render_unavailable(bool $headOnly = false): void
{
    header('Content-Type: text/html; charset=utf-8');
    if ($headOnly) {
        exit;
    }
    require __DIR__ . '/views/unavailable.php';
}
