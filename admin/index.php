<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/layout.php';

$stats = dashboard_stats();

// Activité récente
try {
    $recentMessages = db()->query("SELECT nom, email, sujet, statut, created_at FROM contacts ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $recentInscriptions = db()->query("SELECT CONCAT(prenom, ' ', nom) AS nom_complet, formation_nom, statut, created_at FROM inscriptions ORDER BY created_at DESC LIMIT 5")->fetchAll();
    // Vues blog
    $topPosts = db()->query("SELECT titre, vues, published_at FROM blog_posts WHERE statut='publie' ORDER BY vues DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {
    $recentMessages = $recentInscriptions = $topPosts = [];
}
?>

<div class="page-header">
  <h1 class="page-title">Dashboard</h1>
  <p class="page-subtitle">Bienvenue, <?= htmlspecialchars($adminUser['nom']) ?> -- <?= date('l d F Y') ?></p>
</div>

<!-- -- KPI Cards -- -->
<div class="kpi-grid">
  <div class="kpi-card kpi-primary">
    <div class="kpi-icon"><i class="fas fa-envelope"></i></div>
    <div class="kpi-body">
      <div class="kpi-num"><?= $stats['messages_total'] ?></div>
      <div class="kpi-label">Messages reçus</div>
      <?php if ($stats['messages_new']): ?>
      <div class="kpi-sub"><span class="badge badge-accent"><?= $stats['messages_new'] ?> nouveau<?= $stats['messages_new'] > 1 ? 'x' : '' ?></span></div>
      <?php endif; ?>
    </div>
    <a href="/admin/messages.php" class="kpi-link"><i class="fas fa-arrow-right"></i></a>
  </div>

  <div class="kpi-card kpi-accent">
    <div class="kpi-icon"><i class="fas fa-user-graduate"></i></div>
    <div class="kpi-body">
      <div class="kpi-num"><?= $stats['inscriptions_total'] ?></div>
      <div class="kpi-label">Inscriptions</div>
      <?php if ($stats['inscriptions_new']): ?>
      <div class="kpi-sub"><span class="badge badge-accent"><?= $stats['inscriptions_new'] ?> en attente</span></div>
      <?php endif; ?>
    </div>
    <a href="/admin/inscriptions.php" class="kpi-link"><i class="fas fa-arrow-right"></i></a>
  </div>

  <div class="kpi-card kpi-success">
    <div class="kpi-icon"><i class="fas fa-at"></i></div>
    <div class="kpi-body">
      <div class="kpi-num"><?= $stats['newsletter_actifs'] ?></div>
      <div class="kpi-label">Abonnés newsletter</div>
    </div>
    <a href="/admin/newsletter.php" class="kpi-link"><i class="fas fa-arrow-right"></i></a>
  </div>

  <div class="kpi-card kpi-info">
    <div class="kpi-icon"><i class="fas fa-newspaper"></i></div>
    <div class="kpi-body">
      <div class="kpi-num"><?= $stats['articles_publie'] ?></div>
      <div class="kpi-label">Articles publiés</div>
      <?php if ($stats['articles_brouillon']): ?>
      <div class="kpi-sub"><?= $stats['articles_brouillon'] ?> brouillon<?= $stats['articles_brouillon'] > 1 ? 's' : '' ?></div>
      <?php endif; ?>
    </div>
    <a href="/admin/blog.php" class="kpi-link"><i class="fas fa-arrow-right"></i></a>
  </div>
</div>

<!-- -- Grille activité -- -->
<div class="admin-grid-2">

  <!-- Messages récents -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>Messages récents</h3>
      <a href="/admin/messages.php" class="btn btn-sm btn-outline">Voir tout</a>
    </div>
    <div class="admin-card-body p-0">
      <?php if ($recentMessages): ?>
      <table class="admin-table">
        <thead><tr><th>Nom</th><th>Sujet</th><th>Statut</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($recentMessages as $m): ?>
          <tr>
            <td><strong><?= htmlspecialchars($m['nom']) ?></strong><br><small style="color:var(--text-light)"><?= htmlspecialchars($m['email']) ?></small></td>
            <td><?= htmlspecialchars(mb_substr($m['sujet'],0,30)) ?></td>
            <td>
              <?php $sBadge = ['non_lu'=>'danger','lu'=>'info','repondu'=>'success','archive'=>'secondary'];?>
              <span class="badge badge-<?= $sBadge[$m['statut']] ?? 'light' ?>"><?= ucfirst(str_replace('_',' ',$m['statut'])) ?></span>
            </td>
            <td style="white-space:nowrap"><?= format_date($m['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="empty-state">Aucun message pour le moment.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Inscriptions récentes -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>Inscriptions récentes</h3>
      <a href="/admin/inscriptions.php" class="btn btn-sm btn-outline">Voir tout</a>
    </div>
    <div class="admin-card-body p-0">
      <?php if ($recentInscriptions): ?>
      <table class="admin-table">
        <thead><tr><th>Candidat</th><th>Formation</th><th>Statut</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($recentInscriptions as $ins): ?>
          <tr>
            <td><strong><?= htmlspecialchars($ins['nom_complet']) ?></strong></td>
            <td><?= htmlspecialchars(mb_substr($ins['formation_nom'],0,28)) ?></td>
            <td>
              <?php $iBadge = ['en_attente'=>'warning','confirme'=>'success','annule'=>'danger','termine'=>'info'];?>
              <span class="badge badge-<?= $iBadge[$ins['statut']] ?? 'light' ?>"><?= ucfirst(str_replace('_',' ',$ins['statut'])) ?></span>
            </td>
            <td style="white-space:nowrap"><?= format_date($ins['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="empty-state">Aucune inscription.</p>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- -- Articles les plus vus -- -->
<?php if ($topPosts): ?>
<div class="admin-card" style="margin-top:24px">
  <div class="admin-card-header">
    <h3>Articles les plus consultés</h3>
    <a href="/admin/blog.php" class="btn btn-sm btn-outline">Gérer le blog</a>
  </div>
  <div class="admin-card-body p-0">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Titre</th><th>Vues</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($topPosts as $i => $p): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars(mb_substr($p['titre'],0,60)) ?></td>
          <td><strong><?= number_format($p['vues']) ?></strong></td>
          <td><?= format_date($p['published_at'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- -- Actions rapides -- -->
<div class="quick-actions" style="margin-top:28px">
  <h3 style="margin-bottom:14px;font-size:1rem">Actions rapides</h3>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="/admin/blog.php?action=new" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nouvel article</a>
    <a href="/admin/content.php"         class="btn btn-outline btn-sm"><i class="fas fa-pencil-alt"></i> Modifier le contenu</a>
    <a href="/admin/newsletter.php"      class="btn btn-outline btn-sm"><i class="fas fa-at"></i> Exporter newsletter</a>
    <a href="/admin/seo.php"             class="btn btn-outline btn-sm"><i class="fas fa-search"></i> Gérer le SEO</a>
    <a href="/admin/inscriptions.php?export=csv" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Export inscriptions</a>
  </div>
</div>

  </main><!-- /admin-content -->
</div><!-- /admin-main -->

<script src="/assets/vendor/aos/aos.js"></script>
<script src="/assets/js/main.js"></script>
</body>
</html>
