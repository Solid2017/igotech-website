<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$lang = current_lang();
$slug = sanitize($_GET['slug'] ?? '');

if (!$slug) {
    redirect('/blog.php');
}

try {
    $stmt = db()->prepare("SELECT b.*, a.nom AS auteur_nom FROM blog_posts b LEFT JOIN admin_users a ON b.auteur_id=a.id WHERE b.slug=? AND b.statut='publie' LIMIT 1");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();

    if (!$post) {
        http_response_code(404);
        redirect('/blog.php');
    }

    // Incrémenter les vues
    db()->prepare("UPDATE blog_posts SET vues=vues+1 WHERE id=?")->execute([$post['id']]);

    // Articles similaires
    $stmt = db()->prepare("SELECT id, titre, slug, image, published_at, categorie FROM blog_posts WHERE statut='publie' AND id != ? AND categorie=? ORDER BY published_at DESC LIMIT 3");
    $stmt->execute([$post['id'], $post['categorie']]);
    $related = $stmt->fetchAll();
    if (count($related) < 3) {
        $stmt = db()->prepare("SELECT id, titre, slug, image, published_at, categorie FROM blog_posts WHERE statut='publie' AND id != ? ORDER BY published_at DESC LIMIT 3");
        $stmt->execute([$post['id']]);
        $related = $stmt->fetchAll();
    }
} catch (Exception $e) {
    redirect('/blog.php');
}

$pageTitle = ($post['meta_title'] ?: $post['titre']) . ' | IgoTech';
$metaDesc  = $post['meta_desc'] ?: excerpt($post['extrait'] ?? '', 155);
$ogImage   = $post['image'] ?: SITE_URL . '/assets/images/og-default.jpg';
$canonical = SITE_URL . '/blog-detail.php?slug=' . urlencode($slug);
$activePage = 'blog';

$schemaJson = json_encode([
  "@context" => "https://schema.org",
  "@type"    => "BlogPosting",
  "headline" => $post['titre'],
  "image"    => $post['image'] ?: SITE_URL . '/assets/images/og-default.jpg',
  "author"   => ["@type" => "Person", "name" => $post['auteur_nom'] ?? 'IgoTech'],
  "datePublished" => $post['published_at'] ?? $post['created_at'],
  "dateModified"  => $post['updated_at'],
  "publisher" => [
    "@type" => "Organization", "name" => "IgoTech",
    "logo"  => ["@type" => "ImageObject", "url" => SITE_URL . '/assets/images/logo.png']
  ]
]);

require_once 'includes/header.php';
?>

<!-- ======== PAGE HERO ======== -->
<section class="page-hero">
  <div class="container page-hero-inner">
    <?php if ($post['categorie']): ?>
    <span class="badge" style="background:rgba(255,255,255,.15);color:#fff;margin-bottom:12px"><?= htmlspecialchars($post['categorie']) ?></span>
    <?php endif; ?>
    <h1 style="max-width:780px"><?= htmlspecialchars($post['titre']) ?></h1>
    <div class="blog-meta" style="margin-top:14px">
      <span style="color:rgba(255,255,255,.7)"><i class="fas fa-calendar"></i> <?= format_date($post['published_at'] ?? $post['created_at']) ?></span>
      <?php if ($post['auteur_nom']): ?>
      <span style="color:rgba(255,255,255,.7)"><i class="fas fa-user"></i> <?= htmlspecialchars($post['auteur_nom']) ?></span>
      <?php endif; ?>
      <span style="color:rgba(255,255,255,.7)"><i class="fas fa-eye"></i> <?= number_format($post['vues']) ?> <?= t('vues','views') ?></span>
    </div>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="/"><?= t('Accueil','Home') ?></a><span>›</span>
      <a href="/blog.php">Blog</a><span>›</span>
      <span aria-current="page"><?= htmlspecialchars(mb_substr($post['titre'], 0, 40)) ?>…</span>
    </nav>
  </div>
</section>

