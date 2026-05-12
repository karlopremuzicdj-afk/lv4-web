<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf_or_abort();
log_user_out();
set_flash('success', 'Uspješno ste se odjavili.');
redirect('index.php');

