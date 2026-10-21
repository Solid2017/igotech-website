<?php
/**
 * IgoTech API -- Inscription à une formation
 * POST /api/inscription.php
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

$nom           = sanitize($_POST['nom']          ?? '');
$prenom        = sanitize($_POST['prenom']       ?? '');
$email         = sanitize($_POST['email']        ?? '');
$telephone     = sanitize($_POST['telephone']    ?? '');
$organisation  = sanitize($_POST['organisation'] ?? '');
$formation_nom = sanitize($_POST['formation_nom']?? '');
$formation_id  = (int)($_POST['formation_id']    ?? 0);
$niveau        = sanitize($_POST['niveau']       ?? '');
$motivation    = sanitize($_POST['motivation']   ?? '');

// Validation
$errors = [];
if (mb_strlen($nom)    < 2) $errors[] = 'Nom requis.';
if (mb_strlen($prenom) < 2) $errors[] = 'Prénom requis.';
if (!valid_email($email))    $errors[] = 'Email invalide.';
if (!$telephone)             $errors[] = 'Téléphone requis.';
if (!$formation_nom)         $errors[] = 'Veuillez sélectionner une formation.';

if ($errors) {
    json_response(false, implode(' ', $errors));
}

// Anti-doublon (même email + même formation)
$stmt = db()->prepare("SELECT id FROM inscriptions WHERE email=? AND formation_nom=?");
$stmt->execute([$email, $formation_nom]);
if ($stmt->fetch()) {
    json_response(false, 'Vous êtes déjà inscrit(e) à cette formation avec cet email.');
}

$ip = get_ip();

try {
    $stmt = db()->prepare("INSERT INTO inscriptions (nom,prenom,email,telephone,organisation,formation_id,formation_nom,niveau,motivation,ip) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$nom,$prenom,$email,$telephone,$organisation ?: null,$formation_id ?: null,$formation_nom,$niveau ?: null,$motivation ?: null,$ip]);
    $inscId = db()->lastInsertId();
} catch (PDOException $e) {
    json_response(false, 'Erreur lors de l\'inscription. Veuillez réessayer.');
}

// Notification admin
if (setting('notif_inscription', '1') === '1') {
    $body = "
        <p>Nouvelle inscription à la formation <strong>" . htmlspecialchars($formation_nom) . "</strong> :</p>
        <table style='width:100%;border-collapse:collapse;font-size:14px'>
            <tr><td style='padding:8px;background:#f4f6fb;font-weight:bold;width:30%'>Candidat</td><td style='padding:8px;border-bottom:1px solid #eee'>" . htmlspecialchars("$prenom $nom") . "</td></tr>
            <tr><td style='padding:8px;background:#f4f6fb;font-weight:bold'>Email</td><td style='padding:8px;border-bottom:1px solid #eee'>" . htmlspecialchars($email) . "</td></tr>
            <tr><td style='padding:8px;background:#f4f6fb;font-weight:bold'>Téléphone</td><td style='padding:8px;border-bottom:1px solid #eee'>" . htmlspecialchars($telephone) . "</td></tr>
            <tr><td style='padding:8px;background:#f4f6fb;font-weight:bold'>Organisation</td><td style='padding:8px;border-bottom:1px solid #eee'>" . htmlspecialchars($organisation ?: '--') . "</td></tr>
            <tr><td style='padding:8px;background:#f4f6fb;font-weight:bold'>Niveau</td><td style='padding:8px;border-bottom:1px solid #eee'>" . htmlspecialchars($niveau ?: '--') . "</td></tr>
        </table>
        <p style='margin-top:20px'><a href='" . SITE_URL . "/admin/inscriptions.php?action=view&id={$inscId}' style='background:#053067;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none'>Gérer l'inscription →</a></p>
    ";
    send_email(setting('site_email', MAIL_ADMIN), 'Nouvelle inscription -- ' . $formation_nom, email_template($body, 'Nouvelle inscription'), $email);
}

// Confirmation candidat
$body2 = "
    <p>Bonjour " . htmlspecialchars($prenom) . ",</p>
    <p>Nous avons bien reçu votre inscription à la formation <strong>" . htmlspecialchars($formation_nom) . "</strong>.</p>
    <p>Notre équipe pédagogique va examiner votre dossier et vous contactera dans les 48h ouvrées pour confirmer votre inscription et vous communiquer les détails de la formation.</p>
    <p><strong>Récapitulatif :</strong></p>
    <ul style='margin:12px 0;padding-left:20px;line-height:2'>
        <li>Formation : <strong>" . htmlspecialchars($formation_nom) . "</strong></li>
        <li>Email : " . htmlspecialchars($email) . "</li>
        <li>Téléphone : " . htmlspecialchars($telephone) . "</li>
    </ul>
    <p>Pour toute question, n'hésitez pas à nous contacter par WhatsApp : <a href='https://wa.me/" . setting('site_whatsapp') . "'>+235 63 03 87 80</a></p>
    <p>Bonne chance dans votre parcours de formation,<br><strong>L'équipe IgoTech</strong></p>
";
send_email($email, 'Inscription reçue -- ' . $formation_nom . ' | IgoTech', email_template($body2, 'Confirmation inscription'));

json_response(true, 'Votre candidature a été envoyée avec succès ! Nous vous contacterons dans les 48h pour confirmer votre inscription.');
