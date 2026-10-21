<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$lang = current_lang();
$seoData = seo('home');

$pageTitle  = $seoData['meta_title']    ?? setting('site_nom') . ' -- ' . setting('site_slogan');
$metaDesc   = $seoData['meta_desc']     ?? '';
$activePage = 'home';

// Chargement des données
try {
    // Derniers articles
    $stmt = db()->query("SELECT id, titre, slug, extrait, image, categorie, published_at FROM blog_posts WHERE statut='publie' ORDER BY published_at DESC LIMIT 3");
    $latestPosts = $stmt->fetchAll();

    // Formations publiées (3 premières)
    $stmt = db()->query("SELECT * FROM formations WHERE est_publie=1 ORDER BY ordre ASC LIMIT 3");
    $formations = $stmt->fetchAll();

    // Projets
    $stmt = db()->query("SELECT * FROM projets WHERE est_publie=1 ORDER BY ordre ASC LIMIT 4");
    $projets = $stmt->fetchAll();

    // Stats
    $stats = [
        'clients'    => setting('stat_clients', '50'),
        'projets'    => setting('stat_projets', '80'),
        'formations' => setting('stat_formations', '20'),
        'stagiaires' => setting('stat_stagiaires', '200'),
    ];
} catch (Exception $e) {
    $latestPosts = $formations = $projets = [];
    $stats = ['clients'=>'50','projets'=>'80','formations'=>'20','stagiaires'=>'200'];
}

$schemaJson = json_encode([
  "@context" => "https://schema.org",
  "@type"    => "WebSite",
  "name"     => "IgoTech",
  "url"      => SITE_URL,
  "potentialAction" => [
    "@type"       => "SearchAction",
    "target"      => SITE_URL . "/blog.php?q={search_term_string}",
    "query-input" => "required name=search_term_string"
  ]
]);

require_once 'includes/header.php';
?>

