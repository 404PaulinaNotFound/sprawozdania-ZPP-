<?php
/**
 * PBF System - Etap 4
 * Główny router z obsługą forum, wątków i postów
 */

session_start();
require 'config.php';
require 'mailer.php';

$action = $_GET['action'] ?? 'home';
$user = getCurrentUser();

if (isset($_GET['logout'])) {
    logActivity('user_logout');
    session_destroy();
    redirect('/');
}

if ($action == 'login' && $_POST && !$user) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $stmt = db()->prepare("SELECT * FROM users WHERE username = ? AND approved = TRUE AND email_verified = TRUE");
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password'])) {
        $_SESSION['user_id'] = $u['id'];
        logActivity('user_login');
        redirect('/');
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/"><i class="bi bi-dice-5"></i> <?= APP_NAME ?></a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="?action=forum"><i class="bi bi-chat-dots"></i> Forum</a></li>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> <?= sanitize($user['username']) ?></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?logout=1">Wyloguj</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="?action=login">Zaloguj</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=register">Rejestracja</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
<?php

switch ($action) {
    case 'forum':
        $stmt = db()->query("SELECT c.*, (SELECT COUNT(*) FROM forums f WHERE f.category_id = c.id) as forum_count FROM categories c ORDER BY c.display_order");
        $categories = $stmt->fetchAll();
        ?>
        <h2><i class="bi bi-chat-dots"></i> Forum</h2>
        <?php foreach ($categories as $cat): ?>
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white"><h4><?= sanitize($cat['title']) ?></h4></div>
            <div class="card-body">
                <?php
                $stmt = db()->prepare("SELECT * FROM forums WHERE category_id = ? ORDER BY display_order");
                $stmt->execute([$cat['id']]);
                $forums = $stmt->fetchAll();
                ?>
                <div class="list-group">
                    <?php foreach ($forums as $forum): ?>
                    <a href="?action=threads&forum_id=<?= $forum['id'] ?>" class="list-group-item list-group-item-action">
                        <h5><?= sanitize($forum['title']) ?></h5>
                        <p class="mb-0 text-muted"><?= sanitize($forum['description']) ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach;
        break;

    case 'threads':
        require 'modules/threads.php';
        break;

    case 'thread_view':
        require 'modules/thread_view.php';
        break;

    case 'forum_search':
        require 'modules/forum_complete.php';
        break;

    case 'login':
        if (isset($_POST['username'])) echo '<div class="alert alert-danger">Błędne dane logowania.</div>';
        ?>
        <div class="row justify-content-center"><div class="col-md-6">
        <div class="card"><div class="card-header"><h4>Logowanie</h4></div><div class="card-body">
        <form method="post">
            <div class="mb-3"><label>Nazwa użytkownika</label><input name="username" class="form-control" required></div>
            <div class="mb-3"><label>Hasło</label><input name="password" type="password" class="form-control" required></div>
            <button class="btn btn-primary">Zaloguj</button>
        </form>
        </div></div>
        </div></div>
        <?php break;

    case 'register':
        if ($_POST) {
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $token = generateToken();
            try {
                $stmt = db()->prepare("INSERT INTO users (username, email, password, verification_token) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hash, $token]);
                sendVerificationEmail($email, $username, $token);
                echo '<div class="alert alert-success">Konto utworzone! Sprawdź email.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Błąd: ' . $e->getMessage() . '</div>';
            }
        }
        ?>
        <div class="row justify-content-center"><div class="col-md-6">
        <div class="card"><div class="card-header"><h4>Rejestracja</h4></div><div class="card-body">
        <form method="post">
            <div class="mb-3"><label>Nazwa użytkownika</label><input name="username" class="form-control" required></div>
            <div class="mb-3"><label>Email</label><input name="email" type="email" class="form-control" required></div>
            <div class="mb-3"><label>Hasło</label><input name="password" type="password" class="form-control" required minlength="6"></div>
            <button class="btn btn-primary">Zarejestruj się</button>
        </form>
        </div></div>
        </div></div>
        <?php break;

    default:
        ?>
        <div class="jumbotron bg-light p-5 rounded">
            <h1 class="display-4"><i class="bi bi-dice-5"></i> <?= APP_NAME ?></h1>
            <p class="lead">System PBF - Play-by-Forum RPG</p>
            <hr class="my-4">
            <?php if (!$user): ?>
            <a class="btn btn-primary btn-lg" href="?action=register">Zarejestruj się</a>
            <a class="btn btn-secondary btn-lg" href="?action=login">Zaloguj</a>
            <?php else: ?>
            <p>Witaj, <strong><?= sanitize($user['username']) ?></strong>!</p>
            <a class="btn btn-primary btn-lg" href="?action=forum">Przejdź do forum</a>
            <?php endif; ?>
        </div>
        <?php
        break;
}
?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
