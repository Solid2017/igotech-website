<?php
/**
 * IgoTech -- Sitemap XML dynamique
 * Soumettez https://igotech.tech/sitemap.php à Google Search Console
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$baseUrl = SITE_URL;

// Pages statiques
$staticPages = [
    ['loc' => $baseUrl . '/',               'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => $baseUrl . '/about.php',      'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => $baseUrl . '/services.php',   'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => $baseUrl . '/formations.php', 'priority' => '0.9', 'changefreq' => 'weekly'],
    ['loc' => $baseUrl . '/blog.php',       'priority' => '0.8', 'changefreq' => 'daily'],
    ['loc' => $baseUrl . '/contact.php',    'priority' => '0.7', 'changefreq' => 'monthly'],
];

// Articles de blog publiés
try {
    $posts = db()->query("SELECT slug, updated_at, published_at FROM blog_posts WHERE statut='publie' ORDER BY published_at DESC")->fetchAll();
} catch (Exception $e) {
    $posts = [];
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">

<?php foreach ($staticPages as $page): ?>
  <url>
    <loc><?= htmlspecialchars($page['loc']) ?></loc>
    <lastmod><?= date('Y-m-d') ?></lastmod>
    <changefreq><?= $page['changefreq'] ?></changefreq>
    <priority><?= $page['priority'] ?></priority>
    <xhtml:link rel="alternate" hreflang="fr" href="<?= htmlspecialchars($page['loc']) ?>?lang=fr"/>
    <xhtml:link rel="alternate" hreflang="en" href="<?= htmlspecialchars($page['loc']) ?>?lang=en"/>
  </url>
<?php endforeach; ?>

<?php foreach ($posts as $post): ?>
  <url>
    <loc><?= htmlspecialchars($baseUrl . '/blog-detail.php?slug=' . urlencode($post['slug'])) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($post['updated_at'])) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach; ?>

</urlset>
