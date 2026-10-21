<?php
$pageTitle  = 'IgoShop';
$activeMenu = 'shop';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';
require_auth('admin');
require_once __DIR__ . '/layout.php';

$pdo    = db();
$tab    = $_GET['tab']    ?? 'produits';
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$error  = '';

/* ── DELETE produit ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_produit'])) {
    csrf_check();
    $pdo->prepare("DELETE FROM shop_produits WHERE id=?")->execute([(int)$_POST['delete_produit']]);
    flash_set('success', 'Produit supprime.');
    redirect('/admin/shop.php?tab=produits');
}

/* ── TOGGLE publie ──────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_produit'])) {
    csrf_check();
    $pdo->prepare("UPDATE shop_produits SET est_publie=1-est_publie WHERE id=?")->execute([(int)$_POST['toggle_produit']]);
    redirect('/admin/shop.php?tab=produits');
}

/* ── STATUT DEVIS ───────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['statut_devis'])) {
    csrf_check();
    $allowed = ['nouveau','traite','archive'];
    $statut  = in_array($_POST['statut_devis'], $allowed) ? $_POST['statut_devis'] : 'nouveau';
    $pdo->prepare("UPDATE shop_devis SET statut=? WHERE id=?")->execute([$statut, (int)$_POST['devis_id']]);
    redirect('/admin/shop.php?tab=devis');
}

/* ── DELETE devis ───────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_devis'])) {
    csrf_check();
    $pdo->prepare("DELETE FROM shop_devis WHERE id=?")->execute([(int)$_POST['delete_devis']]);
    redirect('/admin/shop.php?tab=devis');
}

/* ── SAVE PRODUIT ───────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_produit'])) {
    csrf_check();

    $data = [
        'nom'            => sanitize($_POST['nom']            ?? ''),
        'nom_en'         => sanitize($_POST['nom_en']         ?? ''),
        'description'    => sanitize($_POST['description']    ?? ''),
        'description_en' => sanitize($_POST['description_en'] ?? ''),
        'categorie_id'   => (int)($_POST['categorie_id']      ?? 0) ?: null,
        'marque'         => sanitize($_POST['marque']         ?? ''),
        'reference'      => sanitize($_POST['reference']      ?? ''),
        'prix'           => is_numeric($_POST['prix']         ?? '') ? (int)$_POST['prix'] : null,
        'prix_promo'     => is_numeric($_POST['prix_promo']   ?? '') && (int)$_POST['prix_promo'] > 0 ? (int)$_POST['prix_promo'] : null,
        'stock'          => in_array($_POST['stock'] ?? '', ['disponible','commande','rupture']) ? $_POST['stock'] : 'disponible',
        'est_publie'     => isset($_POST['est_publie'])  ? 1 : 0,
        'est_vedette'    => isset($_POST['est_vedette']) ? 1 : 0,
        'est_nouveau'    => isset($_POST['est_nouveau']) ? 1 : 0,
        'ordre'          => (int)($_POST['ordre'] ?? 0),
    ];
    $data['slug'] = slugify($data['nom']);

    if (!$data['nom']) {
        $error = 'Le nom du produit est obligatoire.';
    } else {
        $imageUrl = sanitize($_POST['image_actuelle'] ?? '');
        if (!empty($_FILES['image']['name'])) {
            $uploaded = upload_image($_FILES['image'], 'shop');
            if ($uploaded) $imageUrl = $uploaded;
            else $error = 'Image invalide (JPG/PNG/WebP, max 5 Mo).';
        }
        $data['image'] = $imageUrl ?: null;

        if (!$error) {
            $editId = (int)($_POST['edit_id'] ?? 0);
            if ($editId) {
                // Verifier slug unique (sauf soi-meme)
                $slugCheck = $pdo->prepare("SELECT id FROM shop_produits WHERE slug=? AND id!=?");
                $slugCheck->execute([$data['slug'], $editId]);
                if ($slugCheck->fetch()) $data['slug'] .= '-' . $editId;

                $sql = "UPDATE shop_produits SET nom=:nom, nom_en=:nom_en, slug=:slug,
                        description=:description, description_en=:description_en,
                        categorie_id=:categorie_id, marque=:marque, reference=:reference,
                        prix=:prix, prix_promo=:prix_promo, stock=:stock, image=:image,
                        est_publie=:est_publie, est_vedette=:est_vedette, est_nouveau=:est_nouveau,
                        ordre=:ordre WHERE id=:id";
                $data['id'] = $editId;
                $pdo->prepare($sql)->execute($data);
                flash_set('success', 'Produit mis a jour.');
            } else {
                // Slug unique
                $slugCheck = $pdo->prepare("SELECT id FROM shop_produits WHERE slug=?");
                $slugCheck->execute([$data['slug']]);
                if ($slugCheck->fetch()) $data['slug'] .= '-' . time();

                $sql = "INSERT INTO shop_produits (nom,nom_en,slug,description,description_en,categorie_id,marque,reference,prix,prix_promo,stock,image,est_publie,est_vedette,est_nouveau,ordre)
                        VALUES (:nom,:nom_en,:slug,:description,:description_en,:categorie_id,:marque,:reference,:prix,:prix_promo,:stock,:image,:est_publie,:est_vedette,:est_nouveau,:ordre)";
                $pdo->prepare($sql)->execute($data);
                flash_set('success', 'Produit cree.');
            }
            redirect('/admin/shop.php?tab=produits');
        }
    }
}

/* ── Charger produit pour edition ───────────────────────── */
$produit = null;
if ($action === 'edit' && $id) {
    $produit = $pdo->prepare("SELECT * FROM shop_produits WHERE id=?");
    $produit->execute([$id]);
    $produit = $produit->fetch();
}

