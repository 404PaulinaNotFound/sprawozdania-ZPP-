<?php
/**
 * Moduł wydarzeń światowych
 */

if (!$user) redirect('?action=login');

// Tworzenie eventu (MG/Admin)
if ($_POST && isset($_POST['create_event']) && hasRole('mg')) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $eventType = $_POST['event_type'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'] ?: null;
    $participantLimit = $_POST['participant_limit'] ?: null;
    
    $stmt = db()->prepare("
        INSERT INTO events (title, description, event_type, start_date, end_date, created_by, participant_limit) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $description, $eventType, $startDate, $endDate, $user['id'], $participantLimit]);
    
    echo alert('Wydarzenie utworzone!', 'success');
}

// Zapis na event
if ($_POST && isset($_POST['join_event'])) {
    $eventId = intval($_POST['event_id']);
    $characterId = intval($_POST['character_id']);
    
    try {
        $stmt = db()->prepare("INSERT INTO event_participants (event_id, character_id) VALUES (?, ?)");
        $stmt->execute([$eventId, $characterId]);
        echo alert('Zapisano na wydarzenie!', 'success');
    } catch (Exception $e) {
        echo alert('Błąd zapisu.', 'warning');
    }
}

// Kalendarz wydarzeń
$stmt = db()->query("
    SELECT e.*, u.username as creator_name,
           (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) as participants_count
    FROM events e
    JOIN users u ON e.created_by = u.id
    WHERE e.status != 'cancelled'
    ORDER BY e.start_date ASC
");
$events = $stmt->fetchAll();

// Moje postacie
$stmt = db()->prepare("SELECT * FROM characters WHERE user_id = ? AND approved_by_mg = TRUE");
$stmt->execute([$user['id']]);
$myCharacters = $stmt->fetchAll();
?>

<h2><i class="bi bi-calendar-event"></i> Kalendarz wydarzeń</h2>

<?php if (hasRole('mg')): ?>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createEventModal">
        <i class="bi bi-plus-circle"></i> Nowe wydarzenie
    </button>
<?php endif; ?>

<div class="row">
    <?php foreach ($events as $event): 
        $isPast = strtotime($event['start_date']) < time();
        $isFull = $event['participant_limit'] && $event['participants_count'] >= $event['participant_limit'];
    ?>
        <div class="col-md-6 mb-3">
            <div class="card border-<?= $isPast ? 'secondary' : 'primary' ?>">
                <div class="card-header">
                    <h5><?= sanitize($event['title']) ?></h5>
                    <span class="badge bg-<?= ['upcoming' => 'info', 'active' => 'success', 'completed' => 'secondary'][$event['status']] ?>">
                        <?= strtoupper($event['status']) ?>
                    </span>
                    <span class="badge bg-warning"><?= strtoupper($event['event_type']) ?></span>
                </div>
                <div class="card-body">
                    <p><?= sanitize($event['description']) ?></p>
                    <p>
                        <i class="bi bi-calendar"></i> <strong>Start:</strong> <?= formatDate($event['start_date']) ?><br>
                        <?php if ($event['end_date']): ?>
                            <i class="bi bi-calendar-check"></i> <strong>Koniec:</strong> <?= formatDate($event['end_date']) ?><br>
                        <?php endif; ?>
                        <i class="bi bi-people"></i> <strong>Uczestników:</strong> <?= $event['participants_count'] ?>
                        <?php if ($event['participant_limit']): ?>
                            / <?= $event['participant_limit'] ?>
                        <?php endif; ?>
                    </p>
                    <p><small>Organizator: <?= sanitize($event['creator_name']) ?></small></p>
                    
                    <?php if (!$isPast && !$isFull && !empty($myCharacters)): ?>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#joinEvent<?= $event['id'] ?>">
                            Zapisz się
                        </button>
                        
                        <!-- Modal zapisu -->
                        <div class="modal fade" id="joinEvent<?= $event['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Wybierz postać</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                            <select name="character_id" class="form-select" required>
                                                <?php foreach ($myCharacters as $char): ?>
                                                    <option value="<?= $char['id'] ?>"><?= sanitize($char['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="join_event" class="btn btn-primary">Zapisz</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (hasRole('mg')): ?>
<!-- Modal tworzenia eventu -->
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Nowe wydarzenie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Tytuł</label>
                        <input name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Opis</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Typ</label>
                        <select name="event_type" class="form-select" required>
                            <option value="war">Wojna</option>
                            <option value="mission">Misja</option>
                            <option value="tournament">Turniej</option>
                            <option value="festival">Festiwal</option>
                            <option value="other">Inne</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Data startu</label>
                        <input name="start_date" type="datetime-local" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Data końca (opcjonalne)</label>
                        <input name="end_date" type="datetime-local" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Limit uczestników (opcjonalne)</label>
                        <input name="participant_limit" type="number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="create_event" class="btn btn-success">Utwórz</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
