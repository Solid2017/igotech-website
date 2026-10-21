-- ============================================================
--  IgoTech — Migration Chatbot
--  À exécuter APRÈS database.sql initial
-- ============================================================

USE `igotech_db`;

-- ─── TABLE : conversations chatbot ────────────────────────────
CREATE TABLE IF NOT EXISTS `chatbot_conversations` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `session_id`  VARCHAR(64)     NOT NULL,
  `visiteur_nom`   VARCHAR(150) DEFAULT NULL,
  `visiteur_email` VARCHAR(191) DEFAULT NULL,
  `visiteur_tel`   VARCHAR(30)  DEFAULT NULL,
  `langue`      ENUM('fr','en') NOT NULL DEFAULT 'fr',
  `nb_messages` INT             NOT NULL DEFAULT 0,
  `statut`      ENUM('ouvert','ferme','lead') NOT NULL DEFAULT 'ouvert',
  `ip`          VARCHAR(45)     DEFAULT NULL,
  `page_origine`VARCHAR(255)    DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session`  (`session_id`),
  KEY `idx_statut`   (`statut`),
  KEY `idx_created`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE : messages chatbot ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `chatbot_messages` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED    NOT NULL,
  `role`            ENUM('user','bot') NOT NULL,
  `contenu`         TEXT            NOT NULL,
  `intent`          VARCHAR(100)    DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conv` (`conversation_id`),
  CONSTRAINT `fk_cb_conv` FOREIGN KEY (`conversation_id`) REFERENCES `chatbot_conversations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Paramètres chatbot dans site_settings ────────────────────
INSERT IGNORE INTO `site_settings` (`cle`, `valeur`, `groupe`, `label`, `type`) VALUES
('chatbot_actif',       '1',           'chatbot', 'Activer le chatbot',              'boolean'),
('chatbot_nom',         'Igo',         'chatbot', 'Nom de l\'assistant',             'text'),
('chatbot_avatar',      '',            'chatbot', 'Avatar (URL image)',               'image'),
('chatbot_bienvenue_fr','Bonjour 👋 Je suis **Igo**, votre assistant IgoTech ! Comment puis-je vous aider aujourd\'hui ?', 'chatbot', 'Message de bienvenue FR', 'textarea'),
('chatbot_bienvenue_en','Hello 👋 I\'m **Igo**, your IgoTech assistant! How can I help you today?', 'chatbot', 'Message de bienvenue EN', 'textarea'),
('chatbot_ai_actif',    '0',           'chatbot', 'Activer l\'IA (Claude API)',       'boolean'),
('chatbot_ai_key',      '',            'chatbot', 'Clé API Anthropic (Claude)',       'text'),
('chatbot_ai_model',    'claude-haiku-4-5-20251001', 'chatbot', 'Modèle Claude', 'text'),
('chatbot_couleur',     '#053067',     'chatbot', 'Couleur principale',               'text'),
('chatbot_position',    'right',       'chatbot', 'Position (right/left)',            'text');
