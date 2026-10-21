<?php
/**
 * IgoTech -- Header global (nav + preloader + meta)
 * Variables attendues depuis la page appelante :
 *   $pageTitle   (string) -- titre <title>
 *   $metaDesc    (string) -- meta description
 *   $metaTitle   (string) -- og:title override (optionnel)
 *   $ogImage     (string) -- og:image (optionnel)
 *   $canonical   (string) -- URL canonique (optionnel)
 *   $activePage  (string) -- 'home' | 'about' | 'services' | 'formations' | 'blog' | 'contact'
 *   $schemaJson  (string) -- JSON-LD (optionnel)
 */
if (!defined('ROOT_PATH')) require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$lang       = current_lang();
$siteName   = setting('site_nom', 'IgoTech');
$siteSlogan = setting('site_slogan', 'Marketing Digital & Applications');
$telephone  = setting('site_telephone', '+235 63 03 87 80');
$email      = setting('site_email', 'contact@igotech.tech');
$facebook   = setting('site_facebook', '#');
$linkedin   = setting('site_linkedin', '#');
$twitter    = setting('site_twitter', '#');
$whatsapp   = setting('site_whatsapp', '23563038780');

$pageTitle ??= $siteName . ' -- ' . $siteSlogan;
$metaDesc  ??= t(
    'IgoTech, agence digitale au Tchad. IT, développement web, IA et formations.',
    'IgoTech, digital agency in Chad. IT, web development, AI and training.'
);
$canonical ??= SITE_URL . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$ogImage   ??= SITE_URL . '/assets/images/og-default.jpg';
$activePage ??= '';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" class="no-js">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- -- Title & Description -- -->
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES) ?>">
  <meta name="robots" content="index, follow">
  <meta name="author" content="IgoTech">
  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
  <link rel="alternate" hreflang="fr" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>?lang=fr">
  <link rel="alternate" hreflang="en" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>?lang=en">

  <!-- -- Open Graph -- -->
  <meta property="og:type"        content="website">
  <meta property="og:site_name"   content="<?= htmlspecialchars($siteName, ENT_QUOTES) ?>">
  <meta property="og:title"       content="<?= htmlspecialchars($pageTitle, ENT_QUOTES) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES) ?>">
  <meta property="og:url"         content="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
  <meta property="og:image"       content="<?= htmlspecialchars($ogImage, ENT_QUOTES) ?>">
  <meta property="og:locale"      content="<?= $lang === 'fr' ? 'fr_FR' : 'en_GB' ?>">

  <!-- -- Twitter Card -- -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= htmlspecialchars($pageTitle, ENT_QUOTES) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES) ?>">
  <meta name="twitter:image"       content="<?= htmlspecialchars($ogImage, ENT_QUOTES) ?>">

  <!-- -- Favicons -- -->
  <link rel="shortcut icon"    type="image/x-icon"            href="/assets/images/favicon.ico">
  <link rel="icon"             type="image/png" sizes="32x32" href="/assets/images/favicon-32.png">
  <link rel="icon"             type="image/png" sizes="16x16" href="/assets/images/favicon-16.png">
  <link rel="apple-touch-icon" sizes="180x180"               href="/assets/images/apple-touch-icon.png">
  <link rel="manifest"                                        href="/site.webmanifest">
  <meta name="theme-color" content="#1414D4">

  <!-- -- Google Fonts (self-hosted fallback via CSS) -- -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">

  <!-- -- FontAwesome (local) -- -->
  <link rel="stylesheet" href="/assets/vendor/fontawesome/css/all.min.css">

  <!-- -- AOS (local) -- -->
  <link rel="stylesheet" href="/assets/vendor/aos/aos.css">

  <!-- -- CSS principal -- -->
  <link rel="stylesheet" href="/assets/css/style.css?v=1.0">

  <?php if (!empty($schemaJson)): ?>
  <!-- -- JSON-LD -- -->
  <script type="application/ld+json"><?= $schemaJson ?></script>
  <?php endif; ?>

  <!-- -- Schema.org Organisation (toutes les pages) -- -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "IgoTech",
    "url": "https://igotech.tech",
    "logo": "https://igotech.tech/assets/images/logo.png",
    "description": "Agence digitale au Tchad spécialisée en IT, développement web, IA et formations.",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Quartier Amtoukoui, Rue de Canal Chari-Mongo, Porte 1286",
      "addressLocality": "N'Djamena",
      "addressCountry": "TD"
    },
    "telephone": "+235630387808",
    "email": "contact@igotech.tech",
    "sameAs": [
      "<?= htmlspecialchars($facebook) ?>",
      "<?= htmlspecialchars($linkedin) ?>"
    ]
  }
  </script>
</head>
<body>

