<?php
$pageTitle  = 'Inscriptions';
$activeMenu = 'inscriptions';
require_once __DIR__ . '/layout.php';

$action = sanitize($_GET['action'] ?? '');
$id     = (int)($_GET['id'] ?? 0);

// -- Actions --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update' && $id) {
    require_auth('editor');
    $statut      = sanitize($_POST['statut']       ?? '');
    $paiement    = sanitize($_POST['paiement']     ?? '');
    $notes_admin = sanitize($_POST['notes_admin']  ?? '');
    db()->prepare("UPDATE inscriptions SET statut=?,paiement=?,notes_admin=? WHERE id=?")
       ->execute([$statut, $paiement, $notes_admin, $id]);
    flash_set('success', 'Inscription mise à jour.');
    redirect('/admin/inscriptions.php');
}

if ($action === 'delete' && $id && is_admin()) {
    db()->prepare("DELETE FROM inscriptions WHERE id=?")->execute([$id]);
    flash_set('success', 'Inscription supprimée.');
    redirect('/admin/inscriptions.php');
}

// -- Export CSV ------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = db()->query("SELECT * FROM inscriptions ORDER BY created_at DESC")->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="inscriptions_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Prénom','Nom','Email','Téléphone','Organisation','Formation','Niveau','Statut','Paiement','Date'], ';');
    foreach ($rows as $r) fputcsv($out, [$r['id'],$r['prenom'],$r['nom'],$r['email'],$r['telephone'],$r['organisation'],$r['formation_nom'],$r['niveau'],$r['statut'],$r['paiement'],$r['created_at']], ';');
    fclose($out); exit;
}

// -- Filtres ---------------------------------------------------
$fStatut    = sanitize($_GET['statut']    ?? '');
$fFormation = sanitize($_GET['formation'] ?? '');
$search     = sanitize($_GET['q']         ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));

