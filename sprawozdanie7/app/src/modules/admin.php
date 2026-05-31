<?php
/**
 * Panel administratora - pełna funkcjonalność
 */

if (!hasRole('admin')) {
    redirect('/?action=home');
}

// Obsługa akcji administratora
if ($_POST) {
    $adminAction = $_POST['admin_action'] ?? '';
    
    switch ($adminAction) {
        case 'approve_user':
            $userId = (int)$_POST['user_id'];
            $stmt = db()->prepare("UPDATE users SET approved = TRUE WHERE id = ?");
            $stmt->execute([$userId]);
            addNotification($userId, 'system', 'Konto zatwierdzone', 'Twoje konto zostało aktywowane przez administratora.');
            echo alert('Użytkownik zatwierdzony!', 'success');
            break;
            
        case 'reject_user':
            $userId = (int)$_POST['user_id'];
            $stmt = db()->prepare("DELETE FROM users WHERE id = ? AND approved = FALSE");
            $stmt->execute([$userId]);
            echo alert('Użytkownik odrzucony i usunięty.', 'info');
            break;
            
        case 'change_role':
            $userId = (int)$_POST['user_id'];
            $newRole = $_POST['new_role'];
            $stmt = db()->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            addNotification($userId, 'system', 'Zmiana roli', "Twoja rola została zmieniona na: $newRole");
            echo alert('Rola użytkownika zmieniona!', 'success');
            break;
            
        case 'create_category':
            $title = sanitize($_POST['title']);
            $description = sanitize($_POST['description']);
            $accessRole = $_POST['access_role'];
            $order = (int)$_POST['display_order'];
            $stmt = db()->prepare("INSERT INTO categories (title, description, access_role, display_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description, $accessRole, $order]);
            echo alert('Kategoria utworzona!', 'success');
            break;
            
        case 'create_forum':
            $categoryId = (int)$_POST['category_id'];
            $title = sanitize($_POST['title']);
            $description = sanitize($_POST['description']);
            $accessRole = $_POST['access_role'];
            $order = (int)$_POST['display_order'];
            $stmt = db()->prepare("INSERT INTO forums (category_id, title, description, access_role, display_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$categoryId, $title, $description, $accessRole, $order]);
            echo alert('Forum utworzone!', 'success');
            break;
            
        case 'resolve_report':
            $reportId = (int)$_POST['report_id'];
            $resolution = sanitize($_POST['resolution']);
            $status = $_POST['status'];
            $stmt = db()->prepare("UPDATE reports SET status = ?, resolution = ?, reviewed_by = ?, resolved_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $resolution, $user['id'], $reportId]);
            echo alert('Zgłoszenie rozpatrzone!', 'success');
            break;
    }
}
?>

<h2><i class="bi bi-gear"></i> Panel Administratora</h2>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#users">Użytkownicy</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#forums">Zarządzanie Forum</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#reports">Zgłoszenia</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#logs">Logi</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#stats">Statystyki</a></li>
</ul>

<div class="tab-content">
    <!-- UŻYTKOWNICY -->
    <div class="tab-pane fade show active" id="users">
        <h3>Oczekujący użytkownicy</h3>
        <?php
        $stmt = db()->query("SELECT * FROM users WHERE approved = FALSE AND email_verified = TRUE ORDER BY created_at DESC");
        $pendingUsers = $stmt->fetchAll();
        ?>
        <?php if (empty($pendingUsers)): ?>
            <p class="text-muted">Brak oczekujących użytkowników</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Data rejestracji</th><th>Akcje</th></tr></thead>
                    <tbody>
                        <?php foreach ($pendingUsers as $u): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td><?= sanitize($u['username']) ?></td>
                                <td><?= sanitize($u['email']) ?></td>
                                <td><?= formatDate($u['created_at']) ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="admin_action" value="approve_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button class="btn btn-sm btn-success">Zatwierdź</button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="admin_action" value="reject_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno odrzucić tego użytkownika?')">Odrzuć</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <hr class="my-4">
        <h3>Wszyscy użytkownicy</h3>
        <?php
        $stmt = db()->query("SELECT * FROM users WHERE approved = TRUE ORDER BY created_at DESC");
        $allUsers = $stmt->fetchAll();
        ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Rola</th><th>Status</th><th>Ostatnia aktywność</th><th>Akcje</th></tr></thead>
                <tbody>
                    <?php foreach ($allUsers as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= sanitize($u['username']) ?></td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td><span class="badge bg-<?= $u['role'] == 'admin' ? 'danger' : ($u['role'] == 'mg' ? 'warning' : 'primary') ?>"><?= strtoupper($u['role']) ?></span></td>
                            <td><?php if (isUserOnline($u['id'])): ?><span class="badge bg-success">Online</span><?php else: ?><span class="badge bg-secondary">Offline</span><?php endif; ?></td>
                            <td><?= formatDate($u['last_activity']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#changeRoleModal<?= $u['id'] ?>">Zmień rolę</button>
                                <div class="modal fade" id="changeRoleModal<?= $u['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog"><div class="modal-content">
                                        <form method="post">
                                            <div class="modal-header"><h5 class="modal-title">Zmień rolę: <?= sanitize($u['username']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body">
                                                <input type="hidden" name="admin_action" value="change_role">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <label>Nowa rola</label>
                                                <select name="new_role" class="form-select">
                                                    <option value="player" <?= $u['role']=='player'?'selected':'' ?>>Player</option>
                                                    <option value="mg" <?= $u['role']=='mg'?'selected':'' ?>>Mistrz Gry</option>
                                                    <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Administrator</option>
                                                </select>
                                            </div>
                                            <div class="modal-footer"><button type="submit" class="btn btn-primary">Zapisz</button></div>
                                        </form>
                                    </div></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- ZARZĄDZANIE FORUM -->
    <div class="tab-pane fade" id="forums">
        <h3>Kategorie i fora</h3>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#newCategoryModal"><i class="bi bi-plus-circle"></i> Nowa kategoria</button>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#newForumModal"><i class="bi bi-plus-circle"></i> Nowe forum</button>
        <?php
        $stmt = db()->query("SELECT * FROM categories ORDER BY display_order");
        $categories = $stmt->fetchAll();
        foreach ($categories as $cat):
        ?>
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white"><strong><?= sanitize($cat['title']) ?></strong> <span class="badge bg-light text-dark"><?= $cat['access_role'] ?></span></div>
                <div class="card-body">
                    <?php $stmt2 = db()->prepare("SELECT * FROM forums WHERE category_id = ? ORDER BY display_order"); $stmt2->execute([$cat['id']]); $forums = $stmt2->fetchAll(); ?>
                    <ul class="list-group">
                        <?php foreach ($forums as $forum): ?>
                            <li class="list-group-item"><?= sanitize($forum['title']) ?> <span class="badge bg-info"><?= $forum['access_role'] ?></span><?php if ($forum['archived']): ?> <span class="badge bg-warning">Zarchiwizowane</span><?php endif; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
        <!-- Modal nowej kategorii -->
        <div class="modal fade" id="newCategoryModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><div class="modal-header"><h5>Nowa kategoria</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="admin_action" value="create_category"><div class="mb-3"><label>Tytuł</label><input name="title" class="form-control" required></div><div class="mb-3"><label>Opis</label><textarea name="description" class="form-control"></textarea></div><div class="mb-3"><label>Dostęp</label><select name="access_role" class="form-select"><option value="all">Wszyscy</option><option value="player">Gracze</option><option value="mg">MG</option><option value="admin">Admin</option></select></div><div class="mb-3"><label>Kolejność</label><input name="display_order" type="number" class="form-control" value="0"></div></div><div class="modal-footer"><button type="submit" class="btn btn-success">Utwórz</button></div></form></div></div></div>
        <!-- Modal nowego forum -->
        <div class="modal fade" id="newForumModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><div class="modal-header"><h5>Nowe forum</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="admin_action" value="create_forum"><div class="mb-3"><label>Kategoria</label><select name="category_id" class="form-select" required><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= sanitize($cat['title']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label>Tytuł</label><input name="title" class="form-control" required></div><div class="mb-3"><label>Opis</label><textarea name="description" class="form-control"></textarea></div><div class="mb-3"><label>Dostęp</label><select name="access_role" class="form-select"><option value="all">Wszyscy</option><option value="player">Gracze</option><option value="mg">MG</option><option value="admin">Admin</option></select></div><div class="mb-3"><label>Kolejność</label><input name="display_order" type="number" class="form-control" value="0"></div></div><div class="modal-footer"><button type="submit" class="btn btn-primary">Utwórz</button></div></form></div></div></div>
    </div>
    
    <!-- ZGŁOSZENIA -->
    <div class="tab-pane fade" id="reports">
        <h3>Zgłoszenia do moderacji</h3>
        <?php
        $stmt = db()->query("SELECT r.*, u.username as reporter_name, reviewer.username as reviewer_name FROM reports r JOIN users u ON r.reporter_id = u.id LEFT JOIN users reviewer ON r.reviewed_by = reviewer.id ORDER BY CASE r.status WHEN 'pending' THEN 1 WHEN 'reviewed' THEN 2 ELSE 3 END, r.created_at DESC");
        $reports = $stmt->fetchAll();
        ?>
        <?php if (empty($reports)): ?>
            <p class="text-muted">Brak zgłoszeń</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>ID</th><th>Zgłaszający</th><th>Typ</th><th>Powód</th><th>Status</th><th>Data</th><th>Akcje</th></tr></thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?= $report['id'] ?></td>
                                <td><?= sanitize($report['reporter_name']) ?></td>
                                <td><span class="badge bg-info"><?= $report['reported_type'] ?></span></td>
                                <td><?= sanitize(substr($report['reason'], 0, 50)) ?>...</td>
                                <td><span class="badge bg-<?= $report['status'] == 'pending' ? 'warning' : 'success' ?>"><?= $report['status'] ?></span></td>
                                <td><?= formatDate($report['created_at']) ?></td>
                                <td>
                                    <?php if ($report['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#resolveModal<?= $report['id'] ?>">Rozpatrz</button>
                                        <div class="modal fade" id="resolveModal<?= $report['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><div class="modal-header"><h5>Rozpatrz zgłoszenie #<?= $report['id'] ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="admin_action" value="resolve_report"><input type="hidden" name="report_id" value="<?= $report['id'] ?>"><p><strong>Powód:</strong><br><?= sanitize($report['reason']) ?></p><div class="mb-3"><label>Decyzja</label><select name="status" class="form-select" required><option value="resolved">Rozwiązane</option><option value="dismissed">Odrzucone</option></select></div><div class="mb-3"><label>Komentarz</label><textarea name="resolution" class="form-control" rows="3" required></textarea></div></div><div class="modal-footer"><button type="submit" class="btn btn-primary">Zapisz decyzję</button></div></form></div></div></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- LOGI -->
    <div class="tab-pane fade" id="logs">
        <h3>Logi aktywności</h3>
        <?php
        $stmt = db()->query("SELECT al.*, u.username FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 100");
        $logs = $stmt->fetchAll();
        ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead><tr><th>Data</th><th>Użytkownik</th><th>Akcja</th><th>Cel</th><th>IP</th></tr></thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= formatDate($log['created_at']) ?></td>
                            <td><?= $log['username'] ? sanitize($log['username']) : 'System' ?></td>
                            <td><code><?= $log['action_type'] ?></code></td>
                            <td><?php if ($log['target_type']): ?><?= $log['target_type'] ?> #<?= $log['target_id'] ?><?php endif; ?></td>
                            <td><small><?= $log['ip_address'] ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- STATYSTYKI -->
    <div class="tab-pane fade" id="stats">
        <h3>Statystyki systemu</h3>
        <div class="row">
            <div class="col-md-3 mb-3"><div class="card text-center"><div class="card-body"><h4><?= db()->query("SELECT COUNT(*) FROM users WHERE approved = TRUE")->fetchColumn() ?></h4><p>Użytkowników</p></div></div></div>
            <div class="col-md-3 mb-3"><div class="card text-center"><div class="card-body"><h4><?= db()->query("SELECT COUNT(*) FROM characters WHERE approved_by_mg = TRUE")->fetchColumn() ?></h4><p>Postaci</p></div></div></div>
            <div class="col-md-3 mb-3"><div class="card text-center"><div class="card-body"><h4><?= db()->query("SELECT COUNT(*) FROM threads")->fetchColumn() ?></h4><p>Wątków</p></div></div></div>
            <div class="col-md-3 mb-3"><div class="card text-center"><div class="card-body"><h4><?= db()->query("SELECT COUNT(*) FROM posts")->fetchColumn() ?></h4><p>Postów</p></div></div></div>
        </div>
    </div>
</div>