<!-- ======== HERO ======== -->
<section class="hero" aria-label="<?= t('Bienvenue sur IgoTech', 'Welcome to IgoTech') ?>">
  <div class="hero-shapes" aria-hidden="true">
    <div class="shape s1"></div>
    <div class="shape s2"></div>
    <div class="shape s3"></div>
  </div>
  <div class="container">
    <div class="hero-inner">
      <div class="hero-content" data-aos="fade-right">
        <div class="hero-badge">
          <i class="fas fa-rocket"></i>
          <?= t('Agence Digitale · N\'Djamena, Tchad', 'Digital Agency · N\'Djamena, Chad') ?>
        </div>
        <h1>
          <?= t('Votre partenaire <span class="highlight">idéal</span> pour la transformation digitale', 'Your <span class="highlight">ideal partner</span> for digital transformation') ?>
        </h1>
        <p class="hero-sub">
          <?= t(
            'Solutions IT sur mesure, développement de plateformes numériques, intelligence artificielle et formations tech pour propulser votre croissance.',
            'Custom IT solutions, digital platform development, artificial intelligence and tech training to drive your growth.'
          ) ?>
        </p>
        <div class="hero-actions">
          <a href="/services.php" class="btn btn-accent btn-lg">
            <i class="fas fa-th-large"></i> <?= t('Nos Services', 'Our Services') ?>
          </a>
          <a href="/contact.php" class="btn btn-outline-white btn-lg">
            <i class="fas fa-comments"></i> <?= t('Parlons de votre projet', 'Talk about your project') ?>
          </a>
        </div>
        <div class="hero-stats">
          <?php
          $heroStats = [
            ['num' => $stats['clients'],    'suffix' => '+', 'label' => t('Clients satisfaits', 'Satisfied clients')],
            ['num' => $stats['projets'],    'suffix' => '+', 'label' => t('Projets livrés', 'Projects delivered')],
            ['num' => $stats['stagiaires'], 'suffix' => '+', 'label' => t('Jeunes formés', 'Youth trained')],
          ];
          foreach ($heroStats as $s): ?>
          <div class="hero-stat">
            <div class="hero-stat-num">
              <span data-count="<?= (int)$s['num'] ?>">0</span><?= $s['suffix'] ?>
            </div>
            <div class="hero-stat-label"><?= $s['label'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="hero-visual" data-aos="fade-left" data-aos-delay="200" aria-hidden="true">
        <div class="hero-media-wrap">
          <video class="hero-video" autoplay muted loop playsinline>
            <source src="/assets/videos/hero-visual.webm" type="video/webm">
            <source src="/assets/videos/hero-visual.mp4"  type="video/mp4">
            <!-- Fallback GIF si pas de vidéo -->
            <img class="hero-gif" src="/assets/images/hero-visual.gif" alt="" width="520" height="480">
          </video>
          <div class="hero-video-glow"></div>
          <div class="hero-video-ring"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======== PARTENAIRES ======== -->
<section class="partners-section" aria-label="<?= t('Ils nous font confiance', 'They trust us') ?>">
  <div class="container">
    <p class="partners-title"><?= t('Ils nous font confiance', 'They trust us') ?></p>
    <div class="partners-logos">
      <img src="/assets/images/partners/partner1.png" alt="Archidiocèse de N'Djamena" loading="lazy">
      <img src="/assets/images/partners/partner2.png" alt="Kotar Ya Marie" loading="lazy">
      <img src="/assets/images/partners/partner3.png" alt="Partenaire 3" loading="lazy">
      <img src="/assets/images/partners/partner4.png" alt="Partenaire 4" loading="lazy">
    </div>
  </div>
</section>

<!-- ======== SERVICES ======== -->
<section class="section bg-light" id="services" aria-labelledby="services-title">
  <div class="container">
    <div class="section-header center" data-aos="fade-up">
      <span class="section-label"><?= t('Ce que nous faisons', 'What we do') ?></span>
      <h2 class="section-title" id="services-title">
        <?= t('Des <span>solutions digitales</span> sur mesure', 'Tailored <span>digital solutions</span>') ?>
      </h2>
      <p class="section-subtitle">
        <?= t(
          'De l\'infrastructure à l\'IA, nous couvrons l\'ensemble de vos besoins technologiques avec expertise et innovation.',
          'From infrastructure to AI, we cover all your technological needs with expertise and innovation.'
        ) ?>
      </p>
    </div>

    <div class="services-grid">
      <?php
      $services = [
        ['icon'=>'fa-server',    'color'=>'', 'title'=>t('Infrastructure IT','IT Infrastructure'), 'desc'=>t('Conception, déploiement et maintenance de réseaux, serveurs et systèmes informatiques sécurisés.','Design, deployment and maintenance of secure networks, servers and IT systems.'), 'link'=>'/services.php#infrastructure'],
        ['icon'=>'fa-code',      'color'=>'', 'title'=>t('Développement Web & Apps','Web & App Development'), 'desc'=>t('Sites institutionnels, applications web métier, plateformes e-commerce adaptées à vos besoins.','Institutional websites, business web applications, e-commerce platforms tailored to your needs.'), 'link'=>'/services.php#developpement'],
        ['icon'=>'fa-brain',     'color'=>'', 'title'=>t('Solutions IA & Data','AI & Data Solutions'), 'desc'=>t('Automatisation, analyse de données, chatbots et systèmes intelligents pour optimiser vos processus.','Automation, data analytics, chatbots and intelligent systems to optimize your processes.'), 'link'=>'/services.php#ia'],
        ['icon'=>'fa-lightbulb', 'color'=>'', 'title'=>t('Conseil en Transformation Digitale','Digital Transformation Consulting'), 'desc'=>t('Audit digital, élaboration de stratégies numériques et accompagnement dans votre transition digitale.','Digital audit, development of digital strategies and support in your digital transition.'), 'link'=>'/services.php#conseil'],
        ['icon'=>'fa-laptop',       'color'=>'', 'title'=>t('Vente de Matériel IT','IT Hardware Sales'),         'desc'=>t('Fourniture d\'équipements informatiques de qualité : ordinateurs, réseaux, accessoires et périphériques.','Supply of quality IT equipment: computers, networks, accessories and peripherals.'),                           'link'=>'/services.php#materiel'],
        ['icon'=>'fa-graduation-cap','color'=>'', 'title'=>t('Formations & Mentorat','Training & Mentoring'),      'desc'=>t('Programmes de formation en développement web, IA, marketing digital et entrepreneuriat technologique.','Training programs in web development, AI, digital marketing and tech entrepreneurship.'),                       'link'=>'/formations.php'],
        ['icon'=>'fa-print',         'color'=>'', 'title'=>t('Imprimerie Professionnelle','Professional Printing'), 'desc'=>t('Flyers, affiches, banderoles, cartes de visite, brochures et impression grand format pour votre communication.','Flyers, posters, banners, business cards, brochures and large-format printing for your communication.'),   'link'=>'/services.php#imprimerie'],
        ['icon'=>'fa-solar-panel',   'color'=>'', 'title'=>t('Énergie Solaire','Solar Energy'),                    'desc'=>t('Études de faisabilité, dimensionnement, audit énergétique et caractérisation de systèmes photovoltaïques.','Feasibility studies, sizing, energy audits and photovoltaic system characterization.'),                        'link'=>'/services.php#solaire'],
      ];
      foreach ($services as $i => $svc): ?>
      <article class="service-card" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
        <div class="service-icon" aria-hidden="true"><i class="fas <?= $svc['icon'] ?>"></i></div>
        <h3><?= $svc['title'] ?></h3>
        <p><?= $svc['desc'] ?></p>
        <a href="<?= $svc['link'] ?>" class="service-link">
          <?= t('En savoir plus', 'Learn more') ?> <i class="fas fa-arrow-right fa-xs"></i>
        </a>
      </article>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-lg">
      <a href="/services.php" class="btn btn-primary"><?= t('Voir tous nos services', 'See all our services') ?></a>
    </div>
  </div>
</section>

<!-- ======== À PROPOS ======== -->
<section class="section" aria-labelledby="about-title">
  <div class="container">
    <div class="about-grid">
      <div class="about-img-wrap" data-aos="fade-right">
        <video class="about-video" autoplay muted loop playsinline>
          <source src="/assets/videos/about.webm" type="video/webm">
          <source src="/assets/videos/about.mp4"  type="video/mp4">
          <img class="about-gif" src="/assets/images/about.gif" alt="<?= t('Équipe IgoTech', 'IgoTech Team') ?>">
        </video>
        <div class="about-badge" aria-label="<?= $stats['projets'] . '+ ' . t('projets réalisés', 'projects delivered') ?>">
          <span class="big"><?= $stats['projets'] ?>+</span>
          <span class="small"><?= t('Projets réalisés', 'Projects delivered') ?></span>
        </div>
      </div>
      <div class="about-text" data-aos="fade-left" data-aos-delay="100">
        <span class="section-label"><?= t('Qui sommes-nous', 'Who we are') ?></span>
        <h2 class="section-title" id="about-title">
          <?= t('IgoTech, au cœur de la <span>révolution digitale</span> au Tchad', 'IgoTech, at the heart of the <span>digital revolution</span> in Chad') ?>
        </h2>
        <p><?= htmlspecialchars(setting('about_texte', '')) ?></p>
        <ul class="checklist mt-md">
          <li><i class="fas fa-check-circle"></i> <?= t('Expertise en IT & Intelligence Artificielle', 'IT & Artificial Intelligence Expertise') ?></li>
          <li><i class="fas fa-check-circle"></i> <?= t('Solutions sur mesure adaptées au contexte africain', 'Tailored solutions adapted to the African context') ?></li>
          <li><i class="fas fa-check-circle"></i> <?= t('Engagement pour la formation des jeunes entrepreneurs', 'Commitment to training young entrepreneurs') ?></li>
          <li><i class="fas fa-check-circle"></i> <?= t('Approche client-first et innovation constante', 'Client-first approach and constant innovation') ?></li>
        </ul>
        <div class="d-flex gap-md mt-md" style="flex-wrap:wrap">
          <a href="/about.php"   class="btn btn-primary"><?= t('Notre histoire', 'Our story') ?></a>
          <a href="/contact.php" class="btn btn-outline"><?= t('Nous contacter', 'Contact us') ?></a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======== STATS ======== -->
<section class="stats-section" aria-label="<?= t('Chiffres clés', 'Key figures') ?>">
  <div class="container">
    <div class="stats-grid">
      <?php
      $kpis = [
        ['num'=>$stats['clients'],    'suffix'=>'+', 'label'=>t('Clients satisfaits','Satisfied clients'),  'icon'=>'fa-users'],
        ['num'=>$stats['projets'],    'suffix'=>'+', 'label'=>t('Projets réalisés','Projects completed'),   'icon'=>'fa-briefcase'],
        ['num'=>$stats['formations'], 'suffix'=>'+', 'label'=>t('Formations lancées','Training launched'),  'icon'=>'fa-chalkboard-teacher'],
        ['num'=>$stats['stagiaires'], 'suffix'=>'+', 'label'=>t('Jeunes formés','Youth trained'),           'icon'=>'fa-user-graduate'],
      ];
      foreach ($kpis as $kpi): ?>
      <div class="stat-item" data-aos="zoom-in">
        <i class="fas <?= $kpi['icon'] ?>" style="font-size:1.8rem;opacity:.5;margin-bottom:10px" aria-hidden="true"></i>
        <div class="stat-number">
          <span data-count="<?= (int)$kpi['num'] ?>">0</span><?= $kpi['suffix'] ?>
        </div>
        <div class="stat-label"><?= $kpi['label'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ======== FORMATIONS ======== -->
<?php if ($formations): ?>
<section class="section bg-light" aria-labelledby="formations-title">
  <div class="container">
    <div class="section-header center" data-aos="fade-up">
      <span class="section-label"><?= t('Apprendre & Progresser', 'Learn & Grow') ?></span>
      <h2 class="section-title" id="formations-title">
        <?= t('Nos <span>Formations</span> Tech', 'Our <span>Tech</span> Training Programs') ?>
      </h2>
      <p class="section-subtitle">
        <?= t(
          'Des programmes professionnels conçus pour les jeunes tchadiens souhaitant exceller dans le numérique.',
          'Professional programs designed for Chadian youth who want to excel in digital technology.'
        ) ?>
      </p>
    </div>
    <div class="formations-grid">
      <?php foreach ($formations as $i => $f): ?>
      <article class="formation-card" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
        <div class="formation-img">
          <?php if ($f['image']): ?>
          <img src="<?= htmlspecialchars($f['image']) ?>" alt="<?= htmlspecialchars($f['titre']) ?>" loading="lazy">
          <?php else: ?>
          <div style="height:100%;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center">
            <i class="fas fa-chalkboard-teacher" style="font-size:3rem;color:rgba(255,255,255,.3)"></i>
          </div>
          <?php endif; ?>
          <div class="formation-cat">
            <span class="badge badge-primary"><?= htmlspecialchars($f['categorie'] ?? '') ?></span>
          </div>
        </div>
        <div class="formation-body">
          <h3><?= htmlspecialchars($lang === 'en' && $f['titre_en'] ? $f['titre_en'] : $f['titre']) ?></h3>
          <p><?= excerpt(htmlspecialchars($lang === 'en' && $f['description_en'] ? $f['description_en'] : $f['description']), 100) ?></p>
          <div class="formation-meta">
            <?php if ($f['duree']): ?><span><i class="fas fa-clock"></i> <?= htmlspecialchars($f['duree']) ?></span><?php endif; ?>
            <?php if ($f['niveau']): ?><span><i class="fas fa-signal"></i> <?= htmlspecialchars(ucfirst($f['niveau'])) ?></span><?php endif; ?>
            <?php if ($f['places']): ?><span><i class="fas fa-users"></i> <?= (int)$f['places'] ?> <?= t('places','seats') ?></span><?php endif; ?>
          </div>
        </div>
        <div class="formation-footer">
          <?php if ($f['prix']): ?>
          <span class="formation-price"><?= price_format((float)$f['prix']) ?></span>
          <?php else: ?>
          <span class="formation-price"><?= t('Sur devis', 'On quote') ?></span>
          <?php endif; ?>
          <a href="/formations.php#inscription" class="btn btn-primary btn-sm">
            <?= t("S'inscrire", 'Register') ?>
          </a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-lg">
      <a href="/formations.php" class="btn btn-outline"><?= t('Toutes nos formations', 'All training programs') ?></a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ======== PROCESSUS ======== -->
<section class="section" aria-labelledby="process-title">
  <div class="container">
    <div class="section-header center" data-aos="fade-up">
      <span class="section-label"><?= t('Comment ça marche', 'How it works') ?></span>
      <h2 class="section-title" id="process-title">
        <?= t('Notre <span>processus</span> de travail', 'Our <span>work</span> process') ?>
      </h2>
    </div>
    <div class="steps-grid">
      <?php
      $steps = [
        ['n'=>'01','title'=>t('Analyse & Audit','Analysis & Audit'),'desc'=>t('Nous analysons vos besoins, contraintes et objectifs pour proposer la meilleure solution.','We analyze your needs, constraints and objectives to propose the best solution.')],
        ['n'=>'02','title'=>t('Conception & Stratégie','Design & Strategy'),'desc'=>t('Élaboration d\'une stratégie digitale sur mesure et conception des maquettes du projet.','Development of a tailored digital strategy and project mockup design.')],
        ['n'=>'03','title'=>t('Développement & Déploiement','Development & Deployment'),'desc'=>t('Développement agile, tests rigoureux et déploiement sécurisé de votre solution.','Agile development, rigorous testing and secure deployment of your solution.')],
        ['n'=>'04','title'=>t('Suivi & Support','Monitoring & Support'),'desc'=>t('Accompagnement continu, maintenance et évolution de votre solution dans le temps.','Ongoing support, maintenance and evolution of your solution over time.')],
      ];
      foreach ($steps as $i => $step): ?>
      <div class="step-card" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
        <div class="step-num" aria-hidden="true"><?= $step['n'] ?></div>
        <h4><?= $step['title'] ?></h4>
        <p><?= $step['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ======== BLOG ======== -->
<?php if ($latestPosts): ?>
<section class="section bg-light" aria-labelledby="blog-title">
  <div class="container">
    <div class="section-header center" data-aos="fade-up">
      <span class="section-label"><?= t('Actualités', 'News') ?></span>
      <h2 class="section-title" id="blog-title">
        <?= t('Dernières <span>actualités</span>', 'Latest <span>news</span>') ?>
      </h2>
    </div>
    <div class="blog-grid">
      <?php foreach ($latestPosts as $i => $post): ?>
      <article class="blog-card" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
        <div class="blog-img">
          <img src="<?= $post['image'] ? htmlspecialchars($post['image']) : '/assets/images/blog-placeholder.jpg' ?>"
               alt="<?= htmlspecialchars($post['titre']) ?>" loading="lazy">
        </div>
        <div class="blog-body">
          <div class="blog-meta">
            <span><i class="fas fa-calendar"></i> <?= format_date($post['published_at'] ?? $post['created_at']) ?></span>
            <?php if ($post['categorie']): ?>
            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($post['categorie']) ?></span>
            <?php endif; ?>
          </div>
          <h3><a href="/blog-detail.php?slug=<?= urlencode($post['slug']) ?>"><?= htmlspecialchars($post['titre']) ?></a></h3>
          <p><?= excerpt($post['extrait'] ?? '', 110) ?></p>
          <a href="/blog-detail.php?slug=<?= urlencode($post['slug']) ?>" class="blog-read-more">
            <?= t('Lire la suite', 'Read more') ?> <i class="fas fa-arrow-right fa-xs"></i>
          </a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-lg">
      <a href="/blog.php" class="btn btn-outline"><?= t('Tous les articles', 'All articles') ?></a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ======== CTA ======== -->
<section class="cta-section" aria-labelledby="cta-title">
  <div class="container" data-aos="zoom-in">
    <h2 id="cta-title"><?= t('Prêt à transformer votre entreprise ?', 'Ready to transform your business?') ?></h2>
    <p>
      <?= t(
        'Contactez-nous dès aujourd\'hui pour discuter de votre projet et obtenir une consultation gratuite.',
        'Contact us today to discuss your project and get a free consultation.'
      ) ?>
    </p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
      <a href="/contact.php"     class="btn btn-white btn-lg"><?= t('Démarrer un projet', 'Start a project') ?></a>
      <a href="/formations.php"  class="btn btn-outline-white btn-lg"><?= t('Voir les formations', 'See training') ?></a>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
