<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

require_standard_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf_or_abort();

$action = value_from($_POST, 'action');
$filmId = (int) ($_POST['film_id'] ?? 0);
$returnTo = safe_redirect_target(value_from($_POST, 'return_to'), 'index.php');

if ($filmId < 1) {
    set_flash('error', 'Odabrani film nije valjan.');
    redirect($returnTo);
}

try {
    if ($action === 'add') {
        $result = add_film_to_library($pdo, (int) $currentUser['id'], $filmId);

        if ($result['added']) {
            set_flash('success', 'Film "' . $result['title'] . '" dodan je u vašu videoteku.');
        } else {
            set_flash('info', 'Film je već spremljen u vašoj videoteci.');
        }
    } elseif ($action === 'remove') {
        $removed = remove_film_from_library($pdo, (int) $currentUser['id'], $filmId);
        set_flash(
            $removed ? 'success' : 'info',
            $removed
                ? 'Film je uklonjen iz vaše videoteke.'
                : 'Film nije pronađen u vašoj videoteci.',
        );
    } else {
        set_flash('error', 'Nepoznata akcija.');
    }
} catch (Throwable $exception) {
    set_flash('error', 'Greška pri spremanju u bazu: ' . $exception->getMessage());
}

redirect($returnTo);
