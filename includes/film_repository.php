<?php
declare(strict_types=1);

function fetch_filter_options(PDO $pdo): array
{
    $genres = $pdo
        ->query("SELECT DISTINCT genre FROM films WHERE genre <> '' ORDER BY genre ASC")
        ->fetchAll(PDO::FETCH_COLUMN);

    $countryRows = $pdo
        ->query("SELECT DISTINCT country FROM films WHERE country <> ''")
        ->fetchAll(PDO::FETCH_COLUMN);

    $countries = [];

    foreach ($countryRows as $countryRow) {
        foreach (explode(',', (string) $countryRow) as $country) {
            $country = trim($country);
            if ($country !== '') {
                $countries[$country] = $country;
            }
        }
    }

    natcasesort($countries);

    return [
        'genres' => array_values(array_filter(array_map('strval', $genres))),
        'countries' => array_values($countries),
    ];
}

function ensure_initial_films_loaded(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM films')->fetchColumn();

    if ($count > 0) {
        return;
    }

    import_films_from_csv($pdo, __DIR__ . '/../filmtv_movies.csv', 0);
}

function fetch_films(PDO $pdo, array $filters = [], int $limit = 20): array
{
    $sql = 'SELECT id, external_id, title, year, genre, duration, country, directors, actors, rating, total_votes, description, notes
            FROM films
            WHERE 1 = 1';

    $params = [];

    $search = trim((string) ($filters['search'] ?? ''));
    if ($search !== '') {
        $sql .= ' AND title LIKE :search';
        $params['search'] = '%' . $search . '%';
    }

    $genre = trim((string) ($filters['genre'] ?? ''));
    if ($genre !== '') {
        $sql .= ' AND genre = :genre';
        $params['genre'] = $genre;
    }

    $yearFrom = (int) ($filters['year_from'] ?? 0);
    if ($yearFrom > 0) {
        $sql .= ' AND year >= :year_from';
        $params['year_from'] = $yearFrom;
    }

    $yearTo = (int) ($filters['year_to'] ?? 0);
    if ($yearTo > 0) {
        $sql .= ' AND year <= :year_to';
        $params['year_to'] = $yearTo;
    }

    $country = trim((string) ($filters['country'] ?? ''));
    if ($country !== '') {
        $sql .= ' AND country LIKE :country';
        $params['country'] = '%' . $country . '%';
    }

    $ratingMin = trim((string) ($filters['rating_min'] ?? ''));
    if ($ratingMin !== '') {
        $sql .= ' AND rating >= :rating_min';
        $params['rating_min'] = (float) $ratingMin;
    }

    $allowedSorts = [
        'title-asc' => 'title ASC',
        'title-desc' => 'title DESC',
        'year-asc' => 'year ASC, title ASC',
        'year-desc' => 'year DESC, title ASC',
        'rating-asc' => 'rating ASC, title ASC',
        'rating-desc' => 'rating DESC, title ASC',
    ];

    $sort = (string) ($filters['sort'] ?? '');
    $orderBy = $allowedSorts[$sort] ?? 'title ASC';

    $sql .= ' ORDER BY ' . $orderBy . ' LIMIT :limit';

    $statement = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $parameterType = match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_float($value) => PDO::PARAM_STR,
            default => PDO::PARAM_STR,
        };
        $statement->bindValue(':' . $key, $value, $parameterType);
    }

    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function fetch_library_film_ids(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare('SELECT film_id FROM wanted_films WHERE user_id = :user_id');
    $statement->execute(['user_id' => $userId]);

    return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

function count_library_items(PDO $pdo, int $userId): int
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM wanted_films WHERE user_id = :user_id');
    $statement->execute(['user_id' => $userId]);

    return (int) $statement->fetchColumn();
}

