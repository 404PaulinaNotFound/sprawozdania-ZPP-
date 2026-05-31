<?php
/**
 * Moduł wiedzy o świecie (lore) i FAQ
 */

// Dwie sekcje: Lore & FAQ
$section = $_GET['section'] ?? 'lore';

if ($section == 'lore') {
    // Tworzenie strony lore (MG/Admin)
    if ($_POST && isset($_POST['create_lore']) && hasRole('mg')) {
        $title = sanitize($_POST['title']);
        $content = $_POST['content']; // Pozwalam na HTML
        $category = sanitize($_POST['category']);
        $isPublic = isset($_POST['is_public']);
        
        $stmt = db()->prepare("
            INSERT INTO lore_pages (title, content, category, created_by, is_public) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $content, $category, $user['id'], $isPublic]);
        
        echo alert('Strona dodana!', 'success');
    }
    
    // Lista stron lore
    $sql = "SELECT * FROM lore_pages WHERE is_public = TRUE";
    if (hasRole('mg')) {
        $sql = "SELECT * FROM lore_pages";
    }
    $stmt = db()->query($sql . " ORDER BY category, title");
    $pages = $stmt->fetchAll();
    
    // Grupuj po kategoriach
    $grouped = [];
    foreach ($pages as $page) {
        $cat = $page['category'] ?: 'Ogólne';
        $grouped[$cat][] = $page;
    }
    ?>
    <h2><i class="bi bi-book"></i> Wiedza o świecie</h2>
    
    <div class="mb-3">
        <a href="?action=lore&section=lore" class="btn btn-primary">Lore</a>
        <a href="?action=lore&section=faq" class="btn btn-secondary">FAQ</a>
        <?php if (hasRole('mg')): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createLoreModal">
                <i class="bi bi-plus-circle"></i> Nowa strona
            </button>
        <?php endif; ?>
    </div>
    
    <?php foreach ($grouped as $category => $items): ?>
        <h3><?= sanitize($category) ?></h3>
        <div class="list-group mb-4">
            <?php foreach ($items as $page): ?>
                <a href="?action=lore_view&id=<?= $page['id'] ?>" class="list-group-item list-group-item-action">
                    <h5><?= sanitize($page['title']) ?></h5>
                    <?php if (!$page['is_public']): ?>
                        <span class="badge bg-warning">Tylko MG</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    
    <?php if (hasRole('mg')): ?>
    <!-- Modal nowej strony -->
    <div class="modal fade" id="createLoreModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Nowa strona lore</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Tytuł</label>
                            <input name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Kategoria</label>
                            <input name="category" class="form-control" placeholder="np. Historia, Geografia">
                        </div>
                        <div class="mb-3">
                            <label>Treść</label>
                            <textarea name="content" class="form-control" rows="10" required></textarea>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_public" class="form-check-input" id="isPublic" checked>
                            <label class="form-check-label" for="isPublic">Widoczne dla graczy</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="create_lore" class="btn btn-success">Dodaj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php
    
} else { // FAQ
    $stmt = db()->query("SELECT * FROM faq_entries ORDER BY category, display_order");
    $faqs = $stmt->fetchAll();
    
    $grouped = [];
    foreach ($faqs as $faq) {
        $cat = $faq['category'] ?: 'Ogólne';
        $grouped[$cat][] = $faq;
    }
    ?>
    <h2><i class="bi bi-question-circle"></i> Częste pytania (FAQ)</h2>
    
    <div class="mb-3">
        <a href="?action=lore&section=lore" class="btn btn-secondary">Lore</a>
        <a href="?action=lore&section=faq" class="btn btn-primary">FAQ</a>
    </div>
    
    <?php foreach ($grouped as $category => $items): ?>
        <h3><?= sanitize($category) ?></h3>
        <div class="accordion mb-4" id="faq<?= md5($category) ?>">
            <?php foreach ($items as $index => $faq): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $faq['id'] ?>">
                            <?= sanitize($faq['question']) ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $faq['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#faq<?= md5($category) ?>">
                        <div class="accordion-body">
                            <?= nl2br(sanitize($faq['answer'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    <?php
}
