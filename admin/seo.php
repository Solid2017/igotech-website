<?php
$pageTitle  = 'Gestion SEO';
$activeMenu = 'seo';
require_once __DIR__ . '/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_auth('editor');
    if (!csrf_check()) { flash_set('error','Token invalide.'); redirect('/admin/seo.php'); }

    $pages = ['home','about','services','formations','blog','contact'];
    foreach ($pages as $page) {
        $metaTitle    = sanitize($_POST["mt_{$page}"]    ?? '');
        $metaDesc     = sanitize($_POST["md_{$page}"]    ?? '');
        $metaTitleEn  = sanitize($_POST["mt_en_{$page}"] ?? '');
        $metaDescEn   = sanitize($_POST["md_en_{$page}"] ?? '');

        $stmt = db()->prepare("INSERT INTO seo_pages (page,meta_title,meta_title_en,meta_desc,meta_desc_en) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title),meta_title_en=VALUES(meta_title_en),meta_desc=VALUES(meta_desc),meta_desc_en=VALUES(meta_desc_en)");
        $stmt->execute([$page, $metaTitle, $metaTitleEn, $metaDesc, $metaDescEn]);
    }

    flash_set('success', 'Paramètres SEO mis à jour.');
    redirect('/admin/seo.php');
}

// Chargement des données SEO
$seoData = [];
$rows = db()->query("SELECT * FROM seo_pages")->fetchAll();
foreach ($rows as $r) { $seoData[$r['page']] = $r; }

$pagesInfo = [
    'home'       => ['label'=>'Accueil',    'url'=>'/'],
    'about'      => ['label'=>'À propos',   'url'=>'/about.php'],
    'services'   => ['label'=>'Services',   'url'=>'/services.php'],
    'formations' => ['label'=>'Formations', 'url'=>'/formations.php'],
    'blog'       => ['label'=>'Blog',       'url'=>'/blog.php'],
    'contact'    => ['label'=>'Contact',    'url'=>'/contact.php'],
];
?>

<div class="page-header">
  <h1 class="page-title">Gestion SEO</h1>
</div>

<!-- Tips SEO -->
<div class="admin-card" style="margin-bottom:24px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff">
  <div class="admin-card-body">
    <h3 style="color:#fff;margin-bottom:14px"><i class="fas fa-lightbulb"></i> Bonnes pratiques SEO</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;font-size:.86rem;opacity:.9">
      <div><i class="fas fa-check" style="margin-right:6px"></i> Title : 50-60 caractères max</div>
      <div><i class="fas fa-check" style="margin-right:6px"></i> Description : 150-160 caractères</div>
      <div><i class="fas fa-check" style="margin-right:6px"></i> Inclure le mot-clé principal</div>
      <div><i class="fas fa-check" style="margin-right:6px"></i> Description unique par page</div>
      <div><i class="fas fa-check" style="margin-right:6px"></i> Call-to-action dans la description</div>
      <div><i class="fas fa-check" style="margin-right:6px"></i> Éviter le bourrage de mots-clés</div>
    </div>
  </div>
</div>

