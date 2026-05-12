<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

require_standard_user();

$userRatings = fetch_user_image_ratings($pdo, (int) $currentUser['id']);

$pageTitle = 'Moje ocjene';
$pageHeading = 'Moje ocjene fotografija';
$pageDescription = 'Pregled svih fotografija koje ste ocijenili u galeriji.';
$styles = ['style.css', 'style_slike.css'];
$activePage = 'gallery';

require __DIR__ . '/includes/header.php';
?>
      <section class="ratings-history">
        <div class="section-heading">
          <h2>Moje ocjene fotografija</h2>
          <a class="secondary-button" href="photo_action.php?action=export_my_ratings">Izvezi CSV</a>
        </div>

<?php if ($userRatings === []): ?>
        <div class="empty-state">
          <p>Još niste ocijenili nijednu fotografiju.</p>
          <a class="primary-link-button" href="gallery.php">Otvori galeriju</a>
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
                <td><a class="text-link" href="photo.php?id=<?= e((string) ($ratingRow['image_id'] ?? '')) ?>"><?= e((string) $ratingRow['description']) ?></a></td>
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
<?php require __DIR__ . '/includes/footer.php'; ?>
