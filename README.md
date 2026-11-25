# sk-klient - strona statusu naprawy dla klienta

Samodzielna strona, którą klient dostaje linkiem w SMS-ie
(`https://<domena>/status/<token>/`). Pokazuje numer zgłoszenia, sprzęt, etap
naprawy (**Przyjęty / W trakcie naprawy / Gotowy do wydania**, po wydaniu
sprzętu także **Odebrany**), daty etapów, publiczną notatkę serwisu i kontakt.

Aplikacja **nie ma własnej bazy danych**. Status pobiera z publicznego endpointu
serwisu (`sk-serwis`):

```
GET <api_base>/status/<token>/?format=json
-> {"ok":true,"status":{"number":"SK-2026-000001","stage":"ready",...}}
```

Takie rozdzielenie jest celowe: tokeny, mapowanie statusów i dane klientów są
w jednym miejscu (repozytorium `sk-serwis`), a ten projekt tylko renderuje to,
co dostanie. Wyciek kodu tej aplikacji nie daje dostępu do bazy ani do tokenów.

## Wymagania

PHP 8.1+ z rozszerzeniem `curl` (opcjonalnie; bez niego działa `file_get_contents`)
albo dowolny serwer potrafiący uruchomić `index.php`. Żadnych zależności, żadnego
Composera, żadnego Node.

## Struktura

```
index.php                 router: /status/<token>/ -> JSON z API -> widok
bootstrap.php             konfiguracja, helpery, klient HTTP API
config.example.php        wzór konfiguracji (config.php nie trafia do repo)
router.php                router tylko do podglądu lokalnego (php -S)
views/status.php          widok strony statusu (kopia z sk-serwis)
views/unavailable.php     komunikat, gdy API serwisu nie odpowiada (503)
public/tracking/          status.css + fonts/*.woff2 (kopia z sk-serwis)
public/favicon.*          ikony
deploy/nginx.conf         przykładowa konfiguracja nginx
```

## Konfiguracja

```bash
cp config.example.php config.php
```

| Klucz | Znaczenie |
|---|---|
| `api_base` | adres serwisu, z którego czytamy JSON (np. `https://sk.mati-it.pl`) |
| `api_timeout` | czas oczekiwania na API w sekundach (domyślnie 5) |
| `public_url` | adres tej strony (stopka, canonical) |
| `log_dir` | katalog logów technicznych (bez tokenów i danych klienta) |

## Podgląd lokalny

```bash
php -S 127.0.0.1:8141 -t . router.php
# http://127.0.0.1:8141/status/<token>/
```

## Wdrożenie (nginx + PHP-FPM)

Wzór: `deploy/nginx.conf`. Najważniejsze zasady:

- root ustawiony na `public/`, wejście przez `index.php` (poza webrootem),
- `/status/<token>/` przekierowane do `index.php` (bez `try_files`),
- `/tracking/` serwowane jako pliki statyczne z długim cache,
- `noindex`/`no-store` i CSP ustawia `index.php`; jeśli dodajesz nagłówki
  w nginx, pamiętaj, że `add_header` w `location` kasuje dziedziczenie.

Po wdrożeniu sprawdź:

```bash
curl -I https://<domena>/status/<token>/        # 200 + Cache-Control: no-store
curl -I https://<domena>/status/zly-token/      # 404
curl -I https://<domena>/tracking/status.css    # 200 + immutable
```

Kody: `200` strona statusu, `404` nieaktywny/nieprawidłowy link, `405` metoda
inna niż GET/HEAD, `429` limit tempa z API, `503` API serwisu nie odpowiada
(osobny komunikat, nie „link nieaktywny”).

## Synchronizacja widoku i stylów z `sk-serwis`

`views/status.php` i `public/tracking/` są kopiami z repozytorium serwisu.
Źródłem prawdy jest `sk-serwis` - po zmianie wyglądu strony statusu skopiuj pliki:

```powershell
$src = '..\sk-serwis'
Copy-Item "$src\panel\views\status.php" 'views\status.php' -Force
Copy-Item "$src\public\tracking" 'public\tracking' -Recurse -Force
```

Następnie podbij `?v=` przy `status.css` w `views/status.php` i `views/unavailable.php`,
żeby przeglądarki pobrały nowy arkusz.

## Bezpieczeństwo i prywatność

- Aplikacja nie przechowuje danych klientów; w logach nie ma tokenów ani numerów.
- Token jest sekretem: `noindex`, `no-store`, `Referrer-Policy: no-referrer`.
- Brak JavaScriptu, brak zewnętrznych zasobów (CSP `default-src 'none'`).
- Link unieważnia serwis (`sk-serwis`) generując nowy token; ta strona nie ma
  własnego stanu do wyczyszczenia.
