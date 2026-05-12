<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

sync_local_images($pdo);

$images = fetch_images_with_ratings($pdo, $currentUser ? (int) $currentUser['id'] : null);
$recentCommentsByImage = [];
$standardUser = is_standard_user();

foreach ($images as $image) {
    $recentCommentsByImage[(int) $image['id']] = fetch_recent_image_comments($pdo, (int) $image['id']);
}

$userRatings = $standardUser ? fetch_user_image_ratings($pdo, (int) $currentUser['id']) : [];

$pageTitle = 'Galerija filmova';
$pageHeading = 'Galerija filmova';
$pageDescription = 'Galerija s trajnim ocjenjivanjem fotografija, prosjecnim ocjenama i administratorskim unosom novih slika.';
$styles = ['style.css', 'style_slike.css'];
$activePage = 'gallery';

require __DIR__ . '/includes/header.php';
?>
      <section class="gallery-section">
        <div class="gallery-intro">
          <div>
            <h2>Ocjenjivanje fotografija</h2>
            <p>
              Fotografije se automatski ucitavaju iz mape <code>/slike/</code>, a obicni korisnici
              mogu spremiti ili azurirati svoju ocjenu od 1 do 5 zvjezdica.
            </p>
          </div>
          <div class="gallery-meta">
            <span class="meta-pill">Ukupno slika: <?= e((string) count($images)) ?></span>
<?php if (!is_admin()): ?>
            <span class="meta-pill">
<?php if ($standardUser): ?>
              Vasih ocjena: <?= e((string) count($userRatings)) ?>
<?php else: ?>
              Prijava je potrebna za ocjenjivanje
<?php endif; ?>
            </span>
<?php endif; ?>
          </div>
        </div>

<?php if (is_admin()): ?>
        <section class="gallery-admin-grid">
          <article class="form-card">
            <h3>Dodaj novu sliku</h3>
            <p>Dozvoljeni formati su JPEG i PNG, maksimalno 5 MB po slici.</p>
            <form action="photo_action.php" method="post" enctype="multipart/form-data" class="stack-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="upload_image">
              <input type="hidden" name="return_to" value="slike.php">

              <div class="filter-group">
                <label for="photo_description">Opis slike</label>
                <input type="text" id="photo_description" name="description" maxlength="255" placeholder="npr. Filmska scena s festivala">
              </div>

              <div class="filter-group">
                <label for="photo_file">Datoteka</label>
                <input type="file" id="photo_file" name="photo_file" accept=".jpg,.jpeg,.png" required>
              </div>

              <button type="submit">Ucitaj sliku</button>
            </form>
          </article>

          <article class="form-card">
            <h3>Administracija ocjena</h3>
            <form action="photo_action.php" method="post" enctype="multipart/form-data" class="stack-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="import_ratings">
              <input type="hidden" name="return_to" value="slike.php">

              <div class="filter-group">
                <label for="ratings_csv">CSV import ocjena</label>
                <input type="file" id="ratings_csv" name="ratings_csv" accept=".csv" required>
                <p class="muted-text">Potrebni stupci: <code>username,filename,rating,comment</code>.</p>
              </div>

              <button type="submit">Uvezi ocjene</button>
            </form>

            <form action="photo_action.php" method="post" class="stack-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="sync_images">
              <input type="hidden" name="return_to" value="slike.php">
              <button type="submit" class="secondary-button">Sinkroniziraj mapu /slike/</button>
            </form>
          </article>
        </section>
<?php endif; ?>

<?php if ($images === []): ?>
        <div class="empty-state">
          <p>Galerija je trenutno prazna. Dodajte lokalne slike u mapu <code>/slike/</code> ili ucitajte novu fotografiju kao administrator.</p>
        </div>
<?php else: ?>
        <div class="gallery gallery-cards">
<?php foreach ($images as $image): ?>
<?php
    $imageId = (int) $image['id'];
    $userRating = (int) ($image['user_rating'] ?? 0);
    $comment = (string) ($image['user_comment'] ?? '');
