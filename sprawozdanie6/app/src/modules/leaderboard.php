<?php
/**
 * RANKING I LEADERBOARD
 * - Ranking postaci według doświadczenia
 * - Ranking graczy według aktywności
 * - System odznak
 */

if (!defined('APP_NAME')) die('Direct access not allowed');

if ($action == 'leaderboard') {
    $type = $_GET['type'] ?? 'characters';
    ?>
    <h2><i class="bi bi-trophy"></i> Ranking</h2>
    
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $type == 'characters' ? 'active' : '' ?>" href="?action=leaderboard&type=characters">Postaci</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $type == 'players' ? 'active' : '' ?>" href="?action=leaderboard&type=players">Gracze</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $type == 'achievements' ? 'active' : '' ?>" href="?action=leaderboard&type=achievements">Odznaki</a>
        </li>
    </ul>
    
    <?php if ($type == 'characters'): ?>
        <?php
        $stmt = db()->query("
            SELECT c.*, u.username,
                   (SELECT COUNT(*) FROM character_achievements WHERE character_id = c.id) as badge_count
            FROM characters c
            JOIN users u ON c.user_id = u.id
            WHERE c.approved_by_mg = TRUE AND c.status = 'active'
            ORDER BY c.experience DESC, c.level DESC
            LIMIT 50
        ");
        $characters = $stmt->fetchAll();
        ?>
        <h4>Top 50 Postaci (według doświadczenia)</h4>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Postać</th>
                        <th>Gracz</th>
                        <th>Poziom</th>
                        <th>Doświadczenie</th>
                        <th>Punkty Historii</th>
                        <th>Odznaki</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($characters as $i => $char): ?>
                        <tr>
                            <td>
                                <?php if ($i == 0): ?>
                                    <i class="bi bi-trophy-fill text-warning"></i>
                                <?php elseif ($i == 1): ?>
                                    <i class="bi bi-trophy-fill text-secondary"></i>
                                <?php elseif ($i == 2): ?>
                                    <i class="bi bi-trophy-fill" style="color: #cd7f32"></i>
                                <?php else: ?>
                                    <?= $i + 1 ?>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= sanitize($char['name']) ?></strong></td>
                            <td><?= sanitize($char['username']) ?></td>
                            <td><?= $char['level'] ?></td>
                            <td><?= number_format($char['experience']) ?></td>
                            <td><?= $char['history_points'] ?></td>
                            <td><?= $char['badge_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
    <?php elseif ($type == 'players'): ?>
        <?php
        $stmt = db()->query("
            SELECT u.username, u.created_at,
                   (SELECT COUNT(*) FROM posts WHERE author_id = u.id) as post_count,
                   (SELECT COUNT(*) FROM threads WHERE author_id = u.id) as thread_count,
                   (SELECT COUNT(*) FROM characters WHERE user_id = u.id AND approved_by_mg = TRUE) as char_count
            FROM users u
            WHERE u.approved = TRUE
            ORDER BY post_count DESC
            LIMIT 50
        ");
        $players = $stmt->fetchAll();
        ?>
        <h4>Top 50 Graczy (według aktywności)</h4>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Gracz</th>
                        <th>Postów</th>
                        <th>Wątków</th>
                        <th>Postaci</th>
                        <th>Członek od</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($players as $i => $player): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= sanitize($player['username']) ?></strong></td>
                            <td><?= $player['post_count'] ?></td>
                            <td><?= $player['thread_count'] ?></td>
                            <td><?= $player['char_count'] ?></td>
                            <td><?= date('Y-m-d', strtotime($player['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
    <?php else: // achievements ?>
        <?php
        $stmt = db()->query("
            SELECT a.*, 
                   (SELECT COUNT(*) FROM character_achievements WHERE achievement_id = a.id) as earned_count
            FROM achievements a
            ORDER BY a.category, a.points DESC
        ");
        $achievements = $stmt->fetchAll();
        ?>
        <h4>Wszystkie odznaki</h4>
        <?php
        $categories = ['combat' => 'Walka', 'social' => 'Społeczność', 'exploration' => 'Eksploracja', 'special' => 'Specjalne'];
        foreach ($categories as $cat_key => $cat_name):
            $cat_achievements = array_filter($achievements, fn($a) => $a['category'] == $cat_key);
            if (count($cat_achievements) > 0):
        ?>
            <h5 class="mt-4"><?= $cat_name ?></h5>
            <div class="row">
                <?php foreach ($cat_achievements as $ach): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5>
                                    <?php if ($ach['icon']): ?>
                                        <i class="<?= sanitize($ach['icon']) ?>"></i>
                                    <?php endif; ?>
                                    <?= sanitize($ach['name']) ?>
                                </h5>
                                <p class="text-muted"><?= sanitize($ach['description']) ?></p>
                                <p>
                                    <span class="badge bg-primary"><?= $ach['points'] ?> punktów</span>
                                    <span class="badge bg-secondary">Zdobyto: <?= $ach['earned_count'] ?>x</span>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php
            endif;
        endforeach;
        ?>
    <?php endif; ?>
    <?php
}
?>
