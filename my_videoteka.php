<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

require_standard_user();

$library = fetch_user_library($pdo, (int) $currentUser['id']);

$pageTitle = 'Moja videoteka';
$pageHeading = 'Moja videoteka';
$pageDescription = 'Pregled i upravljanje filmovima koje ste trajno spremili u svoju videoteku.';
$styles = ['style.css'];
$activePage = 'videoteka';

require __DIR__ . '/includes/header.php';
?>
      <section id="kosarica">
        <h2>Moja videoteka</h2>
        <p>Ukupno spremljenih filmova: <strong><?= e((string) count($library)) ?></strong></p>

<?php if ($library === []): ?>
        <div class="empty-state">
          <p>Vaša videoteka je trenutno prazna. Dodajte filmove s početne stranice.</p>
          <a class="primary-link-button" href="index.php">Pregled filmova</a>
        </div>
<?php else: ?>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Naslov</th>
                <th>Godina</th>
                <th>Žanr</th>
                <th>Trajanje</th>
                <th>Država</th>
                <th>Ocjena</th>
                <th>Dodano</th>
                <th>Akcija</th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($library as $film): ?>
              <tr>
                <td><?= e((string) $film['title']) ?></td>
                <td><?= e((string) $film['year']) ?></td>
                <td><?= e((string) $film['genre']) ?></td>
                <td><?= e((string) $film['duration']) ?> min</td>
                <td><?= e((string) $film['country']) ?></td>
                <td><?= e(number_format((float) $film['rating'], 1)) ?></td>
                <td><?= e((string) $film['added_at']) ?></td>
                <td>
                  <form
                    action="collection_action.php"
                    method="post"
                    class="inline-form"
                    data-confirm="Jeste li sigurni da želite ukloniti ovaj film?"
                  >
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="film_id" value="<?= e((string) $film['id']) ?>">
                    <input type="hidden" name="return_to" value="my_videoteka.php">
                    <button type="submit" class="danger-button">Ukloni</button>
                  </form>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php endif; ?>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
