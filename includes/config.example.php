<?php
/*
 * IgoTech -- Configuration centrale (EXEMPLE)
 * Copiez ce fichier en `config.php` et renseignez vos valeurs.
 *
 *   cp includes/config.example.php includes/config.php
 */

// --- Environnement ---------------------------------------------------
define('ENV', 'production'); // 'development' pour voir les erreurs PHP

if (ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// --- Chemins ---------------------------------------------------------
define('ROOT_PATH',   dirname(__DIR__));
define('INCLUDES',    ROOT_PATH . '/includes');
define('ASSETS_URL',  '/assets');
define('UPLOADS_DIR', ROOT_PATH . '/assets/uploads');
define('UPLOADS_URL', '/assets/uploads');

// --- URL du site -----------------------------------------------------
define('SITE_URL', 'https://votre-domaine.com');

// --- Base de donnees -------------------------------------------------
define('DB_HOST',    'localhost');
define('DB_NAME',    'votre_base');
define('DB_USER',    'votre_utilisateur');
define('DB_PASS',    'votre_mot_de_passe');
define('DB_CHARSET', 'utf8');

// --- Session ---------------------------------------------------------
define('SESSION_NAME',    'igo_sess');
define('SESSION_TIMEOUT', 3600);

// --- Securite --------------------------------------------------------
define('CSRF_TOKEN_NAME', '_csrf_token');
define('HASH_SECRET',     'changez-cette-cle-secrete-en-production');

// --- Email -----------------------------------------------------------
define('MAIL_FROM',      'noreply@votre-domaine.com');
define('MAIL_FROM_NAME', 'IgoTech');
define('MAIL_ADMIN',     'contact@votre-domaine.com');

// --- Upload ----------------------------------------------------------
define('MAX_FILE_SIZE',  5 * 1024 * 1024);
define('ALLOWED_IMAGES', array('image/jpeg', 'image/png', 'image/webp', 'image/gif'));

// --- Pagination ------------------------------------------------------
define('ITEMS_PER_PAGE', 15);

// --- Connexion PDO (singleton) ---------------------------------------
function db()
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST
             . ';dbname='    . DB_NAME
             . ';charset='   . DB_CHARSET;

        $options = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8 COLLATE utf8_unicode_ci',
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (ENV === 'development') {
                die('<pre>Erreur DB : ' . htmlspecialchars($e->getMessage()) . '</pre>');
            }
            die('Erreur technique. Veuillez reessayer plus tard.');
        }
    }
    return $pdo;
}

// --- Demarrage de session securise ----------------------------------
function start_session()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

        session_set_cookie_params(array(
            'lifetime' => SESSION_TIMEOUT,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ));
        session_start();
    }
}

start_session();
