<?php
/**
 * Widok wątku z pełną funkcjonalnością postów
 * Cytowanie, edycja, usuwanie, zgłaszanie, tagi, pin/lock (MG)
 */

if (!$user) redirect('/?action=login');

$threadId = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("
    SELECT t.*, u.username as author_name, f.title as forum_title, f.id as forum_id
    FROM threads t 
    JOIN users u ON t.author_id = u.id
    JOIN forums f ON t.forum_id = f.id
    WHERE t.id = ?
");
$stmt->execute([$threadId]);
$thread = $stmt->fetch();

if (!$thread) {
    echo alert('Wątek nie istnieje', 'danger');
    redirect('/');
}

$stmt = db()->prepare("UPDATE threads SET view_count = view_count + 1 WHERE id = ?");
$stmt->execute([$threadId]);

if ($_POST) {
    $postAction = $_POST['post_action'] ?? '';
    switch ($postAction) {
        case 'create_post':
            $content = sanitize($_POST['content']);
            $quotedId = isset($_POST['quoted_post_id']) && $_POST['quoted_post_id'] ? (int)$_POST['quoted_post_id'] : null;
            $stmt = db()->prepare("INSERT INTO posts (thread_id, author_id, content, quoted_post_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$threadId, $user['id'], $content, $quotedId]);
            $stmt = db()->prepare("UPDATE threads SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$threadId]);
            logActivity('post_create', 'post', db()->lastInsertId());
            if ($thread['author_id'] != $user['id']) {
                addNotification($thread['author_id'], 'reply', 'Nowa odpowiedź', "Ktoś odpowiedział w wątku: {$thread['title']}", "?action=thread_view&id=$threadId");
            }
            echo alert('Post dodany!', 'success');
            break;
        case 'edit_post':
            $postId = (int)$_POST['post_id'];
            $newContent = sanitize($_POST['content']);
            $editReason = sanitize($_POST['edit_reason'] ?? '');
            $stmt = db()->prepare("SELECT author_id FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            if ($post && ($post['author_id'] == $user['id'] || hasRole('mg'))) {
                $stmt = db()->prepare("UPDATE posts SET content = ?, edited = TRUE, edit_reason = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newContent, $editReason, $postId]);
                echo alert('Post zaktualizowany!', 'success');
            }
            break;
        case 'delete_post':
            $postId = (int)$_POST['post_id'];
            $stmt = db()->prepare("SELECT author_id FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            if ($post && ($post['author_id'] == $user['id'] || hasRole('mg'))) {
                $stmt = db()->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$postId]);
                echo alert('Post usunięty!', 'info');
            }
            break;
        case 'toggle_pin':
            if (hasRole('mg')) {
                $stmt = db()->prepare("UPDATE threads SET pinned = NOT pinned WHERE id = ?");
                $stmt->execute([$threadId]);
                echo alert('Status przypięcia zmieniony!', 'success');
            }
            break;
        case 'toggle_lock':
            if (hasRole('mg')) {
                $stmt = db()->prepare("UPDATE threads SET locked = NOT locked WHERE id = ?");
                $stmt->execute([$threadId]);
                echo alert('Status blokady zmieniony!', 'success');
            }
            break;
    }
}

$stmt = db()->prepare("SELECT * FROM thread_tags WHERE thread_id = ?");
$stmt->execute([$threadId]);
$tags = $stmt->fetchAll();

$stmt = db()->prepare("
    SELECT p.*, u.username, u.role,
           quoted.content as quoted_content,
           quoted_user.username as quoted_author
    FROM posts p
    JOIN users u ON p.author_id = u.id
    LEFT JOIN posts quoted ON p.quoted_post_id = quoted.id
    LEFT JOIN users quoted_user ON quoted.author_id = quoted_user.id
    WHERE p.thread_id = ?
    ORDER BY p.created_at ASC
");
$stmt->execute([$threadId]);
$posts = $stmt->fetchAll();
?>

<div class="card mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?= sanitize($thread['title']) ?></h3>
                <small class="text-muted">Forum: <a href="?action=threads&forum_id=<?= $thread['forum_id'] ?>"><?= sanitize($thread['forum_title']) ?></a> | Autor: <?= sanitize($thread['author_name']) ?> | Wyświetleń: <?= $thread['view_count'] ?></small>
            </div>
            <div>
                <?php if ($thread['pinned']): ?><span class="badge bg-warning"><i class="bi bi-pin-fill"></i> Przypięty</span><?php endif; ?>
                <?php if ($thread['locked']): ?><span class="badge bg-danger"><i class="bi bi-lock-fill"></i> Zablokowany</span><?php endif; ?>
            </div>
        </div>
        <?php if (!empty($tags)): ?>
        <div class="mt-2">
            <?php foreach ($tags as $tag): ?>
                <span class="badge" style="background-color: <?= $tag['tag_color'] ?>"><?= sanitize($tag['tag_name']) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (hasRole('mg')): ?>
        <div class="mt-2">
            <form method="post" class="d-inline"><input type="hidden" name="post_action" value="toggle_pin"><button class="btn btn-sm btn-warning"><i class="bi bi-pin"></i> <?= $thread['pinned'] ? 'Odepnij' : 'Przypnij' ?></button></form>
            <form method="post" class="d-inline"><input type="hidden" name="post_action" value="toggle_lock"><button class="btn btn-sm btn-danger"><i class="bi bi-lock"></i> <?= $thread['locked'] ? 'Odblokuj' : 'Zablokuj' ?></button></form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($posts as $post): ?>
<div class="card mb-3" id="post-<?= $post['id'] ?>">
    <div class="card-header d-flex justify-content-between">
        <div>
            <strong><?= sanitize($post['username']) ?></strong>
            <span class="badge bg-<?= $post['role'] == 'admin' ? 'danger' : ($post['role'] == 'mg' ? 'warning' : 'primary') ?>"><?= strtoupper($post['role']) ?></span>
        </div>
        <small class="text-muted"><?= formatDate($post['created_at']) ?><?php if ($post['edited']): ?> <i class="bi bi-pencil-square"></i><?php endif; ?></small>
    </div>
    <div class="card-body">
        <?php if ($post['quoted_content']): ?>
        <blockquote class="blockquote bg-light p-2 border-start border-primary border-4">
            <small class="text-muted"><?= sanitize($post['quoted_author']) ?> napisał(a):</small>
            <p class="mb-0"><?= nl2br(sanitize(substr($post['quoted_content'], 0, 200))) ?>...</p>
        </blockquote>
        <?php endif; ?>
        <div class="post-content"><?= nl2br(sanitize($post['content'])) ?></div>
    </div>
    <div class="card-footer">
        <?php if (!$thread['locked']): ?>
        <button class="btn btn-sm btn-outline-primary" onclick="quotePost(<?= $post['id'] ?>, '<?= addslashes(sanitize($post['username'])) ?>')"><i class="bi bi-chat-quote"></i> Cytuj</button>
        <?php endif; ?>
        <?php if ($post['author_id'] == $user['id'] || hasRole('mg')): ?>
        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editPost<?= $post['id'] ?>"><i class="bi bi-pencil"></i> Edytuj</button>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delPost<?= $post['id'] ?>"><i class="bi bi-trash"></i> Usuń</button>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="editPost<?= $post['id'] ?>" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <div class="modal-header"><h5 class="modal-title">Edytuj post</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="post_action" value="edit_post">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <textarea name="content" class="form-control" rows="5" required><?= sanitize($post['content']) ?></textarea>
                <input name="edit_reason" class="form-control mt-2" placeholder="Powód edycji (opcjonalnie)">
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning">Zapisz</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="delPost<?= $post['id'] ?>" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post">
            <div class="modal-header"><h5 class="modal-title">Usuń post</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="hidden" name="post_action" value="delete_post"><input type="hidden" name="post_id" value="<?= $post['id'] ?>"><p>Czy na pewno usunąć ten post?</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button><button type="submit" class="btn btn-danger">Usuń</button></div>
        </form>
    </div></div>
</div>
<?php endforeach; ?>

<?php if (!$thread['locked']): ?>
<div class="card">
    <div class="card-header"><h5>Dodaj odpowiedź</h5></div>
    <div class="card-body">
        <form method="post" id="replyForm">
            <input type="hidden" name="post_action" value="create_post">
            <input type="hidden" name="quoted_post_id" id="quotedPostId" value="">
            <div id="quotePreview" class="mb-3" style="display:none;">
                <blockquote class="blockquote bg-light p-2 border-start border-primary border-4">
                    <small class="text-muted"><span id="quotedAuthor"></span> napisał(a):</small>
                    <p class="mb-0" id="quotedText"></p>
                </blockquote>
                <button type="button" class="btn btn-sm btn-secondary" onclick="clearQuote()">Usuń cytat</button>
            </div>
            <textarea name="content" class="form-control mb-3" rows="6" required placeholder="Wpisz odpowiedź..."></textarea>
            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Wyślij</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning"><i class="bi bi-lock-fill"></i> Wątek jest zablokowany.</div>
<?php endif; ?>

<div class="mt-3"><a href="?action=threads&forum_id=<?= $thread['forum_id'] ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Powrót</a></div>

<script>
function quotePost(postId, author) {
    const content = document.querySelector('#post-' + postId + ' .post-content').innerText;
    document.getElementById('quotedPostId').value = postId;
    document.getElementById('quotedAuthor').innerText = author;
    document.getElementById('quotedText').innerText = content.substring(0, 200);
    document.getElementById('quotePreview').style.display = 'block';
    document.getElementById('replyForm').scrollIntoView({ behavior: 'smooth' });
}
function clearQuote() {
    document.getElementById('quotedPostId').value = '';
    document.getElementById('quotePreview').style.display = 'none';
}
</script>
