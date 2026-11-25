<?php
/**
 * Strona awaryjna: API serwisu nie odpowiada. Markup i klasy jak w
 * views/status.php, żeby strona wyglądała spójnie także przy awarii.
 */
$service = [
    'name' => 'Studio-Komp',
    'phone' => '500 613 500',
    'phone_href' => 'tel:+48500613500',
    'address' => 'Kotlarska 14, 50-150 Wrocław',
    'hours' => 'Poniedziałek - piątek: 7:00 - 15:00',
    'url' => (string) cfg('public_url', ''),
];
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#ffd400">
<title>Status naprawy chwilowo niedostępny - <?= $h($service['name']) ?></title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="stylesheet" href="/tracking/status.css?v=1">
</head>
<body>
<header class="masthead">
  <div class="masthead__inner">
    <span class="brand"><span class="brand__mark" aria-hidden="true"></span><?= $h($service['name']) ?></span>
    <span class="masthead__meta mono">Serwis laptopów · Wrocław</span>
  </div>
</header>
<main class="page" id="main">
  <section class="empty" aria-labelledby="unavailable-title">
    <p class="label">Status naprawy</p>
    <h1 class="empty__title" id="unavailable-title">Strona chwilowo niedostępna</h1>
    <p class="empty__text">Nie udało się pobrać statusu naprawy. Spróbuj odświeżyć stronę za chwilę - jeśli problem się powtarza, zadzwoń do serwisu.</p>
    <a class="phone" href="<?= $h($service['phone_href']) ?>"><?= $h($service['phone']) ?></a>
    <dl class="rows">
      <div class="row"><dt>Adres</dt><dd><?= $h($service['address']) ?></dd></div>
      <div class="row"><dt>Godziny</dt><dd><?= $h($service['hours']) ?></dd></div>
    </dl>
  </section>
</main>
<footer class="foot">
  <div class="foot__inner">
    <p class="foot__note">Status naprawy pobieramy z systemu serwisu.</p>
    <p class="foot__meta mono"><?= $h($service['name']) ?> · Kotlarska 14 · Wrocław</p>
  </div>
</footer>
</body>
</html>
