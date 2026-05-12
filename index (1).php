<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
ensure_initial_films_loaded($pdo);

$filmsPageUrl = basename((string) ($_SERVER['PHP_SELF'] ?? 'films.php'));
$filmsPageUrl = $filmsPageUrl !== '' ? $filmsPageUrl : 'films.php';

$filters = [
    'search' => value_from($_GET, 'search'),
    'genre' => value_from($_GET, 'genre'),
    'year_from' => value_from($_GET, 'year_from'),
    'year_to' => value_from($_GET, 'year_to'),
    'country' => value_from($_GET, 'country'),
    'rating_min' => value_from($_GET, 'rating_min', '0'),
    'sort' => value_from($_GET, 'sort'),
];

$filterOptions = fetch_filter_options($pdo);
$films = fetch_films($pdo, $filters, 20);
$libraryIds = $currentUser ? fetch_library_film_ids($pdo, (int) $currentUser['id']) : [];
$libraryIdLookup = array_flip($libraryIds);
$libraryPreview = $currentUser ? fetch_user_library($pdo, (int) $currentUser['id'], 5) : [];
$libraryCount = $currentUser ? count_library_items($pdo, (int) $currentUser['id']) : 0;

$pageTitle = 'Svijet filmova';
$pageHeading = 'Svijet filmova';
$pageDescription = 'Server-side videoteka s prijavom korisnika, filtriranjem filmova i trajnim spremanjem osobne kolekcije.';
$styles = ['style.css'];
$activePage = 'films';

require __DIR__ . '/includes/header.php';
?>
      <section>
        <article>
          <h2>O aplikaciji</h2>
          <p>
            Filmovi se dohvaćaju iz MySQL baze, filtriraju SQL upitima na serverskoj strani
            i mogu se spremiti u osobnu videoteku prijavljenog korisnika.
          </p>
        </article>
      </section>

      <section class="content-layout">
        <article>
          <h2>Popularni filmovi</h2>
          <form method="get" class="filter-panel">
            <div class="filter-grid">
              <div class="filter-group">
                <label for="search">Pretraživanje naslova</label>
                <input type="text" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="npr. Diner">
              </div>

              <div class="filter-group">
                <label for="genre">Žanr</label>
                <select id="genre" name="genre">
                  <option value="">Svi žanrovi</option>
<?php foreach ($filterOptions['genres'] as $genre): ?>
                  <option value="<?= e($genre) ?>"<?= selected($filters['genre'], $genre) ?>><?= e($genre) ?></option>
<?php endforeach; ?>
                </select>
              </div>

              <div class="filter-group">
                <label for="year_from">Godina od</label>
                <input type="number" id="year_from" name="year_from" value="<?= e($filters['year_from']) ?>" placeholder="1980">
              </div>

              <div class="filter-group">
                <label for="year_to">Godina do</label>
                <input type="number" id="year_to" name="year_to" value="<?= e($filters['year_to']) ?>" placeholder="2000">
              </div>

              <div class="filter-group">
                <label for="country">Država</label>
                <select id="country" name="country">
                  <option value="">Sve države</option>
<?php foreach ($filterOptions['countries'] as $country): ?>
                  <option value="<?= e($country) ?>"<?= selected($filters['country'], $country) ?>><?= e($country) ?></option>
<?php endforeach; ?>
                </select>
              </div>

              <div class="filter-group">
                <label for="sort">Sortiranje</label>
                <select id="sort" name="sort">
                  <option value="">Naslov A-Z</option>
                  <option value="title-desc"<?= selected($filters['sort'], 'title-desc') ?>>Naslov Z-A</option>
                  <option value="year-asc"<?= selected($filters['sort'], 'year-asc') ?>>Godina uzlazno</option>
                  <option value="year-desc"<?= selected($filters['sort'], 'year-desc') ?>>Godina silazno</option>
                  <option value="rating-asc"<?= selected($filters['sort'], 'rating-asc') ?>>Ocjena uzlazno</option>
                  <option value="rating-desc"<?= selected($filters['sort'], 'rating-desc') ?>>Ocjena silazno</option>
                </select>
              </div>

              <div class="filter-group">
                <label for="filter-rating">Minimalna ocjena</label>
                <input
                  type="range"
                  id="filter-rating"
                  name="rating_min"
                  min="0"
                  max="10"
                  step="0.1"
                  value="<?= e($filters['rating_min']) ?>"
                >
                <span id="rating-value"><?= e(number_format((float) $filters['rating_min'], 1)) ?></span>
              </div>
            </div>

            <div class="action-row">
              <button type="submit">Filtriraj</button>
              <a class="secondary-button" href="<?= e($filmsPageUrl) ?>">Resetiraj</a>
            </div>
          </form>

          <div class="table-responsive">
            <table id="filmovi-tablica">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Naslov</th>
                  <th>Godina</th>
                  <th>Trajanje</th>
                  <th>Ocjena</th>
                  <th>Država</th>
                  <th>Žanr</th>
                  <th>Akcija</th>
                </tr>
              </thead>
              <tbody>
