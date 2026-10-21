<?php
$pageTitle  = 'Contenu du site';
$activeMenu = 'content';
require_once __DIR__ . '/layout.php';

$tab = $_GET['tab'] ?? 'general';

/* ── Sauvegarde ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_auth('editor');
    if (!csrf_check()) { flash_set('error','Token invalide.'); redirect('/admin/content.php?tab='.$tab); }

    $allKeys = [
        /* general */
        'site_nom','site_slogan','site_email','site_telephone','site_telephone2','site_adresse',
        'site_facebook','site_linkedin','site_twitter','site_whatsapp',
        /* hero */
        'hero_titre','hero_sous_titre',
        /* stats */
        'stat_clients','stat_projets','stat_formations','stat_stagiaires',
        /* a propos */
        'about_titre','about_texte','about_mission','about_vision',
        'about_valeur1_titre','about_valeur1_texte',
        'about_valeur2_titre','about_valeur2_texte',
        'about_valeur3_titre','about_valeur3_texte',
        /* equipe */
        'team1_nom','team1_poste','team1_bio',
        'team2_nom','team2_poste','team2_bio',
        'team3_nom','team3_poste','team3_bio',
        'team4_nom','team4_poste','team4_bio',
        /* formations page */
        'formations_titre','formations_sous_titre','formations_intro',
        /* services */
        'service_infra_titre','service_infra_texte',
        'service_dev_titre','service_dev_texte',
        'service_ia_titre','service_ia_texte',
        'service_conseil_titre','service_conseil_texte',
        'service_materiel_titre','service_materiel_texte',
        /* contact */
        'contact_titre','contact_texte','contact_horaires',
        /* notifs */
        'notif_contact','notif_inscription','email_expediteur',
    ];

    foreach ($allKeys as $key) {
        if (isset($_POST[$key])) {
            setting_set($key, sanitize($_POST[$key]));
        }
    }

    /* Inserer les cles manquantes si besoin */
    $existing = db()->query("SELECT cle FROM site_settings")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($allKeys as $key) {
        if (isset($_POST[$key]) && !in_array($key, $existing)) {
            db()->prepare("INSERT IGNORE INTO site_settings (cle, valeur, groupe) VALUES (?,?,?)")
               ->execute([$key, sanitize($_POST[$key]), 'general']);
        }
    }

    flash_set('success', 'Contenu mis a jour.');
    redirect('/admin/content.php?tab='.$tab);
}

/* ── Chargement settings ───────────────────────────────── */
$s = [];
foreach (db()->query("SELECT cle, valeur FROM site_settings")->fetchAll() as $row) {
    $s[$row['cle']] = $row['valeur'];
}
function sv(array $s, string $key, string $default = ''): string {
    return htmlspecialchars($s[$key] ?? $default);
}
?>

<?= flash_html() ?>

<!-- TABS -->
<div style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:24px;flex-wrap:wrap">
  <?php
  $tabs = [
    'general'    => ['fas fa-cog',         'Général'],
    'accueil'    => ['fas fa-home',         'Accueil'],
    'apropos'    => ['fas fa-building',     'À propos'],
    'equipe'     => ['fas fa-users',        'Équipe'],
    'services'   => ['fas fa-tools',        'Services'],
    'formations' => ['fas fa-graduation-cap','Formations'],
    'contact'    => ['fas fa-envelope',     'Contact'],
    'notifs'     => ['fas fa-bell',         'Notifications'],
  ];
  foreach ($tabs as $key => [$icon, $label]):
  ?>
  <a href="?tab=<?= $key ?>"
     style="padding:10px 18px;font-size:.83rem;font-weight:600;color:<?= $tab===$key?'#053067':'#6c7a91' ?>;
            border-bottom:2px solid <?= $tab===$key?'#053067':'transparent' ?>;
            margin-bottom:-2px;text-decoration:none;transition:.2s;white-space:nowrap">
    <i class="<?= $icon ?>"></i> <?= $label ?>
  </a>
  <?php endforeach; ?>
</div>

