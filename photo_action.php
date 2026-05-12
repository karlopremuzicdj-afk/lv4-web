<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $requestMethod === 'POST' ? value_from($_POST, 'action') : value_from($_GET, 'action');
$returnTo = safe_redirect_target(
    $requestMethod === 'POST' ? value_from($_POST, 'return_to') : value_from($_GET, 'return_to'),
    'slike.php',
);

if ($action === 'export_my_ratings') {
    require_standard_user();

    try {
        $csv = export_user_ratings_csv($pdo, (int) $currentUser['id']);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="moje_ocjene.csv"');
        echo $csv;
        exit;
    } catch (Throwable $exception) {
        set_flash('error', 'CSV izvoz nije uspio: ' . $exception->getMessage());
        redirect('slike.php');
    }
}

if ($requestMethod !== 'POST') {
    redirect('slike.php');
}

verify_csrf_or_abort();

try {
    if ($action === 'rate_image') {
        require_standard_user();
        sync_local_images($pdo);

        $imageId = (int) ($_POST['image_id'] ?? 0);
        if ($imageId < 1 || !find_image_by_id($pdo, $imageId)) {
            throw new RuntimeException('Odabrana fotografija nije pronađena.');
        }

        [$cleanData, $errors] = validate_rating_input($_POST);
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', array_values($errors)));
        }

        upsert_image_rating(
            $pdo,
            (int) $currentUser['id'],
            $imageId,
            (int) $cleanData['rating'],
            (string) $cleanData['comment'],
        );
        set_flash('success', 'Ocjena fotografije je spremljena.');
    } elseif ($action === 'upload_image') {
        require_admin();
        $description = trim(value_from($_POST, 'description'));
        $photoFile = $_FILES['photo_file'] ?? [];
        $errors = validate_image_upload($photoFile);

        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        create_uploaded_image($pdo, $photoFile, $description, (int) $currentUser['id']);
        set_flash('success', 'Nova fotografija je uspješno dodana u galeriju.');
    } elseif ($action === 'import_ratings') {
        require_admin();
        $ratingsFile = $_FILES['ratings_csv'] ?? [];
        $imported = import_ratings_from_csv($pdo, $ratingsFile);
        set_flash('success', 'CSV import ocjena je završen. Uvezeno redaka: ' . $imported . '.');
    } elseif ($action === 'sync_images') {
        require_admin();
        $count = sync_local_images($pdo);
        set_flash('success', 'Sinkronizacija mape /slike/ je završena. Obrađeno datoteka: ' . $count . '.');
    } else {
        throw new RuntimeException('Nepoznata akcija nad fotografijama.');
    }
} catch (Throwable $exception) {
    set_flash('error', $exception->getMessage());
}

redirect($returnTo);
