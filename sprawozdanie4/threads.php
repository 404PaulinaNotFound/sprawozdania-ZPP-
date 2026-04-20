<?php
/**
 * Moduł wątków i postów forum
 */

if (!isset($user)) redirect('/');

$forumId = $_GET['forum_id'] ?? 0;

// Pobierz forum
$stmt = db()->prepare("SELECT * FROM forums WHERE id = ?");
$stmt->execute([$forumId]);
$forum = $stmt->fetch();

if (!$forum) {
    echo alert('Forum nie istnieje', 'danger');
    exit;
}

// Tworzenie nowego wątku
if ($_POST && isset($_POST['create_thread'])) {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    
    $stmt = db()->prepare("
        INSERT INTO threads (forum_id, title, author_id) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$forumId, $title, $user['id']]);
    $threadId = db()->lastInsertId();
    
    // Pierwszy post
    $stmt = db()->prepare("
        INSERT INTO posts (thread_id, author_id, content) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$threadId, $user['id'], $content]);
    
    logActivity('thread_create', 'thread', $threadId);
    echo alert('Wątek utworzony!', 'success');
}

// Wyszukiwanie
$search = $_GET['search'] ?? '';
$sql = "SELECT t.*, u.username, 
        (SELECT COUNT(*) FROM posts p WHERE p.thread_id = t.id) as post_count
        FROM threads t 
        JOIN users u ON t.author_id = u.id 
        WHERE t.forum_id = ?";

if ($search) {
    $sql .= " AND MATCH(t.title) AGAINST(? IN NATURAL LANGUAGE MODE)";
    $stmt = db()->prepare($sql);
    $stmt->execute([$forumId, $search]);
} else {
    $stmt = db()->prepare($sql . " ORDER BY t.updated_at DESC");
    $stmt->execute([$forumId]);
}

$threads = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-chat-dots"></i> <?= sanitize($forum['title']) ?></h2>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newThreadModal">
        <i class="bi bi-plus-circle"></i> Nowy wątek
    </button>
</div>

<!-- Wyszukiwanie -->
<form method="get" class="mb-3">
    <input type="hidden" name="action" value="threads">
    <input type="hidden" name="forum_id" value="<?= $forumId ?>">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Szukaj wątków..." value="<?= sanitize($search) ?>">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
    </div>
</form>

<!-- Lista wątków -->
<div class="list-group">
    <?php foreach ($threads as $thread): ?>
        <a href="?action=thread_view&id=<?= $thread['id'] ?>" class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1"><?= sanitize($thread['title']) ?></h5>
                <small><?= formatDate($thread['updated_at']) ?></small>
            </div>
            <p class="mb-1">Autor: <?= sanitize($thread['username']) ?> | Odpowiedzi: <?= $thread['post_count'] - 1 ?></p>
        </a>
    <?php endforeach; ?>
</div>

<!-- Modal nowego wątku -->
<div class="modal fade" id="newThreadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Nowy wątek</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Tytuł</label>
                        <input name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Treść</label>
                        <textarea name="content" class="form-control" rows="6" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="create_thread" class="btn btn-success">Utwórz</button>
                </div>
            </form>
        </div>
    </div>
</div>