<form method="POST">
  <?= csrf_field() ?>
  <input type="hidden" name="_tab" value="<?= $tab ?>">

  <?php if ($tab === 'general'): ?>
  <!-- ── GÉNÉRAL ── -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">
    <div>
      <div class="admin-card" style="padding:24px;margin-bottom:20px">
        <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
          <i class="fas fa-info-circle" style="color:#053067;margin-right:8px"></i>Informations générales
        </h3>
        <?php foreach ([
          ['site_nom','text','Nom du site'],
          ['site_slogan','text','Slogan'],
          ['site_email','email','Email de contact'],
          ['site_telephone','text','Téléphone principal'],
          ['site_telephone2','text','Téléphone secondaire'],
          ['site_adresse','text','Adresse complète'],
        ] as [$k,$t,$l]): ?>
        <div class="form-group">
          <label class="form-label"><?= $l ?></label>
          <input type="<?= $t ?>" name="<?= $k ?>" class="form-control" value="<?= sv($s,$k) ?>">
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <div class="admin-card" style="padding:24px;margin-bottom:20px">
        <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
          <i class="fas fa-share-alt" style="color:#053067;margin-right:8px"></i>Réseaux sociaux
        </h3>
        <?php foreach ([
          ['site_facebook','Facebook URL'],
          ['site_linkedin','LinkedIn URL'],
          ['site_twitter','Twitter/X URL'],
          ['site_whatsapp','WhatsApp (chiffres uniquement)'],
        ] as [$k,$l]): ?>
        <div class="form-group">
          <label class="form-label"><?= $l ?></label>
          <input type="text" name="<?= $k ?>" class="form-control" value="<?= sv($s,$k) ?>">
        </div>
        <?php endforeach; ?>
      </div>
      <div class="admin-card" style="padding:24px">
        <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
          <i class="fas fa-chart-bar" style="color:#053067;margin-right:8px"></i>Chiffres clés
        </h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <?php foreach ([
            ['stat_clients','Clients'],
            ['stat_projets','Projets'],
            ['stat_formations','Formations'],
            ['stat_stagiaires','Jeunes formés'],
          ] as [$k,$l]): ?>
          <div class="form-group">
            <label class="form-label"><?= $l ?></label>
            <input type="number" name="<?= $k ?>" class="form-control" value="<?= sv($s,$k,'0') ?>" min="0">
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <?php elseif ($tab === 'accueil'): ?>
  <!-- ── ACCUEIL ── -->
  <div style="max-width:700px">
    <div class="admin-card" style="padding:24px">
      <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
        <i class="fas fa-home" style="color:#053067;margin-right:8px"></i>Section Hero (bannière principale)
      </h3>
      <div class="form-group">
        <label class="form-label">Titre principal</label>
        <input type="text" name="hero_titre" class="form-control" value="<?= sv($s,'hero_titre') ?>">
        <small style="color:#6c7a91">Affiché en grand sur la page d'accueil</small>
      </div>
      <div class="form-group">
        <label class="form-label">Sous-titre / Description</label>
        <textarea name="hero_sous_titre" class="form-control" rows="3"><?= sv($s,'hero_sous_titre') ?></textarea>
      </div>
    </div>
  </div>

  <?php elseif ($tab === 'apropos'): ?>
  <!-- ── À PROPOS ── -->
  <div style="max-width:760px">
    <div class="admin-card" style="padding:24px;margin-bottom:20px">
      <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
        <i class="fas fa-building" style="color:#053067;margin-right:8px"></i>Présentation générale
      </h3>
      <div class="form-group">
        <label class="form-label">Titre de la section</label>
        <input type="text" name="about_titre" class="form-control" value="<?= sv($s,'about_titre','À propos d\'IgoTech') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Texte de présentation</label>
        <textarea name="about_texte" class="form-control" rows="5"><?= sv($s,'about_texte') ?></textarea>
      </div>
    </div>
    <div class="admin-card" style="padding:24px;margin-bottom:20px">
      <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
        <i class="fas fa-bullseye" style="color:#053067;margin-right:8px"></i>Mission & Vision
      </h3>
      <div class="form-group">
        <label class="form-label">Notre Mission</label>
        <textarea name="about_mission" class="form-control" rows="3"><?= sv($s,'about_mission') ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Notre Vision</label>
        <textarea name="about_vision" class="form-control" rows="3"><?= sv($s,'about_vision') ?></textarea>
      </div>
    </div>
    <div class="admin-card" style="padding:24px">
      <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
        <i class="fas fa-heart" style="color:#053067;margin-right:8px"></i>Nos Valeurs (3 valeurs)
      </h3>
      <?php for ($i = 1; $i <= 3; $i++): ?>
      <div style="background:#f8fafc;border-radius:8px;padding:14px;margin-bottom:12px">
        <p style="font-size:.8rem;font-weight:700;color:#053067;margin-bottom:10px">Valeur <?= $i ?></p>
        <div style="display:grid;grid-template-columns:1fr 2fr;gap:12px">
          <div class="form-group" style="margin:0">
            <label class="form-label">Titre</label>
            <input type="text" name="about_valeur<?= $i ?>_titre" class="form-control"
                   value="<?= sv($s,'about_valeur'.$i.'_titre') ?>"
                   placeholder="ex: Excellence">
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Description</label>
            <input type="text" name="about_valeur<?= $i ?>_texte" class="form-control"
                   value="<?= sv($s,'about_valeur'.$i.'_texte') ?>"
                   placeholder="ex: Nous visons l'excellence dans chaque projet">
          </div>
        </div>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <?php elseif ($tab === 'equipe'): ?>
  <!-- ── ÉQUIPE ── -->
  <div style="max-width:760px">
    <div style="background:#e0f2fe;border:1px solid #7dd3fc;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:.84rem;color:#0369a1">
      <i class="fas fa-info-circle"></i>
      Les photos de l'équipe se uploadent via FTP dans <code>/assets/images/team/</code><br>
      Nommez-les : <code>team1.jpg</code>, <code>team2.jpg</code>, <code>team3.jpg</code>, <code>team4.jpg</code>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <?php for ($i = 1; $i <= 4; $i++): ?>
      <div class="admin-card" style="padding:18px">
        <p style="font-size:.82rem;font-weight:700;color:#053067;margin-bottom:12px">
          <i class="fas fa-user-circle"></i> Membre <?= $i ?>
        </p>
        <div class="form-group">
          <label class="form-label">Nom complet</label>
          <input type="text" name="team<?= $i ?>_nom" class="form-control"
                 value="<?= sv($s,'team'.$i.'_nom') ?>"
                 placeholder="Prénom Nom">
        </div>
        <div class="form-group">
          <label class="form-label">Poste / Fonction</label>
          <input type="text" name="team<?= $i ?>_poste" class="form-control"
                 value="<?= sv($s,'team'.$i.'_poste') ?>"
                 placeholder="ex: Directeur Technique">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Biographie courte</label>
          <textarea name="team<?= $i ?>_bio" class="form-control" rows="2"
                    placeholder="Courte description du rôle..."><?= sv($s,'team'.$i.'_bio') ?></textarea>
        </div>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <?php elseif ($tab === 'services'): ?>
  <!-- ── SERVICES ── -->
  <div style="max-width:760px">
    <?php
    $services = [
      ['infra',     'fas fa-server',       'Infrastructure IT'],
      ['dev',       'fas fa-code',         'Développement Web & Mobile'],
      ['ia',        'fas fa-brain',        'Intelligence Artificielle & Data'],
      ['conseil',   'fas fa-lightbulb',    'Conseil Digital'],
      ['materiel',  'fas fa-laptop',       'Matériel Informatique'],
    ];
    foreach ($services as [$key, $icon, $name]):
    ?>
    <div class="admin-card" style="padding:20px;margin-bottom:16px">
      <h3 style="font-size:.9rem;color:#1a2c4e;margin:0 0 14px;display:flex;align-items:center;gap:8px">
        <i class="<?= $icon ?>" style="color:#053067"></i> <?= $name ?>
      </h3>
      <div class="form-group">
        <label class="form-label">Titre affiché</label>
        <input type="text" name="service_<?= $key ?>_titre" class="form-control"
               value="<?= sv($s,'service_'.$key.'_titre', $name) ?>">
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Description / accroche</label>
        <textarea name="service_<?= $key ?>_texte" class="form-control" rows="3"><?= sv($s,'service_'.$key.'_texte') ?></textarea>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php elseif ($tab === 'formations'): ?>
  <!-- ── PAGE FORMATIONS ── -->
  <div style="max-width:700px">
    <div class="admin-card" style="padding:24px;margin-bottom:16px">
      <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
        <i class="fas fa-graduation-cap" style="color:#053067;margin-right:8px"></i>Entête de la page Formations
      </h3>
      <div class="form-group">
        <label class="form-label">Titre de la page</label>
        <input type="text" name="formations_titre" class="form-control"
               value="<?= sv($s,'formations_titre','Nos Formations & Mentorat') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Sous-titre</label>
        <input type="text" name="formations_sous_titre" class="form-control"
               value="<?= sv($s,'formations_sous_titre') ?>">
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Introduction (paragraphe de présentation)</label>
        <textarea name="formations_intro" class="form-control" rows="4"><?= sv($s,'formations_intro') ?></textarea>
      </div>
    </div>
    <div style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:12px 16px;font-size:.84rem;color:#713f12">
      <i class="fas fa-lightbulb"></i>
      Pour gérer le catalogue des formations (ajouter, modifier, supprimer) :
      <a href="/admin/formations.php" style="color:#053067;font-weight:700">Aller dans Formations</a>
    </div>
  </div>

  <?php elseif ($tab === 'contact'): ?>
  <!-- ── CONTACT ── -->
  <div style="max-width:700px">
    <div class="admin-card" style="padding:24px">
      <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
        <i class="fas fa-map-marker-alt" style="color:#053067;margin-right:8px"></i>Page Contact
      </h3>
      <div class="form-group">
        <label class="form-label">Titre de la page</label>
        <input type="text" name="contact_titre" class="form-control"
               value="<?= sv($s,'contact_titre','Contactez-nous') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Texte d'introduction</label>
        <textarea name="contact_texte" class="form-control" rows="3"><?= sv($s,'contact_texte') ?></textarea>
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Horaires d'ouverture</label>
        <textarea name="contact_horaires" class="form-control" rows="3"
                  placeholder="ex: Lun–Ven : 8h–18h&#10;Sam : 9h–13h"><?= sv($s,'contact_horaires') ?></textarea>
      </div>
    </div>
  </div>

  <?php elseif ($tab === 'notifs'): ?>
  <!-- ── NOTIFICATIONS ── -->
  <div style="max-width:500px">
    <div class="admin-card" style="padding:24px">
      <h3 style="font-size:.95rem;color:#1a2c4e;margin:0 0 16px;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
        <i class="fas fa-bell" style="color:#053067;margin-right:8px"></i>Notifications email
      </h3>
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:14px;font-size:.88rem">
        <input type="checkbox" name="notif_contact" value="1"
               <?= ($s['notif_contact'] ?? '1') === '1' ? 'checked' : '' ?> style="width:auto">
        Notifier à chaque nouveau message de contact
      </label>
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:20px;font-size:.88rem">
        <input type="checkbox" name="notif_inscription" value="1"
               <?= ($s['notif_inscription'] ?? '1') === '1' ? 'checked' : '' ?> style="width:auto">
        Notifier à chaque nouvelle inscription
      </label>
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Email d'expédition</label>
        <input type="email" name="email_expediteur" class="form-control"
               value="<?= sv($s,'email_expediteur','noreply@igotech.tech') ?>">
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Bouton sauvegarde -->
  <div style="margin-top:28px;padding-top:20px;border-top:1px solid #e2e8f0">
    <button type="submit" class="btn btn-primary" style="font-size:.95rem;padding:12px 28px">
      <i class="fas fa-save"></i> Enregistrer les modifications
    </button>
  </div>

</form>

</main>
</div>
</body>
</html>
