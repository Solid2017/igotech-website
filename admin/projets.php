<?php
$pageTitle  = 'Projets & Portfolio';
$activeMenu = 'projets';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';
require_auth('admin');
require_once __DIR__ . '/layout.php';

$pdo    = db();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$error  = '';

/* ── DELETE ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_check();
    $pdo->prepare("DELETE FROM projets WHERE id=?")->execute([(int)$_POST['delete_id']]);
    flash_set('success', 'Projet supprimé.');
    redirect('/admin/projets.php');
}

/* ── TOGGLE ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    csrf_check();
    $pdo->prepare("UPDATE projets SET est_publie = 1 - est_publie WHERE id=?")->execute([(int)$_POST['toggle_id']]);
    redirect('/admin/projets.php');
}

/* ── SAVE ───────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    csrf_check();
    $data = [
        'titre'         => sanitize($_POST['titre'] ?? ''),
        'client'        => sanitize($_POST['client'] ?? ''),
        'description'   => sanitize($_POST['description'] ?? ''),
        'categorie'     => sanitize($_POST['categorie'] ?? ''),
        'lien'          => sanitize($_POST['lien'] ?? ''),
        'technologies'  => sanitize($_POST['technologies'] ?? ''),
        'ordre'         => (int)($_POST['ordre'] ?? 0),
        'est_publie'    => isset($_POST['est_publie']) ? 1 : 0,
    ];

    if (!$data['titre']) {
        $error = 'Le titre est obligatoire.';
    } else {
        $imageUrl = sanitize($_POST['image_actuelle'] ?? '');
        if (!empty($_FILES['image']['name'])) {
            $uploaded = upload_image($_FILES['image'], 'projets');
            if ($uploaded) $imageUrl = $uploaded;
            else $error = 'Image invalide.';
        }
        $data['image'] = $imageUrl ?: null;

        if (!$error) {
            $editId = (int)($_POST['edit_id'] ?? 0);
            if ($editId) {
                $sql = "UPDATE projets SET titre=:titre, client=:client, description=:description,
                        categorie=:categorie, lien=:lien, technologies=:technologies,
                        image=:image, ordre=:ordre, est_publie=:est_publie WHERE id=:id";
                $data['id'] = $editId;
                $pdo->prepare($sql)->execute($data);
                flash_set('success', 'Projet mis à jour.');
            } else {
                $sql = "INSERT INTO projets (titre,client,description,categorie,lien,technologies,image,ordre,est_publie)
                        VALUES (:titre,:client,:description,:categorie,:lien,:technologies,:image,:ordre,:est_publie)";
                $pdo->prepare($sql)->execute($data);
                flash_set('success', 'Projet créé.');
            }
            redirect('/admin/projets.php');
        }
    }
}

/* ── CHARGER PROJET ─────────────────────────────────────── */
$projet = null;
if ($action === 'edit' && $id) {
    $projet = $pdo->prepare("SELECT * FROM projets WHERE id=?");
    $projet->execute([$id]);
    $projet = $projet->fetch();
    if (!$projet) { flash_set('error', 'Projet introuvable.'); redirect('/admin/projets.php'); }
}

$projets = $pdo->query("SELECT * FROM projets ORDER BY ordre ASC, id DESC")->fetchAll();
?>

<?= flash_html() ?>

<?php if ($action === 'list'): ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px">
  <h2 style="margin:0;font-size:1.1rem;color:#1a2c4e"><?= count($projets) ?> projet(s)</h2>
  <a href="?action=new" class="btn btn-primary" style="font-size:.88rem;padding:8px 18px">
    <i class="fas fa-plus"></i> Nouveau projet
  </a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
  <?php if (empty($projets)): ?>
  <p style="color:#6c7a91;grid-column:1/-1;text-align:center;padding:40px">
    Aucun projet. <a href="?action=new">Créer le premier</a>
  </p>
  <?php endif; ?>
  <?php foreach ($projets as $p): ?>
  <div style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.08);border:1px solid #e2e8f0">
    <?php if ($p['image']): ?>
    <img src="<?= htmlspecialchars($p['image']) ?>" alt=""
         style="width:100%;height:160px;object-fit:cover">
    <?php else: ?>
    <div style="width:100%;height:100px;background:linear-gradient(135deg,#053067,#0a4f9e);display:flex;align-items:center;justify-content:center">
      <i class="fas fa-folder-open" style="font-size:2rem;color:rgba(255,255,255,.4)"></i>
    </div>
    <?php endif; ?>
    <div style="padding:14px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px">
        <strong style="font-size:.9rem;color:#1a2c4e;line-height:1.3"><?= htmlspecialchars($p['titre']) ?></strong>
        <form method="post" style="flex-shrink:0">
          <?= csrf_field() ?>
          <input type="hidden" name="toggle_id" value="<?= $p['id'] ?>">
          <button type="submit" style="background:none;border:none;cursor:pointer;padding:0">
            <?php if ($p['est_publie']): ?>
              <span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700">Publié</span>
            <?php else: ?>
              <span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700">Masqué</span>
            <?php endif; ?>
          </button>
        </form>
      </div>
      <?php if ($p['client']): ?>
      <p style="font-size:.78rem;color:#6c7a91;margin-bottom:4px"><i class="fas fa-building" style="margin-right:4px"></i><?= htmlspecialchars($p['client']) ?></p>
      <?php endif; ?>
      <?php if ($p['categorie']): ?>
      <span style="font-size:.72rem;background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:10px"><?= htmlspecialchars($p['categorie']) ?></span>
      <?php endif; ?>
      <?php if ($p['technologies']): ?>
      <p style="font-size:.75rem;color:#6c7a91;margin-top:6px"><i class="fas fa-code" style="margin-right:4px"></i><?= htmlspecialchars($p['technologies']) ?></p>
      <?php endif; ?>
      <div style="display:flex;gap:6px;margin-top:10px">
        <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem;flex:1;text-align:center">
          <i class="fas fa-edit"></i> Modifier
        </a>
        <form method="post" onsubmit="return confirm('Supprimer ce projet ?')">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
          <button type="submit" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem;border-color:#e63946;color:#e63946">
            <i class="fas fa-trash"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else: /* ── FORMULAIRE ── */ ?>

