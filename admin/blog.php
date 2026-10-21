<?php
$pageTitle  = 'Blog / Actualités';
$activeMenu = 'blog';
require_once __DIR__ . '/layout.php';

$action = sanitize($_GET['action'] ?? '');
$id     = (int)($_GET['id'] ?? 0);

// -- Suppression ----------------------------------------------
if ($action === 'delete' && $id && is_admin()) {
    db()->prepare("DELETE FROM blog_posts WHERE id=?")->execute([$id]);
    flash_set('success', 'Article supprimé.');
    redirect('/admin/blog.php');
}

// -- Changement de statut rapide ------------------------------
if (in_array($action, ['publish','unpublish','archive']) && $id) {
    $newStatus = ['publish'=>'publie','unpublish'=>'brouillon','archive'=>'archive'][$action];
    $published = ($action === 'publish') ? "published_at=NOW()," : "";
    db()->prepare("UPDATE blog_posts SET statut=?, {$published} updated_at=NOW() WHERE id=?")->execute([$newStatus, $id]);
    flash_set('success', 'Article mis à jour.');
    redirect('/admin/blog.php');
}

// -- Sauvegarde article ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create','edit'])) {
    require_auth('editor');
    if (!csrf_check()) { flash_set('error','Token invalide.'); redirect('/admin/blog.php'); }

    $titre   = sanitize($_POST['titre']    ?? '');
    $slug    = slugify(sanitize($_POST['slug'] ?? '') ?: $titre);
    $extrait = sanitize($_POST['extrait']  ?? '');
    $contenu = $_POST['contenu']  ?? ''; // HTML -- validé par l'éditeur
    $cat     = sanitize($_POST['categorie'] ?? '');
    $tags    = sanitize($_POST['tags']     ?? '');
    $statut  = sanitize($_POST['statut']   ?? 'brouillon');
    $metaT   = sanitize($_POST['meta_title'] ?? '');
    $metaD   = sanitize($_POST['meta_desc']  ?? '');

    // Image
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $image = upload_image($_FILES['image'], 'blog');
    }

    if (!$titre) {
        flash_set('error', 'Le titre est requis.');
    } else {
        if ($action === 'create') {
            $published = ($statut === 'publie') ? 'NOW()' : 'NULL';
            $stmt = db()->prepare("INSERT INTO blog_posts (titre,slug,extrait,contenu,image,categorie,tags,statut,meta_title,meta_desc,auteur_id,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,{$published})");
            $stmt->execute([$titre,$slug,$extrait,$contenu,$image,$cat,$tags,$statut,$metaT,$metaD,$adminUser['id']]);
            flash_set('success', 'Article créé avec succès.');
        } else {
            $imgSql = $image ? ", image=?" : "";
            $params = [$titre,$slug,$extrait,$contenu,$cat,$tags,$statut,$metaT,$metaD];
            if ($image) $params[] = $image;
            $params[] = $id;
            db()->prepare("UPDATE blog_posts SET titre=?,slug=?,extrait=?,contenu=?,categorie=?,tags=?,statut=?,meta_title=?,meta_desc=?{$imgSql},updated_at=NOW() WHERE id=?")->execute($params);
            if ($statut === 'publie') db()->prepare("UPDATE blog_posts SET published_at=COALESCE(published_at,NOW()) WHERE id=?")->execute([$id]);
            flash_set('success', 'Article mis à jour.');
        }
        redirect('/admin/blog.php');
    }
}

