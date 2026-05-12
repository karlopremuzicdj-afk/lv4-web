<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

sync_local_images($pdo);

$imageId = (int) ($_GET['id'] ?? 0);
$image = $imageId > 0 ? fetch_image_with_rating($pdo, $imageId, $currentUser ? (int) $currentUser['id'] : null) : null;
$recentComments = $image ? fetch_recent_image_comments($pdo, $imageId, 12) : [];
$standardUser = is_standard_user();

if (!$image) {
    http_response_code(404);
}

$userRating = $image ? (int) ($image['user_rating'] ?? 0) : 0;
$userComment = $image ? (string) ($image['user_comment'] ?? '') : '';

$pageTitle = $image ? ((string) $image['description'] . ' | Foto') : 'Fotografija nije pronađena';
$pageHeading = $image ? (string) $image['description'] : 'Fotografija nije pronađena';
$pageDescription = $image
    ? 'Detaljni prikaz fotografije s mogućnošću ocjenjivanja od 1 do 5 zvjezdica.'
    : 'Tražena fotografija nije pronađena u galeriji.';
$styles = ['style.css', 'style_slike.css'];
$activePage = 'gallery';

require __DIR__ . '/includes/header.php';
?>
      <section class="page-shell detail-shell">
<?php if (!$image): ?>
        <article class="form-card">
          <h2>Fotografija nije pronađena</h2>
          <p>Provjerite poveznicu ili se vratite na galeriju.</p>
          <a class="primary-link-button" href="gallery.php">Natrag na galeriju</a>
        </article>
<?php else: ?>
        <article class="form-card detail-card photo-detail-card">
          <div class="detail-header">
            <div>
              <p class="detail-kicker">Izvor: <?= e((string) $image['source']) ?></p>
              <h2><?= e((string) $image['description']) ?></h2>
              <p class="muted-text">Prosjek: <?= e(number_format((float) $image['average_rating'], 1)) ?>/5 • Ukupno ocjena: <?= e((string) $image['ratings_count']) ?></p>
            </div>
            <div class="rating-badge-large"><?= e(number_format((float) $image['average_rating'], 1)) ?></div>
          </div>

          <img class="detail-photo" src="<?= e((string) $image['path']) ?>" alt="<?= e((string) $image['description']) ?>">

<?php if ($standardUser): ?>
          <form action="photo_action.php" method="post" class="rating-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="rate_image">
            <input type="hidden" name="image_id" value="<?= e((string) $imageId) ?>">
            <input type="hidden" name="return_to" value="<?= e(current_relative_url()) ?>">

            <fieldset class="star-fieldset">
              <legend>Vaša ocjena</legend>
              <div class="star-picker">
<?php for ($star = 5; $star >= 1; $star--): ?>
                <input type="radio" id="photo-star-<?= e((string) $star) ?>" name="rating" value="<?= e((string) $star) ?>"<?= $userRating === $star ? ' checked' : '' ?>>
                <label for="photo-star-<?= e((string) $star) ?>" title="<?= e((string) $star) ?> zvjezdica">★</label>
<?php endfor; ?>
              </div>
            </fieldset>

            <div class="filter-group">
              <label for="photo-comment">Komentar</label>
              <textarea id="photo-comment" name="comment" rows="4" maxlength="500"><?= e($userComment) ?></textarea>
            </div>

            <div class="action-row">
              <button type="submit"><?= $userRating > 0 ? 'Ažuriraj ocjenu' : 'Spremi ocjenu' ?></button>
              <a class="secondary-button" href="gallery.php">Natrag na galeriju</a>
            </div>
          </form>
<?php elseif (is_admin()): ?>
          <div class="empty-state">
            <p>Administratorski račun ne ocjenjuje fotografije. Ovdje možete pregledati sadržaj i upravljati galerijom.</p>
          </div>
<?php else: ?>
          <div class="empty-state">
            <p><a href="login.php?next=<?= urlencode(current_relative_url()) ?>">Prijavite se</a> kako biste ocijenili ovu fotografiju.</p>
          </div>
<?php endif; ?>

<?php if ($recentComments !== []): ?>
          <div class="comment-block">
            <h3>Komentari korisnika</h3>
<?php foreach ($recentComments as $recentComment): ?>
            <article class="comment-item">
              <p class="comment-meta">
                <strong><?= e((string) $recentComment['username']) ?></strong>
                <span><?= e(str_repeat('★', (int) $recentComment['rating'])) ?></span>
              </p>
              <p><?= e((string) $recentComment['comment']) ?></p>
            </article>
<?php endforeach; ?>
          </div>
<?php endif; ?>
        </article>
<?php endif; ?>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
