<?php
$pageTitle  = 'Formations';
$activeMenu = 'formations';
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
    $pdo->prepare("DELETE FROM formations WHERE id=?")->execute([(int)$_POST['delete_id']]);
    flash_set('success', 'Formation supprimée.');
    redirect('/admin/formations.php');
}

/* ── TOGGLE PUBLICATION ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    csrf_check();
    $pdo->prepare("UPDATE formations SET est_publie = 1 - est_publie WHERE id=?")->execute([(int)$_POST['toggle_id']]);
    redirect('/admin/formations.php');
}

/* ── SAVE (CREATE / EDIT) ───────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    csrf_check();

    $data = [
        'titre'          => sanitize($_POST['titre'] ?? ''),
        'titre_en'       => sanitize($_POST['titre_en'] ?? ''),
        'description'    => sanitize($_POST['description'] ?? ''),
        'description_en' => sanitize($_POST['description_en'] ?? ''),
        'duree'          => sanitize($_POST['duree'] ?? ''),
        'prix'           => is_numeric($_POST['prix'] ?? '') ? (float)$_POST['prix'] : null,
        'niveau'         => in_array($_POST['niveau'] ?? '', ['debutant','intermediaire','avance','tous']) ? $_POST['niveau'] : 'tous',
        'categorie'      => sanitize($_POST['categorie'] ?? ''),
        'places'         => is_numeric($_POST['places'] ?? '') ? (int)$_POST['places'] : null,
        'est_publie'     => isset($_POST['est_publie']) ? 1 : 0,
        'ordre'          => (int)($_POST['ordre'] ?? 0),
    ];

    if (!$data['titre']) {
        $error = 'Le titre est obligatoire.';
    } else {
        // Gestion image
        $imageUrl = sanitize($_POST['image_actuelle'] ?? '');
        if (!empty($_FILES['image']['name'])) {
            $uploaded = upload_image($_FILES['image'], 'formations');
            if ($uploaded) $imageUrl = $uploaded;
            else $error = 'Image invalide (JPG/PNG/WebP, max 5 Mo).';
        }
        $data['image'] = $imageUrl ?: null;

        if (!$error) {
            $editId = (int)($_POST['edit_id'] ?? 0);
            if ($editId) {
                $sql = "UPDATE formations SET titre=:titre, titre_en=:titre_en,
                        description=:description, description_en=:description_en,
                        duree=:duree, prix=:prix, niveau=:niveau, categorie=:categorie,
                        places=:places, image=:image, est_publie=:est_publie, ordre=:ordre
                        WHERE id=:id";
                $data['id'] = $editId;
                $pdo->prepare($sql)->execute($data);
                flash_set('success', 'Formation mise à jour.');
            } else {
                $sql = "INSERT INTO formations (titre,titre_en,description,description_en,duree,prix,niveau,categorie,places,image,est_publie,ordre)
                        VALUES (:titre,:titre_en,:description,:description_en,:duree,:prix,:niveau,:categorie,:places,:image,:est_publie,:ordre)";
                $pdo->prepare($sql)->execute($data);
                flash_set('success', 'Formation créée.');
            }
            redirect('/admin/formations.php');
        }
    }
}

/* ── CHARGER FORMATION POUR EDITION ─────────────────────── */
$formation = null;
if ($action === 'edit' && $id) {
    $formation = $pdo->prepare("SELECT * FROM formations WHERE id=?");
    $formation->execute([$id]);
    $formation = $formation->fetch();
    if (!$formation) { flash_set('error', 'Formation introuvable.'); redirect('/admin/formations.php'); }
}

/* ── LISTE ──────────────────────────────────────────────── */
$formations = $pdo->query("SELECT * FROM formations ORDER BY ordre ASC, id ASC")->fetchAll();

$niveauLabels = ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','avance'=>'Avancé','tous'=>'Tous niveaux'];
?>

<?= flash_html() ?>

<?php if ($action === 'list'): ?>