<!-- ======== ARTICLE ======== -->
<section class="section">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:50px;align-items:start">

      <!-- Contenu principal -->
      <article>
        <?php if ($post['image']): ?>
        <div style="border-radius:var(--radius-lg);overflow:hidden;margin-bottom:36px;box-shadow:var(--shadow-lg)">
          <img src="<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['titre']) ?>" style="width:100%;max-height:480px;object-fit:cover" loading="eager">
        </div>
        <?php endif; ?>

        <?php if ($post['extrait']): ?>
        <p style="font-size:1.1rem;font-weight:600;line-height:1.8;color:var(--primary);border-left:4px solid var(--primary);padding-left:16px;margin-bottom:28px">
          <?= htmlspecialchars($post['extrait']) ?>
        </p>
        <?php endif; ?>

        <div class="blog-content" style="line-height:1.9;color:var(--text)">
          <?= $post['contenu'] ?>
        </div>

        <!-- Tags -->
        <?php if ($post['tags']): ?>
        <div style="margin-top:36px;padding-top:24px;border-top:1px solid var(--gray-200)">
          <strong style="font-size:.88rem"><?= t('Tags :','Tags:') ?></strong>
          <?php foreach (explode(',', $post['tags']) as $tag): ?>
          <a href="/blog.php?q=<?= urlencode(trim($tag)) ?>" class="badge badge-light" style="margin-left:6px;font-size:.78rem">
            <?= htmlspecialchars(trim($tag)) ?>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Partage -->
        <div style="margin-top:28px;padding:20px;background:var(--gray-100);border-radius:var(--radius);display:flex;align-items:center;gap:16px;flex-wrap:wrap">
          <strong style="font-size:.9rem"><?= t('Partager cet article :','Share this article:') ?></strong>
          <?php $shareUrl = urlencode(SITE_URL . '/blog-detail.php?slug=' . urlencode($slug)); $shareTitle = urlencode($post['titre']); ?>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener"
             class="btn btn-sm" style="background:#1877f2;color:#fff;border-radius:var(--radius-full);padding:8px 16px">
            <i class="fab fa-facebook-f"></i> Facebook
          </a>
          <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener"
             class="btn btn-sm" style="background:#000;color:#fff;border-radius:var(--radius-full);padding:8px 16px">
            <i class="fab fa-x-twitter"></i> Twitter
          </a>
          <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= $shareUrl ?>&title=<?= $shareTitle ?>" target="_blank" rel="noopener"
             class="btn btn-sm" style="background:#0a66c2;color:#fff;border-radius:var(--radius-full);padding:8px 16px">
            <i class="fab fa-linkedin-in"></i> LinkedIn
          </a>
          <a href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener"
             class="btn btn-sm" style="background:#25d366;color:#fff;border-radius:var(--radius-full);padding:8px 16px">
            <i class="fab fa-whatsapp"></i> WhatsApp
          </a>
        </div>

        <!-- Navigation précédent/suivant -->
        <div style="display:flex;justify-content:space-between;margin-top:36px;gap:16px;flex-wrap:wrap">
          <?php
          $prev = db()->prepare("SELECT titre, slug FROM blog_posts WHERE statut='publie' AND published_at < ? ORDER BY published_at DESC LIMIT 1");
          $prev->execute([$post['published_at'] ?? $post['created_at']]);
          $prevPost = $prev->fetch();
          $next = db()->prepare("SELECT titre, slug FROM blog_posts WHERE statut='publie' AND published_at > ? ORDER BY published_at ASC LIMIT 1");
          $next->execute([$post['published_at'] ?? $post['created_at']]);
          $nextPost = $next->fetch();
          ?>
          <?php if ($prevPost): ?>
          <a href="/blog-detail.php?slug=<?= urlencode($prevPost['slug']) ?>"
             style="flex:1;padding:16px;background:var(--gray-100);border-radius:var(--radius);text-decoration:none;display:block;min-width:0">
            <small style="color:var(--text-light);display:block;margin-bottom:4px"><i class="fas fa-arrow-left"></i> <?= t('Article précédent','Previous article') ?></small>
            <span style="font-weight:700;font-size:.9rem;color:var(--dark)"><?= htmlspecialchars(mb_substr($prevPost['titre'],0,50)) ?>…</span>
          </a>
          <?php endif; ?>
          <?php if ($nextPost): ?>
          <a href="/blog-detail.php?slug=<?= urlencode($nextPost['slug']) ?>"
             style="flex:1;padding:16px;background:var(--gray-100);border-radius:var(--radius);text-decoration:none;display:block;text-align:right;min-width:0">
            <small style="color:var(--text-light);display:block;margin-bottom:4px"><?= t('Article suivant','Next article') ?> <i class="fas fa-arrow-right"></i></small>
            <span style="font-weight:700;font-size:.9rem;color:var(--dark)"><?= htmlspecialchars(mb_substr($nextPost['titre'],0,50)) ?>…</span>
          </a>
          <?php endif; ?>
        </div>
      </article>

      <!-- Sidebar -->
      <aside style="position:sticky;top:100px">
        <!-- À propos IgoTech -->
        <div style="background:var(--primary);color:#fff;border-radius:var(--radius-lg);padding:24px;margin-bottom:24px">
          <img src="/assets/images/logo.png" alt="IgoTech" style="height:40px;filter:brightness(0) invert(1);margin-bottom:14px">
          <p style="font-size:.88rem;opacity:.85;line-height:1.7;margin-bottom:16px">
            <?= t('Agence digitale au Tchad. IT, développement, IA et formations pour votre succès numérique.','Digital agency in Chad. IT, development, AI and training for your digital success.') ?>
          </p>
          <a href="/contact.php" class="btn btn-white btn-sm w-100" style="justify-content:center"><?= t('Nous contacter','Contact us') ?></a>
        </div>

        <!-- Articles récents -->
        <?php if ($related): ?>
        <div style="background:var(--white);border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow);margin-bottom:24px">
          <h3 style="font-size:1rem;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid var(--primary)"><?= t('Articles récents','Recent articles') ?></h3>
          <?php foreach ($related as $r): ?>
          <a href="/blog-detail.php?slug=<?= urlencode($r['slug']) ?>"
             style="display:flex;gap:12px;margin-bottom:14px;text-decoration:none;align-items:flex-start">
            <img src="<?= $r['image'] ?: '/assets/images/blog-placeholder.jpg' ?>"
                 alt="" style="width:60px;height:50px;object-fit:cover;border-radius:var(--radius-sm);flex-shrink:0"
                 loading="lazy">
            <div>
              <div style="font-weight:700;font-size:.83rem;color:var(--dark);line-height:1.4;margin-bottom:3px"><?= htmlspecialchars(mb_substr($r['titre'],0,60)) ?>…</div>
              <div style="font-size:.75rem;color:var(--text-light)"><?= format_date($r['published_at'] ?? '') ?></div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Newsletter sidebar -->
        <div style="background:var(--gray-100);border-radius:var(--radius-lg);padding:24px">
          <h3 style="font-size:1rem;margin-bottom:8px"><?= t('Newsletter','Newsletter') ?></h3>
          <p style="font-size:.83rem;color:var(--text-light);margin-bottom:14px"><?= t('Recevez nos articles par email.','Receive our articles by email.') ?></p>
          <form id="newsletterForm">
            <?= csrf_field() ?>
            <div class="newsletter-inputs" style="flex-direction:column;gap:8px">
              <input type="email" name="email" placeholder="Email" class="form-control" required>
              <button type="submit" class="btn btn-primary w-100" style="justify-content:center">
                <span class="btn-text"><?= t("S'abonner",'Subscribe') ?></span>
                <span class="btn-spinner" hidden><i class="fas fa-spinner fa-spin"></i></span>
              </button>
            </div>
            <div class="form-msg" id="newsletterMsg" style="margin-top:8px;font-size:.82rem"></div>
          </form>
        </div>
      </aside>

    </div>
  </div>
</section>

<style>
.blog-content h2 { font-size:1.4rem; margin:28px 0 12px; color:var(--primary); }
.blog-content h3 { font-size:1.15rem; margin:22px 0 10px; }
.blog-content p  { margin-bottom:16px; }
.blog-content ul, .blog-content ol { margin:12px 0 16px 24px; }
.blog-content li { margin-bottom:6px; }
.blog-content img { border-radius:var(--radius); box-shadow:var(--shadow); margin:16px 0; }
.blog-content blockquote {
  border-left:4px solid var(--primary); padding:12px 20px; margin:20px 0;
  background:var(--light); border-radius:0 var(--radius) var(--radius) 0;
  font-style:italic; color:var(--text-light);
}
.blog-content a { color:var(--primary); font-weight:600; }
.blog-content strong { color:var(--dark); }
.blog-content code { background:var(--gray-100); padding:2px 6px; border-radius:3px; font-size:.88em; }
@media(max-width:767px){
  .container > div[style*="grid-template-columns:1fr 320px"]{display:block!important}
  aside{margin-top:40px;position:static!important}
}
</style>

<?php require_once 'includes/footer.php'; ?>
