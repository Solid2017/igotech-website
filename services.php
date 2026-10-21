<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$lang = current_lang();
$seoData = seo('services');

$pageTitle  = $seoData['meta_title'] ?? t('Nos Services | IT, IA, Imprimerie, Énergie Solaire -- IgoTech', 'Our Services | IT, AI, Printing, Solar Energy -- IgoTech');
$metaDesc   = $seoData['meta_desc']  ?? t('Infrastructure IT, développement web, solutions IA, imprimerie professionnelle, énergie solaire et conseil digital au Tchad.', 'IT infrastructure, web development, AI solutions, professional printing, solar energy and digital consulting in Chad.');
$activePage = 'services';

require_once 'includes/header.php';
?>

<!-- ======== PAGE HERO ======== -->
<section class="page-hero">
  <video class="page-hero-video-bg" autoplay muted loop playsinline>
    <source src="/assets/videos/page-hero-bg.webm" type="video/webm">
    <source src="/assets/videos/page-hero-bg.mp4"  type="video/mp4">
  </video>
  <div class="container page-hero-inner">
    <h1><?= t('Nos Services', 'Our Services') ?></h1>
    <p><?= t('Des solutions digitales complètes pour propulser votre croissance.', 'Complete digital solutions to drive your growth.') ?></p>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="/"><?= t('Accueil', 'Home') ?></a>
      <span>›</span>
      <span aria-current="page"><?= t('Services', 'Services') ?></span>
    </nav>
  </div>
</section>

<!-- ======== INTRO ======== -->
<section class="section-sm">
  <div class="container" style="max-width:760px;text-align:center" data-aos="fade-up">
    <p style="font-size:1.08rem;line-height:1.9;color:var(--text-light)">
      <?= t(
        'IgoTech vous accompagne à chaque étape de votre transformation digitale grâce à une gamme complète de services IT, adaptée aux réalités du marché tchadien et africain.',
        'IgoTech supports you at every step of your digital transformation with a complete range of IT services, adapted to the realities of the Chadian and African market.'
      ) ?>
    </p>
  </div>
</section>

