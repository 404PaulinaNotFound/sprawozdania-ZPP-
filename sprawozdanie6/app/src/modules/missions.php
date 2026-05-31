<?php
/**
 * Moduł misji
 */

if (!$user) redirect('/?action=login');

if ($action == 'missions') {
    $stmt = db()->query("
        SELECT m.*, u.username as created_by_name,
               (SELECT COUNT(*) FROM mission_participants mp WHERE mp.mission_id = m.id) as participant_count
        FROM missions m
        JOIN users u ON m.created_by = u.id
        ORDER BY m.created_at DESC
    ");
    $missions = $stmt->fetchAll();
    ?>
    <h2><i class="bi bi-map"></i> Misje</h2>
    <?php if (hasRole('mg')): ?>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#newMissionModal">
        <i class="bi bi-plus-circle"></i> Nowa misja
    </button>
    <?php endif; ?>
    
    <div class="row">
    <?php foreach ($missions as $m): ?>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5><?= sanitize($m['title']) ?></h5>
                    <span class="badge bg-<?= $m['status']=='active'?'success':($m['status']=='completed'?'secondary':'primary') ?>"><?= $m['status'] ?></span>
                </div>
                <div class="card-body">
                    <p><?= sanitize(substr($m['description'], 0, 150)) ?>...</p>
                    <p><small>Uczestnicy: <?= $m['participant_count'] ?>/<?= $m['max_participants'] ?? '?' ?></small></p>
                    <p><small>Nagrody: <strong><?= $m['reward_xp'] ?> XP</strong>, <?= $m['reward_gold'] ?> złota</small></p>
                </div>
                <div class="card-footer">
                    <a href="?action=mission_view&id=<?= $m['id'] ?>" class="btn btn-primary btn-sm">Szczegóły</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    
    <?php if (hasRole('mg')): ?>
    <div class="modal fade" id="newMissionModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
        <form method="post">
            <div class="modal-header"><h5 class="modal-title">Nowa misja</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="mission_action" value="create">
                <div class="mb-3"><label>Tytuł</label><input name="title" class="form-control" required></div>
                <div class="mb-3"><label>Opis</label><textarea name="description" class="form-control" rows="5" required></textarea></div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Nagroda XP</label><input name="reward_xp" type="number" class="form-control" value="100" min="0"></div>
                    <div class="col-md-4 mb-3"><label>Nagroda złota</label><input name="reward_gold" type="number" class="form-control" value="0" min="0"></div>
                    <div class="col-md-4 mb-3"><label>Maks. uczestników</label><input name="max_participants" type="number" class="form-control" value="5" min="1"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success">Utwórz</button></div>
        </form>
    </div></div></div>
    <?php endif;
}

elseif ($action == 'mission_view') {
    $missionId = (int)($_GET['id'] ?? 0);
    
    if ($_POST) {
        $ma = $_POST['mission_action'] ?? '';
        if ($ma == 'create' && hasRole('mg')) {
            $stmt = db()->prepare("INSERT INTO missions (title, description, reward_xp, reward_gold, max_participants, created_by, status) VALUES (?, ?, ?, ?, ?, ?, 'proposed')");
            $stmt->execute([sanitize($_POST['title']), sanitize($_POST['description']), (int)$_POST['reward_xp'], (int)$_POST['reward_gold'], (int)$_POST['max_participants'], $user['id']]);
            redirect('?action=missions');
        } elseif ($ma == 'join') {
            $charId = (int)$_POST['character_id'];
            $stmt = db()->prepare("INSERT IGNORE INTO mission_participants (mission_id, character_id) VALUES (?, ?)");
            $stmt->execute([$missionId, $charId]);
            echo alert('Dołączono do misji!', 'success');
        } elseif ($ma == 'leave') {
            $charId = (int)$_POST['character_id'];
            $stmt = db()->prepare("DELETE FROM mission_participants WHERE mission_id = ? AND character_id = ?");
            $stmt->execute([$missionId, $charId]);
            echo alert('Wypisano z misji.', 'info');
        } elseif ($ma == 'change_status' && hasRole('mg')) {
            $newStatus = $_POST['new_status'];
            if (in_array($newStatus, ['proposed','active','completed','failed'])) {
                db()->prepare("UPDATE missions SET status = ? WHERE id = ?")->execute([$newStatus, $missionId]);
                if ($newStatus == 'completed') {
                    $ps = db()->prepare("SELECT c.user_id FROM mission_participants mp JOIN characters c ON mp.character_id = c.id WHERE mp.mission_id = ?");
                    $ps->execute([$missionId]);
                    $m = db()->prepare("SELECT title, reward_xp FROM missions WHERE id = ?"); $m->execute([$missionId]); $mission_data = $m->fetch();
                    foreach ($ps->fetchAll() as $p) {
                        addNotification($p['user_id'], 'system', 'Misja ukończona!', 'Misja "' . $mission_data['title'] . '" została ukończona! Twoja postać otrzymuje ' . $mission_data['reward_xp'] . ' XP.');
                    }
                }
                echo alert('Status zmieniony', 'success');
            }
        }
    }
    
    $stmt = db()->prepare("SELECT m.*, u.username as created_by_name FROM missions m JOIN users u ON m.created_by = u.id WHERE m.id = ?");
    $stmt->execute([$missionId]);
    $mission = $stmt->fetch();
    
    if (!$mission) { echo alert('Misja nie istnieje', 'danger'); exit; }
    
    $stmt = db()->prepare("SELECT c.*, u.username FROM mission_participants mp JOIN characters c ON mp.character_id = c.id JOIN users u ON c.user_id = u.id WHERE mp.mission_id = ?");
    $stmt->execute([$missionId]);
    $participants = $stmt->fetchAll();
    
    $myChars = db()->prepare("SELECT * FROM characters WHERE user_id = ? AND approved_by_mg = TRUE");
    $myChars->execute([$user['id']]);
    $myCharacters = $myChars->fetchAll();
    
    $myParticipantIds = array_column($participants, 'id');
    ?>
    <h2><i class="bi bi-map"></i> <?= sanitize($mission['title']) ?></h2>
    <span class="badge bg-<?= $mission['status']=='active'?'success':'primary' ?>"><?= $mission['status'] ?></span>
    
    <div class="row mt-3">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-body"><p><?= nl2br(sanitize($mission['description'])) ?></p></div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h5>Uczestnicy</h5></div>
                <div class="card-body">
                    <?php if (count($participants) == 0): ?><p class="text-muted">Brak uczestników</p><?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($participants as $p): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong><?= sanitize($p['name']) ?></strong> (<?= sanitize($p['username']) ?>)</span>
                            <span class="badge bg-info">Lvl <?= $p['level'] ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header"><h5>Nagrody</h5></div>
                <div class="card-body">
                    <p><strong>XP:</strong> <?= $mission['reward_xp'] ?></p>
                    <p><strong>Złoto:</strong> <?= $mission['reward_gold'] ?></p>
                    <?php if ($mission['reward_item']): ?><p><strong>Przedmiot:</strong> <?= sanitize($mission['reward_item']) ?></p><?php endif; ?>
                </div>
            </div>
            <?php if ($mission['status'] == 'active' || $mission['status'] == 'proposed'): ?>
            <div class="card mb-3">
                <div class="card-header"><h5>Twoje postacie</h5></div>
                <div class="card-body">
                    <?php foreach ($myCharacters as $char): ?>
                    <?php if (in_array($char['id'], $myParticipantIds)): ?>
                    <form method="post" class="mb-2">
                        <input type="hidden" name="mission_action" value="leave">
                        <input type="hidden" name="character_id" value="<?= $char['id'] ?>">
                        <button class="btn btn-sm btn-secondary w-100"><?= sanitize($char['name']) ?> - Wypisz</button>
                    </form>
                    <?php else: ?>
                    <form method="post" class="mb-2">
                        <input type="hidden" name="mission_action" value="join">
                        <input type="hidden" name="character_id" value="<?= $char['id'] ?>">
                        <button class="btn btn-sm btn-success w-100"><?= sanitize($char['name']) ?> - Dołącz</button>
                    </form>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (hasRole('mg')): ?>
            <div class="card">
                <div class="card-header"><h5>Status MG</h5></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="mission_action" value="change_status">
                        <select name="new_status" class="form-select mb-2">
                            <option value="proposed" <?= $mission['status']=='proposed'?'selected':'' ?>>Proponowana</option>
                            <option value="active" <?= $mission['status']=='active'?'selected':'' ?>>Aktywna</option>
                            <option value="completed" <?= $mission['status']=='completed'?'selected':'' ?>>Ukończona</option>
                            <option value="failed" <?= $mission['status']=='failed'?'selected':'' ?>>Nieudana</option>
                        </select>
                        <button type="submit" class="btn btn-warning btn-sm w-100">Zmień</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <a href="?action=missions" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Powrót</a>
    <?php
}
?>