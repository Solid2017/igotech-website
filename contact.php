<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$lang = current_lang();
$seoData = seo('contact');

$pageTitle  = $seoData['meta_title'] ?? t('Contactez IgoTech | N\'Djamena, Tchad', 'Contact IgoTech | N\'Djamena, Chad');
$metaDesc   = $seoData['meta_desc']  ?? t('Contactez IgoTech pour vos projets IT, formations ou toute demande d\'information.', 'Contact IgoTech for your IT projects, training or any information request.');
$activePage = 'contact';

require_once 'includes/header.php';
?>

<!-- ======== PAGE HERO ======== -->
<section class="page-hero">
  <video class="page-hero-video-bg" autoplay muted loop playsinline>
    <source src="/assets/videos/page-hero-bg.webm" type="video/webm">
    <source src="/assets/videos/page-hero-bg.mp4"  type="video/mp4">
  </video>
  <div class="container page-hero-inner">
    <h1><?= t('Contactez-nous', 'Contact Us') ?></h1>
    <p><?= t('Notre équipe est disponible pour répondre à toutes vos questions.', 'Our team is available to answer all your questions.') ?></p>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="/"><?= t('Accueil', 'Home') ?></a>
      <span aria-hidden="true">›</span>
      <span aria-current="page"><?= t('Contact', 'Contact') ?></span>
    </nav>
  </div>
</section>

