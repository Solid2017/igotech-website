<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$lang = current_lang();
$seoData = seo('about');

$pageTitle  = $seoData['meta_title'] ?? t('À propos d\'IgoTech | Agence Digitale au Tchad', 'About IgoTech | Digital Agency in Chad');
$metaDesc   = $seoData['meta_desc']  ?? t('Découvrez IgoTech, notre mission, nos valeurs et notre équipe.', 'Discover IgoTech, our mission, values and team.');
$activePage = 'about';

$stats = [
    'clients'    => setting('stat_clients',    '50'),
    'projets'    => setting('stat_projets',    '80'),
    'formations' => setting('stat_formations', '20'),
    'stagiaires' => setting('stat_stagiaires', '200'),
];

require_once 'includes/header.php';
?>

<!-- ======== PAGE HERO ======== -->
<section class="page-hero">
  <video class="page-hero-video-bg" autoplay muted loop playsinline>
    <source src="/assets/videos/page-hero-bg.webm" type="video/webm">
    <source src="/assets/videos/page-hero-bg.mp4"  type="video/mp4">
  </video>
  <div class="container page-hero-inner">
    <h1><?= t('À propos d\'IgoTech', 'About IgoTech') ?></h1>
    <p><?= t('Notre mission, nos valeurs et notre engagement pour le digital en Afrique.', 'Our mission, values and commitment to digital in Africa.') ?></p>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="/"><?= t('Accueil', 'Home') ?></a>
      <span>›</span>
      <span aria-current="page"><?= t('À propos', 'About') ?></span>
    </nav>
  </div>
</section>

<!-- ======== MISSION ======== -->
<section class="section">
  <div class="container">
    <div class="about-grid">
      <div class="about-img-wrap" data-aos="fade-right">
        <video class="about-video" autoplay muted loop playsinline>
          <source src="/assets/videos/about.webm" type="video/webm">
          <source src="/assets/videos/about.mp4"  type="video/mp4">
          <img class="about-gif" src="/assets/images/about.gif" alt="<?= t('L\'équipe IgoTech', 'IgoTech Team') ?>">
        </video>
        <div class="about-badge">
          <span class="big"><?= $stats['projets'] ?>+</span>
          <span class="small"><?= t('Projets réalisés', 'Projects delivered') ?></span>
        </div>
      </div>
      <div data-aos="fade-left" data-aos-delay="100">
        <span class="section-label"><?= t('Notre histoire', 'Our story') ?></span>
        <h2 class="section-title"><?= t('Qui sommes-<span>nous</span> ?', 'Who are <span>we</span>?') ?></h2>
        <p style="line-height:1.9;margin-bottom:16px">
          <?= t(
            'IgoTech est une agence digitale dynamique basée à N\'Djamena, au Tchad. Fondée avec la vision de devenir le catalyseur de la transformation numérique en Afrique centrale, nous accompagnons entreprises, organisations et jeunes entrepreneurs dans leur voyage vers le digital.',
            'IgoTech is a dynamic digital agency based in N\'Djamena, Chad. Founded with the vision of becoming the catalyst for digital transformation in Central Africa, we support businesses, organizations and young entrepreneurs on their journey to digital.'
          ) ?>
        </p>
        <p style="line-height:1.9;color:var(--text-light)">
          <?= t(
            'Notre approche repose sur trois piliers fondamentaux : l\'innovation technologique, les solutions sur mesure adaptées au contexte africain, et l\'autonomisation des jeunes talenteux du continent.',
            'Our approach rests on three fundamental pillars: technological innovation, tailored solutions adapted to the African context, and empowering talented young people on the continent.'
          ) ?>
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:28px">
          <?php
          $vals = [
            ['icon'=>'fa-bullseye',    'title'=>t('Mission','Mission'),   'text'=>t('Accompagner la transformation digitale au Tchad et en Afrique.','Supporting digital transformation in Chad and Africa.')],
            ['icon'=>'fa-eye',         'title'=>t('Vision','Vision'),     'text'=>t('Devenir le leader de l\'innovation digitale en Afrique centrale.','Become the leader of digital innovation in Central Africa.')],
            ['icon'=>'fa-star',        'title'=>t('Valeurs','Values'),    'text'=>t('Innovation, excellence, intégrité et impact social durable.','Innovation, excellence, integrity and lasting social impact.')],
            ['icon'=>'fa-hands-helping','title'=>t('Engagement','Commitment'),'text'=>t('Formation et mentorat des jeunes entrepreneurs technologiques.','Training and mentoring of young tech entrepreneurs.')],
          ];
          foreach ($vals as $v): ?>
          <div style="padding:16px;background:var(--gray-100);border-radius:var(--radius);border-left:3px solid var(--primary)">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
              <i class="fas <?= $v['icon'] ?>" style="color:var(--primary)"></i>
              <strong style="font-size:.9rem"><?= $v['title'] ?></strong>
            </div>
            <p style="font-size:.84rem;color:var(--text-light);margin:0;line-height:1.6"><?= $v['text'] ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======== STATS ======== -->