<!-- ================ PRELOADER ================ -->
<div id="preloader" aria-hidden="true">
  <div class="preloader-inner">

    <!-- Halos concentriques -->
    <div class="preloader-rings">
      <span class="ring ring-1"></span>
      <span class="ring ring-2"></span>
      <span class="ring ring-3"></span>
    </div>

    <!-- Logo central -->
    <div class="preloader-logo-wrap">
      <img src="/assets/images/logo.png" alt="IgoTech" class="preloader-logo">
    </div>

    <!-- Nom animé lettre par lettre -->
    <div class="preloader-brand" aria-label="IgoTech">
      <span class="pl" style="--i:0">I</span><span class="pl" style="--i:1">g</span><span class="pl" style="--i:2">o</span><span class="pl pl-gap" style="--i:3">T</span><span class="pl" style="--i:4">e</span><span class="pl" style="--i:5">c</span><span class="pl" style="--i:6">h</span>
    </div>

    <!-- Tagline -->
    <p class="preloader-tagline">Votre partenaire digital au Tchad</p>

    <!-- Points de chargement -->
    <div class="preloader-dots">
      <span></span><span></span><span></span>
    </div>

  </div>
</div>

<!-- ================ TOP BAR ================ -->
<div class="topbar">
  <div class="container">
    <div class="topbar-left">
      <a href="tel:<?= preg_replace('/\s+/', '', $telephone) ?>">
        <i class="fas fa-phone-alt"></i> <?= htmlspecialchars($telephone) ?>
      </a>
      <a href="mailto:<?= htmlspecialchars($email) ?>">
        <i class="fas fa-envelope"></i> <?= htmlspecialchars($email) ?>
      </a>
    </div>
    <div class="topbar-right">
      <a href="?lang=fr" class="lang-btn <?= $lang === 'fr' ? 'active' : '' ?>" title="Français">FR</a>
      <span>|</span>
      <a href="?lang=en" class="lang-btn <?= $lang === 'en' ? 'active' : '' ?>" title="English">EN</a>
      <div class="topbar-social">
        <a href="<?= htmlspecialchars($facebook) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="<?= htmlspecialchars($linkedin) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        <a href="<?= htmlspecialchars($twitter) ?>"  target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-x-twitter"></i></a>
        <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
      </div>
    </div>
  </div>
</div>

<!-- ================ NAVBAR ================ -->
<header class="navbar" id="navbar">
  <div class="container navbar-inner">

    <!-- Logo -->
    <a href="/" class="navbar-logo" aria-label="<?= htmlspecialchars($siteName) ?> -- Accueil">
      <img src="/assets/images/logo.png" alt="IgoTech" height="50" width="auto">
    </a>

    <!-- Nav links -->
    <nav class="navbar-nav" id="navMenu" role="navigation" aria-label="Menu principal">
      <ul>
        <li><a href="/"            class="nav-link <?= $activePage === 'home'       ? 'active' : '' ?>"><?= t('Accueil','Home') ?></a></li>
        <li><a href="/about.php"   class="nav-link <?= $activePage === 'about'      ? 'active' : '' ?>"><?= t('À propos','About') ?></a></li>
        <li class="has-dropdown">
          <a href="/services.php" class="nav-link <?= $activePage === 'services'   ? 'active' : '' ?>"><?= t('Services','Services') ?> <i class="fas fa-chevron-down fa-xs"></i></a>
          <ul class="dropdown">
            <li><a href="/services.php#infrastructure"><i class="fas fa-server"></i>      <?= t('Infrastructure IT','IT Infrastructure') ?></a></li>
            <li><a href="/services.php#developpement"><i class="fas fa-code"></i>         <?= t('Développement','Development') ?></a></li>
            <li><a href="/services.php#ia"><i class="fas fa-brain"></i>                   <?= t('Solutions IA','AI Solutions') ?></a></li>
            <li><a href="/services.php#conseil"><i class="fas fa-lightbulb"></i>          <?= t('Conseil Digital','Digital Consulting') ?></a></li>
            <li><a href="/services.php#materiel"><i class="fas fa-laptop"></i>            <?= t('Matériel IT','IT Hardware') ?></a></li>
            <li><a href="/services.php#imprimerie"><i class="fas fa-print"></i>           <?= t('Imprimerie','Printing') ?></a></li>
            <li><a href="/services.php#solaire"><i class="fas fa-solar-panel"></i>        <?= t('Énergie Solaire','Solar Energy') ?></a></li>
          </ul>
        </li>
        <li><a href="/formations.php" class="nav-link <?= $activePage === 'formations' ? 'active' : '' ?>"><?= t('Formations','Training') ?></a></li>
        <li><a href="/shop.php"       class="nav-link nav-link-shop <?= $activePage === 'shop' ? 'active' : '' ?>"><i class="fas fa-store"></i> <?= t('IgoShop','IgoShop') ?></a></li>
        <li><a href="/blog.php"       class="nav-link <?= $activePage === 'blog'       ? 'active' : '' ?>"><?= t('Blog','Blog') ?></a></li>
        <li><a href="/contact.php"    class="nav-link <?= $activePage === 'contact'    ? 'active' : '' ?>"><?= t('Contact','Contact') ?></a></li>
      </ul>
    </nav>

    <!-- CTA + Hamburger -->
    <div class="navbar-actions">
      <a href="/contact.php" class="btn btn-primary btn-sm navbar-cta">
        <?= t('Devis gratuit','Free Quote') ?>
      </a>
      <button class="hamburger" id="hamburger" aria-label="Menu" aria-expanded="false" aria-controls="navMenu">
        <span></span><span></span><span></span>
      </button>
    </div>

  </div>
</header>
