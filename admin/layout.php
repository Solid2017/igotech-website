<?php
/**
 * IgoTech Admin -- Layout partagé (sidebar + topbar)
 * Inclusion : require_once 'layout.php' AVANT tout output HTML
 * Variables attendues : $pageTitle, $activeMenu
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/auth.php';

require_auth(); // Redirige si non connecté

$adminUser = admin();
$stats     = dashboard_stats();
$pageTitle ??= 'Admin -- IgoTech';
$activeMenu ??= 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> -- IgoTech Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="shortcut icon"    type="image/x-icon"            href="/assets/images/favicon.ico">
  <link rel="icon"             type="image/png" sizes="32x32" href="/assets/images/favicon-32.png">
  <link rel="icon"             type="image/png" sizes="16x16" href="/assets/images/favicon-16.png">
  <link rel="apple-touch-icon" sizes="180x180"               href="/assets/images/apple-touch-icon.png">
  <meta name="theme-color" content="#1414D4">
  <link rel="stylesheet" href="/assets/vendor/fontawesome/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-page">

<!-- ======== SIDEBAR ======== -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-header">
    <a href="/admin/index.php" class="sidebar-logo">
      <img src="/assets/images/logo.png" alt="IgoTech" height="40">
      <span>Admin</span>
    </a>
    <button class="sidebar-close" id="sidebarClose" aria-label="Fermer"><i class="fas fa-times"></i></button>
  </div>

  <nav class="sidebar-nav" aria-label="Navigation admin">
    <ul>

      <li class="nav-section">Principal</li>

      <li class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
        <a href="/admin/index.php">
          <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>
      </li>

      <li class="nav-section">Interactions</li>

      <li class="<?= $activeMenu === 'messages' ? 'active' : '' ?>">
        <a href="/admin/messages.php">
          <i class="fas fa-envelope"></i> <span>Messages</span>
          <?php if ($stats['messages_new'] > 0): ?>
          <span class="sidebar-badge"><?= $stats['messages_new'] ?></span>
          <?php endif; ?>
        </a>
      </li>

      <li class="<?= $activeMenu === 'inscriptions' ? 'active' : '' ?>">
        <a href="/admin/inscriptions.php">
          <i class="fas fa-user-graduate"></i> <span>Inscriptions</span>
          <?php if ($stats['inscriptions_new'] > 0): ?>
          <span class="sidebar-badge"><?= $stats['inscriptions_new'] ?></span>
          <?php endif; ?>
        </a>
      </li>

      <li class="<?= $activeMenu === 'newsletter' ? 'active' : '' ?>">
        <a href="/admin/newsletter.php">
          <i class="fas fa-at"></i> <span>Newsletter</span>
        </a>
      </li>

      <li class="nav-section">Contenu</li>

      <li class="<?= $activeMenu === 'formations' ? 'active' : '' ?>">
        <a href="/admin/formations.php">
          <i class="fas fa-graduation-cap"></i> <span>Formations</span>
        </a>
      </li>

      <li class="<?= $activeMenu === 'projets' ? 'active' : '' ?>">
        <a href="/admin/projets.php">
          <i class="fas fa-briefcase"></i> <span>Projets & Portfolio</span>
        </a>
      </li>

      <li class="<?= $activeMenu === 'shop' ? 'active' : '' ?>">
        <a href="/admin/shop.php">
          <i class="fas fa-store"></i> <span>IgoShop</span>
          <?php
          try {
            $shopDevis = db()->query("SELECT COUNT(*) FROM shop_devis WHERE statut='nouveau'")->fetchColumn();
            if ($shopDevis > 0): ?>
          <span class="sidebar-badge"><?= $shopDevis ?></span>
          <?php endif;
          } catch (Exception $e) { /* table may not exist yet */ } ?>
        </a>
      </li>

      <li class="<?= $activeMenu === 'blog' ? 'active' : '' ?>">
        <a href="/admin/blog.php">
          <i class="fas fa-newspaper"></i> <span>Blog / Actualités</span>
        </a>
      </li>

      <li class="<?= $activeMenu === 'content' ? 'active' : '' ?>">
        <a href="/admin/content.php">
          <i class="fas fa-pencil-alt"></i> <span>Contenu des pages</span>
        </a>
      </li>

      <li class="nav-section">Outils</li>

      <li class="<?= $activeMenu === 'chatbot' ? 'active' : '' ?>">
        <a href="/admin/chatbot.php">
          <i class="fas fa-robot"></i> <span>Chatbot</span>
          <?php
          try {
            $cbLeads = db()->query("SELECT COUNT(*) FROM chatbot_conversations WHERE statut='ouvert' AND nb_messages > 0")->fetchColumn();
            if ($cbLeads > 0): ?>
          <span class="sidebar-badge"><?= $cbLeads ?></span>
          <?php endif;
          } catch (Exception $e) { /* table may not exist yet */ } ?>
        </a>
      </li>

      <li class="nav-section">Paramètres</li>

      <li class="<?= $activeMenu === 'seo' ? 'active' : '' ?>">
        <a href="/admin/seo.php">
          <i class="fas fa-search"></i> <span>SEO</span>
        </a>
      </li>

      <?php if (is_admin()): ?>
      <li class="<?= $activeMenu === 'users' ? 'active' : '' ?>">
        <a href="/admin/users.php">
          <i class="fas fa-users-cog"></i> <span>Utilisateurs</span>
        </a>
      </li>
      <?php endif; ?>

      <li class="<?= $activeMenu === 'settings' ? 'active' : '' ?>">
        <a href="/admin/settings.php">
          <i class="fas fa-cog"></i> <span>Paramètres</span>
        </a>
      </li>

    </ul>
  </nav>

  <div class="sidebar-footer">
    <a href="/" target="_blank" rel="noopener" class="sidebar-site-link">
      <i class="fas fa-external-link-alt"></i> Voir le site
    </a>
    <a href="/admin/auth.php?logout=1" class="sidebar-logout"
       onclick="return confirm('Déconnexion ?')">
      <i class="fas fa-sign-out-alt"></i> Déconnexion
    </a>
  </div>
