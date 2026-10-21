<?php
/**
 * IgoTech -- Fonctions utilitaires globales
 */

require_once __DIR__ . '/config.php';

// ===========================================================
//  SÉCURITÉ
// ===========================================================

/**
 * Nettoie une entrée utilisateur (chaîne)
 */
function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Nettoie pour usage dans une URL
 */
function sanitize_url(string $url): string {
    return filter_var(trim($url), FILTER_SANITIZE_URL) ?: '';
}

/**
 * Valide un email
 */
function valid_email(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Génère un token CSRF et le stocke en session
 */
function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Vérifie le token CSRF soumis
 */
function csrf_check(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

/**
 * Champ CSRF caché pour les formulaires
 */
function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

/**
 * Retourne l'IP réelle du visiteur
 */
function get_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return sanitize(explode(',', $_SERVER[$key])[0]);
        }
    }
    return '0.0.0.0';
}

// ===========================================================
//  BASE DE DONNÉES (helpers)
// ===========================================================

/**
 * Récupère un paramètre de site_settings
 */
function setting(string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        $stmt = db()->prepare('SELECT valeur FROM site_settings WHERE cle = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? (string)$row['valeur'] : $default;
    }
    return $cache[$key];
}

/**
 * Met à jour un paramètre de site_settings
 */
function setting_set(string $key, string $value): void {
    $stmt = db()->prepare('UPDATE site_settings SET valeur = ? WHERE cle = ?');
    $stmt->execute([$value, $key]);
}

/**
 * Récupère les données SEO pour une page
 */
function seo(string $page): array {
    $stmt = db()->prepare('SELECT * FROM seo_pages WHERE page = ?');
    $stmt->execute([$page]);
    return $stmt->fetch() ?: [];
}

// ===========================================================
//  SLUGIFICATION
// ===========================================================

function slugify(string $str): string {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $accents = [
        'à'=>'a','á'=>'a','â'=>'a','ä'=>'a','æ'=>'ae','ã'=>'a','å'=>'a','ā'=>'a',
        'ç'=>'c','ć'=>'c','č'=>'c',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ē'=>'e','ę'=>'e','ě'=>'e',
        'î'=>'i','ï'=>'i','í'=>'i','ī'=>'i','į'=>'i','ì'=>'i',
        'ñ'=>'n','ń'=>'n',
        'ô'=>'o','ö'=>'o','ò'=>'o','ó'=>'o','œ'=>'oe','ø'=>'o','ō'=>'o','õ'=>'o',
        'ú'=>'u','ü'=>'u','ù'=>'u','û'=>'u','ū'=>'u',
        'ÿ'=>'y', 'ß'=>'ss',
    ];
    $str = strtr($str, $accents);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}

// ===========================================================
//  TEXTE & AFFICHAGE
// ===========================================================

function excerpt(string $text, int $length = 150): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

function timeago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'à l\'instant';
    if ($diff < 3600)   return floor($diff/60) . ' min';
    if ($diff < 86400)  return floor($diff/3600) . ' h';
    if ($diff < 604800) return floor($diff/86400) . ' j';
    return date('d/m/Y', strtotime($datetime));
}

function format_date(string $datetime, string $format = 'd/m/Y'): string {
    return date($format, strtotime($datetime));
}

function price_format(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

// ===========================================================
//  PAGINATION
// ===========================================================

function paginate(int $total, int $page, int $perPage = ITEMS_PER_PAGE): array {
    $totalPages = (int) ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $page,
        'total_pages' => $totalPages,
        'offset'      => ($page - 1) * $perPage,
        'has_prev'    => $page > 1,
        'has_next'    => $page < $totalPages,
    ];
}

// ===========================================================
//  UPLOAD DE FICHIER
// ===========================================================

function upload_image(array $file, string $subfolder = 'uploads'): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGES, true)) return false;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('igo_', true) . '.' . strtolower($ext);
    $dir      = UPLOADS_DIR . '/' . $subfolder;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) return false;
    return UPLOADS_URL . '/' . $subfolder . '/' . $filename;
}

// ===========================================================
//  EMAIL (via mail() natif -- compatible hébergement mutualisé)
// ===========================================================

function send_email(string $to, string $subject, string $html, string $replyTo = ''): bool {
    $from    = MAIL_FROM_NAME . ' <' . MAIL_FROM . '>';
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $from,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    if ($replyTo) $headers[] = 'Reply-To: ' . $replyTo;
    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, implode("\r\n", $headers));
}

function email_template(string $body, string $title = ''): string {
    $siteName = setting('site_nom', 'IgoTech');
    return "<!DOCTYPE html>
<html lang='fr'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<title>{$title}</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6fb;margin:0;padding:0}
.wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.head{background:#053067;padding:30px;text-align:center}
.head img{height:50px}
.head h1{color:#fff;font-size:18px;margin:10px 0 0}
.body{padding:30px;color:#333;line-height:1.7}
.footer{background:#f4f6fb;padding:20px;text-align:center;color:#999;font-size:12px}
.btn{display:inline-block;background:#053067;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;margin-top:16px}
</style></head>
<body>
<div class='wrap'>
  <div class='head'>
    <h1>{$siteName}</h1>
  </div>
  <div class='body'>{$body}</div>
  <div class='footer'>&copy; " . date('Y') . " {$siteName} -- igotech.tech</div>
</div>
</body></html>";
}

// ===========================================================
//  JSON RESPONSE
// ===========================================================

function json_response(bool $success, string $message = '', array $data = []): never {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// ===========================================================
//  REDIRECT
// ===========================================================

function redirect(string $url, int $code = 302): never {
    header('Location: ' . $url, true, $code);
    exit;
}

function redirect_back(string $fallback = '/'): never {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    redirect($ref ?: $fallback);
}

// ===========================================================
//  FLASH MESSAGES (session)
// ===========================================================

function flash_set(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): array {
    $msgs = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $msgs;
}

function flash_html(): string {
    $html = '';
    foreach (flash_get() as $f) {
        $t   = htmlspecialchars($f['type'], ENT_QUOTES);
        $msg = htmlspecialchars($f['msg'],  ENT_QUOTES);
        $html .= "<div class='alert alert-{$t}'>{$msg}<button class='alert-close'>&times;</button></div>\n";
    }
    return $html;
}

// ===========================================================
//  LANGUE
// ===========================================================

function current_lang(): string {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr','en'])) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    return $_SESSION['lang'] ?? 'fr';
}

function t(string $fr, string $en = ''): string {
    return current_lang() === 'en' && $en ? $en : $fr;
}
