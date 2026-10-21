
-- ============================================================
-- TABLE : admin_users
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nom`          VARCHAR(100)    NOT NULL,
  `email`        VARCHAR(191)    NOT NULL,
  `password`     VARCHAR(255)    NOT NULL,
  `role`         ENUM('super_admin','admin','editor') NOT NULL DEFAULT 'editor',
  `avatar`       VARCHAR(255)    DEFAULT NULL,
  `last_login`   DATETIME        DEFAULT NULL,
  `is_active`    TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compte super-admin par défaut (mot de passe : Admin@IgoTech2025)
INSERT IGNORE INTO `admin_users` (`nom`, `email`, `password`, `role`) VALUES
('Super Admin', 'admin@igotech.tech', '$2b$12$7f98QkPOmXHHxCx1xogn/ev3zTrhfVTvAQmd.bMJ3kQBesaeGAXca', 'super_admin');

-- ============================================================
-- TABLE : contacts (formulaire de contact)
-- ============================================================
CREATE TABLE IF NOT EXISTS `contacts` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(150)    NOT NULL,
  `email`       VARCHAR(191)    NOT NULL,
  `telephone`   VARCHAR(30)     DEFAULT NULL,
  `sujet`       VARCHAR(255)    NOT NULL,
  `message`     TEXT            NOT NULL,
  `statut`      ENUM('non_lu','lu','repondu','archive') NOT NULL DEFAULT 'non_lu',
  `ip`          VARCHAR(45)     DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_statut` (`statut`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : inscriptions (formations & événements)
-- ============================================================
CREATE TABLE IF NOT EXISTS `inscriptions` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nom`             VARCHAR(150)    NOT NULL,
  `prenom`          VARCHAR(150)    NOT NULL,
  `email`           VARCHAR(191)    NOT NULL,
  `telephone`       VARCHAR(30)     NOT NULL,
  `organisation`    VARCHAR(200)    DEFAULT NULL,
  `formation_id`    INT UNSIGNED    DEFAULT NULL,
  `formation_nom`   VARCHAR(255)    NOT NULL,
  `niveau`          VARCHAR(100)    DEFAULT NULL,
  `motivation`      TEXT            DEFAULT NULL,
  `statut`          ENUM('en_attente','confirme','annule','termine') NOT NULL DEFAULT 'en_attente',
  `paiement`        ENUM('en_attente','partiel','complet') NOT NULL DEFAULT 'en_attente',
  `notes_admin`     TEXT            DEFAULT NULL,
  `ip`              VARCHAR(45)     DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_statut` (`statut`),
  KEY `idx_formation` (`formation_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : formations (catalogue)
-- ============================================================
CREATE TABLE IF NOT EXISTS `formations` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `titre`         VARCHAR(255)    NOT NULL,
  `titre_en`      VARCHAR(255)    DEFAULT NULL,
  `description`   TEXT            NOT NULL,
  `description_en` TEXT           DEFAULT NULL,
  `duree`         VARCHAR(100)    DEFAULT NULL,
  `prix`          DECIMAL(10,2)   DEFAULT NULL,
  `niveau`        ENUM('debutant','intermediaire','avance','tous') NOT NULL DEFAULT 'tous',
  `categorie`     VARCHAR(100)    DEFAULT NULL,
  `image`         VARCHAR(255)    DEFAULT NULL,
  `places`        INT             DEFAULT NULL,
  `est_publie`    TINYINT(1)      NOT NULL DEFAULT 1,
  `ordre`         INT             NOT NULL DEFAULT 0,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_publie` (`est_publie`),
  KEY `idx_ordre` (`ordre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `formations` (`titre`, `titre_en`, `description`, `duree`, `prix`, `niveau`, `categorie`, `est_publie`, `ordre`) VALUES
('Développement Web Full-Stack', 'Full-Stack Web Development', 'Apprenez à créer des applications web complètes avec HTML, CSS, JavaScript, PHP et MySQL.', '3 mois', 150000.00, 'debutant', 'Développement', 1, 1),
('Infrastructure IT & Réseaux', 'IT Infrastructure & Networks', 'Maîtrisez la gestion des infrastructures informatiques, la configuration des réseaux et la sécurité.', '2 mois', 120000.00, 'intermediaire', 'Infrastructure', 1, 2),
('Marketing Digital', 'Digital Marketing', 'Stratégies de marketing digital, SEO, réseaux sociaux et publicité en ligne pour booster votre business.', '1 mois', 80000.00, 'debutant', 'Marketing', 1, 3),
('Intelligence Artificielle & Data', 'Artificial Intelligence & Data', 'Introduction à l\'IA, machine learning et analyse de données avec Python.', '2 mois', 180000.00, 'intermediaire', 'IA & Data', 1, 4),
('Entrepreneuriat Tech', 'Tech Entrepreneurship', 'De l\'idée au business plan : créez votre startup technologique en Afrique.', '6 semaines', 60000.00, 'tous', 'Entrepreneuriat', 1, 5);

-- ============================================================
-- TABLE : blog_posts
-- ============================================================
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `titre`         VARCHAR(255)    NOT NULL,
  `titre_en`      VARCHAR(255)    DEFAULT NULL,
  `slug`          VARCHAR(255)    NOT NULL,
  `extrait`       TEXT            DEFAULT NULL,
  `extrait_en`    TEXT            DEFAULT NULL,
  `contenu`       LONGTEXT        NOT NULL,
  `contenu_en`    LONGTEXT        DEFAULT NULL,
  `image`         VARCHAR(255)    DEFAULT NULL,
  `auteur_id`     INT UNSIGNED    NOT NULL DEFAULT 1,
  `categorie`     VARCHAR(100)    DEFAULT NULL,
  `tags`          VARCHAR(500)    DEFAULT NULL,
  `statut`        ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
  `vues`          INT UNSIGNED    NOT NULL DEFAULT 0,
  `meta_title`    VARCHAR(255)    DEFAULT NULL,
  `meta_desc`     VARCHAR(500)    DEFAULT NULL,
  `published_at`  DATETIME        DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`),
  KEY `idx_statut` (`statut`),
  KEY `idx_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `blog_posts` (`titre`, `slug`, `extrait`, `contenu`, `auteur_id`, `categorie`, `statut`, `published_at`) VALUES
('IgoTech lance ses nouvelles formations en IA pour les jeunes tchadiens', 'igotech-formations-ia-jeunes', 'IgoTech ouvre les inscriptions pour sa série de formations en Intelligence Artificielle dédiées aux jeunes entrepreneurs du Tchad.', '<p>IgoTech est fier d\'annoncer le lancement de ses nouvelles formations en Intelligence Artificielle, spécialement conçues pour les jeunes tchadiens souhaitant embrasser la révolution numérique.</p><p>Ces programmes intensifs couvrent les fondamentaux du machine learning, de l\'analyse de données et du développement d\'applications IA.</p>', 1, 'Formations', 'publie', NOW()),
('Transformation digitale au Tchad : enjeux et opportunités', 'transformation-digitale-tchad', 'Le Tchad est à l\'aube d\'une transformation digitale majeure. IgoTech décrypte les enjeux et les opportunités pour les entreprises locales.', '<p>Le numérique représente un levier de croissance considérable pour l\'économie tchadienne. Dans cet article, nous explorons les principales tendances et opportunités de la transformation digitale au Tchad.</p>', 1, 'Actualités', 'publie', NOW()),
('Comment choisir la bonne infrastructure IT pour votre entreprise', 'choisir-infrastructure-it-entreprise', 'Un guide pratique pour les PME et startups qui souhaitent mettre en place une infrastructure informatique solide et évolutive.', '<p>La mise en place d\'une infrastructure IT adaptée est cruciale pour la compétitivité de votre entreprise. IgoTech vous guide dans ce choix stratégique.</p>', 1, 'Conseils', 'publie', NOW());

-- ============================================================
-- TABLE : newsletter_subscribers
-- ============================================================
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `email`         VARCHAR(191)    NOT NULL,
  `nom`           VARCHAR(150)    DEFAULT NULL,
  `langue`        ENUM('fr','en') NOT NULL DEFAULT 'fr',
  `statut`        ENUM('actif','desabonne') NOT NULL DEFAULT 'actif',
  `token`         VARCHAR(64)     DEFAULT NULL,
  `ip`            VARCHAR(45)     DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  KEY `idx_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : site_settings (CMS léger — clé/valeur)
-- ============================================================
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id`        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `cle`       VARCHAR(100)    NOT NULL,
  `valeur`    LONGTEXT        DEFAULT NULL,
  `groupe`    VARCHAR(50)     NOT NULL DEFAULT 'general',
  `label`     VARCHAR(200)    DEFAULT NULL,
  `type`      ENUM('text','textarea','html','image','email','url','boolean','json') NOT NULL DEFAULT 'text',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cle_unique` (`cle`),
  KEY `idx_groupe` (`groupe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`cle`, `valeur`, `groupe`, `label`, `type`) VALUES
-- Général
('site_nom',          'IgoTech',                                    'general', 'Nom du site',            'text'),
('site_slogan',       'Marketing Digital & Applications',           'general', 'Slogan',                 'text'),
('site_email',        'contact@igotech.tech',                       'general', 'Email de contact',       'email'),
('site_telephone',    '+235 63 03 87 80',                           'general', 'Téléphone',              'text'),
('site_telephone2',   '+235 92 57 80 57',                           'general', 'Téléphone 2',            'text'),
('site_adresse',      'Quartier Amtoukoui, N\'Djamena, Tchad',      'general', 'Adresse',                'text'),
('site_facebook',     '#',                                          'social',  'Facebook URL',           'url'),
('site_linkedin',     '#',                                          'social',  'LinkedIn URL',           'url'),
('site_twitter',      '#',                                          'social',  'Twitter/X URL',          'url'),
('site_whatsapp',     '+23563038780',                               'social',  'WhatsApp',               'text'),
-- Hero
('hero_titre',        'Votre partenaire idéal pour la transformation digitale',  'hero', 'Titre Hero',    'text'),
('hero_sous_titre',   'Solutions IT, Développement, IA & Formation pour les entreprises et jeunes entrepreneurs du Tchad', 'hero', 'Sous-titre Hero', 'text'),
-- À propos
('about_texte',       'IgoTech est une agence digitale dynamique basée au Tchad, spécialisée dans la gestion d\'infrastructures IT, le développement de plateformes numériques et les solutions basées sur l\'intelligence artificielle. Notre mission : accompagner individus et organisations dans leur transformation digitale.', 'about', 'Texte À propos', 'textarea'),
-- Compteurs
('stat_clients',      '50',   'stats', 'Clients satisfaits',  'text'),
('stat_projets',      '80',   'stats', 'Projets réalisés',    'text'),
('stat_formations',   '20',   'stats', 'Formations lancées',  'text'),
('stat_stagiaires',   '200',  'stats', 'Jeunes formés',       'text'),
-- Email notifications
('notif_contact',     '1',    'notif', 'Notif. email contact','boolean'),
('notif_inscription', '1',    'notif', 'Notif. email inscription','boolean'),
('email_expediteur',  'noreply@igotech.tech', 'notif', 'Email expéditeur', 'email');

-- ============================================================
-- TABLE : seo_pages
-- ============================================================
CREATE TABLE IF NOT EXISTS `seo_pages` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `page`          VARCHAR(100)    NOT NULL,
  `meta_title`    VARCHAR(255)    DEFAULT NULL,
  `meta_title_en` VARCHAR(255)    DEFAULT NULL,
  `meta_desc`     VARCHAR(500)    DEFAULT NULL,
  `meta_desc_en`  VARCHAR(500)    DEFAULT NULL,
  `og_image`      VARCHAR(255)    DEFAULT NULL,
  `canonical`     VARCHAR(255)    DEFAULT NULL,
  `schema_json`   LONGTEXT        DEFAULT NULL,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_unique` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `seo_pages` (`page`, `meta_title`, `meta_title_en`, `meta_desc`, `meta_desc_en`) VALUES
('home',        'IgoTech — Marketing Digital & Applications | N\'Djamena, Tchad', 'IgoTech — Digital Marketing & Applications | N\'Djamena, Chad', 'IgoTech, agence digitale au Tchad. Services IT, développement web, IA, formations et conseil en transformation digitale.', 'IgoTech, digital agency in Chad. IT services, web development, AI, training and digital transformation consulting.'),
('about',       'À propos d\'IgoTech | Agence Digitale au Tchad', 'About IgoTech | Digital Agency in Chad', 'Découvrez IgoTech, notre mission, nos valeurs et notre équipe dédiée à la transformation digitale en Afrique.', 'Discover IgoTech, our mission, values and team dedicated to digital transformation in Africa.'),
('services',    'Nos Services | IT, IA, Développement Web — IgoTech', 'Our Services | IT, AI, Web Development — IgoTech', 'Infrastructure IT, développement de plateformes numériques, solutions IA, vente de matériel et consulting digital.', 'IT infrastructure, digital platform development, AI solutions, hardware sales and digital consulting.'),
('formations',  'Formations & Mentorat Tech | IgoTech Tchad', 'Tech Training & Mentorship | IgoTech Chad', 'Formations professionnelles en développement web, IA, marketing digital et entrepreneuriat pour les jeunes tchadiens.', 'Professional training in web development, AI, digital marketing and entrepreneurship for Chadian youth.'),
('blog',        'Blog & Actualités | IgoTech', 'Blog & News | IgoTech', 'Restez informé des dernières actualités tech, conseils et innovations digitales de l\'agence IgoTech.', 'Stay informed about the latest tech news, tips and digital innovations from IgoTech agency.'),
('contact',     'Contactez IgoTech | N\'Djamena, Tchad', 'Contact IgoTech | N\'Djamena, Chad', 'Contactez IgoTech pour vos projets IT, formations ou toute demande d\'information. Basés à N\'Djamena, Tchad.', 'Contact IgoTech for your IT projects, training or any information request. Based in N\'Djamena, Chad.');

-- ============================================================
-- TABLE : portfolio / projets
-- ============================================================
CREATE TABLE IF NOT EXISTS `projets` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `titre`         VARCHAR(255)    NOT NULL,
  `client`        VARCHAR(200)    DEFAULT NULL,
  `description`   TEXT            DEFAULT NULL,
  `categorie`     VARCHAR(100)    DEFAULT NULL,
  `image`         VARCHAR(255)    DEFAULT NULL,
  `lien`          VARCHAR(255)    DEFAULT NULL,
  `technologies`  VARCHAR(500)    DEFAULT NULL,
  `est_publie`    TINYINT(1)      NOT NULL DEFAULT 1,
  `ordre`         INT             NOT NULL DEFAULT 0,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `projets` (`titre`, `client`, `description`, `categorie`, `technologies`, `est_publie`, `ordre`) VALUES
('Plateforme de gestion RH', 'Entreprise locale', 'Application web complète de gestion des ressources humaines avec tableaux de bord et reporting.', 'Développement Web', 'PHP, MySQL, JavaScript', 1, 1),
('Système de caisse & inventaire', 'PME Tchad', 'Logiciel de point de vente et de gestion des stocks adapté aux contraintes locales.', 'Application', 'PHP, MySQL, Bootstrap', 1, 2),
('Site institutionnel multilingue', 'ONG internationale', 'Site web bilingue FR/EN avec système de gestion de contenu personnalisé.', 'Site Web', 'PHP, MySQL, CSS, JS', 1, 3),
('Infrastructure réseau d\'entreprise', 'Société N\'Djamena', 'Mise en place d\'infrastructure réseau sécurisée et système de surveillance.', 'Infrastructure IT', 'Cisco, Linux, WiFi 6', 1, 4);

SET FOREIGN_KEY_CHECKS = 1;
