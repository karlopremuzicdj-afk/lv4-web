<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$form = [
    'username' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $form['username'] = trim(value_from($_POST, 'username'));
    $form['email'] = trim(value_from($_POST, 'email'));
    $password = value_from($_POST, 'password');
    $passwordConfirm = value_from($_POST, 'password_confirm');

    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $form['username'])) {
        $errors['username'] = 'Korisničko ime mora imati 3-30 znakova i sadržavati samo slova, brojeve ili _.';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email adresa nije ispravna.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Lozinka mora imati barem 8 znakova.';
    }

    if ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Lozinke se ne podudaraju.';
    }

    if ($errors === []) {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username OR email = :email');
        $statement->execute([
            'username' => $form['username'],
            'email' => $form['email'],
        ]);

        if ((int) $statement->fetchColumn() > 0) {
            $errors['username'] = 'Korisničko ime ili email već postoje.';
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, role)
                 VALUES (:username, :email, :password_hash, :role)',
            );
            $insert->execute([
                'username' => $form['username'],
                'email' => $form['email'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'user',
            ]);

            $user = find_user_by_identity($pdo, $form['username']);
            if ($user) {
                log_user_in($user);
            }

            set_flash('success', 'Registracija je uspješna. Vaš korisnički račun je spreman.');
            redirect('index.php');
        }
    }
}

$pageTitle = 'Registracija';
$pageHeading = 'Registracija korisnika';
$pageDescription = 'Izradite korisnički račun za spremanje filmova i upravljanje osobnom videotekom.';
$styles = ['style.css'];
$activePage = 'home';

require __DIR__ . '/includes/header.php';
?>
      <section class="page-shell">
        <article class="form-card">
          <h2>Registracija</h2>
          <form method="post" class="stack-form">
            <?= csrf_field() ?>

            <div class="filter-group">
              <label for="username">Korisničko ime</label>
              <input type="text" id="username" name="username" value="<?= e($form['username']) ?>" required>
<?php if (isset($errors['username'])): ?>
              <p class="field-error"><?= e($errors['username']) ?></p>
<?php endif; ?>
            </div>

            <div class="filter-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?= e($form['email']) ?>" required>
<?php if (isset($errors['email'])): ?>
              <p class="field-error"><?= e($errors['email']) ?></p>
<?php endif; ?>
            </div>

            <div class="filter-group">
              <label for="password">Lozinka</label>
              <input type="password" id="password" name="password" required>
<?php if (isset($errors['password'])): ?>
              <p class="field-error"><?= e($errors['password']) ?></p>
<?php endif; ?>
            </div>

            <div class="filter-group">
              <label for="password_confirm">Ponovite lozinku</label>
              <input type="password" id="password_confirm" name="password_confirm" required>
<?php if (isset($errors['password_confirm'])): ?>
              <p class="field-error"><?= e($errors['password_confirm']) ?></p>
<?php endif; ?>
            </div>

            <div class="action-row">
              <button type="submit">Kreiraj račun</button>
              <a class="secondary-button" href="login.php">Već imate račun?</a>
            </div>
          </form>
        </article>
      </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
