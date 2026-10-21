<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if (!$email || !$password) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (!admin_login($email, $password)) {
        sleep(1);
        $error = 'Email ou mot de passe incorrect.';
    } else {
        redirect('/admin/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion Admin — IgoTech</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="shortcut icon"    type="image/x-icon"            href="/assets/images/favicon.ico">
  <link rel="icon"             type="image/png" sizes="32x32" href="/assets/images/favicon-32.png">
  <link rel="apple-touch-icon" sizes="180x180"               href="/assets/images/apple-touch-icon.png">
  <meta name="theme-color" content="#1414D4">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: linear-gradient(135deg, #0D0DAA 0%, #1414D4 60%, #3535E8 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .card {
      background: #fff;
      border-radius: 16px;
      padding: 40px 36px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
    }

    .logo-wrap {
      text-align: center;
      margin-bottom: 28px;
    }
    .logo-wrap img {
      height: 60px;
      width: auto;
    }
    .logo-fallback {
      display: inline-block;
      font-size: 1.6rem;
      font-weight: 800;
      color: #053067;
      letter-spacing: .04em;
    }

    h1 {
      text-align: center;
      font-size: 1.25rem;
      font-weight: 700;
      color: #1a2c4e;
      margin-bottom: 24px;
    }

    .alert {
      background: #fef2f2;
      border: 1px solid #fca5a5;
      color: #991b1b;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: .875rem;
      margin-bottom: 18px;
    }

    .form-group { margin-bottom: 18px; }

    label {
      display: block;
      font-size: .82rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 6px;
    }

    .input-wrap { position: relative; }

    .input-wrap svg {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px; height: 16px;
      color: #9ca3af;
      pointer-events: none;
    }

    input[type="email"],
    input[type="password"],
    input[type="text"] {
      width: 100%;
      padding: 11px 12px 11px 40px;
      border: 1.5px solid #d1d5db;
      border-radius: 8px;
      font-size: .95rem;
      color: #111827;
      background: #f9fafb;
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      -webkit-appearance: none;
    }
    input:focus {
      border-color: #053067;
      box-shadow: 0 0 0 3px rgba(5,48,103,.12);
      background: #fff;
    }

    .pwd-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
      color: #6b7280;
      line-height: 1;
    }

    .remember {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 22px;
      font-size: .85rem;
      color: #6b7280;
      cursor: pointer;
    }
    .remember input { width: auto; cursor: pointer; }

    .btn-submit {
      display: block;
      width: 100%;
      padding: 13px;
      background: #053067;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: .02em;
      transition: background .2s, transform .1s;
      -webkit-appearance: none;
    }
    .btn-submit:hover   { background: #0a4f9e; }
    .btn-submit:active  { transform: scale(.98); }

    .back-link {
      text-align: center;
      margin-top: 18px;
      font-size: .82rem;
    }
    .back-link a { color: #053067; text-decoration: none; }
    .back-link a:hover { text-decoration: underline; }
  </style>
</head>
<body>

<div class="card">

  <!-- Logo -->
  <div class="logo-wrap">
    <img src="/assets/images/logo.png" alt="IgoTech"
         onerror="this.style.display='none';document.getElementById('logo-text').style.display='inline-block'">
    <span class="logo-fallback" id="logo-text" style="display:none">IgoTech</span>
  </div>

  <h1>Espace Administration</h1>

  <?php if ($error): ?>
  <div class="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?= flash_html() ?>

  <form method="POST" action="">

    <div class="form-group">
      <label for="email">Adresse email</label>
      <div class="input-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="admin@igotech.tech"
               autocomplete="email" autofocus required>
      </div>
    </div>

    <div class="form-group">
      <label for="password">Mot de passe</label>
      <div class="input-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <input type="password" id="password" name="password"
               placeholder="••••••••"
               autocomplete="current-password" required>
        <button type="button" class="pwd-toggle" onclick="togglePwd()" aria-label="Afficher/masquer">
          <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>

    <label class="remember">
      <input type="checkbox" name="remember">
      Se souvenir de moi
    </label>

    <button type="submit" class="btn-submit">Se connecter</button>

  </form>

  <div class="back-link">
    <a href="/">&larr; Retour au site</a>
  </div>

</div>

<script>
function togglePwd() {
  var input = document.getElementById('password');
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