?>
          <article class="gallery-card">
            <a href="photo.php?id=<?= e((string) $imageId) ?>" class="gallery-link">
              <img src="<?= e((string) $image['path']) ?>" alt="<?= e((string) $image['description']) ?>" loading="lazy">
            </a>

            <div class="card-body">
              <h3><?= e((string) $image['description']) ?></h3>
              <p class="meta-line">
                <span>Prosjek: <strong><?= e(number_format((float) $image['average_rating'], 1)) ?></strong>/5</span>
                <span>Broj ocjena: <strong><?= e((string) $image['ratings_count']) ?></strong></span>
              </p>
              <div class="rating-summary" aria-label="Prosjecna ocjena">
                <span class="stars-display"><?= str_repeat('&#9733;', (int) round((float) $image['average_rating'])) . str_repeat('&#9734;', 5 - (int) round((float) $image['average_rating'])) ?></span>
              </div>

<?php if ($standardUser): ?>
              <form action="photo_action.php" method="post" class="rating-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="rate_image">
                <input type="hidden" name="image_id" value="<?= e((string) $imageId) ?>">
                <input type="hidden" name="return_to" value="photo.php?id=<?= e((string) $imageId) ?>">

                <fieldset class="star-fieldset">
                  <legend>Vasa ocjena</legend>
                  <div class="star-picker">
<?php for ($star = 5; $star >= 1; $star--): ?>
                    <input
                      type="radio"
                      id="image-<?= e((string) $imageId) ?>-star-<?= e((string) $star) ?>"
                      name="rating"
                      value="<?= e((string) $star) ?>"
<?= $userRating === $star ? ' checked' : '' ?>
                    >
                    <label for="image-<?= e((string) $imageId) ?>-star-<?= e((string) $star) ?>" title="<?= e((string) $star) ?> zvjezdica">★</label>
<?php endfor; ?>
                  </div>
                </fieldset>

                <div class="filter-group">
                  <label for="comment-<?= e((string) $imageId) ?>">Komentar</label>
                  <textarea id="comment-<?= e((string) $imageId) ?>" name="comment" rows="3" maxlength="500" placeholder="Kratki komentar uz ocjenu (opcionalno)"><?= e($comment) ?></textarea>
                </div>

                <button type="submit"><?= $userRating > 0 ? 'Azuriraj ocjenu' : 'Spremi ocjenu' ?></button>
              </form>
<?php elseif (is_admin()): ?>
              <p class="login-note">Administratorski racun koristi galeriju za pregled i upravljanje slikama, bez ocjenjivanja.</p>
<?php else: ?>
              <p class="login-note">
                <a href="login.php?next=<?= urlencode('photo.php?id=' . $imageId) ?>">Prijavite se</a> kako biste ocijenili ovu sliku.
              </p>
<?php endif; ?>

<?php if (($recentCommentsByImage[$imageId] ?? []) !== []): ?>
              <div class="comment-block">
                <h4>Nedavni komentari</h4>
<?php foreach ($recentCommentsByImage[$imageId] as $recentComment): ?>
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
            </div>
          </article>
<?php endforeach; ?>
        </div>
<?php endif; ?>

<?php if ($standardUser): ?>
        <section class="ratings-history">
          <div class="section-heading">
            <h2>Moje ocjene fotografija</h2>
            <div class="action-row">
              <a class="secondary-button" href="photo_action.php?action=export_my_ratings">Izvezi CSV</a>
              <a class="primary-link-button" href="myratings.php">Otvori zasebnu stranicu ocjena</a>
            </div>
          </div>

<?php if ($userRatings === []): ?>
          <div class="empty-state">
            <p>Jos niste ocijenili nijednu fotografiju.</p>
          </div>
<?php else: ?>
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Fotografija</th>
                  <th>Opis</th>
                  <th>Ocjena</th>
                  <th>Komentar</th>
                  <th>Vrijeme ocjene</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($userRatings as $ratingRow): ?>
                <tr>
                  <td><img class="ratings-thumb" src="<?= e((string) $ratingRow['path']) ?>" alt="<?= e((string) $ratingRow['description']) ?>"></td>
                  <td><?= e((string) $ratingRow['description']) ?></td>
                  <td><?= e(str_repeat('★', (int) $ratingRow['rating'])) ?></td>
                  <td><?= e((string) $ratingRow['comment']) ?></td>
                  <td><?= e((string) $ratingRow['rated_at']) ?></td>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
<?php endif; ?>
        </section>
<?php endif; ?>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
