<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Svijet filmova';
$pageHeading = $pageHeading ?? $pageTitle;
$pageDescription = $pageDescription ?? 'Web aplikacija za upravljanje virtualnom videotekom.';
$styles = $styles ?? ['style.css'];
$activePage = $activePage ?? 'home';
$flashMessages = pull_flash_messages();
?>
<!doctype html>
<html lang="hr">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($pageTitle) ?></title>
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?= e($style) ?>">
<?php endforeach; ?>
  </head>
  <body>
    <header>
      <div class="header-top">
        <div>
          <h1><?= e($pageHeading) ?></h1>
          <p><?= e($pageDescription) ?></p>
        </div>
        <div class="user-panel">
<?php if ($currentUser): ?>
          <p class="user-meta">
            <strong><?= e($currentUser['username']) ?></strong>
            <span class="badge"><?= e(format_role((string) $currentUser['role'])) ?></span>
          </p>
          <form action="logout.php" method="post" class="inline-form">
            <?= csrf_field() ?>
            <button type="submit" class="secondary-button">Odjava</button>
          </form>
<?php else: ?>
          <div class="auth-links">
            <a class="secondary-button" href="login.php">Prijava</a>
            <a class="primary-link-button" href="register.php">Registracija</a>
          </div>
<?php endif; ?>
        </div>
      </div>
    </header>

    <nav class="dropdown-nav" aria-label="Glavna navigacija">
      <div class="menu-wrapper">
        <span class="menu-button">Izbornik</span>
        <ul class="menu-list">
          <li><a href="films.php">Početna</a></li>
          <li><a href="grafikon.php">Grafikon</a></li>
          <li><a href="gallery.php">Galerija</a></li>
<?php if (is_standard_user()): ?>
          <li><a href="my_videoteka.php">Moja videoteka</a></li>
<?php endif; ?>
<?php if (is_admin()): ?>
          <li><a href="dashboard.php">Administracija</a></li>
<?php endif; ?>
        </ul>
      </div>

    </nav>

    <main>
<?php foreach ($flashMessages as $message): ?>
      <div class="alert alert-<?= e((string) ($message['type'] ?? 'info')) ?>">
        <?= e((string) ($message['message'] ?? '')) ?>
      </div>
<?php endforeach; ?>