<form method="POST" action="">
  <?= csrf_field() ?>

  <?php foreach ($pagesInfo as $pageKey => $info): ?>
  <?php $sd = $seoData[$pageKey] ?? []; ?>

  <div class="admin-card" style="margin-bottom:20px">
    <div class="admin-card-header" style="background:var(--gray-100)">
      <h3>
        <i class="fas fa-file-alt" style="color:var(--primary)"></i>
        Page : <?= $info['label'] ?>
        <span style="font-size:.78rem;color:var(--text-light);font-weight:400;margin-left:8px"><?= $info['url'] ?></span>
      </h3>
      <a href="<?= $info['url'] ?>" target="_blank" class="btn btn-sm btn-outline" style="font-size:.78rem">
        <i class="fas fa-external-link-alt"></i> Voir la page
      </a>
    </div>
    <div class="admin-card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <!-- FR -->
        <div>
          <h4 style="font-size:.9rem;margin-bottom:12px;color:var(--primary)">🇫🇷 Français</h4>
          <div class="form-group">
            <label>Meta Title FR</label>
            <input type="text" name="mt_<?= $pageKey ?>" class="form-control" data-maxlength="60"
                   value="<?= htmlspecialchars($sd['meta_title'] ?? '') ?>"
                   placeholder="Titre optimisé SEO -- 50-60 car.">
          </div>
          <div class="form-group">
            <label>Meta Description FR</label>
            <textarea name="md_<?= $pageKey ?>" class="form-control" rows="3" data-maxlength="160"
                      placeholder="Description courte et incitative -- 150-160 car."><?= htmlspecialchars($sd['meta_desc'] ?? '') ?></textarea>
          </div>
        </div>
        <!-- EN -->
        <div>
          <h4 style="font-size:.9rem;margin-bottom:12px;color:var(--primary)">🇬🇧 English</h4>
          <div class="form-group">
            <label>Meta Title EN</label>
            <input type="text" name="mt_en_<?= $pageKey ?>" class="form-control" data-maxlength="60"
                   value="<?= htmlspecialchars($sd['meta_title_en'] ?? '') ?>"
                   placeholder="SEO optimized title -- 50-60 chars">
          </div>
          <div class="form-group">
            <label>Meta Description EN</label>
            <textarea name="md_en_<?= $pageKey ?>" class="form-control" rows="3" data-maxlength="160"
                      placeholder="Short, compelling description -- 150-160 chars"><?= htmlspecialchars($sd['meta_desc_en'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Aperçu Google -->
      <div style="margin-top:16px;padding:16px;background:var(--gray-100);border-radius:var(--radius);font-size:.86rem">
        <div style="color:var(--text-light);font-size:.74rem;margin-bottom:8px">Aperçu dans les résultats Google :</div>
        <div style="color:#1a0dab;font-size:1.1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" id="preview-title-<?= $pageKey ?>">
          <?= htmlspecialchars($sd['meta_title'] ?? $info['label'] . ' | IgoTech') ?>
        </div>
        <div style="color:#006621;font-size:.8rem">https://igotech.tech<?= $info['url'] ?></div>
        <div style="color:#545454" id="preview-desc-<?= $pageKey ?>">
          <?= htmlspecialchars(mb_substr($sd['meta_desc'] ?? '', 0, 160)) ?>
        </div>
      </div>
    </div>
  </div>

  <script>
  (function() {
    const titleEl = document.querySelector('[name="mt_<?= $pageKey ?>"]');
    const descEl  = document.querySelector('[name="md_<?= $pageKey ?>"]');
    const previewT = document.getElementById('preview-title-<?= $pageKey ?>');
    const previewD = document.getElementById('preview-desc-<?= $pageKey ?>');
    if (titleEl) titleEl.addEventListener('input', () => previewT.textContent = titleEl.value || '<?= $info['label'] ?> | IgoTech');
    if (descEl)  descEl.addEventListener('input',  () => previewD.textContent = descEl.value.slice(0,160));
  })();
  </script>

  <?php endforeach; ?>

  <div style="margin-top:8px">
    <button type="submit" class="btn btn-primary btn-lg">
      <i class="fas fa-save"></i> Enregistrer les paramètres SEO
    </button>
  </div>
</form>

<!-- Sitemap & Robots -->
<div class="admin-card" style="margin-top:24px">
  <div class="admin-card-header"><h3><i class="fas fa-sitemap"></i> Outils SEO techniques</h3></div>
  <div class="admin-card-body">
    <div style="display:flex;gap:16px;flex-wrap:wrap">
      <a href="/sitemap.php" target="_blank" class="btn btn-outline btn-sm">
        <i class="fas fa-sitemap"></i> Voir le sitemap.xml
      </a>
      <a href="/robots.txt" target="_blank" class="btn btn-outline btn-sm">
        <i class="fas fa-robot"></i> Voir robots.txt
      </a>
      <a href="https://search.google.com/search-console" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
        <i class="fab fa-google"></i> Google Search Console
      </a>
    </div>
    <p style="color:var(--text-light);font-size:.82rem;margin-top:14px">
      <strong>sitemap.php</strong> génère automatiquement le sitemap XML de toutes vos pages et articles publiés.<br>
      Soumettez l'URL <code>https://igotech.tech/sitemap.php</code> dans Google Search Console.
    </p>
  </div>
</div>

  </main>
</div>
<script src="/assets/js/main.js"></script>
</body></html>