<?php
$services = [
  [
    'id'       => 'infrastructure',
    'icon'     => 'fa-server',
    'title_fr' => 'Infrastructure IT & Réseaux',
    'title_en' => 'IT Infrastructure & Networks',
    'desc_fr'  => 'Nous concevons, déployons et maintenons des infrastructures informatiques robustes et sécurisées pour votre organisation. De la mise en place de réseaux locaux et étendus à la gestion de serveurs et systèmes cloud, nous assurons la continuité et la fiabilité de vos systèmes.',
    'desc_en'  => 'We design, deploy and maintain robust and secure IT infrastructures for your organization. From setting up local and wide area networks to managing servers and cloud systems, we ensure the continuity and reliability of your systems.',
    'items_fr' => ['Installation et configuration de réseaux LAN/WAN','Administration de serveurs Windows/Linux','Solutions de sauvegarde et reprise après sinistre','Sécurité réseau et pare-feux','Surveillance et maintenance proactive','Virtualisation et cloud'],
    'items_en' => ['LAN/WAN network installation and configuration','Windows/Linux server administration','Backup and disaster recovery solutions','Network security and firewalls','Proactive monitoring and maintenance','Virtualization and cloud'],
    'bg'       => 'var(--gray-100)',
  ],
  [
    'id'       => 'developpement',
    'icon'     => 'fa-code',
    'title_fr' => 'Développement Web & Applications',
    'title_en' => 'Web & Application Development',
    'desc_fr'  => 'De la conception à la mise en production, nous développons des solutions web et mobiles sur mesure adaptées à vos besoins métier. Sites institutionnels, applications de gestion, plateformes e-commerce ou portails clients, notre équipe livre des produits performants et évolutifs.',
    'desc_en'  => 'From design to production, we develop custom web and mobile solutions adapted to your business needs. Institutional websites, management applications, e-commerce platforms or client portals, our team delivers high-performance and scalable products.',
    'items_fr' => ['Sites web institutionnels et vitrines','Applications web métier sur mesure','Plateformes e-commerce','Applications mobiles (Android/iOS)','API & intégrations tierces','CMS personnalisés'],
    'items_en' => ['Institutional and showcase websites','Custom business web applications','E-commerce platforms','Mobile apps (Android/iOS)','APIs & third-party integrations','Custom CMS'],
    'bg'       => 'var(--white)',
  ],
  [
    'id'       => 'ia',
    'icon'     => 'fa-brain',
    'title_fr' => 'Solutions IA & Data Analytics',
    'title_en' => 'AI & Data Analytics Solutions',
    'desc_fr'  => 'Exploitez la puissance de l\'intelligence artificielle et de l\'analyse de données pour optimiser vos processus, prendre de meilleures décisions et créer de nouvelles opportunités. Nous développons des solutions IA adaptées à votre secteur d\'activité.',
    'desc_en'  => 'Harness the power of artificial intelligence and data analytics to optimize your processes, make better decisions and create new opportunities. We develop AI solutions tailored to your sector.',
    'items_fr' => ['Analyse et visualisation de données','Tableaux de bord intelligents (BI)','Chatbots et assistants virtuels','Automatisation des processus (RPA)','Systèmes de recommandation','Machine Learning sur mesure'],
    'items_en' => ['Data analysis and visualization','Intelligent dashboards (BI)','Chatbots and virtual assistants','Process automation (RPA)','Recommendation systems','Custom machine learning'],
    'bg'       => 'var(--gray-100)',
  ],
  [
    'id'       => 'conseil',
    'icon'     => 'fa-lightbulb',
    'title_fr' => 'Conseil en Transformation Digitale',
    'title_en' => 'Digital Transformation Consulting',
    'desc_fr'  => 'Nos consultants expérimentés vous guident dans votre transformation numérique. Nous réalisons des audits complets de votre environnement digital, élaborons des feuilles de route stratégiques et vous accompagnons dans la conduite du changement.',
    'desc_en'  => 'Our experienced consultants guide you through your digital transformation. We conduct comprehensive audits of your digital environment, develop strategic roadmaps and support you in change management.',
    'items_fr' => ['Audit digital complet','Élaboration de stratégie numérique','Conduite du changement','Formation des équipes','Accompagnement DSI','Optimisation des processus'],
    'items_en' => ['Comprehensive digital audit','Digital strategy development','Change management','Team training','CIO advisory','Process optimization'],
    'bg'       => 'var(--white)',
  ],
  [
    'id'       => 'materiel',
    'icon'     => 'fa-laptop',
    'title_fr' => 'Vente de Matériel Informatique',
    'title_en' => 'IT Hardware Sales',
    'desc_fr'  => 'Nous fournissons du matériel informatique de qualité, sélectionné parmi les meilleures marques mondiales. Ordinateurs, serveurs, équipements réseau, périphériques et accessoires : nous vous proposons les outils adaptés à votre budget et à vos besoins.',
    'desc_en'  => 'We supply quality IT hardware, selected from the world\'s best brands. Computers, servers, network equipment, peripherals and accessories: we offer tools adapted to your budget and needs.',
    'items_fr' => ['Ordinateurs portables et fixes','Serveurs et NAS','Équipements réseau (switches, routeurs)','Imprimantes et multifonctions','Accessoires et périphériques','Systèmes de vidéosurveillance'],
    'items_en' => ['Laptops and desktops','Servers and NAS','Network equipment (switches, routers)','Printers and multifunction devices','Accessories and peripherals','Video surveillance systems'],
    'bg'       => 'var(--gray-100)',
  ],
  [
    'id'       => 'imprimerie',
    'icon'     => 'fa-print',
    'title_fr' => 'Imprimerie & Impression Professionnelle',
    'title_en' => 'Printing & Professional Print Services',
    'desc_fr'  => 'IgoTech met à votre disposition un service d\'imprimerie professionnel complet pour valoriser votre image de marque. Flyers, affiches, banderoles, cartes de visite, brochures, kakémonos et papeterie d\'entreprise — nous imprimons vos supports de communication avec précision et qualité.',
    'desc_en'  => 'IgoTech offers a complete professional printing service to enhance your brand image. Flyers, posters, banners, business cards, brochures, roll-ups and corporate stationery — we print your communication materials with precision and quality.',
    'items_fr' => ['Flyers, affiches et dépliants','Cartes de visite et papeterie d\'entreprise','Banderoles et kakémonos grand format','Brochures et catalogues produits','Impression sur textile (t-shirts, uniformes)','Packaging et étiquettes personnalisées'],
    'items_en' => ['Flyers, posters and leaflets','Business cards and corporate stationery','Banners and roll-up displays','Brochures and product catalogues','Textile printing (t-shirts, uniforms)','Custom packaging and labels'],
    'bg'       => 'var(--white)',
  ],
  [
    'id'       => 'solaire',
    'icon'     => 'fa-solar-panel',
    'title_fr' => 'Études & Caractérisation en Énergie Solaire',
    'title_en' => 'Solar Energy Studies & Characterization',
    'desc_fr'  => 'Fort de notre expertise en énergie renouvelable, IgoTech réalise des études de faisabilité, des audits énergétiques et des caractérisations de systèmes photovoltaïques pour particuliers, entreprises et organisations. Nous vous aidons à dimensionner votre installation solaire, évaluer ses performances et optimiser votre investissement dans un contexte d\'ensoleillement exceptionnel comme le Tchad.',
    'desc_en'  => 'Drawing on our renewable energy expertise, IgoTech carries out feasibility studies, energy audits and characterization of photovoltaic systems for individuals, businesses and organizations. We help you size your solar installation, assess its performance and optimize your investment in an exceptionally sunny environment like Chad.',
    'items_fr' => ['Études de faisabilité et dimensionnement solaire','Audit énergétique et bilan de consommation','Caractérisation et test de panneaux photovoltaïques','Conception de systèmes hybrides (solaire + réseau)','Suivi et monitoring des performances','Formation et transfert de compétences solaires'],
    'items_en' => ['Feasibility studies and solar system sizing','Energy audit and consumption assessment','Photovoltaic panel characterization and testing','Hybrid system design (solar + grid)','Performance monitoring and tracking','Solar skills training and transfer'],
    'bg'       => 'var(--gray-100)',
  ],
];
?>

