<?php
/**
 * PANEL ADMINISTRATORA - SECURED
 * - Akceptacja nowych użytkowników
 * - Zarządzanie użytkownikami
 * - Zarządzanie forum (kategorie, fora)
 * - Statystyki systemu
 */

if (!defined('APP_NAME')) die('Direct access not allowed');

if (!hasRole('admin')) {
    echo alert('Brak dostępu - wymagana rola administratora', 'danger');
    exit;
}

if ($action == 'admin') {
    $pending_users = db()->query("SELECT COUNT(*) FROM users WHERE approved = FALSE AND email_verified = TRUE")->fetchColumn();
    $total_users = db()->query("SELECT COUNT(*) FROM users WHERE approved = TRUE")->fetchColumn();
    $total_chars = db()->query("SELECT COUNT(*) FROM characters")->fetchColumn();
    $total_threads = db()->query("SELECT COUNT(*) FROM threads")->fetchColumn();
    $total_posts = db()->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    ?>
    <h2><i class="bi bi-gear"></i> Panel Administratora</h2>
    <div class="row mb-4">
        <div class="col-md-2"><div class="card text-center"><div class="card-body"><h3 class="text-warning"><?= $pending_users ?></h3><p>Użytkownicy do akceptacji</p><a href="?action=admin_users" class="btn btn-sm btn-primary">Zarządzaj</a></div></div></div>
        <div class="col-md-2"><div class="card text-center"><div class="card-body"><h3><?= $total_users ?></h3><p>Użytkownicy</p></div></div></div>
        <div class="col-md-2"><div class="card text-center"><div class="card-body"><h3><?= $total_chars ?></h3><p>Postacie</p></div></div></div>
        <div class="col-md-2"><div class="card text-center"><div class="card-body"><h3><?= $total_threads ?></h3><p>Wątki</p></div></div></div>
        <div class="col-md-2"><div class="card text-center"><div class="card-body"><h3><?= $total_posts ?></h3><p>Posty</p></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Zarządzanie</h5></div>
                <div class="card-body">
                    <a href="?action=admin_users" class="btn btn-primary btn-block mb-2"><i class="bi bi-people"></i> Zarządzaj użytkownikami</a>
                    <a href="?action=admin_forum" class="btn btn-primary btn-block mb-2"><i class="bi bi-chat-dots"></i> Zarządzaj forum</a>
                    <a href="?action=admin_achievements" class="btn btn-primary btn-block mb-2"><i class="bi bi-trophy"></i> Zarządzaj odznakami</a>
                    <a href="?action=admin_logs" class="btn btn-primary btn-block"><i class="bi bi-journal-text"></i> Logi aktywności</a>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