<?php if ($films === []): ?>
                <tr>
                  <td colspan="8">Nema filmova za odabrane filtre.</td>
                </tr>
<?php else: ?>
<?php foreach ($films as $film): ?>
                <tr>
                  <td><?= e((string) $film['id']) ?></td>
                  <td>
                    <strong><a class="text-link" href="film.php?id=<?= e((string) $film['id']) ?>"><?= e((string) $film['title']) ?></a></strong>
                    <div class="muted-text"><?= e((string) $film['directors']) ?></div>
                  </td>
                  <td><?= e((string) $film['year']) ?></td>
                  <td><?= e((string) $film['duration']) ?> min</td>
                  <td><?= e(number_format((float) $film['rating'], 1)) ?></td>
                  <td><?= e((string) $film['country']) ?></td>
                  <td><?= e((string) $film['genre']) ?></td>
                  <td>
<?php if ($currentUser): ?>
<?php $alreadyAdded = isset($libraryIdLookup[(int) $film['id']]); ?>
                    <form action="collection_action.php" method="post" class="inline-form">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="add">
                      <input type="hidden" name="film_id" value="<?= e((string) $film['id']) ?>">
                      <input type="hidden" name="return_to" value="<?= e(current_relative_url()) ?>">
                      <button type="submit"<?= $alreadyAdded ? ' disabled' : '' ?>>
                        <?= $alreadyAdded ? 'Dodano' : 'Dodaj' ?>
                      </button>
                    </form>
<?php else: ?>
                    <a class="secondary-button" href="login.php?next=<?= urlencode(current_relative_url()) ?>">Prijava za dodavanje</a>
<?php endif; ?>
                  </td>
                </tr>
<?php endforeach; ?>
<?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>

        <aside>
          <h3>Filmska slika</h3>
          <p>Početna stranica i dalje koristi postojeći responzivni prikaz slike iz prethodnih laboratorijskih vježbi.</p>
          <picture>
            <source media="(max-width:768px)" srcset="slike/movie-mobile.jpg">
            <img src="slike/movie-desktop.jpg" alt="Filmska scena">
          </picture>
        </aside>
      </section>

      <section id="kosarica">
        <h2>Moja videoteka</h2>
<?php if ($currentUser): ?>
        <p>Broj odabranih filmova: <span id="cart-count"><?= e((string) $libraryCount) ?></span></p>
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
              </tr>
            </thead>
            <tbody id="cart-body">
<?php if ($libraryPreview === []): ?>
              <tr>
                <td colspan="6">Još nemate filmove u osobnoj videoteci.</td>
              </tr>
<?php else: ?>
<?php foreach ($libraryPreview as $film): ?>
              <tr>
                <td><?= e((string) $film['title']) ?></td>
                <td><?= e((string) $film['year']) ?></td>
                <td><?= e((string) $film['genre']) ?></td>
                <td><?= e((string) $film['duration']) ?> min</td>
                <td><?= e((string) $film['country']) ?></td>
                <td><?= e(number_format((float) $film['rating'], 1)) ?></td>
              </tr>
<?php endforeach; ?>
<?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="action-row">
          <a class="primary-link-button" href="my_videoteka.php">Otvori cijelu videoteku</a>
        </div>
<?php else: ?>
        <div class="empty-state">
          <p>Za spremanje željenih filmova u osobnu videoteku prijavite se ili otvorite korisnički račun.</p>
          <div class="action-row">
            <a class="primary-link-button" href="register.php">Registracija</a>
            <a class="secondary-button" href="login.php">Prijava</a>
          </div>
        </div>
<?php endif; ?>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
