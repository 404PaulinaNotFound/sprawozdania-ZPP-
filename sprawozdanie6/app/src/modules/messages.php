<?php
/**
 * Wiadomości prywatne
 */

if (!$user) redirect('/?action=login');

if ($action == 'message_send') {
    if ($_POST) {
        $toId = (int)$_POST['to_user_id'];
        $subject = sanitize($_POST['subject']);
        $body = sanitize($_POST['body']);
        $stmt = db()->prepare("INSERT INTO private_messages (sender_id, receiver_id, subject, body) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], $toId, $subject, $body]);
        addNotification($toId, 'message', 'Nowa wiadomość', 'Masz nową wiadomość od ' . $user['username'], '?action=messages');
        echo alert('Wiadomość wysłana!', 'success');
    }
    $users_list = db()->query("SELECT id, username FROM users WHERE id != {$user['id']} AND approved = TRUE ORDER BY username")->fetchAll();
    ?>
    <h2><i class="bi bi-pencil-square"></i> Nowa wiadomość</h2>
    <div class="card"><div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label>Do:</label>
                <select name="to_user_id" class="form-select" required>
                    <?php foreach ($users_list as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= sanitize($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label>Temat</label><input name="subject" class="form-control" required maxlength="200"></div>
            <div class="mb-3"><label>Treść</label><textarea name="body" class="form-control" rows="8" required></textarea></div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Wyślij</button>
            <a href="?action=messages" class="btn btn-secondary">Anuluj</a>
        </form>
    </div></div>
    <?php

} elseif ($action == 'message_view') {
    $msgId = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT pm.*, s.username as sender_name, r.username as receiver_name FROM private_messages pm JOIN users s ON pm.sender_id = s.id JOIN users r ON pm.receiver_id = r.id WHERE pm.id = ? AND (pm.receiver_id = ? OR pm.sender_id = ?)");
    $stmt->execute([$msgId, $user['id'], $user['id']]);
    $msg = $stmt->fetch();
    if (!$msg) { echo alert('Wiadomość nie istnieje', 'danger'); exit; }
    if ($msg['receiver_id'] == $user['id'] && !$msg['read']) {
        db()->prepare("UPDATE private_messages SET read = TRUE, read_at = NOW() WHERE id = ?")->execute([$msgId]);
    }
    ?>
    <h2><i class="bi bi-envelope-open"></i> <?= sanitize($msg['subject']) ?></h2>
    <div class="card">
        <div class="card-header">
            Od: <strong><?= sanitize($msg['sender_name']) ?></strong> do <strong><?= sanitize($msg['receiver_name']) ?></strong>
            <small class="text-muted float-end"><?= formatDate($msg['created_at']) ?></small>
        </div>
        <div class="card-body"><?= nl2br(sanitize($msg['body'])) ?></div>
    </div>
    <div class="mt-3"><a href="?action=messages" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Powrót</a></div>
    <?php

} else {
    $stmt = db()->prepare("
        SELECT pm.*, s.username as sender_name, r.username as receiver_name
        FROM private_messages pm
        JOIN users s ON pm.sender_id = s.id
        JOIN users r ON pm.receiver_id = r.id
        WHERE pm.receiver_id = ? OR pm.sender_id = ?
        ORDER BY pm.created_at DESC
    ");
    $stmt->execute([$user['id'], $user['id']]);
    $messages = $stmt->fetchAll();
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-envelope"></i> Wiadomości</h2>
        <a href="?action=message_send" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Nowa wiadomość</a>
    </div>
    <?php if (count($messages) == 0): ?>
        <div class="alert alert-info">Brak wiadomości</div>
    <?php else: ?>
    <div class="list-group">
        <?php foreach ($messages as $msg): ?>
        <a href="?action=message_view&id=<?= $msg['id'] ?>" class="list-group-item list-group-item-action <?= (!$msg['read'] && $msg['receiver_id'] == $user['id']) ? 'list-group-item-warning' : '' ?>">
            <div class="d-flex justify-content-between">
                <h5 class="mb-1"><?= sanitize($msg['subject']) ?> <?php if (!$msg['read'] && $msg['receiver_id'] == $user['id']): ?><span class="badge bg-danger">Nowe</span><?php endif; ?></h5>
                <small><?= formatDate($msg['created_at']) ?></small>
            </div>
            <p class="mb-0">Od: <?= sanitize($msg['sender_name']) ?> do: <?= sanitize($msg['receiver_name']) ?></p>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif;
}
?>