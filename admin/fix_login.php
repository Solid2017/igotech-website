<?php
/**
 * IgoTech -- Script de reset temporaire
 * SUPPRIMER CE FICHIER IMMEDIATEMENT APRES USAGE
 */

$step = $_GET['step'] ?? '1';
$msg  = '';

require_once dirname(__DIR__) . '/includes/config.php';

// Etape 2 : execution du reset
if ($step === '2' && isset($_GET['confirm'])) {

    // 1. Reset du mot de passe
    $hash = password_hash('Admin@IgoTech2025', PASSWORD_BCRYPT);
    try {
        $pdo = db();
        $r = $pdo->prepare("UPDATE admin_users SET password=?, is_active=1 WHERE email='admin@igotech.tech'");
        $r->execute([$hash]);
        $affected = $r->rowCount();

        if ($affected === 0) {
            // L'utilisateur n'existe pas, on le cree
            $pdo->prepare("INSERT INTO admin_users (nom, email, password, role, is_active) VALUES (?,?,?,?,1)")
                ->execute(['Super Admin', 'admin@igotech.tech', $hash, 'super_admin']);
            $msg = 'Compte admin cree avec succes.';
        } else {
            $msg = 'Mot de passe reinitialise avec succes.';
        }

        // 2. Test session
        session_name('igo_sess');
        session_start();
        $_SESSION['test'] = 'ok_' . time();
        session_write_close();

        // 3. Verification que password_verify fonctionne
        $verify = password_verify('Admin@IgoTech2025', $hash);

        $msg .= '<br>password_verify() : ' . ($verify ? '<span style="color:green">OK</span>' : '<span style="color:red">ECHEC</span>');
        $msg .= '<br>Session ecrite : <span style="color:green">OK</span>';
        $msg .= '<br>Hash genere : <code style="font-size:.75rem">' . htmlspecialchars($hash) . '</code>';
        $msg .= '<br><br><strong>Vous pouvez maintenant vous connecter :</strong><br>';
        $msg .= 'Email : admin@igotech.tech<br>Mot de passe : Admin@IgoTech2025';

    } catch (Exception $e) {
        $msg = '<span style="color:red">Erreur : ' . htmlspecialchars($e->getMessage()) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>IgoTech -- Reset Login</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:sans-serif;background:#0f172a;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
  .box{background:#fff;border-radius:12px;padding:32px;max-width:480px;width:100%}
  h1{color:#053067;font-size:1.2rem;margin-bottom:8px}
  p{color:#475569;font-size:.88rem;margin-bottom:20px;line-height:1.6}
  .warn{background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:12px;font-size:.82rem;color:#713f12;margin-bottom:20px}
  .btn{display:block;width:100%;padding:13px;background:#053067;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;text-align:center;text-decoration:none}
  .btn:hover{background:#0a4f9e}
  .msg{margin-top:20px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:14px;font-size:.85rem;color:#166534;line-height:1.8}
  code{background:#e2e8f0;padding:2px 6px;border-radius:4px}
  .link{display:block;text-align:center;margin-top:16px;color:#053067;font-size:.85rem}
</style>
</head>
<body>
<div class="box">

  <?php if ($step === '1'): ?>
    <h1>Reset mot de passe admin</h1>
    <p>Ce script va reinitialiser le mot de passe du compte <strong>admin@igotech.tech</strong> avec la valeur <strong>Admin@IgoTech2025</strong>.</p>
    <div class="warn">
      Attention : supprimer ce fichier immediatement apres utilisation !<br>
      Chemin : <code>/htdocs/admin/fix_login.php</code>
    </div>
    <a href="?step=2&confirm=yes" class="btn">Reinitialiser le mot de passe</a>

  <?php else: ?>
    <h1>Resultat</h1>
    <?php if ($msg): ?>
    <div class="msg"><?= $msg ?></div>
    <?php endif; ?>
    <a href="/admin/login.php" class="btn" style="margin-top:20px">Aller a la page de connexion</a>
    <p class="link" style="margin-top:12px;color:#dc2626">N'oubliez pas de supprimer fix_login.php !</p>
  <?php endif; ?>

</div>
</body>
</html>