<!-- ── LISTE ── -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px">
  <h2 style="margin:0;font-size:1.1rem;color:#1a2c4e"><?= count($formations) ?> formation(s)</h2>
  <a href="?action=new" class="btn btn-primary" style="font-size:.88rem;padding:8px 18px">
    <i class="fas fa-plus"></i> Nouvelle formation
  </a>
</div>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Ordre</th><th>Titre</th><th>Catégorie</th><th>Durée</th>
        <th>Prix</th><th>Niveau</th><th>Places</th><th>Statut</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($formations)): ?>
      <tr><td colspan="9" style="text-align:center;padding:30px;color:#6c7a91">Aucune formation. <a href="?action=new">Créer la première</a></td></tr>
    <?php endif; ?>
    <?php foreach ($formations as $f): ?>
    <tr>
      <td style="text-align:center;color:#6c7a91;font-size:.82rem"><?= $f['ordre'] ?></td>
      <td>
        <strong style="font-size:.9rem"><?= htmlspecialchars($f['titre']) ?></strong>
        <?php if ($f['titre_en']): ?>
        <br><small style="color:#6c7a91"><?= htmlspecialchars($f['titre_en']) ?></small>
        <?php endif; ?>
      </td>
      <td><span style="font-size:.8rem"><?= htmlspecialchars($f['categorie'] ?? '—') ?></span></td>
      <td style="font-size:.85rem"><?= htmlspecialchars($f['duree'] ?? '—') ?></td>
      <td style="font-size:.85rem;white-space:nowrap">
        <?= $f['prix'] ? number_format($f['prix'], 0, ',', ' ') . ' FCFA' : '—' ?>
      </td>
      <td>
        <span style="font-size:.75rem;background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:10px">
          <?= $niveauLabels[$f['niveau']] ?? $f['niveau'] ?>
        </span>
      </td>
      <td style="text-align:center;font-size:.85rem"><?= $f['places'] ?? '∞' ?></td>
      <td>
        <form method="post" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="toggle_id" value="<?= $f['id'] ?>">
          <button type="submit" style="background:none;border:none;cursor:pointer;padding:0">
            <?php if ($f['est_publie']): ?>
              <span style="background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:10px;font-size:.75rem;font-weight:700">Publié</span>
            <?php else: ?>
              <span style="background:#f3f4f6;color:#6b7280;padding:3px 10px;border-radius:10px;font-size:.75rem;font-weight:700">Brouillon</span>
            <?php endif; ?>
          </button>
        </form>
      </td>
      <td style="white-space:nowrap">
        <a href="?action=edit&id=<?= $f['id'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem" title="Modifier">
          <i class="fas fa-edit"></i>
        </a>
        <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cette formation ?')">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_id" value="<?= $f['id'] ?>">
          <button type="submit" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem;border-color:#e63946;color:#e63946" title="Supprimer">
            <i class="fas fa-trash"></i>
          </button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php else: /* ── FORMULAIRE CREATE / EDIT ── */ ?>

