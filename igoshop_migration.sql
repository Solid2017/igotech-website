-- ============================================================
-- IgoShop -- Tables produits, categories, devis
-- A importer apres database.sql
-- ============================================================

-- TABLE : categories produits
CREATE TABLE IF NOT EXISTS `shop_categories` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(100)  NOT NULL,
  `nom_en`      VARCHAR(100)  DEFAULT NULL,
  `slug`        VARCHAR(100)  NOT NULL,
  `icone`       VARCHAR(60)   DEFAULT 'fas fa-box',
  `ordre`       INT           NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shop_categories` (`nom`, `nom_en`, `slug`, `icone`, `ordre`) VALUES
('Informatique',        'Computers & IT',       'informatique',  'fas fa-laptop',          1),
('Energie Solaire',     'Solar Energy',         'solaire',       'fas fa-solar-panel',     2),
('Reseau & Securite',   'Network & Security',   'reseau',        'fas fa-network-wired',   3),
('Accessoires',         'Accessories',          'accessoires',   'fas fa-plug',            4),
('Onduleurs & UPS',     'UPS & Inverters',      'onduleurs',     'fas fa-battery-full',    5);

-- TABLE : produits
CREATE TABLE IF NOT EXISTS `shop_produits` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nom`             VARCHAR(255)    NOT NULL,
  `nom_en`          VARCHAR(255)    DEFAULT NULL,
  `slug`            VARCHAR(255)    NOT NULL,
  `description`     TEXT            DEFAULT NULL,
  `description_en`  TEXT            DEFAULT NULL,
  `categorie_id`    INT UNSIGNED    DEFAULT NULL,
  `marque`          VARCHAR(100)    DEFAULT NULL,
  `reference`       VARCHAR(100)    DEFAULT NULL,
  `prix`            DECIMAL(12,0)   DEFAULT NULL COMMENT 'Prix en FCFA, NULL = sur devis',
  `prix_promo`      DECIMAL(12,0)   DEFAULT NULL,
  `image`           VARCHAR(255)    DEFAULT NULL,
  `images_json`     JSON            DEFAULT NULL,
  `caracteristiques` JSON           DEFAULT NULL,
  `est_publie`      TINYINT(1)      NOT NULL DEFAULT 1,
  `est_vedette`     TINYINT(1)      NOT NULL DEFAULT 0,
  `est_nouveau`     TINYINT(1)      NOT NULL DEFAULT 0,
  `stock`           ENUM('disponible','commande','rupture') NOT NULL DEFAULT 'disponible',
  `ordre`           INT             NOT NULL DEFAULT 0,
  `vues`            INT UNSIGNED    NOT NULL DEFAULT 0,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`),
  KEY `idx_categorie` (`categorie_id`),
  KEY `idx_publie` (`est_publie`),
  KEY `idx_vedette` (`est_vedette`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE : demandes de devis
CREATE TABLE IF NOT EXISTS `shop_devis` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `produit_id`    INT UNSIGNED    DEFAULT NULL,
  `produit_nom`   VARCHAR(255)    NOT NULL,
  `nom`           VARCHAR(150)    NOT NULL,
  `email`         VARCHAR(191)    NOT NULL,
  `telephone`     VARCHAR(30)     NOT NULL,
  `organisation`  VARCHAR(200)    DEFAULT NULL,
  `quantite`      INT UNSIGNED    NOT NULL DEFAULT 1,
  `message`       TEXT            DEFAULT NULL,
  `statut`        ENUM('nouveau','traite','archive') NOT NULL DEFAULT 'nouveau',
  `notes_admin`   TEXT            DEFAULT NULL,
  `ip`            VARCHAR(45)     DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_statut` (`statut`),
  KEY `idx_produit` (`produit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 25 PRODUITS EXEMPLES
-- ============================================================

INSERT INTO `shop_produits` (`nom`, `slug`, `description`, `categorie_id`, `marque`, `reference`, `prix`, `est_publie`, `est_vedette`, `est_nouveau`, `stock`, `ordre`) VALUES

-- INFORMATIQUE (cat 1)
('Laptop HP ProBook 450 G9',
 'laptop-hp-probook-450-g9',
 'Ordinateur portable professionnel 15,6" Full HD. Processeur Intel Core i5-1235U, 8 Go RAM DDR4, SSD 256 Go, Windows 11 Pro. Ideal pour les professionnels et etudiants.',
 1, 'HP', 'HP-PB450G9', 550000, 1, 1, 0, 'disponible', 1),

('PC Bureau Dell OptiPlex 3090',
 'pc-bureau-dell-optiplex-3090',
 'Ordinateur de bureau compact et performant. Intel Core i3-10105, 8 Go RAM, SSD 256 Go, Windows 11 Pro. Tour ultra-slim ideal pour bureaux et PME.',
 1, 'Dell', 'DELL-OP3090', 380000, 1, 0, 0, 'disponible', 2),

('Imprimante HP LaserJet Pro M404dn',
 'imprimante-hp-laserjet-pro-m404dn',
 'Imprimante laser monochrome professionnelle. 38 ppm, impression recto-verso automatique, Ethernet, USB. Idéale pour les environnements de bureau exigeants.',
 1, 'HP', 'HP-M404DN', 195000, 1, 0, 0, 'disponible', 3),

('Ecran Samsung 24" Full HD',
 'ecran-samsung-24-full-hd',
 'Moniteur LED 24 pouces 1920x1080 Full HD. Dalle IPS, temps de reponse 5ms, 75Hz, ports HDMI et VGA, reglage en hauteur. Parfait pour le bureau.',
 1, 'Samsung', 'SAM-24FHD', 120000, 1, 0, 1, 'disponible', 4),

('Laptop Lenovo ThinkPad E15 Gen4',
 'laptop-lenovo-thinkpad-e15',
 'Ordinateur portable 15,6" business ultra-fiable. AMD Ryzen 5 5625U, 16 Go RAM, SSD 512 Go, Windows 11 Pro. Clavier retro-eclaire, autonomie 10h.',
 1, 'Lenovo', 'LEN-TPE15G4', 620000, 1, 1, 1, 'disponible', 5),

('Clavier + Souris Sans Fil Logitech MK470',
 'clavier-souris-logitech-mk470',
 'Combo clavier-souris sans fil ultra-silencieux. Recepteur USB nano, autonomie jusqu a 36 mois, compatible Windows/Mac. Frappe silencieuse et confortable.',
 1, 'Logitech', 'LOG-MK470', 35000, 1, 0, 0, 'disponible', 6),

('Disque Dur Externe Seagate 1 To',
 'disque-dur-externe-seagate-1to',
 'Disque dur portable USB 3.0. Capacite 1 To, compatible Windows/Mac/Linux. Design slim, plug & play, ideal pour sauvegardes et transferts.',
 1, 'Seagate', 'SEA-1TO-EXT', 45000, 1, 0, 0, 'disponible', 7),

('Webcam Logitech C920 HD Pro',
 'webcam-logitech-c920-hd',
 'Webcam Full HD 1080p 30fps. Autofocus, microphone stereo integre, compatible Zoom/Teams/Meet. Montage universel pour ecran ou trepied.',
 1, 'Logitech', 'LOG-C920', 55000, 1, 0, 0, 'disponible', 8),

('Imprimante Epson EcoTank ET-2850',
 'imprimante-epson-ecotank-et2850',
 'Imprimante multifonction couleur avec reservoir d encre recharge. Impression, copie, scan, Wi-Fi. Economique : 7500 pages noir ou 6000 pages couleur par recharge.',
 1, 'Epson', 'EPS-ET2850', 155000, 1, 0, 1, 'disponible', 9),

('Cle USB Kingston 64 Go USB 3.2',
 'cle-usb-kingston-64go',
 'Cle USB haute vitesse 64 Go. Interface USB 3.2 Gen 1, lecture jusqu a 130 Mo/s, format compact. Compatible Windows, Mac et Linux.',
 1, 'Kingston', 'KING-64USB', 8500, 1, 0, 0, 'disponible', 10),

-- SOLAIRE (cat 2)
('Panneau Solaire Monocristallin 200W',
 'panneau-solaire-mono-200w',
 'Panneau photovoltaique monocristallin haute efficacite 200W. Tension Voc 24V, rendement 21%, cadre aluminium anodise, verre trempe 3,2mm. Garantie 25 ans.',
 2, 'SolarMax', 'SOL-MONO200', 85000, 1, 1, 0, 'disponible', 11),

('Panneau Solaire Monocristallin 300W',
 'panneau-solaire-mono-300w',
 'Panneau solaire monocristallin 300W premium. Voc 37V, rendement 22%, certifie IEC 61215. Ideal pour installations residentielles et commerciales.',
 2, 'SolarMax', 'SOL-MONO300', 120000, 1, 1, 0, 'disponible', 12),

('Batterie Solaire AGM 12V 100Ah',
 'batterie-solaire-agm-12v-100ah',
 'Batterie au plomb AGM (Absorbant Glass Mat) 12V 100Ah sans entretien. Decharge profonde 80%, 500 cycles, etanche, compatible tous regulateurs.',
 2, 'PowerCell', 'PWC-AGM100', 95000, 1, 0, 0, 'disponible', 13),

('Batterie Lithium LiFePO4 12V 200Ah',
 'batterie-lithium-lifepo4-200ah',
 'Batterie lithium fer phosphate 12V 200Ah. 3000+ cycles de vie, BMS integre, charge rapide, poids 24 kg seulement. La solution durable pour le solaire.',
 2, 'PowerCell', 'PWC-LFP200', 320000, 1, 1, 1, 'disponible', 14),

('Kit Solaire Complet 500W Maison',
 'kit-solaire-complet-500w',
 'Kit solaire autonome complet 500W : 2 panneaux 250W + regulateur MPPT 40A + batterie 200Ah + onduleur 1000W + cablage et fixations. Installation incluse sur N Djamena.',
 2, 'IgoSolar', 'IGO-KIT500', 650000, 1, 1, 1, 'disponible', 15),

('Regulateur de Charge MPPT 40A 12/24V',
 'regulateur-mppt-40a',
 'Controleur de charge solaire MPPT 40A compatible 12V/24V. Ecran LCD, protection surcharge/court-circuit, efficacite > 98%, pour panneaux jusqu a 1000W.',
 2, 'SolarMax', 'SOL-MPPT40', 42000, 1, 0, 0, 'disponible', 16),

('Lampe Solaire LED Exterieure 30W',
 'lampe-solaire-led-30w',
 'Lampe solaire tout-en-un 30W avec panneau integre. 6000K lumens blanc, capteur crepusculaire, telecommande, IP65 etanche. Autonomie 12h en veille.',
 2, 'SolarMax', 'SOL-LAMP30', 28000, 1, 0, 1, 'disponible', 17),

('Onduleur Solaire Hybride 2000W 24V',
 'onduleur-solaire-hybride-2000w',
 'Onduleur-chargeur solaire hybride 2000W 24V. Gestion priorite solaire/batterie/reseau, ecran LCD, sortie 220V AC, compatible gel/AGM/lithium. Pour maison et bureau.',
 2, 'PowerCell', 'PWC-HYB2000', 185000, 1, 1, 0, 'disponible', 18),

('Systeme Solaire Pro 1kWc',
 'systeme-solaire-pro-1kwc',
 'Installation solaire professionnelle 1 kWc cle en main : 4 panneaux 250W + onduleur hybride 3kW + batteries 400Ah lithium + tableau de protection + monitoring Wi-Fi.',
 2, 'IgoSolar', 'IGO-PRO1KW', 1850000, 1, 1, 1, 'commande', 19),

-- RESEAU (cat 3)
('Switch Reseau TP-Link 8 Ports 1Gbps',
 'switch-tplink-8ports-gigabit',
 'Switch non-manage 8 ports Gigabit 10/100/1000 Mbps. Plug & play, transfert non bloquant, boitier metallique, ideal pour PME et domicile.',
 3, 'TP-Link', 'TPL-SW8G', 18000, 1, 0, 0, 'disponible', 20),

('Routeur WiFi 6 TP-Link AX3000',
 'routeur-wifi6-tplink-ax3000',
 'Routeur Wi-Fi 6 double bande AX3000 (2.4+5GHz). 4 antennes, MU-MIMO, OFDMA, port WAN Gigabit, 4 ports LAN. Couverture jusqu a 250 m2.',
 3, 'TP-Link', 'TPL-AX3000', 85000, 1, 0, 1, 'disponible', 21),

('Routeur Professionnel Mikrotik hEX',
 'routeur-mikrotik-hex',
 'Routeur filaire Mikrotik hEX 5 ports Gigabit. RouterOS niveau 4, processeur 880MHz, 256 Mo RAM. Solution professionnelle pour PME et fournisseurs d acces.',
 3, 'Mikrotik', 'MKT-HEX', 55000, 1, 0, 0, 'disponible', 22),

('Cable Reseau Cat6 FTP (boite 305m)',
 'cable-reseau-cat6-305m',
 'Cable Ethernet Cat6 FTP 305 metres (1 boite). Blindage global, 250 MHz, compatible PoE, couleur gris. Norme EIA/TIA 568-B, garanti 10 ans.',
 3, 'Nexans', 'NEX-CAT6-305', 65000, 1, 0, 0, 'disponible', 23),

-- ONDULEURS (cat 5)
('Onduleur UPS APC 1500VA',
 'onduleur-ups-apc-1500va',
 'Alimentation sans interruption APC Back-UPS 1500VA 865W. 8 prises protegees, protection surtensions, logiciel PowerChute, autonomie 10 min pleine charge.',
 5, 'APC', 'APC-BX1500', 145000, 1, 1, 0, 'disponible', 24),

('Onduleur UPS Eaton 600VA Line-Interactive',
 'onduleur-ups-eaton-600va',
 'UPS line-interactive 600VA 360W. Regulation automatique de tension (AVR), 4 prises C13, USB pour arret auto, ideal pour PC et equipements reseau.',
 5, 'Eaton', 'EATON-600VA', 75000, 1, 0, 0, 'disponible', 25);
