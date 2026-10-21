<?php
$pageTitle  = 'Gestion des utilisateurs';
$activeMenu = 'users';
require_once __DIR__ . '/layout.php';
require_auth('admin'); // Minimum admin requis

$action = sanitize($_GET['action'] ?? '');
$id     = (int)($_GET['id'] ?? 0);

// -- Suppression ----------------------------------------------
if ($action === 'delete' && $id && is_super_admin() && $id !== $adminUser['id']) {
    db()->prepare("DELETE FROM admin_users WHERE id=?")->execute([$id]);
    flash_set('success', 'Utilisateur supprimé.');
    redirect('/admin/users.php');
}

// -- Toggle actif/inactif -------------------------------------
if ($action === 'toggle' && $id && $id !== $adminUser['id']) {
    db()->prepare("UPDATE admin_users SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
    flash_set('success', 'Statut modifié.');
    redirect('/admin/users.php');
}

// -- Sauvegarde (création / édition) --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash_set('error','Token invalide.'); redirect('/admin/users.php'); }

    $nom      = sanitize($_POST['nom']   ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $role     = sanitize($_POST['role']  ?? 'editor');
    $password = $_POST['password'] ?? '';
    $password2= $_POST['password2'] ?? '';
    $editId   = (int)($_POST['edit_id'] ?? 0);

    if (!$nom || !$email || !valid_email($email)) {
        flash_set('error', 'Nom et email valides requis.');
    } elseif ($editId === 0 && strlen($password) < 8) {
        flash_set('error', 'Mot de passe : minimum 8 caractères.');
    } elseif ($password && $password !== $password2) {
        flash_set('error', 'Les mots de passe ne correspondent pas.');
    } else {
        if ($editId === 0) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
            db()->prepare("INSERT INTO admin_users (nom,email,password,role) VALUES (?,?,?,?)")
               ->execute([$nom,$email,$hash,$role]);
            flash_set('success', 'Utilisateur créé.');
        } else {
            if ($password) {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
                db()->prepare("UPDATE admin_users SET nom=?,email=?,role=?,password=? WHERE id=?")->execute([$nom,$email,$role,$hash,$editId]);
            } else {
                db()->prepare("UPDATE admin_users SET nom=?,email=?,role=? WHERE id=?")->execute([$nom,$email,$role,$editId]);
            }
            flash_set('success', 'Utilisateur mis à jour.');
        }
        redirect('/admin/users.php');
    }
}

// -- Profil (changement de mot de passe) ----------------------
if ($action === 'profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash_set('error','Token invalide.'); redirect('/admin/users.php?action=profile'); }
    $oldPwd = $_POST['old_password'] ?? '';
    $newPwd = $_POST['new_password'] ?? '';
    $newPwd2= $_POST['new_password2'] ?? '';

    $user = db()->prepare("SELECT password FROM admin_users WHERE id=?");
    $user->execute([$adminUser['id']]);
    $user = $user->fetch();

    if (!password_verify($oldPwd, $user['password'])) {
        flash_set('error', 'Mot de passe actuel incorrect.');
    } elseif (strlen($newPwd) < 8) {
        flash_set('error', 'Nouveau mot de passe : 8 caractères minimum.');
    } elseif ($newPwd !== $newPwd2) {
        flash_set('error', 'Confirmation incorrecte.');
    } else {
        $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost'=>12]);
        db()->prepare("UPDATE admin_users SET password=? WHERE id=?")->execute([$hash, $adminUser['id']]);
        flash_set('success', 'Mot de passe modifié.');
    }
    redirect('/admin/users.php?action=profile');
}

$users = db()->query("SELECT * FROM admin_users ORDER BY id ASC")->fetchAll();
$editUser = $id ? db()->prepare("SELECT * FROM admin_users WHERE id=?") : null;
if ($editUser) { $editUser->execute([$id]); $editUser = $editUser->fetch(); }
?>

<div class="page-header">
  <h1 class="page-title">
    <?= $action === 'profile' ? 'Mon profil' : 'Gestion des utilisateurs' ?>
  </h1>
</div>