/* ── Donnees listes ─────────────────────────────────────── */
$categories  = $pdo->query("SELECT * FROM shop_categories ORDER BY ordre ASC")->fetchAll();
$produits    = $pdo->query("SELECT p.*, c.nom AS cat_nom FROM shop_produits p LEFT JOIN shop_categories c ON c.id=p.categorie_id ORDER BY p.ordre ASC, p.id DESC")->fetchAll();
$devisNew    = (int)$pdo->query("SELECT COUNT(*) FROM shop_devis WHERE statut='nouveau'")->fetchColumn();

/* ── Filtre devis ───────────────────────────────────────── */
$devisStatut = in_array($_GET['ds'] ?? '', ['nouveau','traite','archive']) ? $_GET['ds'] : '';
$devisSql    = "SELECT d.*, p.image AS prod_img FROM shop_devis d LEFT JOIN shop_produits p ON p.id=d.produit_id";
if ($devisStatut) $devisSql .= " WHERE d.statut='$devisStatut'";
$devisSql .= " ORDER BY d.created_at DESC";
$devis = $pdo->query($devisSql)->fetchAll();

/* ── KPIs ───────────────────────────────────────────────── */
$kpis = [
    'produits'  => count($produits),
    'publies'   => (int)$pdo->query("SELECT COUNT(*) FROM shop_produits WHERE est_publie=1")->fetchColumn(),
    'devis_new' => $devisNew,
    'devis_tot' => (int)$pdo->query("SELECT COUNT(*) FROM shop_devis")->fetchColumn(),
];
?>

<?= flash_html() ?>

<!-- KPIs -->
<div class="admin-kpi-grid" style="margin-bottom:24px">
  <div class="kpi-card" style="--kpi-color:#053067">
    <div class="kpi-icon"><i class="fas fa-box"></i></div>
    <div class="kpi-info"><span class="kpi-value"><?= $kpis['produits'] ?></span><span class="kpi-label">Produits</span></div>
  </div>
  <div class="kpi-card" style="--kpi-color:#10b981">
    <div class="kpi-icon"><i class="fas fa-eye"></i></div>
    <div class="kpi-info"><span class="kpi-value"><?= $kpis['publies'] ?></span><span class="kpi-label">Publiés</span></div>
  </div>
  <div class="kpi-card" style="--kpi-color:#f59e0b">
    <div class="kpi-icon"><i class="fas fa-file-invoice"></i></div>
    <div class="kpi-info"><span class="kpi-value"><?= $kpis['devis_new'] ?></span><span class="kpi-label">Devis nouveaux</span></div>
  </div>
  <div class="kpi-card" style="--kpi-color:#6366f1">
    <div class="kpi-icon"><i class="fas fa-chart-bar"></i></div>
    <div class="kpi-info"><span class="kpi-value"><?= $kpis['devis_tot'] ?></span><span class="kpi-label">Total devis</span></div>
  </div>
