<?php
/**
 * PANEL MISTRZA GRY (MG)
 * - Akceptacja postaci
 * - Zatwierdzanie umiejętności
 * - Moderacja zgłoszeń
 */

if (!defined('APP_NAME')) die('Direct access not allowed');

if (!hasRole('mg')) {
    echo alert('Brak dostępu - wymagana rola MG', 'danger');
    exit;
}

if ($action == 'mg_panel') {
    $pending_chars = db()->query("SELECT COUNT(*) FROM characters WHERE approved_by_mg = FALSE")->fetchColumn();
    $pending_skills = db()->query("SELECT COUNT(*) FROM character_skills WHERE approved = FALSE")->fetchColumn();
    $pending_reports = db()->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
    ?>
    <h2><i class="bi bi-shield"></i> Panel Mistrza Gry</h2>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card text-center"><div class="card-body">
            <h3 class="text-warning"><?= $pending_chars ?></h3>
            <p>Postacie do akceptacji</p>
            <a href="?action=mg_characters" class="btn btn-sm btn-primary">Zarządzaj</a>
        </div></div></div>
        <div class="col-md-4"><div class="card text-center"><div class="card-body">
            <h3 class="text-info"><?= $pending_skills ?></h3>
            <p>Umiejętności do zatwierdzenia</p>
            <a href="?action=mg_skills" class="btn btn-sm btn-primary">Zarządzaj</a>
        </div></div></div>
        <div class="col-md-4"><div class="card text-center"><div class="card-body">
            <h3 class="text-danger"><?= $pending_reports ?></h3>
            <p>Zgłoszenia</p>
            <a href="?action=mg_reports" class="btn btn-sm btn-primary">Zarządzaj</a>
        </div></div></div>
    </div>
    <?php
}

elseif ($action == 'mg_characters') {
    if ($_POST && isset($_POST['char_action'])) {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo alert('Nieprawidłowy token CSRF', 'danger');
        } else {
            $char_id = intval($_POST['char_id']);
            if ($_POST['char_action'] == 'approve') {
                db()->prepare("UPDATE characters SET approved_by_mg = TRUE WHERE id = ?")->execute([$char_id]);
                $c = db()->prepare("SELECT user_id, name FROM characters WHERE id = ?");
                $c->execute([$char_id]);
                $char = $c->fetch();
                if ($char) addNotification($char['user_id'], 'system', 'Postać zaakceptowana', 'Twoja postać ' . $char['name'] . ' została zaakceptowana!', '?action=character_view&id=' . $char_id);
                echo alert('Postać zaakceptowana', 'success');
            } elseif ($_POST['char_action'] == 'reject') {
                $reason = trim($_POST['reason'] ?? '');
                if (strlen($reason) >= 5) {
                    $c = db()->prepare("SELECT user_id, name FROM characters WHERE id = ?");
                    $c->execute([$char_id]);
                    $char = $c->fetch();
                    if ($char) addNotification($char['user_id'], 'system', 'Postać odrzucona', 'Postać ' . $char['name'] . ' odrzucona. Powód: ' . $reason);
                    db()->prepare("DELETE FROM characters WHERE id = ?")->execute([$char_id]);
                    echo alert('Postać odrzucona', 'warning');
                } else {
                    echo alert('Powód musi mieć min. 5 znaków', 'danger');
                }
            }
        }
    }
    $stmt = db()->query("SELECT c.*, u.username FROM characters c JOIN users u ON c.user_id = u.id WHERE c.approved_by_mg = FALSE ORDER BY c.created_at ASC");
    $pending = $stmt->fetchAll();
    ?>
    <h2>Postacie do akceptacji</h2>
    <a href="?action=mg_panel" class="btn btn-secondary mb-3">Powrót</a>
    <?php if (count($pending) == 0): ?>
        <div class="alert alert-info">Brak postaci oczekujących</div>
    <?php else: foreach ($pending as $char): ?>
        <div class="card mb-3">
            <div class="card-header"><h5><?= sanitize($char['name']) ?></h5><small>Gracz: <?= sanitize($char['username']) ?></small></div>
            <div class="card-body"><p><?= nl2br(sanitize($char['description'])) ?></p></div>
            <div class="card-footer">
                <form method="post" class="d-inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="char_id" value="<?= $char['id'] ?>">
                    <button type="submit" name="char_action" value="approve" class="btn btn-success">Zaakceptuj</button>
                </form>
                <button class="btn btn-danger" onclick="rejectChar(<?= $char['id'] ?>)">Odrzuc</button>
            </div>
        </div>
    <?php endforeach; endif; ?>
    <script>
    function rejectChar(id) {
        const reason = prompt('Powód odrzucenia (min 5 znaków):');
        if (reason && reason.length >= 5) {
            const form = document.createElement('form');
            form.method = 'post';
            [{name:'csrf_token',value:'<?= generateCSRFToken() ?>'},{name:'char_id',value:id},{name:'char_action',value:'reject'},{name:'reason',value:reason}]
                .forEach(i => { const inp = document.createElement('input'); inp.type='hidden'; inp.name=i.name; inp.value=i.value; form.appendChild(inp); });
            document.body.appendChild(form); form.submit();
        } else alert('Min. 5 znaków!');
    }
    </script>
    <?php
}

