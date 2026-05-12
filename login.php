<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$identity = '';
$next = safe_redirect_target(value_from($_GET, 'next') ?: value_from($_POST, 'next'), 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $identity = trim(value_from($_POST, 'identity'));
    $password = value_from($_POST, 'password');

    if ($identity === '') {
        $errors['identity'] = 'Unesite korisničko ime ili email.';
    }

    if ($password === '') {
        $errors['password'] = 'Unesite lozinku.';
    }

    if ($errors === []) {
        $user = find_user_by_identity($pdo, $identity);

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $errors['identity'] = 'Pogrešno korisničko ime, email ili lozinka.';
        } else {
            log_user_in($user);
            set_flash('success', 'Prijava je uspješna. Dobro došli natrag, ' . $user['username'] . '!');
            redirect($next);
        }
    }
}

$pageTitle = 'Prijava';
$pageHeading = 'Prijava korisnika';
$pageDescription = 'Prijavite se kako biste spremali filmove u osobnu videoteku.';
$styles = ['style.css'];
$activePage = 'home';

require __DIR__ . '/includes/header.php';
?>
      <section class="page-shell">
        <article class="form-card">
          <h2>Prijava</h2>
          <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">

            <div class="filter-group">
              <label for="identity">Korisničko ime ili email</label>
              <input type="text" id="identity" name="identity" value="<?= e($identity) ?>" required>
<?php if (isset($errors['identity'])): ?>
              <p class="field-error"><?= e($errors['identity']) ?></p>
<?php endif; ?>
            </div>

            <div class="filter-group">
              <label for="password">Lozinka</label>
              <input type="password" id="password" name="password" required>
<?php if (isset($errors['password'])): ?>
              <p class="field-error"><?= e($errors['password']) ?></p>
<?php endif; ?>
            </div>

            <div class="action-row">
              <button type="submit">Prijava</button>
              <a class="secondary-button" href="register.php">Nemate račun?</a>
            </div>
          </form>
        </article>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