<div style="margin-bottom:16px">
  <a href="/admin/projets.php" class="btn btn-outline" style="font-size:.82rem;padding:6px 14px">
    <i class="fas fa-arrow-left"></i> Retour
  </a>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" style="max-width:700px">
  <?= csrf_field() ?>
  <input type="hidden" name="save" value="1">
  <input type="hidden" name="edit_id" value="<?= $projet['id'] ?? 0 ?>">
  <input type="hidden" name="image_actuelle" value="<?= htmlspecialchars($projet['image'] ?? '') ?>">

  <div class="admin-card" style="padding:24px;margin-bottom:20px">
    <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
      <i class="fas fa-briefcase" style="color:#053067;margin-right:8px"></i>Informations du projet
    </h3>
    <div class="form-group">
      <label class="form-label">Titre <span style="color:#e63946">*</span></label>
      <input type="text" name="titre" class="form-control" required
             value="<?= htmlspecialchars($projet['titre'] ?? '') ?>"
             placeholder="Nom du projet">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="form-group">
        <label class="form-label">Client / Organisation</label>
        <input type="text" name="client" class="form-control"
               value="<?= htmlspecialchars($projet['client'] ?? '') ?>"
               placeholder="Nom du client">
      </div>
      <div class="form-group">
        <label class="form-label">Catégorie</label>
        <input type="text" name="categorie" class="form-control"
               value="<?= htmlspecialchars($projet['categorie'] ?? '') ?>"
               placeholder="ex: Site Web, Application, Infrastructure…">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="4"
                placeholder="Décrivez le projet, les défis et les solutions apportées…"><?= htmlspecialchars($projet['description'] ?? '') ?></textarea>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="form-group">
        <label class="form-label">Technologies utilisées</label>
        <input type="text" name="technologies" class="form-control"
               value="<?= htmlspecialchars($projet['technologies'] ?? '') ?>"
               placeholder="PHP, MySQL, React…">
      </div>
      <div class="form-group">
        <label class="form-label">Lien du projet (URL)</label>
        <input type="url" name="lien" class="form-control"
               value="<?= htmlspecialchars($projet['lien'] ?? '') ?>"
               placeholder="https://…">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Ordre d'affichage</label>
      <input type="number" name="ordre" class="form-control" min="0" style="max-width:120px"
             value="<?= $projet['ordre'] ?? 0 ?>">
    </div>
  </div>

  <div class="admin-card" style="padding:24px;margin-bottom:20px">
    <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
      <i class="fas fa-image" style="color:#053067;margin-right:8px"></i>Image du projet
    </h3>
    <?php if (!empty($projet['image'])): ?>
    <div style="margin-bottom:12px">
      <img src="<?= htmlspecialchars($projet['image']) ?>" alt=""
           style="max-height:140px;border-radius:8px;border:1px solid #e2e8f0">
    </div>
    <?php endif; ?>
    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
    <small style="color:#6c7a91">JPG, PNG ou WebP — max 5 Mo — recommandé : 800×500 px</small>
  </div>

  <div class="admin-card" style="padding:16px;margin-bottom:24px">
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:.9rem;color:#1a2c4e">
      <input type="checkbox" name="est_publie" value="1"<?= ($projet['est_publie'] ?? 1) ? ' checked' : '' ?>>
      Publier ce projet (visible sur le site)
    </label>
  </div>

  <div style="display:flex;gap:10px">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i> <?= $projet ? 'Enregistrer' : 'Créer le projet' ?>
    </button>
    <a href="/admin/projets.php" class="btn btn-outline">Annuler</a>
  </div>
</form>

<?php endif; ?>

</main>
</div>
</body>
</html>