</aside>

<!-- ======== MAIN ======== -->
<div class="admin-main" id="adminMain">

  <!-- -- Topbar -- -->
  <header class="admin-topbar">
    <div class="admin-topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
        <i class="fas fa-bars"></i>
      </button>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="/admin/index.php">Dashboard</a>
        <?php if ($pageTitle !== 'Dashboard'): ?>
        <span>›</span><span><?= htmlspecialchars($pageTitle) ?></span>
        <?php endif; ?>
      </nav>
    </div>
    <div class="admin-topbar-right">
      <!-- Notifications -->
      <div class="admin-notif">
        <?php if ($stats['messages_new'] + $stats['inscriptions_new'] > 0): ?>
        <a href="/admin/messages.php" class="notif-btn" title="Nouvelles notifications">
          <i class="fas fa-bell"></i>
          <span class="notif-dot"></span>
        </a>
        <?php endif; ?>
      </div>
      <!-- Profil -->
      <div class="admin-user-menu">
        <div class="admin-user-btn">
          <div class="admin-avatar">
            <?php if ($adminUser['avatar']): ?>
            <img src="<?= htmlspecialchars($adminUser['avatar']) ?>" alt="">
            <?php else: ?>
            <?= strtoupper(substr($adminUser['nom'], 0, 1)) ?>
            <?php endif; ?>
          </div>
          <span class="admin-user-name"><?= htmlspecialchars($adminUser['nom']) ?></span>
          <i class="fas fa-chevron-down fa-xs"></i>
        </div>
        <div class="admin-user-dropdown">
          <a href="/admin/users.php?action=profile"><i class="fas fa-user"></i> Mon profil</a>
          <a href="/admin/settings.php"><i class="fas fa-cog"></i> Paramètres</a>
          <hr>
          <a href="/" target="_blank"><i class="fas fa-external-link-alt"></i> Voir le site</a>
          <hr>
          <a href="/admin/auth.php?logout=1" onclick="return confirm('Déconnexion ?')"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
      </div>
    </div>
  </header>

  <!-- -- Contenu de la page -- -->
  <main class="admin-content">
    <?= flash_html() ?>
