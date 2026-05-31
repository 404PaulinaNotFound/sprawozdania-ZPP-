<?php
/**
 * System powiadomień
 */

if (!$user) redirect('/?action=login');

if ($_POST) {
    if (isset($_POST['mark_all_read'])) {
        db()->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?")->execute([$user['id']]);
        echo alert('Wszystkie powiadomienia oznaczone jako przeczytane', 'success');
    } elseif (isset($_POST['mark_read'])) {
        $notifId = (int)$_POST['notif_id'];
        db()->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?")->execute([$notifId, $user['id']]);
    }
}

$stmt = db()->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();
$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-bell"></i> Powiadomienia
        <?php if ($unreadCount > 0): ?>
        <span class="badge bg-danger"><?= $unreadCount ?></span>
        <?php endif; ?>
    </h2>
    <?php if ($unreadCount > 0): ?>
    <form method="post">
        <button type="submit" name="mark_all_read" class="btn btn-secondary">
            <i class="bi bi-check-all"></i> Oznacz wszystkie jako przeczytane
        </button>
    </form>
    <?php endif; ?>
</div>

<?php if (count($notifications) == 0): ?>
    <div class="alert alert-info">Brak powiadomień</div>
<?php else: ?>
<div class="list-group">
    <?php foreach ($notifications as $n): ?>
    <div class="list-group-item <?= !$n['is_read'] ? 'list-group-item-warning' : '' ?>">
        <div class="d-flex w-100 justify-content-between">
            <h5 class="mb-1">
                <?php
                $icon = match($n['type']) {
                    'reply' => 'bi-chat-text',
                    'message' => 'bi-envelope',
                    'system' => 'bi-bell',
                    'achievement' => 'bi-trophy',
                    default => 'bi-info-circle'
                };
                ?>
                <i class="bi <?= $icon ?>"></i> <?= sanitize($n['title']) ?>
                <?php if (!$n['is_read']): ?><span class="badge bg-warning ms-2">Nowe</span><?php endif; ?>
            </h5>
            <small class="text-muted"><?= formatDate($n['created_at']) ?></small>
        </div>
        <p class="mb-1"><?= sanitize($n['message']) ?></p>
        <div>
            <?php if ($n['link']): ?>
            <a href="<?= sanitize($n['link']) ?>" class="btn btn-sm btn-outline-primary">Przejdź</a>
            <?php endif; ?>
            <?php if (!$n['is_read']): ?>
            <form method="post" class="d-inline">
                <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                <button type="submit" name="mark_read" class="btn btn-sm btn-outline-secondary">Oznacz jako przeczytane</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>