<?php foreach ($services as $i => $svc): ?>
<section class="section" id="<?= $svc['id'] ?>" style="background:<?= $svc['bg'] ?>">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;<?= $i % 2 !== 0 ? 'direction:rtl' : '' ?>">
      <div data-aos="<?= $i % 2 === 0 ? 'fade-right' : 'fade-left' ?>">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:72px;height:72px;border-radius:var(--radius-lg);background:linear-gradient(135deg,var(--primary),var(--primary-light));margin-bottom:20px;box-shadow:0 4px 20px rgba(5,48,103,.25)">
          <i class="fas <?= $svc['icon'] ?>" style="color:#fff;font-size:1.8rem"></i>
        </div>
        <span class="section-label"><?= t('Notre service', 'Our service') ?></span>
        <h2 class="section-title"><?= t($svc['title_fr'], $svc['title_en']) ?></h2>
        <p style="line-height:1.9;color:var(--text-light);margin-bottom:24px"><?= t($svc['desc_fr'], $svc['desc_en']) ?></p>
        <ul class="checklist">
          <?php
          $items = $lang === 'en' ? $svc['items_en'] : $svc['items_fr'];
          foreach ($items as $item): ?>
          <li><i class="fas fa-check-circle"></i> <?= htmlspecialchars($item) ?></li>
          <?php endforeach; ?>
        </ul>
        <div style="margin-top:24px">
          <a href="/contact.php?sujet=<?= urlencode($lang === 'en' ? $svc['title_en'] : $svc['title_fr']) ?>"
             class="btn btn-primary">
            <?= t('Demander un devis', 'Request a quote') ?>
          </a>
        </div>
      </div>
      <div data-aos="<?= $i % 2 === 0 ? 'fade-left' : 'fade-right' ?>" data-aos-delay="100"
           style="<?= $i % 2 !== 0 ? 'direction:ltr' : '' ?>">
        <div style="background:linear-gradient(135deg,<?= $i % 2 === 0 ? 'var(--primary),var(--primary-light)' : 'var(--accent),#c1121f' ?>);border-radius:var(--radius-xl);padding:40px;color:#fff;box-shadow:var(--shadow-xl)">
          <h3 style="color:#fff;margin-bottom:20px;font-size:1.1rem"><?= t('Pourquoi choisir IgoTech ?', 'Why choose IgoTech?') ?></h3>
          <?php foreach (['Expertise reconnue', 'Solutions sur mesure', 'Support 24/7', 'Prix compétitifs', 'Expérience africaine'] as $r): ?>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
            <i class="fas fa-check-circle" style="opacity:.8"></i>
            <span style="font-size:.9rem;opacity:.9"><?= $r ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endforeach; ?>

<!-- ======== CTA ======== -->
<section class="cta-section">
  <div class="container" data-aos="zoom-in">
    <h2><?= t('Un projet en tête ?', 'Have a project in mind?') ?></h2>
    <p><?= t('Contactez-nous pour une consultation gratuite et découvrez comment IgoTech peut transformer votre vision en réalité.', 'Contact us for a free consultation and discover how IgoTech can transform your vision into reality.') ?></p>
    <a href="/contact.php" class="btn btn-white btn-lg">
      <i class="fas fa-comments"></i> <?= t('Consultation gratuite', 'Free consultation') ?>
    </a>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