function fetch_user_library(PDO $pdo, int $userId, ?int $limit = null): array
{
    $sql = 'SELECT f.id, f.title, f.year, f.genre, f.duration, f.country, f.rating, wf.added_at
            FROM wanted_films wf
            INNER JOIN films f ON f.id = wf.film_id
            WHERE wf.user_id = :user_id
            ORDER BY wf.added_at DESC';

    if ($limit !== null) {
        $sql .= ' LIMIT :limit';
    }

    $statement = $pdo->prepare($sql);
    $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
    if ($limit !== null) {
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $statement->execute();

    return $statement->fetchAll();
}

function add_film_to_library(PDO $pdo, int $userId, int $filmId): array
{
    $filmStatement = $pdo->prepare('SELECT id, title, rating FROM films WHERE id = :id LIMIT 1');
    $filmStatement->execute(['id' => $filmId]);
    $film = $filmStatement->fetch();

    if (!$film) {
        throw new RuntimeException('Film nije pronađen.');
    }

    $pdo->beginTransaction();

    try {
        $insert = $pdo->prepare(
            'INSERT INTO wanted_films (user_id, film_id)
             VALUES (:user_id, :film_id)
             ON DUPLICATE KEY UPDATE added_at = added_at',
        );
        $insert->execute([
            'user_id' => $userId,
            'film_id' => $filmId,
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    return [
        'added' => $insert->rowCount() > 0,
        'title' => (string) $film['title'],
        'rating' => (float) $film['rating'],
    ];
}

function remove_film_from_library(PDO $pdo, int $userId, int $filmId): bool
{
    $pdo->beginTransaction();

    try {
        $statement = $pdo->prepare(
            'DELETE FROM wanted_films
             WHERE user_id = :user_id AND film_id = :film_id',
        );
        $statement->execute([
            'user_id' => $userId,
            'film_id' => $filmId,
        ]);
        $pdo->commit();

        return $statement->rowCount() > 0;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function find_film_by_id(PDO $pdo, int $filmId): ?array
{
    $statement = $pdo->prepare('SELECT * FROM films WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $filmId]);
    $film = $statement->fetch();

    return $film ?: null;
}

function validate_film_input(array $input): array
{
    $currentYear = (int) date('Y');
    $errors = [];

    $externalId = trim((string) ($input['external_id'] ?? ''));
    if ($externalId !== '' && filter_var($externalId, FILTER_VALIDATE_INT) === false) {
        $errors['external_id'] = 'Vanjski ID mora biti cijeli broj.';
    }

    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        $errors['title'] = 'Naslov je obavezan.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Naslov može imati najviše 255 znakova.';
    }

    $year = (int) ($input['year'] ?? 0);
    if ($year < 1888 || $year > $currentYear + 2) {
        $errors['year'] = 'Godina mora biti između 1888. i ' . ($currentYear + 2) . '.';
    }

    $genre = trim((string) ($input['genre'] ?? ''));
    if ($genre === '') {
        $errors['genre'] = 'Žanr je obavezan.';
    } elseif (mb_strlen($genre) > 120) {
        $errors['genre'] = 'Žanr može imati najviše 120 znakova.';
    }

    $duration = (int) ($input['duration'] ?? 0);
    if ($duration < 1 || $duration > 500) {
        $errors['duration'] = 'Trajanje mora biti između 1 i 500 minuta.';
    }

    $country = trim((string) ($input['country'] ?? ''));
    if ($country === '') {
        $errors['country'] = 'Država je obavezna.';
    } elseif (mb_strlen($country) > 191) {
        $errors['country'] = 'Država može imati najviše 191 znak.';
    }

    $directors = trim((string) ($input['directors'] ?? ''));
    if (mb_strlen($directors) > 1000) {
        $errors['directors'] = 'Popis redatelja je predugačak.';
    }

    $actors = trim((string) ($input['actors'] ?? ''));
    if (mb_strlen($actors) > 2000) {
        $errors['actors'] = 'Popis glumaca je predugačak.';
    }

    $ratingRaw = str_replace(',', '.', trim((string) ($input['rating'] ?? '0')));
    $rating = is_numeric($ratingRaw) ? (float) $ratingRaw : -1;
    if ($rating < 0 || $rating > 10) {
        $errors['rating'] = 'Ocjena mora biti između 0 i 10.';
    }

    $totalVotes = (int) ($input['total_votes'] ?? 0);
    if ($totalVotes < 0) {
        $errors['total_votes'] = 'Broj glasova ne može biti negativan.';
    }

    $description = trim((string) ($input['description'] ?? ''));
    if (mb_strlen($description) > 10000) {
        $errors['description'] = 'Opis je predugačak.';
    }

    $notes = trim((string) ($input['notes'] ?? ''));
    if (mb_strlen($notes) > 10000) {
        $errors['notes'] = 'Napomene su predugačke.';
    }

    return [[
        'external_id' => $externalId !== '' ? (int) $externalId : null,
        'title' => $title,
        'year' => $year,
        'genre' => $genre,
        'duration' => $duration,
        'country' => $country,
        'directors' => $directors,
        'actors' => $actors,
        'rating' => number_format(max(0, min(10, $rating)), 1, '.', ''),
        'total_votes' => $totalVotes,
        'description' => $description,
        'notes' => $notes,
    ], $errors];
}

function create_film(PDO $pdo, array $film): int
{
    $statement = $pdo->prepare(
        'INSERT INTO films (
            external_id, title, year, genre, duration, country,
            directors, actors, rating, total_votes, description, notes
        ) VALUES (
            :external_id, :title, :year, :genre, :duration, :country,
            :directors, :actors, :rating, :total_votes, :description, :notes
        )',
    );
    $statement->execute($film);

    return (int) $pdo->lastInsertId();
}

function update_film(PDO $pdo, int $filmId, array $film): void
{
    $film['id'] = $filmId;

    $statement = $pdo->prepare(
        'UPDATE films SET
            external_id = :external_id,
            title = :title,
            year = :year,
            genre = :genre,
            duration = :duration,
            country = :country,
            directors = :directors,
            actors = :actors,
            rating = :rating,
            total_votes = :total_votes,
            description = :description,
            notes = :notes
         WHERE id = :id',
    );
    $statement->execute($film);
}

function delete_film(PDO $pdo, int $filmId): void
{
    $pdo->beginTransaction();

    try {
        $delete = $pdo->prepare('DELETE FROM films WHERE id = :id');
        $delete->execute(['id' => $filmId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function import_films_from_csv(PDO $pdo, string $filePath, int $limit = 0): int
{
    if (!is_file($filePath)) {
        throw new RuntimeException('CSV datoteka nije pronađena.');
    }

    set_time_limit(0);

    $file = new SplFileObject($filePath);
    $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
    $headers = $file->fgetcsv();

    if (!is_array($headers)) {
        throw new RuntimeException('CSV zaglavlje nije moguće pročitati.');
    }

    $headerIndex = array_flip($headers);

    $statement = $pdo->prepare(
        'INSERT INTO films (
            external_id, title, year, genre, duration, country,
            directors, actors, rating, total_votes, description, notes
        ) VALUES (
            :external_id, :title, :year, :genre, :duration, :country,
            :directors, :actors, :rating, :total_votes, :description, :notes
        )
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            year = VALUES(year),
            genre = VALUES(genre),
            duration = VALUES(duration),
            country = VALUES(country),
            directors = VALUES(directors),
            actors = VALUES(actors),
            rating = VALUES(rating),
            total_votes = VALUES(total_votes),
            description = VALUES(description),
            notes = VALUES(notes)',
    );

    $count = 0;
    $pdo->beginTransaction();

    try {
        foreach ($file as $row) {
            if (!is_array($row) || $row === [null] || count($row) < 5) {
                continue;
            }

            $title = trim((string) ($row[$headerIndex['title']] ?? ''));
            if ($title === '') {
                continue;
            }

            $statement->execute([
                'external_id' => (int) ($row[$headerIndex['filmtv_id']] ?? 0),
                'title' => $title,
                'year' => (int) ($row[$headerIndex['year']] ?? 0),
                'genre' => trim((string) ($row[$headerIndex['genre']] ?? '')),
                'duration' => (int) ($row[$headerIndex['duration']] ?? 0),
                'country' => trim((string) ($row[$headerIndex['country']] ?? '')),
                'directors' => trim((string) ($row[$headerIndex['directors']] ?? '')),
                'actors' => trim((string) ($row[$headerIndex['actors']] ?? '')),
                'rating' => number_format((float) ($row[$headerIndex['avg_vote']] ?? 0), 1, '.', ''),
                'total_votes' => (int) ($row[$headerIndex['total_votes']] ?? 0),
                'description' => trim((string) ($row[$headerIndex['description']] ?? '')),
                'notes' => trim((string) ($row[$headerIndex['notes']] ?? '')),
            ]);

            $count++;

            if ($limit > 0 && $count >= $limit) {
                break;
            }
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    return $count;
}

function fetch_dashboard_stats(PDO $pdo): array
{
    return [
        'films' => (int) $pdo->query('SELECT COUNT(*) FROM films')->fetchColumn(),
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'wanted' => (int) $pdo->query('SELECT COUNT(*) FROM wanted_films')->fetchColumn(),
    ];
}
