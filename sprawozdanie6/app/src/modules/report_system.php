<?php
/**
 * SYSTEM ZGŁASZAŃ DO MODERACJI
 */

if (!defined('APP_NAME')) die('Direct access not allowed');

if ($action == 'report') {
    if (!$user) redirect('?action=login');
    
    if ($_POST && isset($_POST['submit_report'])) {
        $reported_type = sanitize($_POST['reported_type']);
        $reported_id = intval($_POST['reported_id']);
        $reason = sanitize($_POST['reason']);
        
        $stmt = db()->prepare("
            INSERT INTO reports (reporter_id, reported_type, reported_id, reason)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user['id'], $reported_type, $reported_id, $reason]);
        
        echo alert('Zgłoszenie wysłane do moderacji', 'success');
    }
    
    // Formularz zgłoszenia
    $type = $_GET['type'] ?? '';
    $id = intval($_GET['id'] ?? 0);
    ?>
    <h2><i class="bi bi-flag"></i> Zgłoś do moderacji</h2>
    
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="reported_type" value="<?= sanitize($type) ?>">
                        <input type="hidden" name="reported_id" value="<?= $id ?>">
                        
                        <div class="mb-3">
                            <label>Typ zgłoszenia</label>
                            <input class="form-control" value="<?= ucfirst($type) ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label>Powód zgłoszenia</label>
                            <textarea name="reason" class="form-control" rows="5" required placeholder="Opisz dlaczego zgłaszasz tę treść..."></textarea>
                        </div>
                        
                        <button type="submit" name="submit_report" class="btn btn-danger">
                            <i class="bi bi-flag"></i> Wyślij zgłoszenie
                        </button>
                        <a href="javascript:history.back()" class="btn btn-secondary">Anuluj</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