<?php if ($action === 'profile'): ?>
<!-- PROFIL -->
<div class="admin-card" style="max-width:500px">
  <div class="admin-card-header"><h3>Changer le mot de passe</h3></div>
  <div class="admin-card-body">
    <form method="POST" action="?action=profile">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>Mot de passe actuel</label>
        <input type="password" name="old_password" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Nouveau mot de passe</label>
        <input type="password" name="new_password" class="form-control" required minlength="8">
      </div>
      <div class="form-group">
        <label>Confirmer le nouveau mot de passe</label>
        <input type="password" name="new_password2" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
    </form>
  </div>
</div>

<?php else: ?>
<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">

  <!-- Liste -->
  <div class="admin-card">
    <div class="admin-card-body p-0">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Dernière connexion</th><th>Actions</th></tr></thead>
        <tbody>
          <?php
          $roleLabels = ['super_admin'=>'Super Admin','admin'=>'Admin','editor'=>'Éditeur'];
          $roleBadge  = ['super_admin'=>'danger','admin'=>'primary','editor'=>'info'];
          foreach ($users as $u): ?>
          <tr <?= $u['id']===$adminUser['id'] ? 'style="background:rgba(5,48,103,.04)"':'' ?>>
            <td><?= $u['id'] ?></td>
            <td><strong><?= htmlspecialchars($u['nom']) ?></strong> <?= $u['id']===$adminUser['id'] ? '<span class="badge badge-light">Moi</span>':'' ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge badge-<?= $roleBadge[$u['role']]??'light' ?>"><?= $roleLabels[$u['role']]??$u['role'] ?></span></td>
            <td><span class="badge badge-<?= $u['is_active'] ? 'success':'secondary' ?>"><?= $u['is_active'] ? 'Actif':'Inactif' ?></span></td>
            <td style="white-space:nowrap"><?= $u['last_login'] ? format_date($u['last_login'], 'd/m/Y H:i') : '--' ?></td>
            <td>
              <div style="display:flex;gap:4px">
                <?php if ($u['id'] !== $adminUser['id']): ?>
                <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline" style="padding:5px 10px" title="Modifier"><i class="fas fa-edit"></i></a>
                <?php if (is_admin()): ?>
                <a href="?action=toggle&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline" style="padding:5px 10px"
                   title="<?= $u['is_active'] ? 'Désactiver':'Activer' ?>">
                  <i class="fas fa-<?= $u['is_active'] ? 'ban':'check' ?>"></i>
                </a>
                <?php endif; ?>
                <?php if (is_super_admin()): ?>
                <a href="?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm" style="padding:5px 10px;background:var(--accent);color:#fff"
                   data-confirm="Supprimer cet utilisateur ?" title="Supprimer"><i class="fas fa-trash"></i></a>
                <?php endif; ?>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Formulaire création/édition -->
  <?php if (is_admin()): ?>
  <div class="admin-card">
    <div class="admin-card-header"><h3><?= ($editUser && $action==='edit') ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' ?></h3></div>
    <div class="admin-card-body">
      <form method="POST" action="<?= $action==='edit' ? '?action=edit&id='.$id : '' ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="edit_id" value="<?= $editUser ? $editUser['id'] : 0 ?>">
        <div class="form-group">
          <label>Nom *</label>
          <input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($editUser['nom'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Rôle</label>
          <select name="role" class="form-control">
            <?php foreach ($roleLabels as $rv=>$rl): ?>
            <option value="<?= $rv ?>" <?= ($editUser['role']??'editor')===$rv ? 'selected':'' ?>><?= $rl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Mot de passe <?= $editUser ? '(laisser vide = inchangé)' : '*' ?></label>
          <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?> minlength="8">
        </div>
        <div class="form-group">
          <label>Confirmer le mot de passe</label>
          <input type="password" name="password2" class="form-control">
        </div>
        <div style="display:flex;gap:8px">
          <button type="submit" class="btn btn-primary"><?= $editUser ? 'Mettre à jour' : 'Créer l\'utilisateur' ?></button>
          <?php if ($editUser): ?><a href="/admin/users.php" class="btn btn-outline">Annuler</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php endif; ?>

  </main>
</div>
<script src="/assets/js/main.js"></script>
</body></html>
