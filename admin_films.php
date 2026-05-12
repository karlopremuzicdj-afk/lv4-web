<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

require_admin();
ensure_initial_films_loaded($pdo);

$errors = [];
$editingFilm = null;
$formData = [
    'external_id' => '',
    'title' => '',
    'year' => '',
    'genre' => '',
    'duration' => '',
    'country' => '',
    'directors' => '',
    'actors' => '',
    'rating' => '0.0',
    'total_votes' => '0',
    'description' => '',
    'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = value_from($_POST, 'action');
    $postedFilmId = (int) ($_POST['film_id'] ?? 0);

    try {
        if ($action === 'create' || $action === 'update') {
            [$cleanData, $errors] = validate_film_input($_POST);
            $formData = array_merge($formData, array_map(static function ($value) {
                return $value === null ? '' : (string) $value;
            }, $cleanData));

            if ($errors === []) {
                if ($action === 'create') {
                    $filmId = create_film($pdo, $cleanData);
                    set_flash('success', 'Film je uspješno dodan u bazu.');
                    redirect('admin_films.php?edit=' . $filmId);
                }

                if ($postedFilmId < 1) {
                    throw new RuntimeException('Nedostaje ID filma za uređivanje.');
                }

                update_film($pdo, $postedFilmId, $cleanData);
                set_flash('success', 'Promjene na filmu su spremljene.');
                redirect('admin_films.php?edit=' . $postedFilmId);
            }
        } elseif ($action === 'delete') {
            if ($postedFilmId < 1) {
                throw new RuntimeException('Film za brisanje nije valjan.');
            }

            delete_film($pdo, $postedFilmId);
            set_flash('success', 'Film je obrisan iz baze.');
            redirect('admin_films.php');
        } elseif ($action === 'import_csv') {
            $limit = (int) ($_POST['import_limit'] ?? 200);
            if ($limit < 0) {
                throw new RuntimeException('Limit uvoza ne može biti negativan.');
            }

            $imported = import_films_from_csv($pdo, __DIR__ . '/filmtv_movies.csv', $limit);
            set_flash('success', 'CSV uvoz je završen. Obrađeno redaka: ' . $imported . '.');
            redirect('admin_films.php');
        } else {
            throw new RuntimeException('Nepoznata administratorska akcija.');
        }
    } catch (PDOException $exception) {
        $duplicateKey = str_contains($exception->getMessage(), '1062');
        $errors['general'] = $duplicateKey
            ? 'Vanjski ID ili drugi jedinstveni podatak već postoji u bazi.'
            : 'Greška baze podataka: ' . $exception->getMessage();
    } catch (Throwable $exception) {
        $errors['general'] = $exception->getMessage();
    }

    if ($action === 'update' && $postedFilmId > 0) {
        $editingFilm = ['id' => $postedFilmId];
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $editingFilm = find_film_by_id($pdo, $editId);
    if ($editingFilm) {
        foreach ($formData as $key => $value) {
            $formData[$key] = (string) ($editingFilm[$key] ?? $value);
        }
    }
}

$search = value_from($_GET, 'search');
$adminFilms = fetch_films(
    $pdo,
    [
        'search' => $search,
        'sort' => 'title-asc',
    ],
    100,
);
$stats = fetch_dashboard_stats($pdo);

$pageTitle = 'Administracija filmova';
$pageHeading = 'Administracija filmova';
$pageDescription = 'Administratorsko sučelje za unos, uređivanje, brisanje i uvoz filmova iz CSV datoteke.';
$styles = ['style.css'];
$activePage = 'admin';

require __DIR__ . '/includes/header.php';
?>
      <section class="stats-grid">
        <article class="stat-card">
          <h2><?= e((string) $stats['films']) ?></h2>
          <p>Filmova u bazi</p>
        </article>
        <article class="stat-card">
          <h2><?= e((string) $stats['users']) ?></h2>
          <p>Registriranih korisnika</p>
        </article>
        <article class="stat-card">
          <h2><?= e((string) $stats['wanted']) ?></h2>
          <p>Spremljenih stavki u videoteci</p>
        </article>
      </section>

      <section class="panel-grid">
        <article class="form-card">
          <h2><?= $editingFilm ? 'Uredi film' : 'Dodaj novi film' ?></h2>
<?php if (isset($errors['general'])): ?>
          <div class="alert alert-error"><?= e($errors['general']) ?></div>
<?php endif; ?>
          <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editingFilm ? 'update' : 'create' ?>">
<?php if ($editingFilm): ?>
            <input type="hidden" name="film_id" value="<?= e((string) $editingFilm['id']) ?>">
<?php endif; ?>

            <div class="form-two-column">
              <div class="filter-group">
                <label for="external_id">Vanjski ID</label>
                <input type="number" id="external_id" name="external_id" value="<?= e($formData['external_id']) ?>">
<?php if (isset($errors['external_id'])): ?>
                <p class="field-error"><?= e($errors['external_id']) ?></p>
<?php endif; ?>
              </div>

              <div class="filter-group">
                <label for="title">Naslov</label>
                <input type="text" id="title" name="title" value="<?= e($formData['title']) ?>" required>
<?php if (isset($errors['title'])): ?>
                <p class="field-error"><?= e($errors['title']) ?></p>
<?php endif; ?>
              </div>

              <div class="filter-group">
                <label for="year">Godina</label>
                <input type="number" id="year" name="year" value="<?= e($formData['year']) ?>" required>
<?php if (isset($errors['year'])): ?>
                <p class="field-error"><?= e($errors['year']) ?></p>
<?php endif; ?>
              </div>

              <div class="filter-group">
                <label for="genre">Žanr</label>
                <input type="text" id="genre" name="genre" value="<?= e($formData['genre']) ?>" required>
<?php if (isset($errors['genre'])): ?>
                <p class="field-error"><?= e($errors['genre']) ?></p>
<?php endif; ?>
              </div>

              <div class="filter-group">
                <label for="duration">Trajanje (min)</label>
                <input type="number" id="duration" name="duration" value="<?= e($formData['duration']) ?>" required>
<?php if (isset($errors['duration'])): ?>
                <p class="field-error"><?= e($errors['duration']) ?></p>
<?php endif; ?>
              </div>

              <div class="filter-group">
                <label for="country">Država</label>
                <input type="text" id="country" name="country" value="<?= e($formData['country']) ?>" required>
<?php if (isset($errors['country'])): ?>
                <p class="field-error"><?= e($errors['country']) ?></p>
<?php endif; ?>
              </div>

              <div class="filter-group">
                <label for="rating">Ocjena</label>
                <input type="number" id="rating" name="rating" min="0" max="10" step="0.1" value="<?= e($formData['rating']) ?>">
<?php if (isset($errors['rating'])): ?>
                <p class="field-error"><?= e($errors['rating']) ?></p>
<?php endif; ?>
              </div>

              <div class="filter-group">
                <label for="total_votes">Ukupno glasova</label>
                <input type="number" id="total_votes" name="total_votes" min="0" value="<?= e($formData['total_votes']) ?>">
<?php if (isset($errors['total_votes'])): ?>
                <p class="field-error"><?= e($errors['total_votes']) ?></p>
<?php endif; ?>
              </div>
            </div>

            <div class="filter-group">
              <label for="directors">Redatelji</label>
              <input type="text" id="directors" name="directors" value="<?= e($formData['directors']) ?>">
<?php if (isset($errors['directors'])): ?>
              <p class="field-error"><?= e($errors['directors']) ?></p>
<?php endif; ?>
            </div>

            <div class="filter-group">
              <label for="actors">Glumci</label>
              <textarea id="actors" name="actors" rows="3"><?= e($formData['actors']) ?></textarea>
<?php if (isset($errors['actors'])): ?>
              <p class="field-error"><?= e($errors['actors']) ?></p>
<?php endif; ?>
            </div>

            <div class="filter-group">
              <label for="description">Opis</label>
              <textarea id="description" name="description" rows="5"><?= e($formData['description']) ?></textarea>
<?php if (isset($errors['description'])): ?>
              <p class="field-error"><?= e($errors['description']) ?></p>
<?php endif; ?>
            </div>

            <div class="filter-group">
              <label for="notes">Napomene</label>
              <textarea id="notes" name="notes" rows="3"><?= e($formData['notes']) ?></textarea>
<?php if (isset($errors['notes'])): ?>
              <p class="field-error"><?= e($errors['notes']) ?></p>
<?php endif; ?>
            </div>

            <div class="action-row">
              <button type="submit"><?= $editingFilm ? 'Spremi promjene' : 'Dodaj film' ?></button>
              <a class="secondary-button" href="admin_films.php">Očisti formu</a>
            </div>
          </form>
        </article>

        <aside class="form-card">
          <h2>CSV uvoz</h2>
          <p>Administratorsko sučelje može uvesti postojeće filmove iz datoteke <code>filmtv_movies.csv</code>.</p>
          <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import_csv">

            <div class="filter-group">
              <label for="import_limit">Broj redaka za uvoz</label>
              <input type="number" id="import_limit" name="import_limit" min="0" value="200">
              <p class="muted-text">Unesite <strong>0</strong> za puni uvoz cijelog CSV-a.</p>
            </div>

            <button type="submit">Pokreni uvoz</button>
          </form>
        </aside>
      </section>

      <section>
        <div class="section-heading">
          <h2>Pregled filmova u bazi</h2>
          <form method="get" class="search-inline">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Pretraži naslov">
            <button type="submit">Traži</button>
          </form>
        </div>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Naslov</th>
                <th>Godina</th>
                <th>Žanr</th>
                <th>Ocjena</th>
                <th>Država</th>
                <th>Akcije</th>
              </tr>
            </thead>
            <tbody>
<?php if ($adminFilms === []): ?>
              <tr>
                <td colspan="7">U bazi trenutno nema filmova koji odgovaraju pretraživanju.</td>
              </tr>
<?php else: ?>
<?php foreach ($adminFilms as $film): ?>
              <tr>
                <td><?= e((string) $film['id']) ?></td>
                <td><?= e((string) $film['title']) ?></td>
                <td><?= e((string) $film['year']) ?></td>
                <td><?= e((string) $film['genre']) ?></td>
                <td><?= e(number_format((float) $film['rating'], 1)) ?></td>
                <td><?= e((string) $film['country']) ?></td>
                <td>
                  <div class="table-actions">
                    <a class="secondary-button" href="admin_films.php?edit=<?= e((string) $film['id']) ?>">Uredi</a>
                    <form
                      action="admin_films.php"
                      method="post"
                      class="inline-form"
                      data-confirm="Jeste li sigurni da želite obrisati ovaj film?"
                    >
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="film_id" value="<?= e((string) $film['id']) ?>">
                      <button type="submit" class="danger-button">Obriši</button>
                    </form>
                  </div>
                </td>
              </tr>
<?php endforeach; ?>
<?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
