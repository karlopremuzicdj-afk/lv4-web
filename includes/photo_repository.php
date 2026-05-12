<?php
declare(strict_types=1);

function photos_directory(): string
{
    return __DIR__ . '/../slike';
}

function relative_photo_path(string $filename): string
{
    return 'slike/' . ltrim($filename, '/\\');
}

function detect_photo_description(string $filename): string
{
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = str_replace(['-', '_'], ' ', $name);
    $name = preg_replace('/\s+/', ' ', (string) $name);

    return trim((string) $name) !== '' ? ucwords(trim((string) $name)) : 'Galerijska fotografija';
}

function sync_local_images(PDO $pdo): int
{
    $directory = photos_directory();
    if (!is_dir($directory)) {
        return 0;
    }

    $statement = $pdo->prepare(
        'INSERT INTO images (filename, description, path, source, mime_type, uploaded_by)
         VALUES (:filename, :description, :path, :source, :mime_type, NULL)
         ON DUPLICATE KEY UPDATE
           description = IF(description = "", VALUES(description), description),
           path = VALUES(path),
           mime_type = VALUES(mime_type)',
    );

    $count = 0;
    foreach (scandir($directory) ?: [] as $file) {
        if (!is_string($file) || $file === '.' || $file === '..') {
            continue;
        }

        if (in_array($file, ['movie-desktop.jpg', 'movie-mobile.jpg'], true)) {
            continue;
        }

        $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            continue;
        }

        $mimeType = $extension === 'png' ? 'image/png' : 'image/jpeg';

        $statement->execute([
            'filename' => $file,
            'description' => detect_photo_description($file),
            'path' => relative_photo_path($file),
            'source' => 'local',
            'mime_type' => $mimeType,
        ]);
        $count++;
    }

    return $count;
}

function fetch_images_with_ratings(PDO $pdo, ?int $userId = null): array
{
    $statement = $pdo->prepare(
        'SELECT
            i.id,
            i.filename,
            i.description,
            i.path,
            i.source,
            i.mime_type,
            i.created_at,
            COALESCE(AVG(r.rating), 0) AS average_rating,
            COUNT(r.id) AS ratings_count,
            MAX(CASE WHEN r.user_id = :user_id THEN r.rating END) AS user_rating,
            MAX(CASE WHEN r.user_id = :user_id_comment THEN r.comment END) AS user_comment
         FROM images i
         LEFT JOIN image_ratings r ON r.image_id = i.id
         GROUP BY i.id, i.filename, i.description, i.path, i.source, i.mime_type, i.created_at
         ORDER BY i.created_at DESC, i.id DESC',
    );
    $statement->execute([
        'user_id' => $userId ?? 0,
        'user_id_comment' => $userId ?? 0,
    ]);

    return $statement->fetchAll();
}

function fetch_image_with_rating(PDO $pdo, int $imageId, ?int $userId = null): ?array
{
    $statement = $pdo->prepare(
        'SELECT
            i.id,
            i.filename,
            i.description,
            i.path,
            i.source,
            i.mime_type,
            i.created_at,
            COALESCE(AVG(r.rating), 0) AS average_rating,
            COUNT(r.id) AS ratings_count,
            MAX(CASE WHEN r.user_id = :user_id THEN r.rating END) AS user_rating,
            MAX(CASE WHEN r.user_id = :user_id_comment THEN r.comment END) AS user_comment
         FROM images i
         LEFT JOIN image_ratings r ON r.image_id = i.id
         WHERE i.id = :image_id
         GROUP BY i.id, i.filename, i.description, i.path, i.source, i.mime_type, i.created_at
         LIMIT 1',
    );
    $statement->execute([
        'user_id' => $userId ?? 0,
        'user_id_comment' => $userId ?? 0,
        'image_id' => $imageId,
    ]);
    $image = $statement->fetch();

    return $image ?: null;
}

function find_image_by_id(PDO $pdo, int $imageId): ?array
{
    $statement = $pdo->prepare('SELECT * FROM images WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $imageId]);
    $image = $statement->fetch();

    return $image ?: null;
}

function validate_image_upload(array $file): array
{
    $errors = [];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Učitavanje slike nije uspjelo.';
        return $errors;
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > 5 * 1024 * 1024) {
        $errors[] = 'Slika mora biti manja od 5 MB.';
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        $errors[] = 'Privremena datoteka nije valjana.';
        return $errors;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpName) ?: '';
    if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
        $errors[] = 'Dopuštene su samo JPEG i PNG slike.';
    }

    return $errors;
}

function create_uploaded_image(PDO $pdo, array $file, string $description, int $userId): int
{
    $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $extension = in_array($extension, ['jpg', 'jpeg', 'png'], true) ? $extension : 'jpg';
    $filename = uniqid('gallery_', true) . '.' . $extension;
    $targetPath = photos_directory() . DIRECTORY_SEPARATOR . $filename;

    if (!is_dir(photos_directory())) {
        mkdir(photos_directory(), 0777, true);
    }

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Premještanje učitane slike nije uspjelo.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($targetPath) ?: 'image/jpeg';

    $statement = $pdo->prepare(
        'INSERT INTO images (filename, description, path, source, mime_type, uploaded_by)
         VALUES (:filename, :description, :path, :source, :mime_type, :uploaded_by)',
    );
    $statement->execute([
        'filename' => $filename,
        'description' => $description !== '' ? $description : detect_photo_description($filename),
        'path' => relative_photo_path($filename),
        'source' => 'upload',
        'mime_type' => $mimeType,
        'uploaded_by' => $userId,
    ]);

    return (int) $pdo->lastInsertId();
}

