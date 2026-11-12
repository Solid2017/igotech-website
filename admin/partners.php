<?php
$pageTitle  = 'Partenaires';
$activeMenu = 'partners';
require_once __DIR__ . '/layout.php';

$uploadDir = dirname(__DIR__) . '/assets/uploads/partners/';
$uploadUrl = '/assets/uploads/partners/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/* ── Helpers ────────────────────────────────────────────────── */
function partners_table_exists(): bool {
    try {
        db()->query("SELECT 1 FROM partenaires LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function partners_create_table(): void {
    db()->exec("CREATE TABLE IF NOT EXISTS partenaires (
        id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nom       VARCHAR(120)  NOT NULL,
        logo      VARCHAR(255)  NOT NULL DEFAULT '',
        lien      VARCHAR(255)  NOT NULL DEFAULT '',
        ordre     TINYINT       NOT NULL DEFAULT 0,
        actif     TINYINT(1)    NOT NULL DEFAULT 1,
        created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}

if (!partners_table_exists()) {
    partners_create_table();
}

/* ── Upload image ────────────────────────────────────────────── */
function upload_partner_logo(array $file, string $uploadDir): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed)) return false;
    if ($file['size'] > 2 * 1024 * 1024) return false; // 2 Mo max
    $ext  = match($mime) {
        'image/jpeg'   => 'jpg',
        'image/png'    => 'png',
        'image/gif'    => 'gif',
        'image/webp'   => 'webp',
        'image/svg+xml'=> 'svg',
        default        => 'png',
    };
    $name = 'partner_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $name)) return false;
    return $name;
}

/* ── POST actions ────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_auth('editor');
    if (!csrf_check()) { flash_set('error','Token invalide.'); redirect('/admin/partners.php'); }

    $action = $_POST['action'] ?? '';

    /* Ajouter */
    if ($action === 'add') {
        $nom   = sanitize($_POST['nom'] ?? '');
        $lien  = sanitize_url($_POST['lien'] ?? '');
        $ordre = (int)($_POST['ordre'] ?? 0);
        $actif = isset($_POST['actif']) ? 1 : 0;
        $logo  = '';

        if (!empty($_FILES['logo']['name'])) {
            $uploaded = upload_partner_logo($_FILES['logo'], $uploadDir);
            if ($uploaded === false) {
                flash_set('error', 'Image invalide (formats: JPG, PNG, GIF, WEBP, SVG — max 2 Mo).');
                redirect('/admin/partners.php');
            }
            $logo = $uploaded;
        }

        if ($nom === '') { flash_set('error','Le nom est requis.'); redirect('/admin/partners.php'); }

        db()->prepare("INSERT INTO partenaires (nom, logo, lien, ordre, actif) VALUES (?,?,?,?,?)")
           ->execute([$nom, $logo, $lien, $ordre, $actif]);
        flash_set('success', "Partenaire « $nom » ajouté.");
        redirect('/admin/partners.php');
    }

    /* Modifier */
    if ($action === 'edit') {
        $id    = (int)($_POST['id'] ?? 0);
        $nom   = sanitize($_POST['nom'] ?? '');
        $lien  = sanitize_url($_POST['lien'] ?? '');
        $ordre = (int)($_POST['ordre'] ?? 0);
        $actif = isset($_POST['actif']) ? 1 : 0;

        $row = db()->prepare("SELECT * FROM partenaires WHERE id=?");
        $row->execute([$id]);
        $partner = $row->fetch();
        if (!$partner) { flash_set('error','Partenaire introuvable.'); redirect('/admin/partners.php'); }

        $logo = $partner['logo'];

        if (!empty($_FILES['logo']['name'])) {
            $uploaded = upload_partner_logo($_FILES['logo'], $uploadDir);
            if ($uploaded === false) {
                flash_set('error', 'Image invalide (formats: JPG, PNG, GIF, WEBP, SVG — max 2 Mo).');
                redirect('/admin/partners.php');
            }
            // Supprimer l'ancien logo
            if ($logo && file_exists($uploadDir . $logo)) {
                unlink($uploadDir . $logo);
            }
            $logo = $uploaded;
        }

        if ($nom === '') { flash_set('error','Le nom est requis.'); redirect('/admin/partners.php'); }

        db()->prepare("UPDATE partenaires SET nom=?, logo=?, lien=?, ordre=?, actif=? WHERE id=?")
           ->execute([$nom, $logo, $lien, $ordre, $actif, $id]);
        flash_set('success', "Partenaire « $nom » mis à jour.");
        redirect('/admin/partners.php');
    }

    /* Supprimer */
    if ($action === 'delete') {
        $id  = (int)($_POST['id'] ?? 0);
        $row = db()->prepare("SELECT logo FROM partenaires WHERE id=?");
        $row->execute([$id]);
        $partner = $row->fetch();
        if ($partner) {
            if ($partner['logo'] && file_exists($uploadDir . $partner['logo'])) {
                unlink($uploadDir . $partner['logo']);
            }
            db()->prepare("DELETE FROM partenaires WHERE id=?")->execute([$id]);
            flash_set('success', 'Partenaire supprimé.');
        }
        redirect('/admin/partners.php');
    }

    /* Toggle actif */
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE partenaires SET actif = 1-actif WHERE id=?")->execute([$id]);
        redirect('/admin/partners.php');
    }
}