<section class="stats-section" aria-label="<?= t('Nos chiffres', 'Our figures') ?>">
  <div class="container">
    <div class="stats-grid">
      <?php
      $kpis = [
        ['num'=>$stats['clients'],    'suffix'=>'+', 'label'=>t('Clients satisfaits','Satisfied clients')],
        ['num'=>$stats['projets'],    'suffix'=>'+', 'label'=>t('Projets réalisés','Projects completed')],
        ['num'=>$stats['formations'], 'suffix'=>'+', 'label'=>t('Formations lancées','Training programs')],
        ['num'=>$stats['stagiaires'], 'suffix'=>'+', 'label'=>t('Jeunes formés','Youth trained')],
      ];
      foreach ($kpis as $k): ?>
      <div class="stat-item" data-aos="zoom-in">
        <div class="stat-number"><span data-count="<?= (int)$k['num'] ?>">0</span><?= $k['suffix'] ?></div>
        <div class="stat-label"><?= $k['label'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ======== SERVICES OFFERTS ======== -->
<section class="section bg-light">
  <div class="container">
    <div class="section-header center" data-aos="fade-up">
      <span class="section-label"><?= t('Notre expertise', 'Our expertise') ?></span>
      <h2 class="section-title"><?= t('Ce que nous <span>maîtrisons</span>', 'What we <span>master</span>') ?></h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:24px">
      <?php
      $expertises = [
        ['icon'=>'fa-code',        'label'=>t('Développement Web','Web Development'),     'pct'=>95],
        ['icon'=>'fa-server',      'label'=>t('Infrastructure IT','IT Infrastructure'),   'pct'=>90],
        ['icon'=>'fa-brain',       'label'=>t('IA & Machine Learning','AI & ML'),          'pct'=>80],
        ['icon'=>'fa-chart-bar',   'label'=>t('Data Analytics','Data Analytics'),           'pct'=>85],
        ['icon'=>'fa-mobile-alt',  'label'=>t('Dév. Mobile','Mobile Dev'),                 'pct'=>75],
        ['icon'=>'fa-shield-alt',  'label'=>t('Cybersécurité','Cybersecurity'),             'pct'=>80],
        ['icon'=>'fa-search',      'label'=>'SEO & Marketing Digital',                     'pct'=>88],
        ['icon'=>'fa-chalkboard',  'label'=>t('Formation Tech','Tech Training'),            'pct'=>95],
      ];
      foreach ($expertises as $e): ?>
      <div style="background:var(--white);border-radius:var(--radius);padding:24px;box-shadow:var(--shadow);text-align:center" data-aos="zoom-in">
        <i class="fas <?= $e['icon'] ?>" style="font-size:2rem;color:var(--primary);margin-bottom:12px;display:block"></i>
        <div style="font-weight:700;font-size:.9rem;margin-bottom:10px"><?= $e['label'] ?></div>
        <div style="background:var(--gray-200);border-radius:var(--radius-full);height:6px;overflow:hidden">
          <div style="width:<?= $e['pct'] ?>%;background:linear-gradient(90deg,var(--primary),var(--accent));height:100%;border-radius:var(--radius-full);transition:width 1.5s ease"></div>
        </div>
        <div style="font-size:.78rem;color:var(--text-light);margin-top:6px"><?= $e['pct'] ?>%</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ======== EQUIPE ======== -->
<section class="section">
  <div class="container">
    <div class="section-header center" data-aos="fade-up">
      <span class="section-label"><?= t('Les Personnes', 'The People') ?></span>
      <h2 class="section-title"><?= t('Notre <span>Équipe</span>', 'Our <span>Team</span>') ?></h2>
      <p class="section-subtitle"><?= t('Des experts passionnés dédiés à votre réussite digitale.', 'Passionate experts dedicated to your digital success.') ?></p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:28px">
      <?php
      $team = [
        ['name'=>'Responsable IT', 'role'=>t('Directeur Technique','CTO'), 'img'=>'/assets/images/team/team1.jpg'],
        ['name'=>'Expert Développement', 'role'=>t('Lead Développeur','Lead Developer'), 'img'=>'/assets/images/team/team2.jpg'],
        ['name'=>'Spécialiste IA', 'role'=>t('Ingénieur IA & Data','AI & Data Engineer'), 'img'=>'/assets/images/team/team3.jpg'],
        ['name'=>'Responsable Formation', 'role'=>t('Directeur Pédagogique','Training Director'), 'img'=>'/assets/images/team/team4.jpg'],
      ];
      foreach ($team as $i => $m): ?>
      <div style="text-align:center" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
        <div style="width:120px;height:120px;border-radius:50%;overflow:hidden;margin:0 auto 16px;border:4px solid var(--light);box-shadow:var(--shadow)">
          <img src="<?= $m['img'] ?>" alt="<?= htmlspecialchars($m['name']) ?>"
               style="width:100%;height:100%;object-fit:cover"
               onerror="this.src='/assets/images/team-placeholder.jpg'"
               loading="lazy">
        </div>
        <h4 style="font-size:1rem;margin-bottom:4px"><?= htmlspecialchars($m['name']) ?></h4>
        <p style="color:var(--primary);font-size:.84rem;font-weight:600"><?= htmlspecialchars($m['role']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ======== CTA ======== -->
<section class="cta-section">
  <div class="container" data-aos="zoom-in">
    <h2><?= t('Rejoignez la transformation digitale', 'Join the digital transformation') ?></h2>
    <p><?= t('Que vous soyez une entreprise ou un jeune entrepreneur, IgoTech est votre partenaire idéal.', 'Whether you are a business or a young entrepreneur, IgoTech is your ideal partner.') ?></p>
    <a href="/contact.php" class="btn btn-white btn-lg"><?= t('Contactez-nous', 'Contact us') ?></a>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
