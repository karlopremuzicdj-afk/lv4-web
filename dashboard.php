<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

require_admin();
sync_local_images($pdo);

$stats = fetch_dashboard_stats($pdo);
$imageStats = fetch_images_with_ratings($pdo, null);
$adminRatings = fetch_admin_image_ratings($pdo, 150);

$pageTitle = 'Dashboard';
$pageHeading = 'Administratorski dashboard';
$pageDescription = 'Središnje administratorsko sučelje za filmove, galeriju i pregled ocjena fotografija.';
$styles = ['style.css', 'style_slike.css'];
$activePage = 'admin';

require __DIR__ . '/includes/header.php';
?>
      <section class="stats-grid">
        <article class="stat-card">
          <h2><?= e((string) $stats['films']) ?></h2>
          <p>Filmova u bazi</p>
        </article>
        <article class="stat-card">
          <h2><?= e((string) count($imageStats)) ?></h2>
          <p>Slika u galeriji</p>
        </article>
        <article class="stat-card">
          <h2><?= e((string) count($adminRatings)) ?></h2>
          <p>Zadnjih ocjena</p>
        </article>
      </section>

      <section class="panel-grid">
        <article class="form-card">
          <h2>Filmovi</h2>
          <p>Za dodavanje, uređivanje i brisanje filmova koristi se administratorsko sučelje videoteke.</p>
          <div class="action-row">
            <a class="primary-link-button" href="admin_films.php">Otvori upravljanje filmovima</a>
            <a class="secondary-button" href="index.php">Pregled filmova</a>
          </div>
        </article>

        <article class="form-card">
          <h2>Galerija i ocjene</h2>
          <p>Dashboard sadrži administratorske alate za upload slika, sinkronizaciju galerije i pregled ocjena bez mogućnosti brisanja.</p>
          <div class="action-row">
            <a class="primary-link-button" href="slike.php">Otvori galeriju</a>
            <a class="secondary-button" href="myratings.php">Moje ocjene</a>
          </div>
        </article>
      </section>

      <section class="gallery-admin-grid">
        <article class="form-card">
          <h3>Dodaj novu sliku</h3>
          <form action="photo_action.php" method="post" enctype="multipart/form-data" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upload_image">
            <input type="hidden" name="return_to" value="dashboard.php">

            <div class="filter-group">
              <label for="dashboard_photo_description">Opis slike</label>
              <input type="text" id="dashboard_photo_description" name="description" maxlength="255">
            </div>

            <div class="filter-group">
              <label for="dashboard_photo_file">Datoteka</label>
              <input type="file" id="dashboard_photo_file" name="photo_file" accept=".jpg,.jpeg,.png" required>
            </div>

            <button type="submit">Učitaj sliku</button>
          </form>
        </article>

        <article class="form-card">
          <h3>Ocjene fotografija</h3>
          <form action="photo_action.php" method="post" enctype="multipart/form-data" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import_ratings">
            <input type="hidden" name="return_to" value="dashboard.php">

            <div class="filter-group">
              <label for="dashboard_ratings_csv">CSV import ocjena</label>
              <input type="file" id="dashboard_ratings_csv" name="ratings_csv" accept=".csv" required>
            </div>

            <button type="submit">Uvezi ocjene</button>
          </form>

          <form action="photo_action.php" method="post" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="sync_images">
            <input type="hidden" name="return_to" value="dashboard.php">
            <button type="submit" class="secondary-button">Sinkroniziraj mapu /slike/</button>
          </form>
        </article>
      </section>

      <section>
        <div class="section-heading">
          <h2>Pregled ocjena</h2>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Korisnik</th>
                <th>Slika</th>
                <th>Ocjena</th>
                <th>Komentar</th>
                <th>Vrijeme</th>
              </tr>
            </thead>
            <tbody>
<?php if ($adminRatings === []): ?>
              <tr>
                <td colspan="6">Još nema spremljenih ocjena.</td>
              </tr>
<?php else: ?>
<?php foreach ($adminRatings as $ratingRow): ?>
              <tr>
                <td><?= e((string) $ratingRow['id']) ?></td>
                <td><?= e((string) $ratingRow['username']) ?></td>
                <td><?= e((string) $ratingRow['description']) ?></td>
                <td><?= e(str_repeat('★', (int) $ratingRow['rating'])) ?></td>
                <td><?= e((string) $ratingRow['comment']) ?></td>
                <td><?= e((string) $ratingRow['rated_at']) ?></td>
              </tr>
<?php endforeach; ?>
<?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
