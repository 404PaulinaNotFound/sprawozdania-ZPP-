<?php
/**
 * FORMULARZ ZGŁASZANIA UMIEJĖTNOŚCI DLA POSTACI
 */

if (!defined('APP_NAME')) die('Direct access not allowed');

if ($action == 'character_view') {
    if (!$user) redirect('?action=login');
    
    $char_id = intval($_GET['id'] ?? 0);
    
    $stmt = db()->prepare("SELECT c.*, u.username FROM characters c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->execute([$char_id]);
    $char = $stmt->fetch();
    
    if (!$char) {
        echo alert('Postać nie istnieje', 'danger');
        exit;
    }
    
    $is_owner = ($char['user_id'] == $user['id']);
    
    if ($_POST && isset($_POST['add_skill']) && $is_owner) {
        $skill_name = sanitize($_POST['skill_name']);
        $skill_description = sanitize($_POST['skill_description']);
        $skill_level = intval($_POST['skill_level']);
        $stmt = db()->prepare("INSERT INTO character_skills (character_id, skill_name, skill_description, skill_level) VALUES (?, ?, ?, ?)");
        $stmt->execute([$char_id, $skill_name, $skill_description, $skill_level]);
        echo alert('Umiejętność zgłoszona do zatwierdzenia przez MG', 'success');
    }
    
    if ($_POST && isset($_POST['add_item']) && $is_owner) {
        $item_name = sanitize($_POST['item_name']);
        $item_description = sanitize($_POST['item_description']);
        $quantity = intval($_POST['quantity']);
        $stmt = db()->prepare("INSERT INTO character_inventory (character_id, item_name, item_description, quantity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$char_id, $item_name, $item_description, $quantity]);
        echo alert('Przedmiot dodany do ekwipunku', 'success');
    }
    
    $stmt = db()->prepare("SELECT cs.*, u.username as approver_name FROM character_skills cs LEFT JOIN users u ON cs.approved_by = u.id WHERE cs.character_id = ? ORDER BY cs.approved DESC, cs.created_at DESC");
    $stmt->execute([$char_id]);
    $skills = $stmt->fetchAll();
    
    $stmt = db()->prepare("SELECT * FROM character_inventory WHERE character_id = ?");
    $stmt->execute([$char_id]);
    $inventory = $stmt->fetchAll();
    
    $stmt = db()->prepare("SELECT a.* FROM achievements a JOIN character_achievements ca ON a.id = ca.achievement_id WHERE ca.character_id = ?");
    $stmt->execute([$char_id]);
    $badges = $stmt->fetchAll();
?>
<h2><i class="bi bi-person-badge"></i> <?= sanitize($char['name']) ?></h2>
<p><small>Gracz: <?= sanitize($char['username']) ?></small></p>

<?php if (!$char['approved_by_mg']): ?>
    <div class="alert alert-warning"><i class="bi bi-hourglass-split"></i> Postać oczekuje na akceptację MG</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><h5>Informacje podstawowe</h5></div>
            <div class="card-body">
                <p><?= nl2br(sanitize($char['description'])) ?></p>
                <p>
                    <span class="badge bg-info">Poziom <?= $char['level'] ?></span>
                    <span class="badge bg-primary">XP: <?= number_format($char['experience']) ?></span>
                    <span class="badge bg-warning">PH: <?= $char['history_points'] ?></span>
                </p>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h5>Statystyki</h5></div>
            <div class="card-body">
                <p><i class="bi bi-shield"></i> <strong>Siła:</strong> <?= $char['strength'] ?></p>
                <p><i class="bi bi-lightning"></i> <strong>Zręczność:</strong> <?= $char['agility'] ?></p>
                <p><i class="bi bi-lightbulb"></i> <strong>Inteligencja:</strong> <?= $char['intelligence'] ?></p>
                <p><i class="bi bi-chat"></i> <strong>Charyzma:</strong> <?= $char['charisma'] ?></p>
                <p><i class="bi bi-heart"></i> <strong>Witalnosc:</strong> <?= $char['vitality'] ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>Umiejętności</h5>
                    <?php if ($is_owner): ?>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSkillModal"><i class="bi bi-plus"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($skills) == 0): ?>
                    <p class="text-muted">Brak umiejętności</p>
                <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($skills as $skill): ?>
                    <li class="list-group-item <?= !$skill['approved'] ? 'list-group-item-warning' : '' ?>">
                        <div class="d-flex justify-content-between">
                            <strong><?= sanitize($skill['skill_name']) ?></strong>
                            <span class="badge bg-primary">Lvl <?= $skill['skill_level'] ?></span>
                        </div>
                        <small><?= sanitize($skill['skill_description']) ?></small><br>
                        <?php if (!$skill['approved']): ?>
                            <small class="text-warning"><i class="bi bi-clock"></i> Oczekuje na zatwierdzenie</small>
                        <?php else: ?>
                            <small class="text-success"><i class="bi bi-check-circle"></i> Zatwierdzone przez <?= sanitize($skill['approver_name']) ?></small>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>Ekwipunek</h5>
                    <?php if ($is_owner): ?>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="bi bi-plus"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($inventory) == 0): ?>
                    <p class="text-muted">Pusty ekwipunek</p>
                <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($inventory as $item): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong><?= sanitize($item['item_name']) ?></strong>
                            <span class="badge bg-secondary">x<?= $item['quantity'] ?></span>
                        </div>
                        <?php if ($item['item_description']): ?><small><?= sanitize($item['item_description']) ?></small><?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (count($badges) > 0): ?>
<div class="card"><div class="card-header"><h5><i class="bi bi-trophy"></i> Odznaki</h5></div><div class="card-body">
    <div class="row">
        <?php foreach ($badges as $badge): ?>
        <div class="col-md-3 mb-2 text-center">
            <?php if ($badge['icon']): ?><i class="<?= sanitize($badge['icon']) ?>" style="font-size:2rem;"></i><br><?php endif; ?>
            <strong><?= sanitize($badge['name']) ?></strong><br>
            <small class="text-muted"><?= sanitize($badge['description']) ?></small>
        </div>
        <?php endforeach; ?>
    </div>
</div></div>
<?php endif; ?>

<!-- Modal umiejętności -->
<div class="modal fade" id="addSkillModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post">
        <div class="modal-header"><h5 class="modal-title">Dodaj umiejętność</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="alert alert-info"><small>Umiejętność musi być zatwierdzona przez MG.</small></div>
            <div class="mb-3"><label>Nazwa</label><input name="skill_name" class="form-control" required maxlength="100"></div>
            <div class="mb-3"><label>Opis</label><textarea name="skill_description" class="form-control" rows="3" required></textarea></div>
            <div class="mb-3"><label>Poziom (1-10)</label><input name="skill_level" type="number" class="form-control" value="1" min="1" max="10" required></div>
        </div>
        <div class="modal-footer"><button type="submit" name="add_skill" class="btn btn-success">Zgłoś</button></div>
    </form>
</div></div></div>

<!-- Modal ekwipunku -->
<div class="modal fade" id="addItemModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post">
        <div class="modal-header"><h5 class="modal-title">Dodaj przedmiot</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label>Nazwa</label><input name="item_name" class="form-control" required maxlength="100"></div>
            <div class="mb-3"><label>Opis (opcjonalnie)</label><textarea name="item_description" class="form-control" rows="2"></textarea></div>
            <div class="mb-3"><label>Ilość</label><input name="quantity" type="number" class="form-control" value="1" min="1" required></div>
        </div>
        <div class="modal-footer"><button type="submit" name="add_item" class="btn btn-success">Dodaj</button></div>
    </form>
</div></div></div>
<?php
}
?>