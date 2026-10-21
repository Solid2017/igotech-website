<?php
$pageTitle  = 'Newsletter';
$activeMenu = 'newsletter';
require_once __DIR__ . '/layout.php';

$action = sanitize($_GET['action'] ?? '');
$id     = (int)($_GET['id'] ?? 0);

// Désabonnement admin
if ($action === 'deactivate' && $id) {
    db()->prepare("UPDATE newsletter_subscribers SET statut='desabonne' WHERE id=?")->execute([$id]);
    flash_set('success', 'Abonné désactivé.');
    redirect('/admin/newsletter.php');
}
if ($action === 'activate' && $id) {
    db()->prepare("UPDATE newsletter_subscribers SET statut='actif' WHERE id=?")->execute([$id]);
    flash_set('success', 'Abonné réactivé.');
    redirect('/admin/newsletter.php');
}
if ($action === 'delete' && $id && is_admin()) {
    db()->prepare("DELETE FROM newsletter_subscribers WHERE id=?")->execute([$id]);
    flash_set('success', 'Abonné supprimé.');
    redirect('/admin/newsletter.php');
}

// Export CSV
if (isset($_GET['export'])) {
    $rows = db()->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC")->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="newsletter_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Email','Nom','Langue','Statut','Date inscription'], ';');
    foreach ($rows as $r) fputcsv($out, [$r['id'],$r['email'],$r['nom'],$r['langue'],$r['statut'],$r['created_at']], ';');
    fclose($out); exit;
}

$fStatut = sanitize($_GET['statut'] ?? 'actif');
$search  = sanitize($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));

$where = "WHERE 1=1"; $params = [];
if ($fStatut) { $where .= " AND statut=?"; $params[] = $fStatut; }
if ($search)  { $where .= " AND (email LIKE ? OR nom LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = db()->prepare("SELECT COUNT(*) FROM newsletter_subscribers $where"); $stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pag   = paginate($total, $page);
$stmt  = db()->prepare("SELECT * FROM newsletter_subscribers $where ORDER BY created_at DESC LIMIT ".ITEMS_PER_PAGE." OFFSET {$pag['offset']}");
$stmt->execute($params);
$subscribers = $stmt->fetchAll();

$totalActifs    = (int)db()->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE statut='actif'")->fetchColumn();
$totalInactifs  = (int)db()->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE statut='desabonne'")->fetchColumn();
?>

<div class="page-header">
  <h1 class="page-title">Newsletter</h1>
  <a href="?export=1" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Export CSV</a>
</div>

<!-- KPI -->
<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
  <div class="admin-card" style="flex:1;min-width:160px">
    <div class="admin-card-body" style="text-align:center">
      <div style="font-size:2rem;font-weight:900;color:var(--primary)"><?= $totalActifs ?></div>
      <div style="color:var(--text-light);font-size:.88rem">Abonnés actifs</div>
    </div>
  </div>
  <div class="admin-card" style="flex:1;min-width:160px">
    <div class="admin-card-body" style="text-align:center">
      <div style="font-size:2rem;font-weight:900;color:var(--gray-600)"><?= $totalInactifs ?></div>
      <div style="color:var(--text-light);font-size:.88rem">Désabonnés</div>
    </div>
  </div>
  <div class="admin-card" style="flex:1;min-width:160px">
    <div class="admin-card-body" style="text-align:center">
      <div style="font-size:2rem;font-weight:900;color:var(--accent)"><?= $totalActifs + $totalInactifs ?></div>
      <div style="color:var(--text-light);font-size:.88rem">Total inscrits</div>
    </div>
  </div>
</div>

<!-- Filtres -->
<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-body">
    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div>
        <label style="font-size:.82rem;font-weight:600;display:block;margin-bottom:4px">Statut</label>
        <select name="statut" class="form-control" style="padding:8px 12px" onchange="this.form.submit()">
          <option value="actif"      <?= $fStatut==='actif'      ? 'selected':'' ?>>Actifs</option>
          <option value="desabonne"  <?= $fStatut==='desabonne'  ? 'selected':'' ?>>Désabonnés</option>
          <option value=""           <?= $fStatut===''           ? 'selected':'' ?>>Tous</option>
        </select>
      </div>
      <div>
        <label style="font-size:.82rem;font-weight:600;display:block;margin-bottom:4px">Recherche</label>
        <div style="display:flex;gap:6px">
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Email, nom…" class="form-control" style="padding:8px 12px">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
          <?php if ($search): ?><a href="/admin/newsletter.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-body p-0">
    <?php if ($subscribers): ?>
    <div style="overflow-x:auto">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Email</th><th>Nom</th><th>Langue</th><th>Statut</th><th>Inscrit le</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($subscribers as $s): ?>
        <tr>
          <td><?= $s['id'] ?></td>
          <td><?= htmlspecialchars($s['email']) ?></td>
          <td><?= htmlspecialchars($s['nom'] ?? '--') ?></td>
          <td><span class="badge badge-light"><?= strtoupper($s['langue']) ?></span></td>
          <td><span class="badge badge-<?= $s['statut']==='actif' ? 'success' : 'secondary' ?>"><?= ucfirst($s['statut']) ?></span></td>
          <td style="white-space:nowrap"><?= format_date($s['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:4px">
              <?php if ($s['statut'] === 'actif'): ?>
              <a href="?action=deactivate&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline" style="padding:5px 10px"
                 data-confirm="Désabonner cet email ?" title="Désabonner"><i class="fas fa-user-minus"></i></a>
              <?php else: ?>
              <a href="?action=activate&id=<?= $s['id'] ?>" class="btn btn-sm" style="padding:5px 10px;background:#10b981;color:#fff" title="Réactiver"><i class="fas fa-user-plus"></i></a>
              <?php endif; ?>
              <?php if (is_admin()): ?>
              <a href="?action=delete&id=<?= $s['id'] ?>" class="btn btn-sm" style="padding:5px 10px;background:var(--accent);color:#fff"
                 data-confirm="Supprimer définitivement ?" title="Supprimer"><i class="fas fa-trash"></i></a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php else: ?>
    <p class="empty-state">Aucun abonné.</p>
    <?php endif; ?>
  </div>
</div>

  </main>
</div>
<script src="/assets/js/main.js"></script>
</body></html>