</div>

<!-- TABS -->
<div style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:24px">
  <?php foreach ([
    'produits' => ['fas fa-box','Produits'],
    'devis'    => ['fas fa-file-invoice','Demandes de devis'.($devisNew?' ('.$devisNew.')':'')],
    'categories'=> ['fas fa-tags','Catégories'],
  ] as $t => [$ic,$lb]): ?>
  <a href="?tab=<?= $t ?>"
     style="padding:10px 18px;font-size:.83rem;font-weight:600;
            color:<?= $tab===$t?'#053067':'#6c7a91' ?>;
            border-bottom:2px solid <?= $tab===$t?'#053067':'transparent' ?>;
            margin-bottom:-2px;text-decoration:none;white-space:nowrap">
    <i class="<?= $ic ?>"></i> <?= $lb ?>
  </a>
  <?php endforeach; ?>
</div>

<?php /* ══════════════ ONGLET PRODUITS ══════════════ */ if ($tab === 'produits'): ?>

<?php if ($action === 'list'): ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <span style="color:#6c7a91;font-size:.88rem"><?= count($produits) ?> produit(s)</span>
  <a href="?tab=produits&action=new" class="btn btn-primary" style="font-size:.88rem;padding:8px 18px">
    <i class="fas fa-plus"></i> Nouveau produit
  </a>
</div>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr><th>Image</th><th>Produit</th><th>Catégorie</th><th>Marque</th><th>Prix</th><th>Stock</th><th>Statut</th><th>Ordre</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($produits as $p):
      $stockColors = ['disponible'=>'#10b981','commande'=>'#f59e0b','rupture'=>'#e63946'];
    ?>
    <tr>
      <td style="width:56px">
        <?php if ($p['image']): ?>
        <img src="<?= htmlspecialchars($p['image']) ?>" alt=""
             style="width:48px;height:48px;object-fit:contain;border-radius:6px;background:#f8fafc;padding:4px;border:1px solid #e2e8f0">
        <?php else: ?>
        <div style="width:48px;height:48px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center">
          <i class="fas fa-image" style="color:#cbd5e1"></i>
        </div>
        <?php endif; ?>
      </td>
      <td>
        <strong style="font-size:.88rem;color:#1a2c4e"><?= htmlspecialchars($p['nom']) ?></strong>
        <?php if ($p['reference']): ?>
        <br><small style="color:#6c7a91">Ref: <?= htmlspecialchars($p['reference']) ?></small>
        <?php endif; ?>
        <?php if ($p['est_vedette']): ?>
        <span style="background:#fef3c7;color:#92400e;font-size:.65rem;padding:1px 6px;border-radius:8px;margin-left:4px"><i class="fas fa-star"></i> Vedette</span>
        <?php endif; ?>
        <?php if ($p['est_nouveau']): ?>
        <span style="background:#d1fae5;color:#065f46;font-size:.65rem;padding:1px 6px;border-radius:8px;margin-left:4px">Nouveau</span>
        <?php endif; ?>
      </td>
      <td style="font-size:.82rem"><?= htmlspecialchars($p['cat_nom'] ?? '—') ?></td>
      <td style="font-size:.82rem"><?= htmlspecialchars($p['marque'] ?? '—') ?></td>
      <td style="font-size:.85rem;font-weight:700;color:#053067;white-space:nowrap">
        <?= $p['prix'] ? number_format($p['prix'],0,',',' ').' FCFA' : '<em style="color:#f59e0b;font-weight:400">Sur devis</em>' ?>
        <?php if ($p['prix_promo']): ?>
        <br><small style="color:#e63946">Promo: <?= number_format($p['prix_promo'],0,',',' ') ?></small>
        <?php endif; ?>
      </td>
      <td>
        <span style="background:<?= $stockColors[$p['stock']] ?? '#6c7a91' ?>22;color:<?= $stockColors[$p['stock']] ?? '#6c7a91' ?>;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:8px">
          <?= ucfirst($p['stock']) ?>
        </span>
      </td>
      <td>
        <form method="post" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="toggle_produit" value="<?= $p['id'] ?>">
          <button type="submit" style="background:none;border:none;cursor:pointer;padding:0">
            <?php if ($p['est_publie']): ?>
            <span style="background:#d1fae5;color:#065f46;padding:2px 9px;border-radius:10px;font-size:.72rem;font-weight:700">Publié</span>
            <?php else: ?>
            <span style="background:#f3f4f6;color:#6b7280;padding:2px 9px;border-radius:10px;font-size:.72rem;font-weight:700">Masqué</span>
            <?php endif; ?>
          </button>
        </form>
      </td>
      <td style="text-align:center;color:#6c7a91;font-size:.82rem"><?= $p['ordre'] ?></td>
      <td style="white-space:nowrap">
        <a href="?tab=produits&action=edit&id=<?= $p['id'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem" title="Modifier">
          <i class="fas fa-edit"></i>
        </a>
        <a href="/shop.php?produit=<?= htmlspecialchars($p['slug']) ?>" target="_blank" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem" title="Voir sur le site">
          <i class="fas fa-external-link-alt"></i>
        </a>
        <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce produit ?')">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_produit" value="<?= $p['id'] ?>">
          <button type="submit" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem;border-color:#e63946;color:#e63946">
            <i class="fas fa-trash"></i>
          </button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php else: /* ── FORMULAIRE PRODUIT ── */ ?>

