<?php
$pageTitle  = 'Messages / Contacts';
$activeMenu = 'messages';
require_once __DIR__ . '/layout.php';

$action = sanitize($_GET['action'] ?? '');
$id     = (int)($_GET['id'] ?? 0);

// -- Actions --------------------------------------------------
if ($action && $id) {
    if (!csrf_check() && in_array($action, ['delete'])) {
        flash_set('error', 'Token invalide.');
    } else {
        switch ($action) {
            case 'lu':
                db()->prepare("UPDATE contacts SET statut='lu' WHERE id=?")->execute([$id]);
                break;
            case 'repondu':
                db()->prepare("UPDATE contacts SET statut='repondu' WHERE id=?")->execute([$id]);
                break;
            case 'archive':
                db()->prepare("UPDATE contacts SET statut='archive' WHERE id=?")->execute([$id]);
                break;
            case 'delete':
                if (is_admin()) {
                    db()->prepare("DELETE FROM contacts WHERE id=?")->execute([$id]);
                    flash_set('success', 'Message supprimé.');
                }
                break;
        }
    }
    redirect('/admin/messages.php');
}

// -- Filtres ---------------------------------------------------
$statut = sanitize($_GET['statut'] ?? '');
$search = sanitize($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$where = "WHERE 1=1";
$params = [];
if ($statut) { $where .= " AND statut=?"; $params[] = $statut; }
if ($search) { $where .= " AND (nom LIKE ? OR email LIKE ? OR sujet LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt  = db()->prepare("SELECT COUNT(*) FROM contacts $where");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pag   = paginate($total, $page);

$stmt  = db()->prepare("SELECT * FROM contacts $where ORDER BY created_at DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET {$pag['offset']}");
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Détail message (modal)
$detail = null;
if ($action === 'view' && $id) {
    $stmt = db()->prepare("SELECT * FROM contacts WHERE id=?");
    $stmt->execute([$id]);
    $detail = $stmt->fetch();
    if ($detail && $detail['statut'] === 'non_lu') {
        db()->prepare("UPDATE contacts SET statut='lu' WHERE id=?")->execute([$id]);
    }
}
?>

<div class="page-header">
  <h1 class="page-title">Messages / Contacts</h1>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="/admin/messages.php?export=csv<?= $statut ? '&statut='.$statut : '' ?>" class="btn btn-outline btn-sm">
      <i class="fas fa-download"></i> Export CSV
    </a>
  </div>
</div>

<?php
// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    require_auth('editor');
    $stmt2 = db()->prepare("SELECT * FROM contacts $where ORDER BY created_at DESC");
    $stmt2->execute($params);
    $rows = $stmt2->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="contacts_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Nom','Email','Téléphone','Sujet','Message','Statut','Date'], ';');
    foreach ($rows as $r) fputcsv($out, [$r['id'],$r['nom'],$r['email'],$r['telephone'],$r['sujet'],strip_tags($r['message']),$r['statut'],$r['created_at']], ';');
    fclose($out); exit;
}
?>

<!-- Filtres -->
<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-body">
    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div>
        <label style="font-size:.82rem;font-weight:600;display:block;margin-bottom:4px">Statut</label>
        <select name="statut" class="form-control" style="padding:8px 12px;min-width:150px" onchange="this.form.submit()">
          <option value="">Tous</option>
          <option value="non_lu"  <?= $statut==='non_lu'  ? 'selected' : '' ?>>Non lu</option>
          <option value="lu"      <?= $statut==='lu'      ? 'selected' : '' ?>>Lu</option>
          <option value="repondu" <?= $statut==='repondu' ? 'selected' : '' ?>>Répondu</option>
          <option value="archive" <?= $statut==='archive' ? 'selected' : '' ?>>Archivé</option>
        </select>
      </div>
      <div>
        <label style="font-size:.82rem;font-weight:600;display:block;margin-bottom:4px">Recherche</label>
        <div style="display:flex;gap:6px">
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, email, sujet…" class="form-control" style="padding:8px 12px">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
          <?php if ($statut || $search): ?>
          <a href="/admin/messages.php" class="btn btn-outline btn-sm" title="Effacer les filtres"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Tableau -->
<div class="admin-card">
  <div class="admin-card-header">
    <span><?= $total ?> message<?= $total > 1 ? 's' : '' ?></span>
  </div>
  <div class="admin-card-body p-0">
    <?php if ($messages): ?>
    <div style="overflow-x:auto">
    <table class="admin-table" data-sortable>
      <thead>
        <tr>
          <th data-col="0">#</th>
          <th data-col="1">Nom / Email</th>
          <th data-col="2">Sujet</th>
          <th data-col="3">Téléphone</th>
          <th data-col="4">Statut</th>
          <th data-col="5">Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($messages as $m):
          $badgeClass = ['non_lu'=>'danger','lu'=>'info','repondu'=>'success','archive'=>'secondary'][$m['statut']] ?? 'light';
        ?>
        <tr <?= $m['statut'] === 'non_lu' ? 'style="font-weight:700;background:rgba(5,48,103,.03)"' : '' ?>>
          <td><?= $m['id'] ?></td>
          <td>
            <?= htmlspecialchars($m['nom']) ?><br>
            <small style="color:var(--text-light)"><?= htmlspecialchars($m['email']) ?></small>
          </td>
          <td><?= htmlspecialchars(mb_substr($m['sujet'],0,35)) ?></td>
          <td><?= htmlspecialchars($m['telephone'] ?? '--') ?></td>
          <td><span class="badge badge-<?= $badgeClass ?>"><?= ucfirst(str_replace('_',' ',$m['statut'])) ?></span></td>
          <td style="white-space:nowrap"><?= format_date($m['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:4px;flex-wrap:nowrap">
              <a href="?action=view&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline" title="Voir" style="padding:5px 10px"><i class="fas fa-eye"></i></a>
              <a href="?action=repondu&id=<?= $m['id'] ?>" class="btn btn-sm" title="Marquer répondu" style="padding:5px 10px;background:var(--primary);color:#fff"><i class="fas fa-check"></i></a>
              <a href="?action=archive&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline" title="Archiver" style="padding:5px 10px"><i class="fas fa-archive"></i></a>
              <?php if (is_admin()): ?>
              <a href="?action=delete&id=<?= $m['id'] ?>" class="btn btn-sm" title="Supprimer"
                 data-confirm="Supprimer ce message ?"
                 style="padding:5px 10px;background:var(--accent);color:#fff"><i class="fas fa-trash"></i></a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <?php if ($pag['total_pages'] > 1): ?>
    <div style="padding:16px">
      <nav class="pagination" style="justify-content:flex-start">
        <?php if ($pag['has_prev']): ?><a href="?page=<?= $pag['current']-1 ?>&statut=<?= $statut ?>&q=<?= urlencode($search) ?>">‹</a><?php endif; ?>
        <?php for ($p=1;$p<=$pag['total_pages'];$p++): ?>
        <?php if($p===$pag['current']): ?><span class="current"><?= $p ?></span>
        <?php else: ?><a href="?page=<?= $p ?>&statut=<?= $statut ?>&q=<?= urlencode($search) ?>"><?= $p ?></a><?php endif; ?>
        <?php endfor; ?>
        <?php if ($pag['has_next']): ?><a href="?page=<?= $pag['current']+1 ?>&statut=<?= $statut ?>&q=<?= urlencode($search) ?>">›</a><?php endif; ?>
      </nav>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <p class="empty-state">Aucun message trouvé.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Modal détail message -->
<?php if ($detail): ?>
<div class="admin-modal-overlay" onclick="this.style.display='none'">
  <div class="admin-modal" onclick="event.stopPropagation()" style="max-width:600px">
    <div class="admin-modal-header">
      <h3>Message de <?= htmlspecialchars($detail['nom']) ?></h3>
      <a href="/admin/messages.php" class="modal-close"><i class="fas fa-times"></i></a>
    </div>
    <div class="admin-modal-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
        <div><label style="font-size:.78rem;color:var(--text-light)">Email</label><div><?= htmlspecialchars($detail['email']) ?></div></div>
        <div><label style="font-size:.78rem;color:var(--text-light)">Téléphone</label><div><?= htmlspecialchars($detail['telephone'] ?? '--') ?></div></div>
        <div><label style="font-size:.78rem;color:var(--text-light)">Sujet</label><div><?= htmlspecialchars($detail['sujet']) ?></div></div>
        <div><label style="font-size:.78rem;color:var(--text-light)">Date</label><div><?= format_date($detail['created_at'], 'd/m/Y H:i') ?></div></div>
      </div>
      <div style="background:var(--gray-100);border-radius:var(--radius);padding:16px;line-height:1.8">
        <?= nl2br(htmlspecialchars($detail['message'])) ?>
      </div>
      <div style="margin-top:16px;display:flex;gap:8px">
        <a href="mailto:<?= htmlspecialchars($detail['email']) ?>" class="btn btn-primary btn-sm">
          <i class="fas fa-reply"></i> Répondre par email
        </a>
        <a href="?action=repondu&id=<?= $detail['id'] ?>" class="btn btn-outline btn-sm">Marquer répondu</a>
      </div>
    </div>
  </div>
</div>
<style>.admin-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:9000;padding:16px}.admin-modal{background:#fff;border-radius:var(--radius-lg);width:100%;box-shadow:var(--shadow-xl)}.admin-modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--gray-200)}.admin-modal-body{padding:24px}.modal-close{color:var(--text-light);font-size:1.2rem}</style>
<?php endif; ?>

  </main>
</div>
<script src="/assets/js/main.js"></script>
</body></html>
