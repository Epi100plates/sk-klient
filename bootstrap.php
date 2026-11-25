<?php
/**
 * Inicjalizacja aplikacji: konfiguracja, helpery, klient HTTP API.
 * Bez sesji, bez bazy danych, bez zależności zewnętrznych.
 */

declare(strict_types=1);

$configFile = getenv('SK_KLIENT_CONFIG') ?: __DIR__ . '/config.php';
$config = is_file($configFile) ? require $configFile : null;

if (!is_array($config)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Brak konfiguracji: skopiuj config.example.php jako config.php.\n";
    exit;
}

/** Wartość z konfiguracji (z wartością domyślną). */
function cfg(string $key, $default = null)
{
    global $config;
    return $config[$key] ?? $default;
}

/** Escapowanie tekstu do HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Log techniczny bez tokenów, numerów i danych klienta. */
function klient_log(string $message): void
{
    $dir = (string) cfg('log_dir', '');
    if ($dir === '') {
        return;
    }
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . str_replace(["\r", "\n"], ' ', $message) . "\n";
    @file_put_contents(rtrim($dir, '/') . '/klient.log', $line, FILE_APPEND | LOCK_EX);
}

/**
 * Pobiera status naprawy z API serwisu.
 * @return array{code:int,data:?array}
 */
function klient_fetch_status(string $token): array
{
    $base = rtrim((string) cfg('api_base'), '/');
    $url = $base . '/status/' . rawurlencode($token) . '/?format=json';
    $timeout = max(1, (int) cfg('api_timeout', 5));
    $body = '';
    $code = 0;
    $error = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => min(3, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                // Cloudflare bez User-Agent potrafi opóźniać odpowiedź (bot fight mode).
                'User-Agent: sk-klient/1.0 (+' . rtrim((string) cfg('public_url'), '/') . ')',
            ],
        ]);
        // Opcjonalny pakiet CA (hostingi bez domyślnego curl.cainfo).
        $caBundle = (string) cfg('ca_bundle', '');
        if ($caBundle !== '' && is_file($caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
        }
        // Wymuszenie IPv4 bywa potrzebne na hostach z niedziałającym IPv6.
        if (!empty(cfg('ipv4_only', false))) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = (string) curl_error($ch);
        curl_close($ch);
        $body = $response === false ? '' : (string) $response;
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
                'timeout' => $timeout,
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        $body = $response === false ? '' : (string) $response;
        $headers = function_exists('http_get_last_response_headers')
            ? (http_get_last_response_headers() ?? [])
            : ($http_response_header ?? []);
        if (isset($headers[0]) && preg_match('/\s(\d{3})\s/', (string) $headers[0], $m)) {
            $code = (int) $m[1];
        }
        if ($response === false) {
            $error = 'brak odpowiedzi';
        }
    }

    if ($code === 0 || ($code >= 500 && $code !== 200)) {
        klient_log('api: kod=' . $code . ' blad=' . mb_substr($error, 0, 120, 'UTF-8'));
        return ['code' => 503, 'data' => null];
    }

    $data = json_decode($body, true);
    return ['code' => $code, 'data' => is_array($data) ? $data : null];
}