elseif ($action == 'mg_skills') {
    if ($_POST && isset($_POST['skill_action'])) {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo alert('Nieprawidłowy token CSRF', 'danger');
        } else {
            $skill_id = intval($_POST['skill_id']);
            if ($_POST['skill_action'] == 'approve') {
                db()->prepare("UPDATE character_skills SET approved = TRUE, approved_by = ? WHERE id = ?")->execute([$user['id'], $skill_id]);
                $s = db()->prepare("SELECT cs.skill_name, c.user_id, c.name FROM character_skills cs JOIN characters c ON cs.character_id = c.id WHERE cs.id = ?");
                $s->execute([$skill_id]);
                $skill = $s->fetch();
                if ($skill) addNotification($skill['user_id'], 'system', 'Umiejętność zaakceptowana', '"' . $skill['skill_name'] . '" dla ' . $skill['name'] . ' zaakceptowana!');
                echo alert('Umiejętność zaakceptowana', 'success');
            } elseif ($_POST['skill_action'] == 'reject') {
                $reason = trim($_POST['reason'] ?? '');
                if (strlen($reason) >= 5) {
                    db()->prepare("DELETE FROM character_skills WHERE id = ?")->execute([$skill_id]);
                    echo alert('Umiejętność odrzucona', 'warning');
                } else {
                    echo alert('Powód: min. 5 znaków', 'danger');
                }
            }
        }
    }
    $stmt = db()->query("SELECT cs.*, c.name as char_name, u.username FROM character_skills cs JOIN characters c ON cs.character_id = c.id JOIN users u ON c.user_id = u.id WHERE cs.approved = FALSE ORDER BY cs.created_at ASC");
    $skills = $stmt->fetchAll();
    ?>
    <h2>Umiejętności do zatwierdzenia</h2>
    <a href="?action=mg_panel" class="btn btn-secondary mb-3">Powrót</a>
    <?php if (count($skills) == 0): ?>
        <div class="alert alert-info">Brak umiejętności</div>
    <?php else: ?>
    <table class="table table-bordered">
        <thead><tr><th>Postać</th><th>Gracz</th><th>Umiejętność</th><th>Opis</th><th>Poz.</th><th>Akcje</th></tr></thead>
        <tbody>
        <?php foreach ($skills as $s): ?>
        <tr>
            <td><?= sanitize($s['char_name']) ?></td>
            <td><?= sanitize($s['username']) ?></td>
            <td><strong><?= sanitize($s['skill_name']) ?></strong></td>
            <td><?= sanitize($s['skill_description']) ?></td>
            <td><?= intval($s['skill_level']) ?></td>
            <td>
                <form method="post" class="d-inline"><?= csrfField() ?><input type="hidden" name="skill_id" value="<?= $s['id'] ?>"><button type="submit" name="skill_action" value="approve" class="btn btn-sm btn-success">OK</button></form>
                <button class="btn btn-sm btn-danger" onclick="rejectSkill(<?= $s['id'] ?>)">X</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <script>
    function rejectSkill(id) {
        const r = prompt('Powód:');
        if (r && r.length >= 5) {
            const form = document.createElement('form'); form.method='post';
            [{name:'csrf_token',value:'<?= generateCSRFToken() ?>'},{name:'skill_id',value:id},{name:'skill_action',value:'reject'},{name:'reason',value:r}]
                .forEach(i=>{const inp=document.createElement('input');inp.type='hidden';inp.name=i.name;inp.value=i.value;form.appendChild(inp);});
            document.body.appendChild(form);form.submit();
        } else alert('Min 5 znaków');
    }
    </script>
    <?php
}

