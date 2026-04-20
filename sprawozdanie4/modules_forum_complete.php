 <?php
/**
 * KOMPLETNY SYSTEM FORUM
 * Wątki, posty, cytowania, wyszukiwanie, historia edycji
 */

if (!defined('APP_NAME')) die('Direct access not allowed');

// ========== WYSZUKIWANIE WĄTKÓW ==========
if ($action == 'forum_search') {
    $query = $_GET['q'] ?? '';
    $results = [];

    if ($query) {
        $stmt = db()->prepare("
            SELECT t.*, f.title as forum_title, u.username as author_name,
                   (SELECT COUNT(*) FROM posts p WHERE p.thread_id = t.id) as post_count
            FROM threads t
            JOIN forums f ON t.forum_id = f.id
            JOIN users u ON t.author_id = u.id
            WHERE t.title LIKE ? OR t.id IN (
                SELECT DISTINCT thread_id FROM posts WHERE content LIKE ?
            )
            ORDER BY t.updated_at DESC LIMIT 50
        ");
        $stmt->execute(["%$query%", "%$query%"]);
        $results = $stmt->fetchAll();
    }
    ?>
    <h2><i class="bi bi-search"></i> Wyszukiwanie na forum</h2>
    <form method="get" class="mb-4">
        <input type="hidden" name="action" value="forum_search">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Szukaj wątków i postów..." value="<?= sanitize($query) ?>" required>
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Szukaj</button>
        </div>
    </form>
    <?php if ($query && count($results) > 0): ?>
        <div class="list-group">
            <?php foreach ($results as $thread): ?>
                <a href="?action=thread_view&id=<?= $thread['id'] ?>" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><?= sanitize($thread['title']) ?></h5>
                        <small><?= date('d.m.Y H:i', strtotime($thread['updated_at'])) ?></small>
                    </div>
                    <p class="mb-1">Forum: <?= sanitize($thread['forum_title']) ?> | Autor: <?= sanitize($thread['author_name']) ?></p>
                    <small>Postów: <?= $thread['post_count'] ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    <?php elseif ($query): ?>
        <div class="alert alert-info">Nie znaleziono wyników.</div>
    <?php endif; ?>
    <?php
}

// ========== LISTA WĄTKÓW W FORUM ==========
elseif ($action == 'threads') {
    $forum_id = intval($_GET['forum_id'] ?? 0);

    $stmt = db()->prepare("SELECT * FROM forums WHERE id = ?");
    $stmt->execute([$forum_id]);
    $forum = $stmt->fetch();
    if (!$forum) { echo alert('Forum nie istnieje', 'danger'); exit; }

    if ($_POST && isset($_POST['create_thread']) && $user) {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { echo alert('Błąd CSRF', 'danger'); exit; }
        $title = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $tags = sanitize($_POST['tags'] ?? '');

        $stmt = db()->prepare("INSERT INTO threads (forum_id, title, author_id) VALUES (?, ?, ?)");
        $stmt->execute([$forum_id, $title, $user['id']]);
        $thread_id = db()->lastInsertId();

        $stmt = db()->prepare("INSERT INTO posts (thread_id, author_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$thread_id, $user['id'], $content]);

        if ($tags) {
            foreach (array_map('trim', explode(',', $tags)) as $tag) {
                if ($tag) {
                    $stmt = db()->prepare("INSERT INTO thread_tags (thread_id, tag_name) VALUES (?, ?)");
                    $stmt->execute([$thread_id, $tag]);
                }
            }
        }
        logActivity('thread_create', 'thread', $thread_id);
        redirect("?action=thread_view&id=$thread_id");
    }

    $stmt = db()->prepare("
        SELECT t.*, u.username as author_name,
               (SELECT COUNT(*) FROM posts p WHERE p.thread_id = t.id) as post_count,
               (SELECT MAX(created_at) FROM posts p WHERE p.thread_id = t.id) as last_post
        FROM threads t
        JOIN users u ON t.author_id = u.id
        WHERE t.forum_id = ? AND t.archived = FALSE
        ORDER BY t.pinned DESC, t.updated_at DESC
    ");
    $stmt->execute([$forum_id]);
    $threads = $stmt->fetchAll();
    ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="?action=forum">Forum</a></li>
            <li class="breadcrumb-item active"><?= sanitize($forum['title']) ?></li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?= sanitize($forum['title']) ?></h2>
        <?php if ($user): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newThreadModal">
                <i class="bi bi-plus-circle"></i> Nowy wątek
            </button>
        <?php endif; ?>
    </div>
    <div class="list-group">
        <?php foreach ($threads as $thread): ?>
            <a href="?action=thread_view&id=<?= $thread['id'] ?>" class="list-group-item list-group-item-action <?= $thread['pinned'] ? 'list-group-item-warning' : '' ?>">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">
                        <?php if ($thread['pinned']): ?><i class="bi bi-pin-fill"></i><?php endif; ?>
                        <?php if ($thread['locked']): ?><i class="bi bi-lock-fill"></i><?php endif; ?>
                        <?= sanitize($thread['title']) ?>
                    </h5>
                    <small><?= date('d.m.Y H:i', strtotime($thread['last_post'] ?? $thread['created_at'])) ?></small>
                </div>
                <small>Autor: <?= sanitize($thread['author_name']) ?> | Postów: <?= $thread['post_count'] ?> | Wyświetleń: <?= $thread['view_count'] ?></small>
            </a>
        <?php endforeach; ?>
    </div>
    <!-- Modal nowego wątku -->
    <div class="modal fade" id="newThreadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <?= csrfField() ?>
                    <div class="modal-header"><h5>Nowy wątek</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Tytuł</label><input name="title" class="form-control" required maxlength="200"></div>
                        <div class="mb-3"><label>Treść</label><textarea name="content" class="form-control" rows="6" required></textarea></div>
                        <div class="mb-3"><label>Tagi (opcjonalnie, oddzielone przecinkami)</label><input name="tags" class="form-control" placeholder="np. fabuła, wydarzenie"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="create_thread" class="btn btn-success">Utwórz wątek</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

// ========== WYŚWIETLANIE WĄTKU Z POSTAMI ==========
elseif ($action == 'thread_view') {
    $thread_id = intval($_GET['id'] ?? 0);
    $stmt = db()->prepare("
        SELECT t.*, u.username as author_name, f.title as forum_title, f.id as forum_id
        FROM threads t JOIN users u ON t.author_id = u.id JOIN forums f ON t.forum_id = f.id
        WHERE t.id = ?
    ");
    $stmt->execute([$thread_id]);
    $thread = $stmt->fetch();
    if (!$thread) { echo alert('Wątek nie istnieje', 'danger'); exit; }

    db()->prepare("UPDATE threads SET view_count = view_count + 1 WHERE id = ?")->execute([$thread_id]);

    if ($_POST && isset($_POST['add_post']) && $user && !$thread['locked']) {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { echo alert('Błąd CSRF', 'danger'); exit; }
        $content = sanitize($_POST['content']);
        $quoted_post_id = intval($_POST['quoted_post_id'] ?? 0) ?: null;
        $stmt = db()->prepare("INSERT INTO posts (thread_id, author_id, content, quoted_post_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$thread_id, $user['id'], $content, $quoted_post_id]);
        db()->prepare("UPDATE threads SET updated_at = NOW() WHERE id = ?")->execute([$thread_id]);
        logActivity('post_create', 'post', db()->lastInsertId());
        redirect("?action=thread_view&id=$thread_id");
    }

    $stmt = db()->prepare("
        SELECT p.*, u.username as author_name, u.role, u.avatar,
               (SELECT COUNT(*) FROM posts WHERE author_id = p.author_id) as author_posts,
               qp.content as quoted_content, qu.username as quoted_author
        FROM posts p
        JOIN users u ON p.author_id = u.id
        LEFT JOIN posts qp ON p.quoted_post_id = qp.id
        LEFT JOIN users qu ON qp.author_id = qu.id
        WHERE p.thread_id = ? ORDER BY p.created_at ASC
    ");
    $stmt->execute([$thread_id]);
    $posts = $stmt->fetchAll();

    $stmt = db()->prepare("SELECT * FROM thread_tags WHERE thread_id = ?");
    $stmt->execute([$thread_id]);
    $tags = $stmt->fetchAll();
    ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="?action=forum">Forum</a></li>
            <li class="breadcrumb-item"><a href="?action=threads&forum_id=<?= $thread['forum_id'] ?>"><?= sanitize($thread['forum_title']) ?></a></li>
            <li class="breadcrumb-item active"><?= sanitize($thread['title']) ?></li>
        </ol>
    </nav>
    <h2><?= sanitize($thread['title']) ?></h2>
    <?php foreach ($tags as $tag): ?>
        <span class="badge" style="background-color:<?= sanitize($tag['tag_color']) ?>"><?= sanitize($tag['tag_name']) ?></span>
    <?php endforeach; ?>
    <?php if ($thread['locked']): ?>
        <div class="alert alert-warning mt-2"><i class="bi bi-lock-fill"></i> Wątek zamknięty</div>
    <?php endif; ?>
    <?php foreach ($posts as $post): ?>
        <div class="card mb-3" id="post-<?= $post['id'] ?>">
            <div class="card-header d-flex justify-content-between">
                <strong><?= sanitize($post['author_name']) ?></strong>
                <span class="badge bg-info"><?= $post['role'] ?></span>
            </div>
            <div class="card-body">
                <?php if ($post['quoted_content']): ?>
                    <div class="alert alert-secondary"><small><strong><?= sanitize($post['quoted_author']) ?> napisał:</strong></small><br><small><?= nl2br(sanitize(substr($post['quoted_content'], 0, 200))) ?>...</small></div>
                <?php endif; ?>
                <p><?= nl2br(sanitize($post['content'])) ?></p>
                <?php if ($post['edited']): ?>
                    <small class="text-muted"><i class="bi bi-pencil"></i> Edytowano: <?= date('d.m.Y H:i', strtotime($post['updated_at'])) ?><?php if ($post['edit_reason']): ?> | Powód: <?= sanitize($post['edit_reason']) ?><?php endif; ?></small>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <small class="text-muted"><?= date('d.m.Y H:i:s', strtotime($post['created_at'])) ?></small>
                <?php if ($user && !$thread['locked']): ?>
                    <button class="btn btn-sm btn-outline-primary float-end" onclick="quotePost(<?= $post['id'] ?>, '<?= addslashes($post['author_name']) ?>')"><i class="bi bi-quote"></i> Cytuj</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if ($user && !$thread['locked']): ?>
        <div class="card">
            <div class="card-header"><h5>Odpowiedz</h5></div>
            <div class="card-body">
                <form method="post" id="replyForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="quoted_post_id" id="quotedPostId">
                    <div id="quotePreview" class="alert alert-secondary d-none mb-2"></div>
                    <textarea name="content" id="replyContent" class="form-control" rows="4" required></textarea>
                    <button type="submit" name="add_post" class="btn btn-primary mt-2">Dodaj odpowiedź</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <script>
    function quotePost(postId, author) {
        document.getElementById('quotedPostId').value = postId;
        document.getElementById('quotePreview').innerHTML = '<strong>' + author + ' napisał:</strong>';
        document.getElementById('quotePreview').classList.remove('d-none');
        document.getElementById('replyContent').focus();
    }
    </script>
    <?php
}
?>
