<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$lang = current_lang();
$seoData = seo('formations');

$pageTitle  = $seoData['meta_title'] ?? t('Formations & Mentorat Tech | IgoTech Tchad', 'Tech Training & Mentorship | IgoTech Chad');
$metaDesc   = $seoData['meta_desc']  ?? t('Formations professionnelles en développement web, IA, marketing digital et entrepreneuriat.', 'Professional training in web development, AI, digital marketing and entrepreneurship.');
$activePage = 'formations';

// Récupère les formations
try {
    $stmt = db()->query("SELECT * FROM formations WHERE est_publie=1 ORDER BY ordre ASC");
    $formations = $stmt->fetchAll();
} catch (Exception $e) {
    $formations = [];
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
    <h1><?= t('Formations & Mentorat', 'Training & Mentorship') ?></h1>
    <p><?= t('Développez vos compétences tech et devenez un acteur de la révolution numérique africaine.', 'Develop your tech skills and become a player in the African digital revolution.') ?></p>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="/"><?= t('Accueil', 'Home') ?></a>
      <span>›</span>
      <span aria-current="page"><?= t('Formations', 'Training') ?></span>
    </nav>
  </div>
</section>

<!-- ======== INTRO ======== -->
<section class="section-sm bg-light">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px">
      <?php
      $avantages = [
        ['icon'=>'fa-graduation-cap', 'title'=>t('Formateurs experts','Expert trainers'),    'desc'=>t('Professionnels actifs dans le secteur digital.','Active professionals in the digital sector.')],
        ['icon'=>'fa-laptop-code',    'title'=>t('Pratique à 70%','70% hands-on'),           'desc'=>t('Apprentissage par la pratique et les projets réels.','Learning by doing with real projects.')],
        ['icon'=>'fa-certificate',    'title'=>t('Certificat reconnu','Recognized certificate'),'desc'=>t('Attestation officielle de formation IgoTech.','Official IgoTech training certificate.')],
        ['icon'=>'fa-users',          'title'=>t('Petits groupes','Small groups'),            'desc'=>t('Maximum 15 apprenants pour un suivi personnalisé.','Maximum 15 learners for personalized follow-up.')],
      ];
      foreach ($avantages as $a): ?>
      <div style="text-align:center;padding:24px 16px;background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow)" data-aos="fade-up">
        <i class="fas <?= $a['icon'] ?>" style="font-size:2rem;color:var(--primary);margin-bottom:10px;display:block"></i>
        <strong style="display:block;margin-bottom:6px;font-size:.92rem"><?= $a['title'] ?></strong>
        <p style="color:var(--text-light);font-size:.82rem;margin:0"><?= $a['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ======== CATALOGUE ======== -->
<section class="section" aria-labelledby="catalogue-title">
  <div class="container">
    <div class="section-header center" data-aos="fade-up">
      <span class="section-label"><?= t('Catalogue', 'Catalogue') ?></span>
      <h2 class="section-title" id="catalogue-title">
        <?= t('Nos <span>programmes</span> de formation', 'Our <span>training</span> programs') ?>
      </h2>
    </div>

    <?php if ($formations): ?>
    <div class="formations-grid">
      <?php foreach ($formations as $i => $f): ?>
      <article class="formation-card" id="formation-<?= $f['id'] ?>" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 100 ?>">
        <div class="formation-img">
          <?php if ($f['image']): ?>
          <img src="<?= htmlspecialchars($f['image']) ?>" alt="<?= htmlspecialchars($f['titre']) ?>" loading="lazy">
          <?php else: ?>
          <div style="height:100%;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center">
            <i class="fas fa-chalkboard-teacher" style="font-size:3rem;color:rgba(255,255,255,.3)"></i>
          </div>
          <?php endif; ?>
          <?php if ($f['categorie']): ?>
          <div class="formation-cat"><span class="badge badge-primary"><?= htmlspecialchars($f['categorie']) ?></span></div>
          <?php endif; ?>
        </div>
        <div class="formation-body">
          <h3><?= htmlspecialchars($lang === 'en' && $f['titre_en'] ? $f['titre_en'] : $f['titre']) ?></h3>
          <p><?= htmlspecialchars($lang === 'en' && $f['description_en'] ? $f['description_en'] : $f['description']) ?></p>
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
          <a href="#inscription" class="btn btn-primary btn-sm formation-inscrit-btn"
             data-nom="<?= htmlspecialchars($lang === 'en' && $f['titre_en'] ? $f['titre_en'] : $f['titre']) ?>"
             data-id="<?= $f['id'] ?>">
            <?= t("S'inscrire", 'Register') ?>
          </a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-center text-muted"><?= t('Aucune formation disponible pour le moment.', 'No training available at the moment.') ?></p>
    <?php endif; ?>
  </div>
</section>

<!-- ======== PROCESSUS D'INSCRIPTION ======== -->
<section class="section bg-light">
  <div class="container">
    <div class="section-header center" data-aos="fade-up">
      <span class="section-label"><?= t('Comment s\'inscrire', 'How to register') ?></span>
      <h2 class="section-title"><?= t('Processus <span>d\'inscription</span>', 'Registration <span>process</span>') ?></h2>
    </div>
    <div class="steps-grid">
      <?php
      $steps = [
        ['n'=>'01','title'=>t('Choisir votre formation','Choose your training'),        'desc'=>t('Parcourez notre catalogue et sélectionnez la formation adaptée à vos objectifs.','Browse our catalogue and select the training that suits your goals.')],
        ['n'=>'02','title'=>t('Remplir le formulaire','Fill in the form'),              'desc'=>t('Complétez le formulaire d\'inscription avec vos informations personnelles.','Complete the registration form with your personal information.')],
        ['n'=>'03','title'=>t('Confirmation','Confirmation'),                           'desc'=>t('Notre équipe vous contacte pour confirmer votre inscription et les modalités.','Our team contacts you to confirm your registration and details.')],
        ['n'=>'04','title'=>t('Commencer la formation','Start training'),               'desc'=>t('Rejoignez votre groupe et commencez votre parcours d\'apprentissage.','Join your group and begin your learning journey.')],
      ];
      foreach ($steps as $i => $s): ?>
      <div class="step-card" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
        <div class="step-num"><?= $s['n'] ?></div>
        <h4><?= $s['title'] ?></h4>
        <p><?= $s['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ======== FORMULAIRE D'INSCRIPTION ======== -->
<section class="section" id="inscription">
  <div class="container" style="max-width:760px">
    <div class="section-header center" data-aos="fade-up">
      <span class="section-label"><?= t('Inscription', 'Registration') ?></span>
      <h2 class="section-title"><?= t("Inscrivez-vous <span>maintenant</span>", 'Register <span>now</span>') ?></h2>
    </div>

    <div class="contact-form" data-aos="fade-up" data-aos-delay="100">
      <?= flash_html() ?>
      <div class="form-msg" id="registrationMsg" role="alert" aria-live="polite"></div>

      <form id="registrationForm" novalidate>
        <?= csrf_field() ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label for="prenom"><?= t('Prénom', 'First name') ?> *</label>
            <input type="text" id="prenom" name="prenom" class="form-control" required minlength="2">
            <div class="form-error"><?= t('Requis.','Required.') ?></div>
          </div>
          <div class="form-group">
            <label for="nom_inscr"><?= t('Nom', 'Last name') ?> *</label>
            <input type="text" id="nom_inscr" name="nom" class="form-control" required minlength="2">
            <div class="form-error"><?= t('Requis.','Required.') ?></div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label for="email_inscr">Email *</label>
            <input type="email" id="email_inscr" name="email" class="form-control" required>
            <div class="form-error"><?= t('Email invalide.','Invalid email.') ?></div>
          </div>
          <div class="form-group">
            <label for="telephone_inscr"><?= t('Téléphone', 'Phone') ?> *</label>
            <input type="tel" id="telephone_inscr" name="telephone" class="form-control" required>
            <div class="form-error"><?= t('Requis.','Required.') ?></div>
          </div>
        </div>

        <div class="form-group">
          <label for="organisation"><?= t('Organisation / Entreprise', 'Organization / Company') ?></label>
          <input type="text" id="organisation" name="organisation" class="form-control"
                 placeholder="<?= t('Nom de votre organisation (optionnel)', 'Organization name (optional)') ?>">
        </div>

        <div class="form-group">
          <label for="formation_nom"><?= t('Formation souhaitée', 'Desired training') ?> *</label>
          <select id="formation_nom" name="formation_nom" class="form-control" required>
            <option value=""><?= t('Sélectionnez une formation', 'Select a training') ?></option>
            <?php foreach ($formations as $f): ?>
            <option value="<?= htmlspecialchars($lang === 'en' && $f['titre_en'] ? $f['titre_en'] : $f['titre']) ?>">
              <?= htmlspecialchars($lang === 'en' && $f['titre_en'] ? $f['titre_en'] : $f['titre']) ?>
            </option>
            <?php endforeach; ?>
            <option value="Autre"><?= t('Autre formation', 'Other training') ?></option>
          </select>
          <input type="hidden" name="formation_id" id="formation_id_hidden">
          <div class="form-error"><?= t('Veuillez sélectionner une formation.','Please select a training.') ?></div>
        </div>

        <div class="form-group">
          <label for="niveau"><?= t('Votre niveau actuel', 'Your current level') ?></label>
          <select id="niveau" name="niveau" class="form-control">
            <option value=""><?= t('Sélectionnez votre niveau', 'Select your level') ?></option>
            <option value="Débutant"><?= t('Débutant', 'Beginner') ?></option>
            <option value="Intermédiaire"><?= t('Intermédiaire', 'Intermediate') ?></option>
            <option value="Avancé"><?= t('Avancé', 'Advanced') ?></option>
          </select>
        </div>

        <div class="form-group">
          <label for="motivation"><?= t('Motivation / Objectifs (optionnel)', 'Motivation / Goals (optional)') ?></label>
          <textarea id="motivation" name="motivation" class="form-control"
                    placeholder="<?= t('Pourquoi souhaitez-vous suivre cette formation ?', 'Why do you want to take this training?') ?>"></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg">
          <span class="btn-text"><i class="fas fa-paper-plane"></i> <?= t("Envoyer ma candidature", 'Submit my application') ?></span>
          <span class="btn-spinner" hidden><i class="fas fa-spinner fa-spin"></i></span>
        </button>
      </form>
    </div>
  </div>
</section>

<script>
// Pré-remplir la formation depuis les boutons
document.querySelectorAll('.formation-inscrit-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const sel = document.getElementById('formation_nom');
    const hid = document.getElementById('formation_id_hidden');
    if (sel) {
      for (let o of sel.options) {
        if (o.value === btn.dataset.nom) { o.selected = true; break; }
      }
    }
    if (hid) hid.value = btn.dataset.id;
  });
});
</script>

<?php require_once 'includes/footer.php'; ?>
