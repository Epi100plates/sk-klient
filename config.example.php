<?php
/**
 * Konfiguracja strony statusu dla klienta (projekt sk-klient).
 * Skopiuj ten plik jako config.php i uzupełnij. config.php nie trafia do repo.
 */
return [
    // Adres serwisu Studio-Komp - z niego pobieramy JSON ze statusem naprawy
    // (ta aplikacja NIE dotyka bazy danych):
    //   GET <api_base>/status/<token>/?format=json
    'api_base' => 'https://sk.mati-it.pl',

    // Czas oczekiwania na odpowiedź API (sekundy).
    'api_timeout' => 5,

    // Adres, pod którym stoi ta strona (do stopki widoku).
    'public_url' => 'https://status.studio-komp.pl',

    // Logowanie błędów API - katalog poza webrootem, bez tokenów w treści.
    'log_dir' => __DIR__ . '/data/logs',

    // Opcjonalnie: ścieżka do pakietu CA, jeśli PHP nie ma curl.cainfo
    // (na typowym hostingu nie jest potrzebne).
    'ca_bundle' => '',

    // Opcjonalnie: wymuś IPv4, jeśli hosting ma niedziałające IPv6
    // (obserwowane jako 10-sekundowe timeouty przy poprawnie działającym API).
    'ipv4_only' => false,
];
