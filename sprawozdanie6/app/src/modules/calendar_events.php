<?php
/**
 * System wydarzeń / Kalendarz
 */

if (!$user) redirect('/?action=login');

if ($_POST && hasRole('mg')) {
    $evAction = $_POST['ev_action'] ?? '';
    if ($evAction == 'create') {
        $stmt = db()->prepare("INSERT INTO events (title, description, event_type, start_date, end_date, status, created_by) VALUES (?, ?, ?, ?, ?, 'upcoming', ?)");
        $stmt->execute([sanitize($_POST['title']), sanitize($_POST['description']), $_POST['event_type'], $_POST['start_date'], $_POST['end_date'], $user['id']]);
        echo alert('Wydarzenie created!', 'success');
    } elseif ($evAction == 'change_status') {
        $evId = (int)$_POST['event_id'];
        $newStatus = $_POST['new_status'];
        if (in_array($newStatus, ['upcoming','active','completed','cancelled'])) {
            db()->prepare("UPDATE events SET status = ? WHERE id = ?")->execute([$newStatus, $evId]);
            echo alert('Status zmieniony', 'success');
        }
    }
}

if ($_POST && isset($_POST['ev_action']) && $_POST['ev_action'] == 'register') {
    $evId = (int)$_POST['event_id'];
    $stmt = db()->prepare("INSERT IGNORE INTO event_participants (event_id, user_id) VALUES (?, ?)");
    $stmt->execute([$evId, $user['id']]);
    echo alert('Zapisano na wydarzenie!', 'success');
}

if ($_POST && isset($_POST['ev_action']) && $_POST['ev_action'] == 'unregister') {
    $evId = (int)$_POST['event_id'];
    $stmt = db()->prepare("DELETE FROM event_participants WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$evId, $user['id']]);
    echo alert('Wypisano z wydarzenia.', 'info');
}

$filter = $_GET['filter'] ?? 'upcoming';
$validFilters = ['upcoming','active','completed','cancelled','all'];
if (!in_array($filter, $validFilters)) $filter = 'upcoming';

$sql = "SELECT e.*, u.username as creator_name, (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) as participant_count FROM events e JOIN users u ON e.created_by = u.id";
if ($filter != 'all') $sql .= " WHERE e.status = '" . $filter . "'";
$sql .= " ORDER BY e.start_date ASC";
$events = db()->query($sql)->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-calendar-event"></i> Wydarzenia</h2>
    <?php if (hasRole('mg')): ?>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newEventModal"><i class="bi bi-plus-circle"></i> Nowe wydarzenie</button>
    <?php endif; ?>
</div>

<div class="btn-group mb-3">
    <?php foreach (['upcoming'=>'Nadchodzące','active'=>'Aktywne','completed'=>'Zakończone','all'=>'Wszystkie'] as $f => $l): ?>
    <a href="?action=events&filter=<?= $f ?>" class="btn btn-<?= $filter == $f ? 'primary' : 'outline-primary' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<div class="row">
<?php foreach ($events as $ev):
    $isRegistered = false;
    $ep = db()->prepare("SELECT 1 FROM event_participants WHERE event_id = ? AND user_id = ?");
    $ep->execute([$ev['id'], $user['id']]);
    $isRegistered = (bool)$ep->fetchColumn();
?>
<div class="col-md-6 mb-3">
    <div class="card h-100">
        <div class="card-header">
            <h5><?= sanitize($ev['title']) ?></h5>
            <span class="badge bg-primary"><?= $ev['event_type'] ?></span>
            <span class="badge bg-<?= $ev['status']=='active'?'success':($ev['status']=='completed'?'secondary':'info') ?>"><?= $ev['status'] ?></span>
        </div>
        <div class="card-body">
            <p><?= sanitize(substr($ev['description'], 0, 200)) ?></p>
            <p><small><i class="bi bi-calendar"></i> <?= date('d.m.Y H:i', strtotime($ev['start_date'])) ?> &ndash; <?= date('d.m.Y H:i', strtotime($ev['end_date'])) ?></small></p>
            <p><small>Uczestnicy: <?= $ev['participant_count'] ?></small></p>
        </div>
        <div class="card-footer">
            <?php if ($ev['status'] != 'completed' && $ev['status'] != 'cancelled'): ?>
            <?php if (!$isRegistered): ?>
            <form method="post" class="d-inline">
                <input type="hidden" name="ev_action" value="register">
                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                <button class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i> Zapisz się</button>
            </form>
            <?php else: ?>
            <form method="post" class="d-inline">
                <input type="hidden" name="ev_action" value="unregister">
                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                <button class="btn btn-sm btn-secondary">Wypisz się</button>
            </form>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (hasRole('mg')): ?>
            <form method="post" class="d-inline">
                <input type="hidden" name="ev_action" value="change_status">
                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                <select name="new_status" class="form-select form-select-sm d-inline w-auto">
                    <option value="upcoming">Nadchodzące</option>
                    <option value="active">Aktywne</option>
                    <option value="completed">Zakończone</option>
                    <option value="cancelled">Odwołane</option>
                </select>
                <button type="submit" class="btn btn-sm btn-warning">Zmień</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php if (hasRole('mg')): ?>
<div class="modal fade" id="newEventModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post">
        <div class="modal-header"><h5 class="modal-title">Nowe wydarzenie</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="ev_action" value="create">
            <div class="mb-3"><label>Tytuł</label><input name="title" class="form-control" required></div>
            <div class="mb-3"><label>Opis</label><textarea name="description" class="form-control" rows="4" required></textarea></div>
            <div class="mb-3"><label>Typ</label>
                <select name="event_type" class="form-select">
                    <option value="session">Sesja</option>
                    <option value="quest">Quest</option>
                    <option value="special">Specjalne</option>
                </select>
            </div>
            <div class="mb-3"><label>Data początku</label><input name="start_date" type="datetime-local" class="form-control" required></div>
            <div class="mb-3"><label>Data końca</label><input name="end_date" type="datetime-local" class="form-control" required></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-success">Utwórz</button></div>
    </form>
</div></div></div>
<?php endif; ?>