elseif ($action == 'mg_reports') {
    if ($_POST && isset($_POST['resolve_report'])) {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo alert('Nieprawidłowy CSRF', 'danger');
        } else {
            $report_id = intval($_POST['report_id']);
            $status = in_array($_POST['status'], ['resolved','dismissed']) ? $_POST['status'] : 'resolved';
            $resolution = trim($_POST['resolution'] ?? '');
            db()->prepare("UPDATE reports SET status = ?, reviewed_by = ?, resolution = ?, resolved_at = NOW() WHERE id = ?")->execute([$status, $user['id'], $resolution, $report_id]);
            echo alert('Zgłoszenie rozpatrzone', 'success');
        }
    }
    $stmt = db()->query("SELECT r.*, u.username as reporter_name FROM reports r JOIN users u ON r.reporter_id = u.id ORDER BY CASE r.status WHEN 'pending' THEN 1 ELSE 2 END, r.created_at DESC");
    $reports = $stmt->fetchAll();
    ?>
    <h2>Zgłoszenia</h2>
    <a href="?action=mg_panel" class="btn btn-secondary mb-3">Powrót</a>
    <?php if (count($reports) == 0): ?>
        <div class="alert alert-info">Brak zgłoszeń</div>
    <?php else: foreach ($reports as $r): ?>
        <div class="card mb-3">
            <div class="card-header">
                <strong>#<?= $r['id'] ?></strong> <span class="badge bg-<?= $r['status']=='pending'?'warning':'secondary' ?>"><?= $r['status'] ?></span>
                <small>Zgłasza: <?= sanitize($r['reporter_name']) ?> | Typ: <?= $r['reported_type'] ?> #<?= $r['reported_id'] ?></small>
            </div>
            <div class="card-body"><p><?= nl2br(sanitize($r['reason'])) ?></p></div>
            <?php if ($r['status'] == 'pending'): ?>
            <div class="card-footer">
                <button class="btn btn-sm btn-success" onclick="resolve(<?= $r['id'] ?>,'resolved')">Rozwiązane</button>
                <button class="btn btn-sm btn-secondary" onclick="resolve(<?= $r['id'] ?>,'dismissed')">Odrzuc</button>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; endif; ?>
    <script>
    function resolve(id,status){
        const n=prompt('Notatka:');
        if(n!==null){
            const form=document.createElement('form');form.method='post';
            [{name:'csrf_token',value:'<?= generateCSRFToken() ?>'},{name:'resolve_report',value:'1'},{name:'report_id',value:id},{name:'status',value:status},{name:'resolution',value:n}]
                .forEach(i=>{const inp=document.createElement('input');inp.type='hidden';inp.name=i.name;inp.value=i.value;form.appendChild(inp);});
            document.body.appendChild(form);form.submit();
        }
    }
    </script>
    <?php
}
?>