<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$filmId = (int) ($_GET['id'] ?? 0);
$film = $filmId > 0 ? find_film_by_id($pdo, $filmId) : null;

if (!$film) {
    http_response_code(404);
}

$libraryIds = $currentUser ? fetch_library_film_ids($pdo, (int) $currentUser['id']) : [];
$alreadyAdded = $film ? in_array((int) $film['id'], $libraryIds, true) : false;

$pageTitle = $film ? ((string) $film['title'] . ' | Film') : 'Film nije pronađen';
$pageHeading = $film ? (string) $film['title'] : 'Film nije pronađen';
$pageDescription = $film
    ? 'Detaljan prikaz pojedinog filma s mogućnošću dodavanja u moju videoteku.'
    : 'Traženi film nije pronađen u bazi.';
$styles = ['style.css'];
$activePage = 'films';

require __DIR__ . '/includes/header.php';
?>
      <section class="page-shell detail-shell">
<?php if (!$film): ?>
        <article class="form-card">
          <h2>Film nije pronađen</h2>
          <p>Provjerite poveznicu ili se vratite na popis svih filmova.</p>
          <a class="primary-link-button" href="films.php">Natrag na filmove</a>
        </article>
<?php else: ?>
        <article class="form-card detail-card">
          <div class="detail-header">
            <div>
              <p class="detail-kicker"><?= e((string) $film['genre']) ?> • <?= e((string) $film['year']) ?></p>
              <h2><?= e((string) $film['title']) ?></h2>
              <p class="muted-text">Država: <?= e((string) $film['country']) ?> • Trajanje: <?= e((string) $film['duration']) ?> min</p>
            </div>
            <div class="rating-badge-large"><?= e(number_format((float) $film['rating'], 1)) ?></div>
          </div>

          <div class="detail-grid">
            <div>
              <h3>Opis</h3>
              <p><?= e((string) $film['description']) ?></p>
            </div>
            <div>
              <h3>Podaci o filmu</h3>
              <p><strong>Redatelji:</strong> <?= e((string) $film['directors']) ?></p>
              <p><strong>Glumci:</strong> <?= e((string) $film['actors']) ?></p>
              <p><strong>Broj glasova:</strong> <?= e((string) $film['total_votes']) ?></p>
<?php if ((string) $film['notes'] !== ''): ?>
              <p><strong>Napomene:</strong> <?= e((string) $film['notes']) ?></p>
<?php endif; ?>
            </div>
          </div>

          <div class="action-row">
<?php if ($currentUser): ?>
            <form
              action="collection_action.php"
              method="post"
              class="inline-form"
<?= (float) $film['rating'] < 5.0 ? ' data-confirm="Ovaj film ima nisku ocjenu – jeste li sigurni da ga želite dodati?"' : '' ?>
            >
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="film_id" value="<?= e((string) $film['id']) ?>">
              <input type="hidden" name="confirm_low_rating" value="0">
              <input type="hidden" name="return_to" value="<?= e(current_relative_url()) ?>">
              <button type="submit"<?= $alreadyAdded ? ' disabled' : '' ?>>
                <?= $alreadyAdded ? 'Već u videoteci' : 'Dodaj u moju videoteku' ?>
              </button>
            </form>
<?php else: ?>
            <a class="primary-link-button" href="login.php?next=<?= urlencode(current_relative_url()) ?>">Prijava za dodavanje</a>
<?php endif; ?>
            <a class="secondary-button" href="films.php">Natrag na popis filmova</a>
          </div>
        </article>
<?php endif; ?>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