<div style="margin-bottom:14px">
  <a href="?tab=produits" class="btn btn-outline" style="font-size:.82rem;padding:6px 14px"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" style="max-width:820px">
  <?= csrf_field() ?>
  <input type="hidden" name="save_produit" value="1">
  <input type="hidden" name="edit_id" value="<?= $produit['id'] ?? 0 ?>">
  <input type="hidden" name="image_actuelle" value="<?= htmlspecialchars($produit['image'] ?? '') ?>">

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- Col gauche -->
    <div>
      <div class="admin-card" style="padding:20px;margin-bottom:16px">
        <h3 style="font-size:.9rem;color:#1a2c4e;margin:0 0 14px;border-bottom:1px solid #e2e8f0;padding-bottom:8px"><i class="fas fa-box" style="color:#053067;margin-right:6px"></i>Informations</h3>
        <div class="form-group">
          <label class="form-label">Nom du produit (FR) *</label>
          <input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($produit['nom'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Nom (English)</label>
          <input type="text" name="nom_en" class="form-control" value="<?= htmlspecialchars($produit['nom_en'] ?? '') ?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label class="form-label">Marque</label>
            <input type="text" name="marque" class="form-control" value="<?= htmlspecialchars($produit['marque'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Référence</label>
            <input type="text" name="reference" class="form-control" value="<?= htmlspecialchars($produit['reference'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Catégorie</label>
          <select name="categorie_id" class="form-control">
            <option value="">-- Aucune --</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>"<?= ($produit['categorie_id'] ?? 0) == $c['id'] ? ' selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Ordre d'affichage</label>
          <input type="number" name="ordre" class="form-control" min="0" value="<?= $produit['ordre'] ?? 0 ?>" style="max-width:100px">
        </div>
      </div>

      <div class="admin-card" style="padding:20px;margin-bottom:16px">
        <h3 style="font-size:.9rem;color:#1a2c4e;margin:0 0 14px;border-bottom:1px solid #e2e8f0;padding-bottom:8px"><i class="fas fa-tag" style="color:#053067;margin-right:6px"></i>Prix & Stock</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label class="form-label">Prix (FCFA)</label>
            <input type="number" name="prix" class="form-control" min="0" step="500"
                   value="<?= $produit['prix'] ?? '' ?>" placeholder="Vide = sur devis">
          </div>
          <div class="form-group">
            <label class="form-label">Prix promo (FCFA)</label>
            <input type="number" name="prix_promo" class="form-control" min="0" step="500"
                   value="<?= $produit['prix_promo'] ?? '' ?>" placeholder="0 = pas de promo">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Disponibilité</label>
          <select name="stock" class="form-control">
            <option value="disponible"<?= ($produit['stock'] ?? 'disponible') === 'disponible' ? ' selected' : '' ?>>En stock</option>
            <option value="commande"<?= ($produit['stock'] ?? '') === 'commande' ? ' selected' : '' ?>>Sur commande</option>
            <option value="rupture"<?= ($produit['stock'] ?? '') === 'rupture' ? ' selected' : '' ?>>Rupture de stock</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Col droite -->
    <div>
      <div class="admin-card" style="padding:20px;margin-bottom:16px">
        <h3 style="font-size:.9rem;color:#1a2c4e;margin:0 0 14px;border-bottom:1px solid #e2e8f0;padding-bottom:8px"><i class="fas fa-align-left" style="color:#053067;margin-right:6px"></i>Description</h3>
        <div class="form-group">
          <label class="form-label">Description (Français)</label>
          <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($produit['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Description (English)</label>
          <textarea name="description_en" class="form-control" rows="4"><?= htmlspecialchars($produit['description_en'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="admin-card" style="padding:20px;margin-bottom:16px">
        <h3 style="font-size:.9rem;color:#1a2c4e;margin:0 0 14px;border-bottom:1px solid #e2e8f0;padding-bottom:8px"><i class="fas fa-image" style="color:#053067;margin-right:6px"></i>Image</h3>
        <?php if (!empty($produit['image'])): ?>
        <div style="margin-bottom:10px">
          <img src="<?= htmlspecialchars($produit['image']) ?>" alt="" style="max-height:120px;border-radius:8px;border:1px solid #e2e8f0">
        </div>
        <?php endif; ?>
        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
        <small style="color:#6c7a91">JPG/PNG/WebP max 5 Mo — 800x600px recommande</small>
      </div>

      <div class="admin-card" style="padding:16px">
        <h3 style="font-size:.9rem;color:#1a2c4e;margin:0 0 12px">Options</h3>
        <?php foreach ([
          ['est_publie', 'Publié (visible sur le site)'],
          ['est_vedette','Mettre en vedette (page d\'accueil shop)'],
          ['est_nouveau','Marquer comme nouveau'],
        ] as [$name,$label]): ?>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.85rem;margin-bottom:8px">
          <input type="checkbox" name="<?= $name ?>" value="1"<?= ($produit[$name] ?? ($name==='est_publie'?1:0)) ? ' checked' : '' ?>>
          <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <div style="margin-top:20px;display:flex;gap:10px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $produit ? 'Enregistrer' : 'Creer le produit' ?></button>
    <a href="?tab=produits" class="btn btn-outline">Annuler</a>
  </div>
</form>

<?php endif; ?>

<?php /* ══════════════ ONGLET DEVIS ══════════════ */ elseif ($tab === 'devis'): ?>

<!-- Export + filtre -->
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px">
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php foreach ([''=>'Tous','nouveau'=>'Nouveaux','traite'=>'Traités','archive'=>'Archivés'] as $v=>$l): ?>
    <a href="?tab=devis&ds=<?= $v ?>"
       style="padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:600;text-decoration:none;
              background:<?= $devisStatut===$v?'#053067':'#f1f5f9' ?>;
              color:<?= $devisStatut===$v?'#fff':'#6c7a91' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <a href="?tab=devis&export=csv" class="btn btn-outline" style="font-size:.82rem;padding:6px 14px">
    <i class="fas fa-file-csv"></i> Exporter CSV
  </a>
</div>

<?php
// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="devis_igoshop_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output','w'); fputs($out,"\xEF\xBB\xBF");
    fputcsv($out,['ID','Produit','Nom','Email','Telephone','Organisation','Quantite','Message','Statut','Date']);
    foreach ($devis as $d) fputcsv($out,[$d['id'],$d['produit_nom'],$d['nom'],$d['email'],$d['telephone'],$d['organisation'],$d['quantite'],$d['message'],$d['statut'],$d['created_at']]);
    fclose($out); exit;
}
?>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr><th>Produit</th><th>Contact</th><th>Qté</th><th>Message</th><th>Statut</th><th>Date</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($devis)): ?>
    <tr><td colspan="7" style="text-align:center;padding:30px;color:#6c7a91">Aucune demande de devis.</td></tr>
    <?php endif; ?>
    <?php foreach ($devis as $d):
      $sc = ['nouveau'=>'#f59e0b','traite'=>'#10b981','archive'=>'#6c7a91'][$d['statut']] ?? '#6c7a91';
    ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <?php if ($d['prod_img']): ?>
          <img src="<?= htmlspecialchars($d['prod_img']) ?>" alt="" style="width:36px;height:36px;object-fit:contain;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0;padding:2px">
          <?php endif; ?>
          <div>
            <strong style="font-size:.82rem"><?= htmlspecialchars($d['produit_nom']) ?></strong>
          </div>
        </div>
      </td>
      <td>
        <strong style="font-size:.85rem"><?= htmlspecialchars($d['nom']) ?></strong><br>
        <a href="mailto:<?= htmlspecialchars($d['email']) ?>" style="font-size:.78rem;color:#053067"><?= htmlspecialchars($d['email']) ?></a><br>
        <small><?= htmlspecialchars($d['telephone']) ?></small>
        <?php if ($d['organisation']): ?>
        <br><small style="color:#6c7a91"><?= htmlspecialchars($d['organisation']) ?></small>
        <?php endif; ?>
      </td>
      <td style="text-align:center;font-weight:700"><?= $d['quantite'] ?></td>
      <td style="max-width:200px;font-size:.8rem;color:#374151">
        <?= $d['message'] ? htmlspecialchars(mb_substr($d['message'],0,80)).(mb_strlen($d['message'])>80?'…':'') : '<em style="color:#6c7a91">—</em>' ?>
      </td>
      <td>
        <form method="post" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="devis_id" value="<?= $d['id'] ?>">
          <select name="statut_devis" onchange="this.form.submit()"
                  style="padding:4px 8px;border:1.5px solid <?= $sc ?>;border-radius:8px;font-size:.75rem;font-weight:700;color:<?= $sc ?>;background:#fff;cursor:pointer;outline:none">
            <option value="nouveau"<?= $d['statut']==='nouveau'?' selected':'' ?>>Nouveau</option>
            <option value="traite"<?= $d['statut']==='traite'?' selected':'' ?>>Traité</option>
            <option value="archive"<?= $d['statut']==='archive'?' selected':'' ?>>Archivé</option>
          </select>
        </form>
      </td>
      <td style="font-size:.78rem;white-space:nowrap"><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
      <td style="white-space:nowrap">
        <a href="mailto:<?= htmlspecialchars($d['email']) ?>" class="btn btn-primary" style="padding:4px 10px;font-size:.75rem" title="Répondre">
          <i class="fas fa-envelope"></i>
        </a>
        <?php if ($d['telephone']): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$d['telephone']) ?>" target="_blank" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem;border-color:#25d366;color:#25d366">
          <i class="fab fa-whatsapp"></i>
        </a>
        <?php endif; ?>
        <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cette demande ?')">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_devis" value="<?= $d['id'] ?>">
          <button type="submit" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem;border-color:#e63946;color:#e63946">
            <i class="fas fa-trash"></i>
          </button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php /* ══════════════ ONGLET CATEGORIES ══════════════ */ elseif ($tab === 'categories'): ?>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>Icône</th><th>Catégorie (FR)</th><th>Catégorie (EN)</th><th>Slug</th><th>Produits</th><th>Ordre</th></tr></thead>
    <tbody>
    <?php foreach ($categories as $c):
      $count = $pdo->prepare("SELECT COUNT(*) FROM shop_produits WHERE categorie_id=?");
      $count->execute([$c['id']]); $count = $count->fetchColumn();
    ?>
    <tr>
      <td><i class="<?= htmlspecialchars($c['icone']) ?>" style="font-size:1.2rem;color:#053067"></i></td>
      <td><strong><?= htmlspecialchars($c['nom']) ?></strong></td>
      <td><?= htmlspecialchars($c['nom_en'] ?? '') ?></td>
      <td><code style="font-size:.78rem"><?= htmlspecialchars($c['slug']) ?></code></td>
      <td style="text-align:center"><?= $count ?></td>
      <td style="text-align:center"><?= $c['ordre'] ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p style="color:#6c7a91;font-size:.82rem;margin-top:12px"><i class="fas fa-info-circle"></i> Les catégories peuvent être modifiées directement en base via phpMyAdmin ou en contactant votre développeur.</p>

<?php endif; ?>

</main>
</div>
</body>
</html>
