<?php
/**
 * IgoTech API -- Inscription newsletter
 * POST /api/newsletter.php
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Méthode non autorisée.');
}

if (!csrf_check()) {
    json_response(false, 'Token de sécurité invalide.');
}

$email = sanitize($_POST['email'] ?? '');
$nom   = sanitize($_POST['nom']   ?? '');
$lang  = in_array($_POST['lang'] ?? '', ['fr','en']) ? $_POST['lang'] : 'fr';

if (!valid_email($email)) {
    json_response(false, 'Adresse email invalide.');
}

// Vérif existant
$stmt = db()->prepare("SELECT id, statut FROM newsletter_subscribers WHERE email=?");
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['statut'] === 'actif') {
        json_response(false, 'Vous êtes déjà abonné(e) à notre newsletter.');
    }
    // Ré-abonnement
    db()->prepare("UPDATE newsletter_subscribers SET statut='actif', nom=?, langue=? WHERE email=?")
       ->execute([$nom ?: null, $lang, $email]);
    json_response(true, 'Bienvenue à nouveau ! Vous êtes de nouveau abonné(e) à notre newsletter.');
}

$token = bin2hex(random_bytes(16));
$ip    = get_ip();

try {
    db()->prepare("INSERT INTO newsletter_subscribers (email,nom,langue,token,ip) VALUES (?,?,?,?,?)")
       ->execute([$email, $nom ?: null, $lang, $token, $ip]);
} catch (PDOException $e) {
    json_response(false, 'Erreur lors de l\'inscription. Veuillez réessayer.');
}

// Email de confirmation
$body = "
    <p>Bonjour" . ($nom ? ' ' . htmlspecialchars($nom) : '') . ",</p>
    <p>Merci de vous être abonné(e) à la newsletter d'IgoTech ! 🎉</p>
    <p>Vous recevrez désormais nos dernières actualités tech, conseils et offres exclusives.</p>
    <p>Pour vous désabonner à tout moment, cliquez sur ce lien :<br>
    <a href='" . SITE_URL . "/newsletter/unsubscribe.php?token={$token}'>" . SITE_URL . "/newsletter/unsubscribe.php?token={$token}</a></p>
    <p>À bientôt,<br><strong>L'équipe IgoTech</strong></p>
";
send_email($email, 'Bienvenue dans la newsletter IgoTech !', email_template($body, 'Inscription newsletter'));

$message = $lang === 'en'
    ? 'You have successfully subscribed to our newsletter. Welcome!'
    : 'Inscription réussie ! Bienvenue dans la newsletter IgoTech.';

json_response(true, $message);
