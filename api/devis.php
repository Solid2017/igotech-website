<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Methode non autorisee.');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    json_response(false, 'Donnees invalides.');
}

$nom          = sanitize($input['nom']           ?? '');
$email        = sanitize($input['email']         ?? '');
$telephone    = sanitize($input['telephone']     ?? '');
$organisation = sanitize($input['organisation']  ?? '');
$produit_id   = (int)($input['produit_id']       ?? 0);
$produit_nom  = sanitize($input['produit_nom']   ?? '');
$quantite     = max(1, (int)($input['quantite']  ?? 1));
$message      = sanitize($input['message']       ?? '');
$ip           = get_ip();

/* ── Validation ─────────────────────────────────────────── */
if (!$nom)                           json_response(false, 'Le nom est requis.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(false, 'Email invalide.');
if (!$telephone)                     json_response(false, 'Le telephone est requis.');
if (!$produit_nom)                   json_response(false, 'Produit non specifie.');

/* ── Anti-spam : 1 devis par IP par produit par 30min ───── */
$recent = db()->prepare("SELECT id FROM shop_devis WHERE ip=? AND produit_id=? AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
$recent->execute([$ip, $produit_id]);
if ($recent->fetch()) {
    json_response(false, 'Vous avez deja envoye une demande pour ce produit. Reessayez dans 30 minutes.');
}

/* ── Insertion ──────────────────────────────────────────── */
$stmt = db()->prepare("INSERT INTO shop_devis (produit_id, produit_nom, nom, email, telephone, organisation, quantite, message, ip)
    VALUES (?,?,?,?,?,?,?,?,?)");
$stmt->execute([
    $produit_id ?: null,
    $produit_nom,
    $nom, $email, $telephone,
    $organisation ?: null,
    $quantite,
    $message ?: null,
    $ip,
]);

/* ── Email admin ─────────────────────────────────────────── */
$adminEmail = MAIL_ADMIN;
$subject    = '[IgoShop] Nouvelle demande de devis — ' . $produit_nom;
$body       = email_template("
<h2 style='color:#053067'>Nouvelle demande de devis</h2>
<table style='width:100%;border-collapse:collapse;font-size:14px'>
  <tr><td style='padding:8px;border:1px solid #e2e8f0;font-weight:600;background:#f8fafc;width:35%'>Produit</td><td style='padding:8px;border:1px solid #e2e8f0'><strong>" . htmlspecialchars($produit_nom) . "</strong></td></tr>
  <tr><td style='padding:8px;border:1px solid #e2e8f0;font-weight:600;background:#f8fafc'>Nom</td><td style='padding:8px;border:1px solid #e2e8f0'>" . htmlspecialchars($nom) . "</td></tr>
  <tr><td style='padding:8px;border:1px solid #e2e8f0;font-weight:600;background:#f8fafc'>Email</td><td style='padding:8px;border:1px solid #e2e8f0'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td></tr>
  <tr><td style='padding:8px;border:1px solid #e2e8f0;font-weight:600;background:#f8fafc'>Telephone</td><td style='padding:8px;border:1px solid #e2e8f0'>" . htmlspecialchars($telephone) . "</td></tr>
  <tr><td style='padding:8px;border:1px solid #e2e8f0;font-weight:600;background:#f8fafc'>Organisation</td><td style='padding:8px;border:1px solid #e2e8f0'>" . htmlspecialchars($organisation ?: '—') . "</td></tr>
  <tr><td style='padding:8px;border:1px solid #e2e8f0;font-weight:600;background:#f8fafc'>Quantite</td><td style='padding:8px;border:1px solid #e2e8f0'>" . $quantite . "</td></tr>
  " . ($message ? "<tr><td style='padding:8px;border:1px solid #e2e8f0;font-weight:600;background:#f8fafc'>Message</td><td style='padding:8px;border:1px solid #e2e8f0'>" . nl2br(htmlspecialchars($message)) . "</td></tr>" : '') . "
</table>
<p style='margin-top:20px'>
  <a href='https://igotech.tech/admin/shop.php?tab=devis' class='btn'>Voir dans l'admin</a>
</p>
", 'Demande de devis IgoShop');

send_email($adminEmail, $subject, $body);

/* ── Email confirmation client ───────────────────────────── */
$confirmBody = email_template("
<h2 style='color:#053067'>Votre demande de devis a ete recue</h2>
<p>Bonjour <strong>" . htmlspecialchars($nom) . "</strong>,</p>
<p>Nous avons bien recu votre demande de devis pour :</p>
<div style='background:#f0f4ff;border-left:4px solid #053067;padding:14px 18px;border-radius:4px;margin:16px 0'>
  <strong>" . htmlspecialchars($produit_nom) . "</strong> — Quantite : " . $quantite . "
</div>
<p>Notre equipe commerciale vous contactera sous <strong>24 heures ouvrables</strong> a l'adresse <strong>" . htmlspecialchars($email) . "</strong> ou au <strong>" . htmlspecialchars($telephone) . "</strong>.</p>
<p>Pour toute urgence, appelez-nous directement :</p>
<p style='font-size:1.1rem;font-weight:700;color:#053067'>+235 63 03 87 80</p>
<p>Merci de votre confiance en IgoTech.</p>
", 'Confirmation demande de devis');

send_email($email, 'Votre demande de devis IgoShop — IgoTech', $confirmBody, $adminEmail);

json_response(true, 'Demande envoyee ! Nous vous contacterons dans les 24h. Verifiez vos emails.', ['id' => db()->lastInsertId()]);