<div style="margin-bottom:16px">
  <a href="/admin/formations.php" class="btn btn-outline" style="font-size:.82rem;padding:6px 14px">
    <i class="fas fa-arrow-left"></i> Retour à la liste
  </a>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" style="max-width:780px">
  <?= csrf_field() ?>
  <input type="hidden" name="save" value="1">
  <input type="hidden" name="edit_id" value="<?= $formation['id'] ?? 0 ?>">
  <input type="hidden" name="image_actuelle" value="<?= htmlspecialchars($formation['image'] ?? '') ?>">

  <!-- Titres -->
  <div class="admin-card" style="padding:24px;margin-bottom:20px">
    <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
      <i class="fas fa-graduation-cap" style="color:#053067;margin-right:8px"></i>Titre & Catégorie
    </h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="form-group">
        <label class="form-label">Titre (Français) <span style="color:#e63946">*</span></label>
        <input type="text" name="titre" class="form-control" required
               value="<?= htmlspecialchars($formation['titre'] ?? '') ?>"
               placeholder="ex: Développement Web Full-Stack">
      </div>
      <div class="form-group">
        <label class="form-label">Titre (English)</label>
        <input type="text" name="titre_en" class="form-control"
               value="<?= htmlspecialchars($formation['titre_en'] ?? '') ?>"
               placeholder="ex: Full-Stack Web Development">
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
      <div class="form-group">
        <label class="form-label">Catégorie</label>
        <input type="text" name="categorie" class="form-control"
               value="<?= htmlspecialchars($formation['categorie'] ?? '') ?>"
               placeholder="ex: Développement, IA, Marketing…">
      </div>
      <div class="form-group">
        <label class="form-label">Niveau</label>
        <select name="niveau" class="form-control">
          <?php foreach ($niveauLabels as $val => $lab): ?>
          <option value="<?= $val ?>"<?= ($formation['niveau'] ?? 'tous') === $val ? ' selected' : '' ?>><?= $lab ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Ordre d'affichage</label>
        <input type="number" name="ordre" class="form-control" min="0"
               value="<?= $formation['ordre'] ?? 0 ?>">
      </div>
    </div>
  </div>

  <!-- Descriptions -->
  <div class="admin-card" style="padding:24px;margin-bottom:20px">
    <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
      <i class="fas fa-align-left" style="color:#053067;margin-right:8px"></i>Description
    </h3>
    <div class="form-group">
      <label class="form-label">Description (Français)</label>
      <textarea name="description" class="form-control" rows="4"
                placeholder="Décrivez le contenu et les objectifs de cette formation…"><?= htmlspecialchars($formation['description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Description (English)</label>
      <textarea name="description_en" class="form-control" rows="4"
                placeholder="Describe the content and goals of this training…"><?= htmlspecialchars($formation['description_en'] ?? '') ?></textarea>
    </div>
  </div>

  <!-- Infos pratiques -->
  <div class="admin-card" style="padding:24px;margin-bottom:20px">
    <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
      <i class="fas fa-info-circle" style="color:#053067;margin-right:8px"></i>Informations pratiques
    </h3>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
      <div class="form-group">
        <label class="form-label">Durée</label>
        <input type="text" name="duree" class="form-control"
               value="<?= htmlspecialchars($formation['duree'] ?? '') ?>"
               placeholder="ex: 3 mois, 6 semaines…">
      </div>
      <div class="form-group">
        <label class="form-label">Prix (FCFA)</label>
        <input type="number" name="prix" class="form-control" min="0" step="1000"
               value="<?= $formation['prix'] ?? '' ?>"
               placeholder="ex: 150000">
      </div>
      <div class="form-group">
        <label class="form-label">Places disponibles</label>
        <input type="number" name="places" class="form-control" min="0"
               value="<?= $formation['places'] ?? '' ?>"
               placeholder="Laisser vide = illimité">
      </div>
    </div>
  </div>

  <!-- Image -->
  <div class="admin-card" style="padding:24px;margin-bottom:20px">
    <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
      <i class="fas fa-image" style="color:#053067;margin-right:8px"></i>Image de couverture
    </h3>
    <?php if (!empty($formation['image'])): ?>
    <div style="margin-bottom:12px">
      <img src="<?= htmlspecialchars($formation['image']) ?>" alt="Aperçu"
           style="max-height:140px;border-radius:8px;border:1px solid #e2e8f0">
      <p style="font-size:.75rem;color:#6c7a91;margin-top:4px">Image actuelle — uploadez-en une nouvelle pour remplacer</p>
    </div>
    <?php endif; ?>
    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
    <small style="color:#6c7a91">JPG, PNG ou WebP — max 5 Mo — recommandé : 800×500 px</small>
  </div>

  <!-- Statut -->
  <div class="admin-card" style="padding:20px;margin-bottom:24px">
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:.9rem;color:#1a2c4e">
      <input type="checkbox" name="est_publie" value="1"<?= ($formation['est_publie'] ?? 1) ? ' checked' : '' ?>>
      Publier cette formation (visible sur le site)
    </label>
  </div>

  <div style="display:flex;gap:10px">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i>
      <?= $formation ? 'Enregistrer les modifications' : 'Créer la formation' ?>
    </button>
    <a href="/admin/formations.php" class="btn btn-outline">Annuler</a>
  </div>
</form>

<?php endif; ?>

</main>
</div>
</body>
</html>