// -- Formulaire édit/création ----------------------------------
if (in_array($action, ['new','edit'])) {
    $post = ($action === 'edit' && $id) ? db()->prepare("SELECT * FROM blog_posts WHERE id=?") : null;
    if ($post) { $post->execute([$id]); $post = $post->fetch(); }
    $formAction = ($action === 'new') ? 'create' : 'edit';
    ?>
<div class="page-header">
  <h1 class="page-title"><?= $action==='new' ? 'Nouvel article' : 'Modifier l\'article' ?></h1>
  <a href="/admin/blog.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<form method="POST" action="?action=<?= $formAction ?>&id=<?= $id ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

    <div>
      <!-- Titre -->
      <div class="admin-card" style="margin-bottom:16px">
        <div class="admin-card-body">
          <div class="form-group">
            <label for="titleInput">Titre <span style="color:var(--accent)">*</span></label>
            <input type="text" id="titleInput" name="titre" class="form-control"
                   value="<?= htmlspecialchars($post['titre'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label for="slugInput">Slug (URL)</label>
            <input type="text" id="slugInput" name="slug" class="form-control"
                   value="<?= htmlspecialchars($post['slug'] ?? '') ?>">
            <small style="color:var(--text-light)">Généré automatiquement depuis le titre</small>
          </div>
          <div class="form-group">
            <label for="extrait">Extrait (résumé)</label>
            <textarea id="extrait" name="extrait" class="form-control" rows="3"
                      data-maxlength="300"><?= htmlspecialchars($post['extrait'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Contenu -->
      <div class="admin-card">
        <div class="admin-card-header"><h3>Contenu</h3></div>
        <div class="admin-card-body">
          <textarea id="contenu" name="contenu" class="form-control" rows="18"
                    style="font-family:monospace;font-size:.86rem"><?= htmlspecialchars($post['contenu'] ?? '') ?></textarea>
          <small style="color:var(--text-light);display:block;margin-top:6px">
            HTML autorisé. Utilisez &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;img&gt;, etc.
          </small>
        </div>
      </div>
    </div>

    <div>
      <!-- Statut & Publication -->
      <div class="admin-card" style="margin-bottom:16px">
        <div class="admin-card-header"><h3>Publication</h3></div>
        <div class="admin-card-body">
          <div class="form-group">
            <label>Statut</label>
            <select name="statut" class="form-control">
              <option value="brouillon" <?= ($post['statut']??'brouillon')==='brouillon' ? 'selected':'' ?>>Brouillon</option>
              <option value="publie"    <?= ($post['statut']??'')==='publie'    ? 'selected':'' ?>>Publié</option>
              <option value="archive"   <?= ($post['statut']??'')==='archive'   ? 'selected':'' ?>>Archivé</option>
            </select>
          </div>
          <div class="form-group">
            <label>Catégorie</label>
            <input type="text" name="categorie" class="form-control"
                   value="<?= htmlspecialchars($post['categorie'] ?? '') ?>"
                   placeholder="ex: Actualités, Conseils, Formations…">
          </div>
          <div class="form-group">
            <label>Tags (séparés par virgule)</label>
            <input type="text" name="tags" class="form-control"
                   value="<?= htmlspecialchars($post['tags'] ?? '') ?>"
                   placeholder="tag1, tag2, tag3">
          </div>
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-save"></i> Enregistrer
          </button>
        </div>
      </div>

      <!-- Image -->
      <div class="admin-card" style="margin-bottom:16px">
        <div class="admin-card-header"><h3>Image à la une</h3></div>
        <div class="admin-card-body">
          <?php if (!empty($post['image'])): ?>
          <img src="<?= htmlspecialchars($post['image']) ?>" style="width:100%;border-radius:var(--radius);margin-bottom:10px">
          <?php endif; ?>
          <input type="file" name="image" accept="image/*" class="form-control" style="padding:8px">
        </div>
      </div>

      <!-- SEO -->
      <div class="admin-card">
        <div class="admin-card-header"><h3>SEO</h3></div>
        <div class="admin-card-body">
          <div class="form-group">
            <label>Meta Title</label>
            <input type="text" name="meta_title" class="form-control" data-maxlength="60"
                   value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Meta Description</label>
            <textarea name="meta_desc" class="form-control" rows="3" data-maxlength="160"><?= htmlspecialchars($post['meta_desc'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

  </div>
</form>

  </main>
</div>
<script src="/assets/js/main.js"></script>
</body></html>
<?php
    exit;
}

// -- Liste des articles ----------------------------------------
$fStatut = sanitize($_GET['statut'] ?? '');
$search  = sanitize($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));

$where = "WHERE 1=1"; $params = [];
if ($fStatut) { $where .= " AND statut=?"; $params[] = $fStatut; }
if ($search)  { $where .= " AND titre LIKE ?"; $params[] = "%$search%"; }

$total = (int)db()->prepare("SELECT COUNT(*) FROM blog_posts $where")->execute($params) && db()->prepare("SELECT COUNT(*) FROM blog_posts $where")->execute($params);
$stmt  = db()->prepare("SELECT COUNT(*) FROM blog_posts $where"); $stmt->execute($params); $total = (int)$stmt->fetchColumn();
$pag   = paginate($total, $page);
$stmt  = db()->prepare("SELECT b.*,a.nom AS auteur_nom FROM blog_posts b LEFT JOIN admin_users a ON b.auteur_id=a.id $where ORDER BY b.created_at DESC LIMIT ".ITEMS_PER_PAGE." OFFSET {$pag['offset']}");
$stmt->execute($params);
$posts = $stmt->fetchAll();
?>

<div class="page-header">
  <h1 class="page-title">Blog / Actualités</h1>
  <a href="/admin/blog.php?action=new" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nouvel article</a>
</div>

<!-- Filtres -->
<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-body">
    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div>
        <label style="font-size:.82rem;font-weight:600;display:block;margin-bottom:4px">Statut</label>
        <select name="statut" class="form-control" style="padding:8px 12px" onchange="this.form.submit()">
          <option value="">Tous</option>
          <option value="publie"    <?= $fStatut==='publie'    ? 'selected':'' ?>>Publiés</option>
          <option value="brouillon" <?= $fStatut==='brouillon' ? 'selected':'' ?>>Brouillons</option>
          <option value="archive"   <?= $fStatut==='archive'   ? 'selected':'' ?>>Archivés</option>
        </select>
      </div>
      <div>
        <label style="font-size:.82rem;font-weight:600;display:block;margin-bottom:4px">Recherche</label>
        <div style="display:flex;gap:6px">
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Titre…" class="form-control" style="padding:8px 12px">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
          <?php if ($fStatut || $search): ?><a href="/admin/blog.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-body p-0">
    <?php if ($posts): ?>
    <div style="overflow-x:auto">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Titre</th><th>Catégorie</th><th>Auteur</th><th>Statut</th><th>Vues</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        <?php
        $sB = ['publie'=>'success','brouillon'=>'warning','archive'=>'secondary'];
        foreach ($posts as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td>
            <strong><?= htmlspecialchars(mb_substr($p['titre'],0,50)) ?></strong><br>
            <small style="color:var(--text-light)">/blog-detail.php?slug=<?= htmlspecialchars($p['slug']) ?></small>
          </td>
          <td><?= htmlspecialchars($p['categorie'] ?? '--') ?></td>
          <td><?= htmlspecialchars($p['auteur_nom'] ?? '--') ?></td>
          <td><span class="badge badge-<?= $sB[$p['statut']]??'light' ?>"><?= ucfirst($p['statut']) ?></span></td>
          <td><?= number_format($p['vues']) ?></td>
          <td style="white-space:nowrap"><?= format_date($p['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:4px;flex-wrap:nowrap">
              <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline" style="padding:5px 10px" title="Modifier"><i class="fas fa-edit"></i></a>
              <?php if ($p['statut']==='brouillon'): ?>
              <a href="?action=publish&id=<?= $p['id'] ?>" class="btn btn-sm" style="padding:5px 10px;background:#10b981;color:#fff" title="Publier"><i class="fas fa-check"></i></a>
              <?php else: ?>
              <a href="?action=unpublish&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline" style="padding:5px 10px" title="Dépublier"><i class="fas fa-eye-slash"></i></a>
              <?php endif; ?>
              <a href="/blog-detail.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="btn btn-sm btn-outline" style="padding:5px 10px" title="Voir"><i class="fas fa-external-link-alt"></i></a>
              <?php if (is_admin()): ?>
              <a href="?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm" style="padding:5px 10px;background:var(--accent);color:#fff"
                 data-confirm="Supprimer cet article ?" title="Supprimer"><i class="fas fa-trash"></i></a>
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
    <p class="empty-state">Aucun article. <a href="?action=new" class="text-primary">Créer le premier article</a></p>
    <?php endif; ?>
  </div>
</div>

  </main>
</div>
<script src="/assets/js/main.js"></script>
</body></html>