function validate_rating_input(array $input): array
{
    $errors = [];
    $rating = (int) ($input['rating'] ?? 0);
    $comment = trim((string) ($input['comment'] ?? ''));

    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Ocjena mora biti između 1 i 5 zvjezdica.';
    }

    if (mb_strlen($comment) > 500) {
        $errors['comment'] = 'Komentar može imati najviše 500 znakova.';
    }

    return [[
        'rating' => $rating,
        'comment' => $comment,
    ], $errors];
}

function upsert_image_rating(PDO $pdo, int $userId, int $imageId, int $rating, string $comment): void
{
    $pdo->beginTransaction();

    try {
        $statement = $pdo->prepare(
            'INSERT INTO image_ratings (user_id, image_id, rating, comment)
             VALUES (:user_id, :image_id, :rating, :comment)
             ON DUPLICATE KEY UPDATE
               rating = VALUES(rating),
               comment = VALUES(comment),
               rated_at = CURRENT_TIMESTAMP',
        );
        $statement->execute([
            'user_id' => $userId,
            'image_id' => $imageId,
            'rating' => $rating,
            'comment' => $comment,
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function fetch_recent_image_comments(PDO $pdo, int $imageId, int $limit = 4): array
{
    $statement = $pdo->prepare(
        'SELECT ir.comment, ir.rating, ir.rated_at, u.username
         FROM image_ratings ir
         INNER JOIN users u ON u.id = ir.user_id
         WHERE ir.image_id = :image_id AND ir.comment <> ""
         ORDER BY ir.rated_at DESC
         LIMIT :limit',
    );
    $statement->bindValue(':image_id', $imageId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function fetch_user_image_ratings(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare(
        'SELECT i.id AS image_id, i.filename, i.description, i.path, ir.rating, ir.comment, ir.rated_at
         FROM image_ratings ir
         INNER JOIN images i ON i.id = ir.image_id
         WHERE ir.user_id = :user_id
         ORDER BY ir.rated_at DESC',
    );
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function fetch_admin_image_ratings(PDO $pdo, int $limit = 200): array
{
    $statement = $pdo->prepare(
        'SELECT ir.id, ir.rating, ir.comment, ir.rated_at, u.username, i.description, i.filename
         FROM image_ratings ir
         INNER JOIN users u ON u.id = ir.user_id
         INNER JOIN images i ON i.id = ir.image_id
         ORDER BY ir.rated_at DESC
         LIMIT :limit',
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function export_user_ratings_csv(PDO $pdo, int $userId): string
{
    $rows = fetch_user_image_ratings($pdo, $userId);
    $stream = fopen('php://temp', 'r+');
    if ($stream === false) {
        throw new RuntimeException('CSV izlaz nije moguće otvoriti.');
    }

    fputcsv($stream, ['filename', 'description', 'rating', 'comment', 'rated_at']);
    foreach ($rows as $row) {
        fputcsv($stream, [
            $row['filename'],
            $row['description'],
            $row['rating'],
            $row['comment'],
            $row['rated_at'],
        ]);
    }

    rewind($stream);
    $csv = stream_get_contents($stream);
    fclose($stream);

    return $csv === false ? '' : $csv;
}

function import_ratings_from_csv(PDO $pdo, array $file): int
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('CSV datoteka nije uspješno učitana.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('CSV datoteka nije valjana.');
    }

    $csv = new SplFileObject($tmpName);
    $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
    $headers = $csv->fgetcsv();
    if (!is_array($headers)) {
        throw new RuntimeException('CSV zaglavlje nije moguće pročitati.');
    }

    $headerIndex = array_flip($headers);
    foreach (['username', 'filename', 'rating', 'comment'] as $required) {
        if (!array_key_exists($required, $headerIndex)) {
            throw new RuntimeException('CSV mora sadržavati stupac: ' . $required);
        }
    }

    $userStatement = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $imageStatement = $pdo->prepare('SELECT id FROM images WHERE filename = :filename LIMIT 1');
    $ratingStatement = $pdo->prepare(
        'INSERT INTO image_ratings (user_id, image_id, rating, comment)
         VALUES (:user_id, :image_id, :rating, :comment)
         ON DUPLICATE KEY UPDATE
           rating = VALUES(rating),
           comment = VALUES(comment),
           rated_at = CURRENT_TIMESTAMP',
    );

    $count = 0;
    $pdo->beginTransaction();

    try {
        foreach ($csv as $row) {
            if (!is_array($row) || $row === [null]) {
                continue;
            }

            $username = trim((string) ($row[$headerIndex['username']] ?? ''));
            $filename = trim((string) ($row[$headerIndex['filename']] ?? ''));
            $rating = (int) ($row[$headerIndex['rating']] ?? 0);
            $comment = trim((string) ($row[$headerIndex['comment']] ?? ''));

            if ($username === '' || $filename === '' || $rating < 1 || $rating > 5) {
                continue;
            }

            $userStatement->execute(['username' => $username]);
            $userId = (int) $userStatement->fetchColumn();
            if ($userId < 1) {
                continue;
            }

            $imageStatement->execute(['filename' => $filename]);
            $imageId = (int) $imageStatement->fetchColumn();
            if ($imageId < 1) {
                continue;
            }

            $ratingStatement->execute([
                'user_id' => $userId,
                'image_id' => $imageId,
                'rating' => $rating,
                'comment' => mb_substr($comment, 0, 500),
            ]);
            $count++;
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    return $count;
}
