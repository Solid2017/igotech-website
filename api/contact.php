<?php
/**
 * IgoTech API -- Formulaire de contact
 * POST /api/contact.php
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

// -- Validation ------------------------------------------------
$nom       = sanitize($_POST['nom']       ?? '');
$email     = sanitize($_POST['email']     ?? '');
$telephone = sanitize($_POST['telephone'] ?? '');
$sujet     = sanitize($_POST['sujet']     ?? '');
$message   = sanitize($_POST['message']   ?? '');

$errors = [];
if (mb_strlen($nom) < 2)     $errors[] = 'Nom requis (min. 2 caractères).';
if (!valid_email($email))     $errors[] = 'Email invalide.';
if (mb_strlen($sujet) < 2)   $errors[] = 'Sujet requis.';
if (mb_strlen($message) < 10) $errors[] = 'Message requis (min. 10 caractères).';

if ($errors) {
    json_response(false, implode(' ', $errors));
}

// -- Anti-spam (même IP + email dans les 5 minutes) ------------
$ip = get_ip();
$stmt = db()->prepare("SELECT id FROM contacts WHERE ip=? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
$stmt->execute([$ip]);
if ($stmt->fetch()) {
    json_response(false, 'Merci de patienter quelques minutes avant d\'envoyer un autre message.');
}

// -- Insertion -------------------------------------------------
try {
    $stmt = db()->prepare("INSERT INTO contacts (nom,email,telephone,sujet,message,ip) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$nom, $email, $telephone, $sujet, $message, $ip]);
    $contactId = db()->lastInsertId();
} catch (PDOException $e) {
    json_response(false, 'Erreur lors de l\'enregistrement. Veuillez réessayer.');
}

// -- Notification admin ----------------------------------------
if (setting('notif_contact', '1') === '1') {
    $adminEmail = setting('site_email', MAIL_ADMIN);
    $body = "
        <p>Nouveau message de contact sur IgoTech :</p>
        <table style='width:100%;border-collapse:collapse;font-size:14px'>
            <tr><td style='padding:8px;background:#f4f6fb;font-weight:bold;width:30%'>Nom</td><td style='padding:8px;border-bottom:1px solid #eee'>" . htmlspecialchars($nom) . "</td></tr>
            <tr><td style='padding:8px;background:#f4f6fb;font-weight:bold'>Email</td><td style='padding:8px;border-bottom:1px solid #eee'>" . htmlspecialchars($email) . "</td></tr>
            <tr><td style='padding:8px;background:#f4f6fb;font-weight:bold'>Téléphone</td><td style='padding:8px;border-bottom:1px solid #eee'>" . htmlspecialchars($telephone ?: '--') . "</td></tr>
            <tr><td style='padding:8px;background:#f4f6fb;font-weight:bold'>Sujet</td><td style='padding:8px;border-bottom:1px solid #eee'>" . htmlspecialchars($sujet) . "</td></tr>
            <tr><td style='padding:8px;background:#f4f6fb;font-weight:bold'>Message</td><td style='padding:8px'>" . nl2br(htmlspecialchars($message)) . "</td></tr>
        </table>
        <p style='margin-top:20px'><a href='" . SITE_URL . "/admin/messages.php?action=view&id={$contactId}' style='background:#053067;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none'>Voir dans l'admin →</a></p>
    ";
    send_email($adminEmail, 'Nouveau message de contact -- IgoTech', email_template($body, 'Nouveau message'), $email);
}

// -- Email de confirmation au visiteur -------------------------
$confirmBody = "
    <p>Bonjour " . htmlspecialchars($nom) . ",</p>
    <p>Merci pour votre message. Notre équipe l'a bien reçu et vous répondra dans les meilleurs délais (généralement sous 24-48h ouvrées).</p>
    <p><strong>Récapitulatif de votre message :</strong></p>
    <blockquote style='background:#f4f6fb;padding:12px 16px;border-left:3px solid #053067;margin:12px 0'>" . nl2br(htmlspecialchars($message)) . "</blockquote>
    <p>Si vous avez une urgence, contactez-nous directement par WhatsApp : <a href='https://wa.me/" . setting('site_whatsapp') . "'>+235 63 03 87 80</a></p>
    <p>Cordialement,<br><strong>L'équipe IgoTech</strong></p>
";
send_email($email, 'Votre message a bien été reçu -- IgoTech', email_template($confirmBody, 'Confirmation'), MAIL_FROM);

json_response(true, 'Votre message a été envoyé avec succès. Nous vous répondrons dans les 24h.');
