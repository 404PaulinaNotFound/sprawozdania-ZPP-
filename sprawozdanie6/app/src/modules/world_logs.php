<?php
/**
 * LOGI ŚWIATOWE / HISTORIA ŚWIATA
 */

if (!defined('APP_NAME')) die('Direct access not allowed');

if ($action == 'world_logs') {
    // Tworzenie wpisu (tylko MG i admin)
    if ($_POST && isset($_POST['create_log']) && hasRole('mg')) {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $event_date = $_POST['event_date'];
        
        $stmt = db()->prepare("
            INSERT INTO world_logs (title, description, event_date, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $event_date, $user['id']]);
        
        echo alert('Wpis dodany do historii świata', 'success');
    }
    
    // Lista logów
    $stmt = db()->query("
        SELECT wl.*, u.username as creator_name
        FROM world_logs wl
        JOIN users u ON wl.created_by = u.id
        ORDER BY wl.event_date DESC
    ");
    $logs = $stmt->fetchAll();
    ?>
    <h2><i class="bi bi-journal-text"></i> Historia Świata</h2>
    <p class="text-muted">Ważne wydarzenia kształtujące fabułę gry</p>
    
    <?php if (hasRole('mg')): ?>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createLogModal">
            <i class="bi bi-plus-circle"></i> Dodaj wpis
        </button>
    <?php endif; ?>
    
    <div class="timeline">
        <?php foreach ($logs as $log): ?>
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <div class="d-flex justify-content-between">
                        <h5><i class="bi bi-calendar3"></i> <?= date('d.m.Y', strtotime($log['event_date'])) ?></h5>
                        <small>Autor: <?= sanitize($log['creator_name']) ?></small>
                    </div>
                </div>
                <div class="card-body">
                    <h4><?= sanitize($log['title']) ?></h4>
                    <p><?= nl2br(sanitize($log['description'])) ?></p>
                    <small class="text-muted">Dodano: <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (count($logs) == 0): ?>
        <div class="alert alert-info">Brak wpisów w historii świata</div>
    <?php endif; ?>
    
    <!-- Modal tworzenia wpisu -->
    <div class="modal fade" id="createLogModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Nowy wpis w historii świata</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Tytuł wydarzenia</label>
                            <input name="title" class="form-control" required maxlength="200">
                        </div>
                        <div class="mb-3">
                            <label>Opis</label>
                            <textarea name="description" class="form-control" rows="6" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Data wydarzenia w fabule</label>
                            <input name="event_date" type="date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="create_log" class="btn btn-success">Dodaj wpis</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}
?>