/* ── GET : charger la liste ──────────────────────────────────── */
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare("SELECT * FROM partenaires WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$partners = db()->query("SELECT * FROM partenaires ORDER BY ordre ASC, id ASC")->fetchAll();
?>

<div class="admin-page-header">
  <div>
    <h1 class="admin-page-title"><i class="fas fa-handshake"></i> Partenaires</h1>
    <p class="admin-page-subtitle">Gérez les logos affichés dans la section « Ils nous font confiance »</p>
  </div>
</div>

<div class="admin-grid-2col" style="gap:2rem;display:grid;grid-template-columns:1fr 380px;align-items:start">

  <!-- ══ LISTE ══ -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title"><i class="fas fa-list"></i> Liste (<?= count($partners) ?>)</h2>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($partners)): ?>
        <div style="padding:2rem;text-align:center;color:var(--text-muted)">
          <i class="fas fa-handshake" style="font-size:2.5rem;margin-bottom:.75rem;display:block;opacity:.3"></i>
          Aucun partenaire — ajoutez le premier via le formulaire.
        </div>
      <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:60px">Logo</th>
            <th>Nom</th>
            <th style="width:60px;text-align:center">Ordre</th>
            <th style="width:80px;text-align:center">Statut</th>
            <th style="width:120px;text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($partners as $p): ?>
          <tr class="<?= $edit && $edit['id'] === $p['id'] ? 'row-highlight' : '' ?>">
            <td>
              <?php if ($p['logo']): ?>
                <img src="<?= $uploadUrl . htmlspecialchars($p['logo']) ?>"
                     alt="<?= htmlspecialchars($p['nom']) ?>"
                     style="height:40px;width:70px;object-fit:contain;background:#f5f5f5;border-radius:6px;padding:4px">
              <?php else: ?>
                <span style="display:flex;align-items:center;justify-content:center;height:40px;width:70px;background:#f0f0f0;border-radius:6px;color:#bbb;font-size:.75rem">
                  <i class="fas fa-image"></i>
                </span>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= htmlspecialchars($p['nom']) ?></strong>
              <?php if ($p['lien']): ?>
                <br><a href="<?= htmlspecialchars($p['lien']) ?>" target="_blank" rel="noopener" style="font-size:.78rem;color:var(--primary)">
                  <i class="fas fa-external-link-alt fa-xs"></i> <?= htmlspecialchars($p['lien']) ?>
                </a>
              <?php endif; ?>
            </td>
            <td style="text-align:center"><?= (int)$p['ordre'] ?></td>
            <td style="text-align:center">
              <form method="POST" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="badge <?= $p['actif'] ? 'badge-success' : 'badge-secondary' ?>"
                        style="border:none;cursor:pointer;padding:.25rem .6rem;font-size:.75rem"
                        title="Cliquer pour changer">
                  <?= $p['actif'] ? 'Actif' : 'Masqué' ?>
                </button>
              </form>
            </td>
            <td style="text-align:right">
              <a href="/admin/partners.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline" title="Modifier">
                <i class="fas fa-pen"></i>
              </a>
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Supprimer « <?= addslashes(htmlspecialchars($p['nom'])) ?> » ?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ FORMULAIRE ══ -->
  <div class="card" style="position:sticky;top:80px">
    <div class="card-header">
      <h2 class="card-title">
        <?php if ($edit): ?>
          <i class="fas fa-pen"></i> Modifier le partenaire
        <?php else: ?>
          <i class="fas fa-plus"></i> Ajouter un partenaire
        <?php endif; ?>
      </h2>
      <?php if ($edit): ?>
        <a href="/admin/partners.php" class="btn btn-sm btn-outline"><i class="fas fa-times"></i> Annuler</a>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?>
          <input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <?php endif; ?>

        <!-- Logo -->
        <div class="form-group">
          <label class="form-label">Logo du partenaire</label>

          <?php if ($edit && $edit['logo']): ?>
          <div style="margin-bottom:.75rem;padding:.75rem;background:#f8f9fa;border-radius:8px;text-align:center">
            <img src="<?= $uploadUrl . htmlspecialchars($edit['logo']) ?>"
                 alt="Logo actuel"
                 style="max-height:70px;max-width:180px;object-fit:contain">
            <p style="font-size:.78rem;color:var(--text-muted);margin-top:.4rem">Logo actuel — un nouveau fichier le remplacera</p>
          </div>
          <?php endif; ?>

          <div class="upload-zone" id="uploadZone"
               style="border:2px dashed #d0d5dd;border-radius:10px;padding:1.5rem;text-align:center;cursor:pointer;transition:all .2s"
               onclick="document.getElementById('logoFile').click()"
               ondragover="this.style.borderColor='#1414D4';event.preventDefault()"
               ondragleave="this.style.borderColor='#d0d5dd'"
               ondrop="handleDrop(event)">
            <i class="fas fa-cloud-upload-alt" style="font-size:1.8rem;color:#9aa0ab;display:block;margin-bottom:.5rem"></i>
            <span id="uploadLabel" style="font-size:.88rem;color:#9aa0ab">Cliquez ou glissez une image<br><small>JPG · PNG · WEBP · SVG — max 2 Mo</small></span>
          </div>
          <input type="file" id="logoFile" name="logo" accept="image/*"
                 style="display:none" onchange="previewLogo(this)">
          <div id="logoPreview" style="display:none;margin-top:.75rem;text-align:center">
            <img id="previewImg" src="" alt="" style="max-height:80px;max-width:200px;object-fit:contain;border-radius:8px;border:1px solid #e0e0e0;padding:6px">
            <p id="previewName" style="font-size:.78rem;color:var(--text-muted);margin-top:.3rem"></p>
          </div>
        </div>

        <!-- Nom -->
        <div class="form-group">
          <label class="form-label" for="partnerNom">Nom du partenaire <span style="color:#e53935">*</span></label>
          <input type="text" id="partnerNom" name="nom" class="form-control"
                 value="<?= htmlspecialchars($edit['nom'] ?? '') ?>"
                 placeholder="ex: Archidiocèse de N'Djamena" required maxlength="120">
        </div>

        <!-- Lien -->
        <div class="form-group">
          <label class="form-label" for="partnerLien">Site web <span style="color:var(--text-muted)">(optionnel)</span></label>
          <input type="url" id="partnerLien" name="lien" class="form-control"
                 value="<?= htmlspecialchars($edit['lien'] ?? '') ?>"
                 placeholder="https://exemple.com">
        </div>

        <!-- Ordre + Actif -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div class="form-group">
            <label class="form-label" for="partnerOrdre">Ordre d'affichage</label>
            <input type="number" id="partnerOrdre" name="ordre" class="form-control"
                   value="<?= (int)($edit['ordre'] ?? 0) ?>" min="0" max="99">
            <small style="color:var(--text-muted)">0 = premier</small>
          </div>
          <div class="form-group" style="padding-top:1.8rem">
            <label class="form-check" style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
              <input type="checkbox" name="actif" value="1"
                     <?= (!isset($edit) || $edit['actif']) ? 'checked' : '' ?>
                     style="width:16px;height:16px">
              <span>Visible sur le site</span>
            </label>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%">
          <i class="fas fa-save"></i>
          <?= $edit ? ' Enregistrer les modifications' : ' Ajouter le partenaire' ?>
        </button>
      </form>
    </div>
  </div>

</div>

<script>
function previewLogo(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewName').textContent = file.name + ' (' + (file.size/1024).toFixed(0) + ' Ko)';
        document.getElementById('logoPreview').style.display = 'block';
        document.getElementById('uploadLabel').textContent = 'Image sélectionnée';
        document.getElementById('uploadZone').style.borderColor = '#1414D4';
    };
    reader.readAsDataURL(file);
}

function handleDrop(event) {
    event.preventDefault();
    document.getElementById('uploadZone').style.borderColor = '#d0d5dd';
    var file = event.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    var input = document.getElementById('logoFile');
    var dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    previewLogo(input);
}
</script>

<style>
.row-highlight { background: #f0f4ff; }
.upload-zone:hover { border-color: #1414D4 !important; background: #f8f9ff; }
</style>

</main>
</div>
<script src="/assets/js/main.js"></script>
</body></html>