$where = "WHERE 1=1"; $params = [];
if ($fStatut)    { $where .= " AND statut=?";           $params[] = $fStatut; }
if ($fFormation) { $where .= " AND formation_nom LIKE ?"; $params[] = "%$fFormation%"; }
if ($search)     { $where .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt  = db()->prepare("SELECT COUNT(*) FROM inscriptions $where");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pag   = paginate($total, $page);

$stmt  = db()->prepare("SELECT * FROM inscriptions $where ORDER BY created_at DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET {$pag['offset']}");
$stmt->execute($params);
$inscriptions = $stmt->fetchAll();

$formations = db()->query("SELECT DISTINCT formation_nom FROM inscriptions ORDER BY formation_nom")->fetchAll(PDO::FETCH_COLUMN);

// Détail pour modal
$detail = null;
if ($action === 'view' && $id) {
    $stmt = db()->prepare("SELECT * FROM inscriptions WHERE id=?");
    $stmt->execute([$id]);
    $detail = $stmt->fetch();
}
?>

<div class="page-header">
  <h1 class="page-title">Inscriptions aux formations</h1>
  <a href="?export=csv" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Export CSV</a>
</div>

<!-- Filtres -->
<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-body">
    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div>
        <label style="font-size:.82rem;font-weight:600;display:block;margin-bottom:4px">Statut</label>
        <select name="statut" class="form-control" style="padding:8px 12px" onchange="this.form.submit()">
          <option value="">Tous</option>
          <option value="en_attente" <?= $fStatut==='en_attente' ? 'selected':'' ?>>En attente</option>
          <option value="confirme"   <?= $fStatut==='confirme'   ? 'selected':'' ?>>Confirmé</option>
          <option value="annule"     <?= $fStatut==='annule'     ? 'selected':'' ?>>Annulé</option>
          <option value="termine"    <?= $fStatut==='termine'    ? 'selected':'' ?>>Terminé</option>
        </select>
      </div>
      <div>
        <label style="font-size:.82rem;font-weight:600;display:block;margin-bottom:4px">Formation</label>
        <select name="formation" class="form-control" style="padding:8px 12px" onchange="this.form.submit()">
          <option value="">Toutes</option>
          <?php foreach ($formations as $f): ?>
          <option value="<?= htmlspecialchars($f) ?>" <?= $fFormation===$f ? 'selected':'' ?>><?= htmlspecialchars(mb_substr($f,0,40)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.82rem;font-weight:600;display:block;margin-bottom:4px">Recherche</label>
        <div style="display:flex;gap:6px">
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, email…" class="form-control" style="padding:8px 12px">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
          <?php if ($fStatut || $fFormation || $search): ?>
          <a href="/admin/inscriptions.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header"><span><?= $total ?> inscription<?= $total>1?'s':'' ?></span></div>
  <div class="admin-card-body p-0">
    <?php if ($inscriptions): ?>
    <div style="overflow-x:auto">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Candidat</th><th>Formation</th><th>Statut</th><th>Paiement</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        <?php
        $sBadge = ['en_attente'=>'warning','confirme'=>'success','annule'=>'danger','termine'=>'info'];
        $pBadge = ['en_attente'=>'warning','partiel'=>'info','complet'=>'success'];
        foreach ($inscriptions as $ins): ?>
        <tr>
          <td><?= $ins['id'] ?></td>
          <td>
            <strong><?= htmlspecialchars($ins['prenom'].' '.$ins['nom']) ?></strong><br>
            <small style="color:var(--text-light)"><?= htmlspecialchars($ins['email']) ?></small>
          </td>
          <td><?= htmlspecialchars(mb_substr($ins['formation_nom'],0,32)) ?></td>
          <td><span class="badge badge-<?= $sBadge[$ins['statut']]??'light' ?>"><?= ucfirst(str_replace('_',' ',$ins['statut'])) ?></span></td>
          <td><span class="badge badge-<?= $pBadge[$ins['paiement']]??'light' ?>"><?= ucfirst($ins['paiement']) ?></span></td>
          <td style="white-space:nowrap"><?= format_date($ins['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:4px">
              <a href="?action=view&id=<?= $ins['id'] ?>" class="btn btn-sm btn-outline" style="padding:5px 10px" title="Voir"><i class="fas fa-eye"></i></a>
              <?php if (is_admin()): ?>
              <a href="?action=delete&id=<?= $ins['id'] ?>" class="btn btn-sm" style="padding:5px 10px;background:var(--accent);color:#fff"
                 data-confirm="Supprimer cette inscription ?" title="Supprimer"><i class="fas fa-trash"></i></a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <?php if ($pag['total_pages'] > 1): ?>
    <div style="padding:16px">
      <nav class="pagination" style="justify-content:flex-start">
        <?php if($pag['has_prev']):?><a href="?page=<?=$pag['current']-1?>&statut=<?=$fStatut?>&q=<?=urlencode($search)?>">‹</a><?php endif;?>
        <?php for($p=1;$p<=$pag['total_pages'];$p++): if($p===$pag['current']):?><span class="current"><?=$p?></span><?php else:?><a href="?page=<?=$p?>&statut=<?=$fStatut?>&q=<?=urlencode($search)?>"><?=$p?></a><?php endif; endfor;?>
        <?php if($pag['has_next']):?><a href="?page=<?=$pag['current']+1?>&statut=<?=$fStatut?>&q=<?=urlencode($search)?>">›</a><?php endif;?>
      </nav>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <p class="empty-state">Aucune inscription trouvée.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Modal détail / édition -->
<?php if ($detail): ?>
<div class="admin-modal-overlay" onclick="this.style.display='none'">
  <div class="admin-modal" onclick="event.stopPropagation()" style="max-width:600px">
    <div class="admin-modal-header">
      <h3>Inscription #<?= $detail['id'] ?> -- <?= htmlspecialchars($detail['prenom'].' '.$detail['nom']) ?></h3>
      <a href="/admin/inscriptions.php" class="modal-close"><i class="fas fa-times"></i></a>
    </div>
    <form method="POST" action="?action=update&id=<?= $detail['id'] ?>">
      <?= csrf_field() ?>
      <div class="admin-modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;font-size:.88rem">
          <div><label style="color:var(--text-light)">Email</label><div><?= htmlspecialchars($detail['email']) ?></div></div>
          <div><label style="color:var(--text-light)">Téléphone</label><div><?= htmlspecialchars($detail['telephone']) ?></div></div>
          <div><label style="color:var(--text-light)">Organisation</label><div><?= htmlspecialchars($detail['organisation'] ?? '--') ?></div></div>
          <div><label style="color:var(--text-light)">Niveau</label><div><?= htmlspecialchars($detail['niveau'] ?? '--') ?></div></div>
          <div style="grid-column:1/-1"><label style="color:var(--text-light)">Formation</label><div><strong><?= htmlspecialchars($detail['formation_nom']) ?></strong></div></div>
        </div>
        <?php if ($detail['motivation']): ?>
        <div style="background:var(--gray-100);border-radius:var(--radius);padding:12px;margin-bottom:16px;font-size:.88rem;line-height:1.7">
          <label style="color:var(--text-light);display:block;margin-bottom:4px">Motivation</label>
          <?= nl2br(htmlspecialchars($detail['motivation'])) ?>
        </div>
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
          <div class="form-group">
            <label>Statut</label>
            <select name="statut" class="form-control">
              <?php foreach (['en_attente'=>'En attente','confirme'=>'Confirmé','annule'=>'Annulé','termine'=>'Terminé'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $detail['statut']===$v ? 'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Paiement</label>
            <select name="paiement" class="form-control">
              <?php foreach (['en_attente'=>'En attente','partiel'=>'Partiel','complet'=>'Complet'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $detail['paiement']===$v ? 'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Notes admin</label>
          <textarea name="notes_admin" class="form-control" rows="3"><?= htmlspecialchars($detail['notes_admin'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary w-100">Enregistrer les modifications</button>
      </div>
    </form>
  </div>
</div>
<style>.admin-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:9000;padding:16px}.admin-modal{background:#fff;border-radius:var(--radius-lg);width:100%;box-shadow:var(--shadow-xl)}.admin-modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--gray-200)}.admin-modal-body{padding:24px}.modal-close{color:var(--text-light);font-size:1.2rem}</style>
<?php endif; ?>

  </main>
</div>
<script src="/assets/js/main.js"></script>
</body></html>