<!-- ======== CONTACT ======== -->
<section class="section">
  <div class="container">
    <div class="contact-grid">

      <!-- Informations -->
      <div class="contact-info" data-aos="fade-right">
        <span class="section-label"><?= t('Nos coordonnées', 'Our details') ?></span>
        <h2 class="section-title"><?= t('Parlons de <span>votre projet</span>', 'Let\'s talk about <span>your project</span>') ?></h2>
        <p class="mb-0" style="margin-bottom:32px;color:var(--text-light)">
          <?= t(
            'Que vous ayez besoin d\'une solution IT, d\'une formation ou d\'un conseil en transformation digitale, nous sommes là pour vous accompagner.',
            'Whether you need an IT solution, training or digital transformation advice, we are here to support you.'
          ) ?>
        </p>

        <div class="contact-items">
          <div class="contact-item">
            <div class="contact-item-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="contact-item-text">
              <strong><?= t('Adresse', 'Address') ?></strong>
              <span><?= htmlspecialchars(setting('site_adresse','Quartier Amtoukoui, N\'Djamena, Tchad')) ?></span>
            </div>
          </div>
          <div class="contact-item">
            <div class="contact-item-icon"><i class="fas fa-phone-alt"></i></div>
            <div class="contact-item-text">
              <strong><?= t('Téléphone', 'Phone') ?></strong>
              <a href="tel:<?= preg_replace('/\s+/','',setting('site_telephone')) ?>"><?= htmlspecialchars(setting('site_telephone')) ?></a>
              <br><a href="tel:<?= preg_replace('/\s+/','',setting('site_telephone2')) ?>"><?= htmlspecialchars(setting('site_telephone2')) ?></a>
            </div>
          </div>
          <div class="contact-item">
            <div class="contact-item-icon"><i class="fas fa-envelope"></i></div>
            <div class="contact-item-text">
              <strong>Email</strong>
              <a href="mailto:<?= htmlspecialchars(setting('site_email')) ?>"><?= htmlspecialchars(setting('site_email')) ?></a>
            </div>
          </div>
          <div class="contact-item">
            <div class="contact-item-icon"><i class="fab fa-whatsapp"></i></div>
            <div class="contact-item-text">
              <strong>WhatsApp</strong>
              <a href="https://wa.me/<?= htmlspecialchars(setting('site_whatsapp')) ?>" target="_blank" rel="noopener">
                <?= t('Écrire sur WhatsApp', 'Write on WhatsApp') ?>
              </a>
            </div>
          </div>
          <div class="contact-item">
            <div class="contact-item-icon"><i class="fas fa-clock"></i></div>
            <div class="contact-item-text">
              <strong><?= t('Horaires', 'Hours') ?></strong>
              <span><?= t('Lun - Ven : 8h00 - 18h00', 'Mon - Fri: 8:00 AM - 6:00 PM') ?></span>
            </div>
          </div>
        </div>

        <!-- Réseaux sociaux -->
        <div style="margin-top:28px">
          <strong style="display:block;margin-bottom:12px;font-size:.88rem"><?= t('Suivez-nous', 'Follow us') ?></strong>
          <div class="footer-social">
            <a href="<?= htmlspecialchars(setting('site_facebook','#')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="<?= htmlspecialchars(setting('site_linkedin','#')) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            <a href="<?= htmlspecialchars(setting('site_twitter','#')) ?>"  target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-x-twitter"></i></a>
          </div>
        </div>
      </div>

      <!-- Formulaire -->
      <div data-aos="fade-left" data-aos-delay="100">
        <div class="contact-form">
          <h3 style="margin-bottom:24px"><?= t('Envoyez-nous un message', 'Send us a message') ?></h3>

          <?= flash_html() ?>
          <div class="form-msg" id="contactMsg" role="alert" aria-live="polite"></div>

          <form id="contactForm" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
              <label for="nom"><?= t('Nom complet', 'Full name') ?> *</label>
              <input type="text" id="nom" name="nom" class="form-control"
                     placeholder="<?= t('Votre nom', 'Your name') ?>" required minlength="2">
              <div class="form-error"><?= t('Ce champ est requis (min. 2 caractères).', 'This field is required (min. 2 characters).') ?></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
              <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="<?= t('Votre email', 'Your email') ?>" required>
                <div class="form-error"><?= t('Email invalide.', 'Invalid email.') ?></div>
              </div>
              <div class="form-group">
                <label for="telephone"><?= t('Téléphone', 'Phone') ?></label>
                <input type="tel" id="telephone" name="telephone" class="form-control"
                       placeholder="+235 xx xx xx xx">
              </div>
            </div>

            <div class="form-group">
              <label for="sujet"><?= t('Sujet', 'Subject') ?> *</label>
              <select id="sujet" name="sujet" class="form-control" required>
                <option value=""><?= t('Sélectionnez un sujet', 'Select a subject') ?></option>
                <option value="Infrastructure IT"><?= t('Infrastructure IT', 'IT Infrastructure') ?></option>
                <option value="Développement Web"><?= t('Développement Web/App', 'Web/App Development') ?></option>
                <option value="Solutions IA"><?= t('Solutions IA & Data', 'AI & Data Solutions') ?></option>
                <option value="Formation"><?= t('Formation & Mentorat', 'Training & Mentoring') ?></option>
                <option value="Conseil Digital"><?= t('Conseil Digital', 'Digital Consulting') ?></option>
                <option value="Matériel IT"><?= t('Achat Matériel IT', 'IT Hardware Purchase') ?></option>
                <option value="Autre"><?= t('Autre', 'Other') ?></option>
              </select>
              <div class="form-error"><?= t('Veuillez sélectionner un sujet.', 'Please select a subject.') ?></div>
            </div>

            <div class="form-group">
              <label for="message">Message *</label>
              <textarea id="message" name="message" class="form-control"
                        placeholder="<?= t('Décrivez votre projet ou votre demande...', 'Describe your project or request...') ?>"
                        required minlength="10"></textarea>
              <div class="form-error"><?= t('Ce champ est requis (min. 10 caractères).', 'This field is required (min. 10 characters).') ?></div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
              <span class="btn-text"><i class="fas fa-paper-plane"></i> <?= t('Envoyer le message', 'Send message') ?></span>
              <span class="btn-spinner" hidden><i class="fas fa-spinner fa-spin"></i> <?= t('Envoi...', 'Sending...') ?></span>
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ======== MAP ======== -->
<section class="map-section" aria-label="<?= t('Notre localisation', 'Our location') ?>">
  <iframe
    src="https://www.openstreetmap.org/export/embed.html?bbox=15.02,12.08,15.12,12.16&layer=mapnik"
    title="<?= t('Localisation IgoTech -- N\'Djamena, Tchad', 'IgoTech location -- N\'Djamena, Chad') ?>"
    loading="lazy"
    allowfullscreen>
  </iframe>
</section>

<?php require_once 'includes/footer.php'; ?>
