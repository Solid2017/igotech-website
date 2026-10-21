<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$lang = current_lang();
$seoData = seo('blog');

$pageTitle  = $seoData['meta_title'] ?? t('Blog & Actualités | IgoTech', 'Blog & News | IgoTech');
$metaDesc   = $seoData['meta_desc']  ?? t('Actualités tech, conseils et innovations digitales de l\'agence IgoTech.', 'Tech news, tips and digital innovations from IgoTech agency.');
$activePage = 'blog';

$page    = max(1, (int)($_GET['page'] ?? 1));
$cat     = sanitize($_GET['cat'] ?? '');
$search  = sanitize($_GET['q'] ?? '');
$perPage = 6;

try {
    $where  = "WHERE statut='publie'";
    $params = [];
    if ($cat) {
        $where .= " AND categorie = ?";
        $params[] = $cat;
    }
    if ($search) {
        $where .= " AND (titre LIKE ? OR extrait LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $stmt  = db()->prepare("SELECT COUNT(*) FROM blog_posts $where");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $pag  = paginate($total, $page, $perPage);
    $stmt = db()->prepare("SELECT * FROM blog_posts $where ORDER BY published_at DESC LIMIT $perPage OFFSET {$pag['offset']}");
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    // Catégories distinctes
    $cats = db()->query("SELECT DISTINCT categorie FROM blog_posts WHERE statut='publie' AND categorie IS NOT NULL ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $posts = []; $pag = paginate(0,1,$perPage); $cats = [];
}

require_once 'includes/header.php';
?>

<!-- ======== PAGE HERO ======== -->
<section class="page-hero">
  <video class="page-hero-video-bg" autoplay muted loop playsinline>
    <source src="/assets/videos/page-hero-bg.webm" type="video/webm">
    <source src="/assets/videos/page-hero-bg.mp4"  type="video/mp4">
  </video>
  <div class="container page-hero-inner">
    <h1><?= t('Blog & Actualités', 'Blog & News') ?></h1>
    <p><?= t('Restez informé des dernières tendances digitales et actualités d\'IgoTech.', 'Stay informed about the latest digital trends and IgoTech news.') ?></p>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="/"><?= t('Accueil','Home') ?></a><span>›</span>
      <span aria-current="page">Blog</span>
    </nav>
  </div>
</section>

<!-- ======== FILTRES ======== -->
<section class="section-sm bg-light">
  <div class="container">
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between">
      <!-- Catégories -->
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/blog.php" class="badge <?= !$cat ? 'badge-primary' : 'badge-light' ?>" style="padding:7px 16px;font-size:.8rem">
          <?= t('Tous', 'All') ?>
        </a>
        <?php foreach ($cats as $c): ?>
        <a href="/blog.php?cat=<?= urlencode($c) ?><?= $search ? '&q='.urlencode($search) : '' ?>"
           class="badge <?= $cat === $c ? 'badge-primary' : 'badge-light' ?>" style="padding:7px 16px;font-size:.8rem">
          <?= htmlspecialchars($c) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <!-- Recherche -->
      <form method="get" action="/blog.php" style="display:flex;gap:8px" role="search">
        <?php if ($cat): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($cat) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="<?= t('Rechercher...','Search...') ?>"
               class="form-control" style="max-width:220px;padding:8px 14px;font-size:.88rem"
               aria-label="<?= t('Recherche', 'Search') ?>">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
      </form>
    </div>
  </div>
</section>

<!-- ======== ARTICLES ======== -->
<section class="section">
  <div class="container">
    <?php if ($posts): ?>
    <div class="blog-grid">
      <?php foreach ($posts as $i => $post): ?>
      <article class="blog-card" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 80 ?>">
        <div class="blog-img">
          <a href="/blog-detail.php?slug=<?= urlencode($post['slug']) ?>" tabindex="-1" aria-hidden="true">
            <img src="<?= $post['image'] ? htmlspecialchars($post['image']) : '/assets/images/blog-placeholder.jpg' ?>"
                 alt="<?= htmlspecialchars($post['titre']) ?>" loading="lazy">
          </a>
        </div>
        <div class="blog-body">
          <div class="blog-meta">
            <span><i class="fas fa-calendar"></i> <?= format_date($post['published_at'] ?? $post['created_at']) ?></span>
            <?php if ($post['categorie']): ?>
            <a href="/blog.php?cat=<?= urlencode($post['categorie']) ?>" class="badge badge-light" style="font-size:.72rem">
              <?= htmlspecialchars($post['categorie']) ?>
            </a>
            <?php endif; ?>
            <?php if ($post['vues']): ?>
            <span><i class="fas fa-eye"></i> <?= number_format($post['vues']) ?></span>
            <?php endif; ?>
          </div>
          <h2 style="font-size:1.05rem">
            <a href="/blog-detail.php?slug=<?= urlencode($post['slug']) ?>">
              <?= htmlspecialchars($post['titre']) ?>
            </a>
          </h2>
          <p><?= excerpt($post['extrait'] ?? '', 120) ?></p>
          <a href="/blog-detail.php?slug=<?= urlencode($post['slug']) ?>" class="blog-read-more">
            <?= t('Lire la suite','Read more') ?> <i class="fas fa-arrow-right fa-xs"></i>
          </a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pag['total_pages'] > 1): ?>
    <nav class="pagination" aria-label="Pagination">
      <?php if ($pag['has_prev']): ?>
      <a href="?page=<?= $pag['current'] - 1 ?><?= $cat ? '&cat='.urlencode($cat) : '' ?><?= $search ? '&q='.urlencode($search) : '' ?>"
         aria-label="<?= t('Précédent','Previous') ?>">‹</a>
      <?php endif; ?>

      <?php for ($p = 1; $p <= $pag['total_pages']; $p++): ?>
      <?php if ($p === $pag['current']): ?>
        <span class="current"><?= $p ?></span>
      <?php else: ?>
        <a href="?page=<?= $p ?><?= $cat ? '&cat='.urlencode($cat) : '' ?><?= $search ? '&q='.urlencode($search) : '' ?>"><?= $p ?></a>
      <?php endif; ?>
      <?php endfor; ?>

      <?php if ($pag['has_next']): ?>
      <a href="?page=<?= $pag['current'] + 1 ?><?= $cat ? '&cat='.urlencode($cat) : '' ?><?= $search ? '&q='.urlencode($search) : '' ?>"
         aria-label="<?= t('Suivant','Next') ?>">›</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="text-center" style="padding:60px 0">
      <i class="fas fa-newspaper" style="font-size:3rem;color:var(--gray-400);margin-bottom:16px;display:block"></i>
      <p style="color:var(--text-light)"><?= t('Aucun article trouvé.','No articles found.') ?></p>
      <a href="/blog.php" class="btn btn-outline mt-md"><?= t('Voir tous les articles','See all articles') ?></a>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
