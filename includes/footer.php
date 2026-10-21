<?php
/**
 * IgoTech -- Footer global
 */
$telephone2 = setting('site_telephone2', '+235 92 57 80 57');
$adresse    = setting('site_adresse',   "Quartier Amtoukoui, N'Djamena, Tchad");
?>

<!-- ================ NEWSLETTER BAR ================ -->
<section class="newsletter-bar" aria-label="Newsletter">
  <div class="container">
    <div class="newsletter-bar-inner" data-aos="fade-up">
      <div class="newsletter-bar-text">
        <h3><?= t('Restez informé', 'Stay Informed') ?></h3>
        <p><?= t('Actualités tech, conseils et offres exclusives d\'IgoTech.', 'Tech news, tips and exclusive offers from IgoTech.') ?></p>
      </div>
      <form class="newsletter-form" id="newsletterForm" novalidate>
        <?= csrf_field() ?>
        <div class="newsletter-inputs">
          <input type="email" name="email" placeholder="<?= t('Votre email', 'Your email') ?>" required aria-label="Email">
          <button type="submit" class="btn btn-white">
            <span class="btn-text"><?= t("S'abonner", 'Subscribe') ?></span>
            <span class="btn-spinner" hidden><i class="fas fa-spinner fa-spin"></i></span>
          </button>
        </div>
        <div class="form-msg" id="newsletterMsg" role="alert" aria-live="polite"></div>
      </form>
    </div>
  </div>
</section>

<!-- ================ FOOTER MAIN ================ -->
<footer class="footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">

      <!-- Col 1 : Brand -->
      <div class="footer-col footer-brand" data-aos="fade-up">
        <a href="/" class="footer-logo">
          <img src="/assets/images/logo.png" alt="IgoTech" height="55" width="auto">
        </a>
        <p><?= t(
          'Agence digitale basée au Tchad. Nous accompagnons entreprises et jeunes entrepreneurs dans leur transformation numérique.',
          'Digital agency based in Chad. We support businesses and young entrepreneurs in their digital transformation.'
        ) ?></p>
        <div class="footer-social">
          <a href="<?= htmlspecialchars($facebook) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="<?= htmlspecialchars($linkedin) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <a href="<?= htmlspecialchars($twitter) ?>"  target="_blank" rel="noopener" aria-label="Twitter/X"><i class="fab fa-x-twitter"></i></a>
          <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>

      <!-- Col 2 : Services -->
      <div class="footer-col" data-aos="fade-up" data-aos-delay="100">
        <h4 class="footer-heading"><?= t('Nos Services', 'Our Services') ?></h4>
        <ul class="footer-links">
          <li><a href="/services.php#infrastructure"><i class="fas fa-angle-right"></i> <?= t('Infrastructure IT', 'IT Infrastructure') ?></a></li>
          <li><a href="/services.php#developpement"><i class="fas fa-angle-right"></i>  <?= t('Développement Web', 'Web Development') ?></a></li>
          <li><a href="/services.php#ia"><i class="fas fa-angle-right"></i>              <?= t('Solutions IA & Data', 'AI & Data Solutions') ?></a></li>
          <li><a href="/services.php#conseil"><i class="fas fa-angle-right"></i>         <?= t('Conseil Digital', 'Digital Consulting') ?></a></li>
          <li><a href="/services.php#materiel"><i class="fas fa-angle-right"></i>        <?= t('Matériel Informatique', 'IT Hardware') ?></a></li>
          <li><a href="/formations.php"><i class="fas fa-angle-right"></i>               <?= t('Formations & Mentorat', 'Training & Mentoring') ?></a></li>
        </ul>
      </div>

      <!-- Col 3 : Liens utiles -->
      <div class="footer-col" data-aos="fade-up" data-aos-delay="150">
        <h4 class="footer-heading"><?= t('Liens Utiles', 'Quick Links') ?></h4>
        <ul class="footer-links">
          <li><a href="/"><i class="fas fa-angle-right"></i>             <?= t('Accueil', 'Home') ?></a></li>
          <li><a href="/about.php"><i class="fas fa-angle-right"></i>    <?= t('À propos', 'About') ?></a></li>
          <li><a href="/formations.php"><i class="fas fa-angle-right"></i><?= t('Formations', 'Training') ?></a></li>
          <li><a href="/blog.php"><i class="fas fa-angle-right"></i>     <?= t('Blog', 'Blog') ?></a></li>
          <li><a href="/contact.php"><i class="fas fa-angle-right"></i>  <?= t('Contact', 'Contact') ?></a></li>
          <li><a href="/admin/login.php"><i class="fas fa-angle-right"></i><?= t('Admin', 'Admin') ?></a></li>
        </ul>
      </div>

      <!-- Col 4 : Contact -->
      <div class="footer-col" data-aos="fade-up" data-aos-delay="200">
        <h4 class="footer-heading"><?= t('Contact', 'Contact') ?></h4>
        <ul class="footer-contact">
          <li>
            <i class="fas fa-map-marker-alt"></i>
            <span><?= htmlspecialchars($adresse) ?></span>
          </li>
          <li>
            <i class="fas fa-phone-alt"></i>
            <span>
              <a href="tel:<?= preg_replace('/\s+/', '', $telephone) ?>"><?= htmlspecialchars($telephone) ?></a><br>
              <a href="tel:<?= preg_replace('/\s+/', '', $telephone2) ?>"><?= htmlspecialchars($telephone2) ?></a>
            </span>
          </li>
          <li>
            <i class="fas fa-envelope"></i>
            <a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a>
          </li>
          <li>
            <i class="fab fa-whatsapp"></i>
            <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a>
          </li>
        </ul>
      </div>

    </div><!-- /footer-grid -->
  </div><!-- /container -->

  <!-- Footer bottom -->
  <div class="footer-bottom">
    <div class="container">
      <p>&copy; <?= date('Y') ?> <strong>IgoTech</strong>. <?= t('Tous droits réservés.', 'All rights reserved.') ?></p>
      <p><?= t('Conçu et développé par', 'Designed and developed by') ?> <a href="/" style="color:#fff">IgoTech</a></p>
    </div>
  </div>
</footer>

<!-- ================ SCROLL TO TOP ================ -->
<button class="scroll-top" id="scrollTop" aria-label="<?= t('Haut de page', 'Back to top') ?>" title="<?= t('Haut de page', 'Back to top') ?>">
  <i class="fas fa-chevron-up"></i>
</button>

<!-- ================ WHATSAPP FLOAT ================ -->
<a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>?text=<?= urlencode(t('Bonjour IgoTech, j\'ai une question.', 'Hello IgoTech, I have a question.')) ?>"
   class="whatsapp-float" target="_blank" rel="noopener" aria-label="WhatsApp">
  <i class="fab fa-whatsapp"></i>
</a>

<!-- ================ SCRIPTS ================ -->
<!-- AOS (local) -->
<script src="/assets/vendor/aos/aos.js"></script>
<!-- Main JS -->
<script src="/assets/js/main.js?v=1.0"></script>

<?php if (setting('chatbot_actif', '1') === '1'): ?>
<!-- ================ CHATBOT WIDGET ================ -->
<link rel="stylesheet" href="/assets/css/chatbot.css">
<script src="/assets/js/chatbot.js?v=1.0" defer></script>
<?php endif; ?>
</body>
</html>
