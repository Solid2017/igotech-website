<?php
/**
 * IgoTech Admin -- Authentification & helpers
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// --- Vérifie que l'admin est connecté ------------------------
function require_auth(string $role = 'editor'): void {
    if (empty($_SESSION['admin_id'])) {
        flash_set('error', 'Veuillez vous connecter pour accéder à cette page.');
        redirect('/admin/login.php');
    }
    // Vérification timeout session
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        flash_set('error', 'Votre session a expiré. Veuillez vous reconnecter.');
        redirect('/admin/login.php');
    }
    $_SESSION['last_activity'] = time();

    // Vérification de rôle
    $roles = ['editor' => 1, 'admin' => 2, 'super_admin' => 3];
    $userRole  = $_SESSION['admin_role'] ?? 'editor';
    $reqLevel  = $roles[$role] ?? 1;
    $userLevel = $roles[$userRole] ?? 1;
    if ($userLevel < $reqLevel) {
        flash_set('error', 'Accès refusé : droits insuffisants.');
        redirect('/admin/index.php');
    }
}

// --- Login ---------------------------------------------------
function admin_login(string $email, string $password): bool {
    $stmt = db()->prepare("SELECT * FROM admin_users WHERE email=? AND is_active=1 LIMIT 1");
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }
    // Régénère l'ID de session
    session_regenerate_id(true);
    $_SESSION['admin_id']       = $user['id'];
    $_SESSION['admin_nom']      = $user['nom'];
    $_SESSION['admin_email']    = $user['email'];
    $_SESSION['admin_role']     = $user['role'];
    $_SESSION['admin_avatar']   = $user['avatar'];
    $_SESSION['last_activity']  = time();

    // Mise à jour last_login
    db()->prepare("UPDATE admin_users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
    return true;
}

// --- Logout --------------------------------------------------
function admin_logout(): void {
    session_unset();
    session_destroy();
    redirect('/admin/login.php');
}

// --- Infos admin courant -------------------------------------
function admin(): array {
    return [
        'id'     => $_SESSION['admin_id']    ?? 0,
        'nom'    => $_SESSION['admin_nom']   ?? '',
        'email'  => $_SESSION['admin_email'] ?? '',
        'role'   => $_SESSION['admin_role']  ?? 'editor',
        'avatar' => $_SESSION['admin_avatar']?? null,
    ];
}

function is_super_admin(): bool {
    return ($_SESSION['admin_role'] ?? '') === 'super_admin';
}

function is_admin(): bool {
    return in_array($_SESSION['admin_role'] ?? '', ['admin', 'super_admin'], true);
}

// --- Statistiques dashboard ----------------------------------
function dashboard_stats(): array {
    $db = db();
    return [
        'messages_new'       => (int)$db->query("SELECT COUNT(*) FROM contacts WHERE statut='non_lu'")->fetchColumn(),
        'messages_total'     => (int)$db->query("SELECT COUNT(*) FROM contacts")->fetchColumn(),
        'inscriptions_new'   => (int)$db->query("SELECT COUNT(*) FROM inscriptions WHERE statut='en_attente'")->fetchColumn(),
        'inscriptions_total' => (int)$db->query("SELECT COUNT(*) FROM inscriptions")->fetchColumn(),
        'newsletter_actifs'  => (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE statut='actif'")->fetchColumn(),
        'articles_publie'    => (int)$db->query("SELECT COUNT(*) FROM blog_posts WHERE statut='publie'")->fetchColumn(),
        'articles_brouillon' => (int)$db->query("SELECT COUNT(*) FROM blog_posts WHERE statut='brouillon'")->fetchColumn(),
        'formations_total'   => (int)$db->query("SELECT COUNT(*) FROM formations WHERE est_publie=1")->fetchColumn(),
    ];
}

// --- Traitement Logout (GET/POST) ----------------------------
if (isset($_GET['logout']) || isset($_POST['logout'])) {
    admin_logout();
}
