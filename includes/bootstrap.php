<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Europe/Zagreb');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

$currentUser = current_user();
$pdo = null;

if (($requireDatabase ?? true) === true) {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/film_repository.php';
    require_once __DIR__ . '/photo_repository.php';

    try {
        $pdo = db();
    } catch (Throwable $exception) {
        http_response_code(500);
        ?>
        <!doctype html>
        <html lang="hr">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Greška baze podataka</title>
          <link rel="stylesheet" href="style.css">
        </head>
        <body>
          <main class="page-shell">
            <section class="form-card">
              <h1>Povezivanje s bazom nije uspjelo</h1>
              <p>Provjerite MySQL postavke u <code>includes/db.php</code> i uvezite <code>lv4_films.sql</code>.</p>
              <p><strong>Detalj:</strong> <?= e($exception->getMessage()) ?></p>
            </section>
          </main>
        </body>
        </html>
        <?php
        exit;
    }
}
