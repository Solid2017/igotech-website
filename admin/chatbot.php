<?php
$pageTitle  = 'Chatbot -- Conversations & Paramètres';
$activeMenu = 'chatbot';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';
require_auth('editor');
require_once __DIR__ . '/layout.php';

$pdo = db();
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'conversations';

/* --- POST: Enregistrer paramètres chatbot ------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    csrf_check();
    $keys = [
        'chatbot_actif', 'chatbot_nom', 'chatbot_bienvenue_fr', 'chatbot_bienvenue_en',
        'chatbot_ai_actif', 'chatbot_ai_provider', 'chatbot_ai_key', 'chatbot_ai_model',
        'chatbot_couleur', 'chatbot_position',
    ];
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            setting_set($k, sanitize($_POST[$k]));
        }
    }
    flash_set('success', 'Paramètres chatbot enregistrés.');
    redirect('/admin/chatbot.php?tab=settings');
}

/* --- POST: Changer statut conversation --------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_statut'])) {
    csrf_check();
    $convId  = (int) ($_POST['conv_id'] ?? 0);
    $statut  = in_array($_POST['set_statut'], ['ouvert', 'ferme', 'lead']) ? $_POST['set_statut'] : 'ouvert';
    $pdo->prepare("UPDATE chatbot_conversations SET statut=? WHERE id=?")
        ->execute([$statut, $convId]);
    redirect('/admin/chatbot.php?tab=conversations');
}

/* --- POST: Supprimer conversation -------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_conv'])) {
    csrf_check();
    $convId = (int) ($_POST['conv_id'] ?? 0);
    $pdo->prepare("DELETE FROM chatbot_conversations WHERE id=?")->execute([$convId]);
    redirect('/admin/chatbot.php?tab=conversations');
}

/* --- Export CSV leads --------------------------------------- */
if (isset($_GET['export']) && $_GET['export'] === 'leads') {
    require_auth('admin');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="chatbot_leads_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8
    fputcsv($out, ['ID', 'Nom', 'Email', 'Téléphone', 'Langue', 'Messages', 'Statut', 'Page origine', 'Date']);
    $rows = $pdo->query(
        "SELECT id, visiteur_nom, visiteur_email, visiteur_tel, langue, nb_messages, statut, page_origine, created_at
         FROM chatbot_conversations
         WHERE statut='lead' OR visiteur_email IS NOT NULL OR visiteur_tel IS NOT NULL
         ORDER BY created_at DESC"
    )->fetchAll();
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['visiteur_nom'], $r['visiteur_email'], $r['visiteur_tel'],
            $r['langue'], $r['nb_messages'], $r['statut'], $r['page_origine'], $r['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

/* --- KPI STATS ---------------------------------------------- */
$stats = $pdo->query("
    SELECT
      COUNT(*) AS total,
      SUM(statut='ouvert') AS ouverts,
      SUM(statut='ferme') AS fermes,
      SUM(statut='lead') AS leads,
      SUM(visiteur_email IS NOT NULL) AS avec_email,
      SUM(visiteur_tel IS NOT NULL) AS avec_tel,
      SUM(langue='fr') AS fr_count,
      SUM(langue='en') AS en_count
    FROM chatbot_conversations
")->fetch();

/* --- CONVERSATIONS LIST -------------------------------------- */
$page    = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 20;
$search  = sanitize($_GET['q'] ?? '');
$statut  = in_array($_GET['statut'] ?? '', ['ouvert', 'ferme', 'lead', '']) ? ($_GET['statut'] ?? '') : '';

$where  = '1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (visiteur_nom LIKE ? OR visiteur_email LIKE ? OR visiteur_tel LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($statut !== '') {
    $where .= ' AND statut = ?';
    $params[] = $statut;
}

$total = (int) $pdo->prepare("SELECT COUNT(*) FROM chatbot_conversations WHERE $where")
                    ->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM chatbot_conversations WHERE $where")
                    ->execute($params) : 0;
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM chatbot_conversations WHERE $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pg = paginate($total, $page, $perPage);

$listStmt = $pdo->prepare(
    "SELECT * FROM chatbot_conversations WHERE $where ORDER BY updated_at DESC LIMIT $perPage OFFSET {$pg['offset']}"
);
$listStmt->execute($params);
$conversations = $listStmt->fetchAll();

/* --- SINGLE CONVERSATION THREAD ---------------------------- */
$viewConv = null;
$messages = [];
if (isset($_GET['view'])) {
    $viewId = (int) $_GET['view'];
    $viewConv = $pdo->prepare("SELECT * FROM chatbot_conversations WHERE id=?");
    $viewConv->execute([$viewId]);
    $viewConv = $viewConv->fetch();
    if ($viewConv) {
        $msgStmt = $pdo->prepare("SELECT * FROM chatbot_messages WHERE conversation_id=? ORDER BY created_at ASC");
        $msgStmt->execute([$viewId]);
        $messages = $msgStmt->fetchAll();
    }
}

/* --- SETTINGS ----------------------------------------------- */
$settings = [
    'chatbot_actif'        => setting('chatbot_actif', '1'),
    'chatbot_nom'          => setting('chatbot_nom', 'Igo'),
    'chatbot_bienvenue_fr' => setting('chatbot_bienvenue_fr', ''),
    'chatbot_bienvenue_en' => setting('chatbot_bienvenue_en', ''),
    'chatbot_ai_actif'     => setting('chatbot_ai_actif', '0'),
    'chatbot_ai_provider'  => setting('chatbot_ai_provider', 'groq'),
    'chatbot_ai_key'       => setting('chatbot_ai_key', ''),
    'chatbot_ai_model'     => setting('chatbot_ai_model', 'llama-3.1-8b-instant'),
    'chatbot_couleur'      => setting('chatbot_couleur', '#053067'),
    'chatbot_position'     => setting('chatbot_position', 'right'),
];
?>

<!-- KPI CARDS -->
<div class="admin-kpi-grid" style="margin-bottom:24px;">
  <div class="kpi-card" style="--kpi-color:#053067">
    <div class="kpi-icon"><i class="fas fa-comments"></i></div>
    <div class="kpi-info">
      <span class="kpi-value"><?= $stats['total'] ?></span>
      <span class="kpi-label">Conversations</span>
    </div>
  </div>
  <div class="kpi-card" style="--kpi-color:#f59e0b">
    <div class="kpi-icon"><i class="fas fa-folder-open"></i></div>
    <div class="kpi-info">
      <span class="kpi-value"><?= $stats['ouverts'] ?></span>
      <span class="kpi-label">Ouvertes</span>
    </div>
  </div>
  <div class="kpi-card" style="--kpi-color:#10b981">
    <div class="kpi-icon"><i class="fas fa-user-check"></i></div>
    <div class="kpi-info">
      <span class="kpi-value"><?= $stats['leads'] ?></span>
      <span class="kpi-label">Leads captés</span>
    </div>
  </div>
  <div class="kpi-card" style="--kpi-color:#e63946">
    <div class="kpi-icon"><i class="fas fa-envelope"></i></div>
    <div class="kpi-info">
      <span class="kpi-value"><?= $stats['avec_email'] ?></span>
      <span class="kpi-label">Avec email</span>
    </div>
  </div>
</div>

<!-- TABS -->
<div class="admin-tabs" style="margin-bottom:20px;border-bottom:2px solid #e2e8f0;display:flex;gap:0;">
  <a href="?tab=conversations" class="admin-tab<?= $tab==='conversations'?' active':'' ?>">
    <i class="fas fa-comments"></i> Conversations
  </a>
  <a href="?tab=leads" class="admin-tab<?= $tab==='leads'?' active':'' ?>">
    <i class="fas fa-user-tag"></i> Leads
  </a>
  <a href="?tab=settings" class="admin-tab<?= $tab==='settings'?' active':'' ?>">
    <i class="fas fa-cog"></i> Paramètres
  </a>
</div>

<style>
.admin-tab{padding:10px 20px;color:#6c7a91;font-weight:600;font-size:.88rem;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .2s,border-color .2s}
.admin-tab:hover,.admin-tab.active{color:#053067;border-bottom-color:#053067}
.admin-tab i{margin-right:6px}
.conv-thread{display:flex;flex-direction:column;gap:10px;padding:16px 0;max-height:420px;overflow-y:auto}
.conv-msg{display:flex;gap:8px;align-items:flex-end}
.conv-msg.user{flex-direction:row-reverse}
.conv-msg .bubble{max-width:75%;padding:8px 14px;border-radius:14px;font-size:.875rem;line-height:1.5}
.conv-msg.bot .bubble{background:#f0f4ff;color:#1a2c4e;border-bottom-left-radius:3px}
.conv-msg.user .bubble{background:#053067;color:#fff;border-bottom-right-radius:3px}
.conv-msg .ts{font-size:.65rem;color:#6c7a91;white-space:nowrap;align-self:flex-end;padding:0 4px}
.conv-avatar{width:26px;height:26px;border-radius:50%;background:#053067;color:#fff;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.conv-avatar.user{background:#e63946}
.intent-badge{font-size:.65rem;padding:1px 7px;border-radius:10px;background:#e2e8f0;color:#6c7a91;margin-top:3px;display:inline-block}
</style>

<?php if ($tab === 'conversations'): ?>

<?php if ($viewConv): ?>
<!-- THREAD VIEW -->
<div style="margin-bottom:16px">
  <a href="?tab=conversations" class="btn btn-outline" style="font-size:.82rem;padding:6px 14px;">
    <i class="fas fa-arrow-left"></i> Retour
  </a>
</div>
<div class="admin-card" style="padding:20px">
  <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:16px">
    <div>
      <strong>Session :</strong> <code style="font-size:.78rem"><?= htmlspecialchars(substr($viewConv['session_id'],0,24)) ?>…</code>
    </div>
    <?php if ($viewConv['visiteur_nom']): ?>
    <div><strong>Nom :</strong> <?= htmlspecialchars($viewConv['visiteur_nom']) ?></div>
    <?php endif; ?>
    <?php if ($viewConv['visiteur_email']): ?>
    <div><strong>Email :</strong> <a href="mailto:<?= htmlspecialchars($viewConv['visiteur_email']) ?>"><?= htmlspecialchars($viewConv['visiteur_email']) ?></a></div>
    <?php endif; ?>
    <?php if ($viewConv['visiteur_tel']): ?>
    <div><strong>Tél :</strong> <?= htmlspecialchars($viewConv['visiteur_tel']) ?></div>
    <?php endif; ?>
    <div><strong>Langue :</strong> <?= strtoupper($viewConv['langue']) ?></div>
    <div><strong>Messages :</strong> <?= $viewConv['nb_messages'] ?></div>
    <div><strong>Page :</strong> <code style="font-size:.78rem"><?= htmlspecialchars($viewConv['page_origine'] ?? '/') ?></code></div>
    <div><strong>IP :</strong> <?= htmlspecialchars($viewConv['ip'] ?? '--') ?></div>
    <div><strong>Statut :</strong>
      <?php
      $sc = ['ouvert'=>'#f59e0b','ferme'=>'#6c7a91','lead'=>'#10b981'];
      $sc2 = $sc[$viewConv['statut']] ?? '#6c7a91';
      ?>
      <span style="background:<?= $sc2 ?>;color:#fff;padding:2px 10px;border-radius:10px;font-size:.75rem;font-weight:700">
        <?= ucfirst($viewConv['statut']) ?>
      </span>
    </div>
    <div><strong>Début :</strong> <?= date('d/m/Y H:i', strtotime($viewConv['created_at'])) ?></div>
  </div>

  <!-- Actions -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
    <form method="post" style="display:inline">
      <?= csrf_field() ?>
      <input type="hidden" name="conv_id" value="<?= $viewConv['id'] ?>">
      <button name="set_statut" value="lead" class="btn btn-outline" style="font-size:.78rem;padding:5px 12px;border-color:#10b981;color:#10b981">
        <i class="fas fa-star"></i> Marquer Lead
      </button>
      <button name="set_statut" value="ferme" class="btn btn-outline" style="font-size:.78rem;padding:5px 12px;border-color:#6c7a91;color:#6c7a91">
        <i class="fas fa-lock"></i> Fermer
      </button>
    </form>
    <?php if ($viewConv['visiteur_email']): ?>
    <a href="mailto:<?= htmlspecialchars($viewConv['visiteur_email']) ?>" class="btn btn-primary" style="font-size:.78rem;padding:5px 12px;">
      <i class="fas fa-envelope"></i> Répondre par email
    </a>
    <?php endif; ?>
    <?php if ($viewConv['visiteur_tel']): ?>
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $viewConv['visiteur_tel']) ?>" target="_blank" class="btn btn-outline" style="font-size:.78rem;padding:5px 12px;border-color:#25d366;color:#25d366">
      <i class="fab fa-whatsapp"></i> WhatsApp
    </a>
    <?php endif; ?>
  </div>

  <!-- Thread -->
  <div class="conv-thread">
    <?php foreach ($messages as $m): ?>
    <div class="conv-msg <?= $m['role'] ?>">
      <div class="conv-avatar <?= $m['role'] ?>"><?= $m['role'] === 'bot' ? 'I' : 'V' ?></div>
      <div>
        <div class="bubble"><?= htmlspecialchars($m['contenu']) ?></div>
        <?php if ($m['intent'] && $m['role'] === 'bot'): ?>
        <span class="intent-badge"><?= htmlspecialchars($m['intent']) ?></span>
        <?php endif; ?>
        <div style="font-size:.62rem;color:#6c7a91;margin-top:2px"><?= date('H:i', strtotime($m['created_at'])) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($messages)): ?>
    <p style="color:#6c7a91;font-size:.85rem;text-align:center">Aucun message dans cette conversation.</p>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- LIST VIEW -->
<?= flash_html() ?>

<form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
  <input type="hidden" name="tab" value="conversations">
  <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher (nom, email, tél)…" class="form-control" style="max-width:260px">
  <select name="statut" class="form-control" style="max-width:160px">
    <option value="">Tous statuts</option>
    <?php foreach (['ouvert','ferme','lead'] as $s): ?>
    <option value="<?= $s ?>"<?= $statut===$s?' selected':'' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-primary" style="padding:8px 18px">Filtrer</button>
  <?php if ($search || $statut): ?>
  <a href="?tab=conversations" class="btn btn-outline" style="padding:8px 14px">Réinitialiser</a>
  <?php endif; ?>
</form>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Visiteur</th>
        <th>Contact</th>
        <th>Lang</th>
        <th>Msgs</th>
        <th>Statut</th>
        <th>Page</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($conversations)): ?>
      <tr><td colspan="9" style="text-align:center;color:#6c7a91;padding:24px">Aucune conversation.</td></tr>
    <?php else: ?>
    <?php foreach ($conversations as $c):
      $statusColors = ['ouvert'=>'#f59e0b','ferme'=>'#6c7a91','lead'=>'#10b981'];
      $sc = $statusColors[$c['statut']] ?? '#6c7a91';
    ?>
    <tr>
      <td><small style="color:#6c7a91">#<?= $c['id'] ?></small></td>
      <td><?= $c['visiteur_nom'] ? htmlspecialchars($c['visiteur_nom']) : '<em style="color:#6c7a91">Anonyme</em>' ?></td>
      <td>
        <?php if ($c['visiteur_email']): ?>
          <a href="mailto:<?= htmlspecialchars($c['visiteur_email']) ?>" style="font-size:.8rem"><?= htmlspecialchars($c['visiteur_email']) ?></a><br>
        <?php endif; ?>
        <?php if ($c['visiteur_tel']): ?>
          <small><?= htmlspecialchars($c['visiteur_tel']) ?></small>
        <?php endif; ?>
        <?php if (!$c['visiteur_email'] && !$c['visiteur_tel']): ?>
          <em style="color:#6c7a91;font-size:.8rem">--</em>
        <?php endif; ?>
      </td>
      <td><span style="text-transform:uppercase;font-weight:700;font-size:.75rem"><?= $c['langue'] ?></span></td>
      <td style="text-align:center"><?= $c['nb_messages'] ?></td>
      <td>
        <span style="background:<?= $sc ?>;color:#fff;padding:2px 9px;border-radius:10px;font-size:.72rem;font-weight:700">
          <?= ucfirst($c['statut']) ?>
        </span>
      </td>
      <td><code style="font-size:.72rem"><?= htmlspecialchars(substr($c['page_origine'] ?? '/', 0, 30)) ?></code></td>
      <td style="white-space:nowrap;font-size:.8rem"><?= date('d/m H:i', strtotime($c['updated_at'])) ?></td>
      <td style="white-space:nowrap">
        <a href="?tab=conversations&view=<?= $c['id'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem" title="Voir thread">
          <i class="fas fa-eye"></i>
        </a>
        <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cette conversation ?')">
          <?= csrf_field() ?>
          <input type="hidden" name="conv_id" value="<?= $c['id'] ?>">
          <button name="delete_conv" value="1" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem;border-color:#e63946;color:#e63946" title="Supprimer">
            <i class="fas fa-trash"></i>
          </button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- PAGINATION -->
<?php if ($pg['total_pages'] > 1): ?>
<div class="pagination" style="margin-top:16px">
  <?php for ($i=1; $i<=$pg['total_pages']; $i++): ?>
  <a href="?tab=conversations&p=<?= $i ?>&q=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>"
     class="page-btn<?= $i===$page?' active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php endif; // end view vs list ?>

<?php elseif ($tab === 'leads'): ?>
<!-- --- LEADS TAB ------------------------------------------- -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <h2 style="margin:0;font-size:1.05rem;color:#1a2c4e">Leads captés par le chatbot</h2>
  <a href="?export=leads" class="btn btn-primary" style="font-size:.82rem;padding:7px 16px">
    <i class="fas fa-file-csv"></i> Exporter CSV
  </a>
</div>

<?php
$leads = $pdo->query("
    SELECT * FROM chatbot_conversations
    WHERE statut='lead' OR visiteur_email IS NOT NULL OR visiteur_tel IS NOT NULL
    ORDER BY created_at DESC
    LIMIT 200
")->fetchAll();
?>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Nom</th>
        <th>Email</th>
        <th>Téléphone</th>
        <th>Lang</th>
        <th>Statut</th>
        <th>Messages</th>
        <th>Page d'entrée</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($leads)): ?>
      <tr><td colspan="10" style="text-align:center;color:#6c7a91;padding:24px">Aucun lead capté pour l'instant.</td></tr>
    <?php else: ?>
    <?php foreach ($leads as $l):
      $statusColors = ['ouvert'=>'#f59e0b','ferme'=>'#6c7a91','lead'=>'#10b981'];
      $sc = $statusColors[$l['statut']] ?? '#6c7a91';
    ?>
    <tr>
      <td><small style="color:#6c7a91">#<?= $l['id'] ?></small></td>
      <td><?= $l['visiteur_nom'] ? htmlspecialchars($l['visiteur_nom']) : '<em style="color:#6c7a91">--</em>' ?></td>
      <td><?= $l['visiteur_email'] ? '<a href="mailto:'.htmlspecialchars($l['visiteur_email']).'">'.htmlspecialchars($l['visiteur_email']).'</a>' : '<em style="color:#6c7a91">--</em>' ?></td>
      <td>
        <?php if ($l['visiteur_tel']): ?>
          <?= htmlspecialchars($l['visiteur_tel']) ?>
          <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $l['visiteur_tel']) ?>" target="_blank" style="color:#25d366;margin-left:4px" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
          </a>
        <?php else: ?><em style="color:#6c7a91">--</em><?php endif; ?>
      </td>
      <td style="text-transform:uppercase;font-weight:700;font-size:.75rem"><?= $l['langue'] ?></td>
      <td>
        <span style="background:<?= $sc ?>;color:#fff;padding:2px 9px;border-radius:10px;font-size:.72rem;font-weight:700">
          <?= ucfirst($l['statut']) ?>
        </span>
      </td>
      <td style="text-align:center"><?= $l['nb_messages'] ?></td>
      <td><code style="font-size:.72rem"><?= htmlspecialchars(substr($l['page_origine'] ?? '/', 0, 30)) ?></code></td>
      <td style="white-space:nowrap;font-size:.8rem"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
      <td style="white-space:nowrap">
        <a href="?tab=conversations&view=<?= $l['id'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:.75rem" title="Voir conversation">
          <i class="fas fa-eye"></i>
        </a>
        <?php if ($l['visiteur_email']): ?>
        <a href="mailto:<?= htmlspecialchars($l['visiteur_email']) ?>" class="btn btn-primary" style="padding:4px 10px;font-size:.75rem" title="Envoyer email">
          <i class="fas fa-envelope"></i>
        </a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php elseif ($tab === 'settings'): ?>
<!-- --- SETTINGS TAB ---------------------------------------- -->
<?= flash_html() ?>

<form method="post" style="max-width:720px">
  <?= csrf_field() ?>

  <div class="admin-card" style="padding:24px;margin-bottom:20px">
    <h3 style="margin:0 0 18px;font-size:1rem;color:#1a2c4e;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
      <i class="fas fa-robot" style="color:#053067;margin-right:8px"></i>Comportement du chatbot
    </h3>

    <div class="form-group">
      <label class="form-label">Chatbot actif</label>
      <div style="display:flex;gap:16px">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="chatbot_actif" value="1"<?= $settings['chatbot_actif']==='1'?' checked':'' ?>> Oui
        </label>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="chatbot_actif" value="0"<?= $settings['chatbot_actif']!=='1'?' checked':'' ?>> Non
        </label>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="chatbot_nom">Nom de l'assistant</label>
      <input type="text" id="chatbot_nom" name="chatbot_nom" class="form-control" value="<?= htmlspecialchars($settings['chatbot_nom']) ?>">
    </div>

    <div class="form-group">
      <label class="form-label" for="chatbot_bienvenue_fr">Message de bienvenue (Français)</label>
      <textarea id="chatbot_bienvenue_fr" name="chatbot_bienvenue_fr" class="form-control" rows="3"><?= htmlspecialchars($settings['chatbot_bienvenue_fr']) ?></textarea>
      <small style="color:#6c7a91">Supporte le Markdown léger : **gras**, *italique*</small>
    </div>

    <div class="form-group">
      <label class="form-label" for="chatbot_bienvenue_en">Message de bienvenue (English)</label>
      <textarea id="chatbot_bienvenue_en" name="chatbot_bienvenue_en" class="form-control" rows="3"><?= htmlspecialchars($settings['chatbot_bienvenue_en']) ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="form-group">
        <label class="form-label" for="chatbot_couleur">Couleur principale</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="color" id="chatbot_couleur" name="chatbot_couleur" value="<?= htmlspecialchars($settings['chatbot_couleur']) ?>" style="width:44px;height:36px;padding:2px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer">
          <input type="text" value="<?= htmlspecialchars($settings['chatbot_couleur']) ?>" class="form-control" style="max-width:120px" oninput="document.getElementById('chatbot_couleur').value=this.value">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="chatbot_position">Position</label>
        <select id="chatbot_position" name="chatbot_position" class="form-control">
          <option value="right"<?= $settings['chatbot_position']==='right'?' selected':'' ?>>Droite</option>
          <option value="left"<?= $settings['chatbot_position']==='left'?' selected':'' ?>>Gauche</option>
        </select>
      </div>
    </div>
  </div>

  <div class="admin-card" style="padding:24px;margin-bottom:20px">
    <h3 style="margin:0 0 18px;font-size:1rem;color:#1a2c4e;border-bottom:1px solid #e2e8f0;padding-bottom:10px">
      <i class="fas fa-brain" style="color:#1414D4;margin-right:8px"></i>Mentor IA — Fallback intelligent
    </h3>
    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:.84rem;color:#0369a1">
      <i class="fas fa-info-circle"></i>
      Lorsque l'IA est activée, le chatbot utilise le modèle choisi en fallback pour les questions hors-catalogue.
    </div>

    <div class="form-group">
      <label class="form-label">Activer le Mentor IA</label>
      <div style="display:flex;gap:16px">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="chatbot_ai_actif" value="1"<?= $settings['chatbot_ai_actif']==='1'?' checked':'' ?>> Oui
        </label>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="chatbot_ai_actif" value="0"<?= $settings['chatbot_ai_actif']!=='1'?' checked':'' ?>> Non
        </label>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="chatbot_ai_provider">Fournisseur IA</label>
      <select id="chatbot_ai_provider" name="chatbot_ai_provider" class="form-control" onchange="updateAIProvider(this.value)">
        <option value="groq"<?= $settings['chatbot_ai_provider']==='groq'?' selected':'' ?>>
          Groq (Llama 3) — Gratuit · console.groq.com
        </option>
        <option value="gemini"<?= $settings['chatbot_ai_provider']==='gemini'?' selected':'' ?>>
          Google Gemini — Gratuit · aistudio.google.com
        </option>
        <option value="claude"<?= $settings['chatbot_ai_provider']==='claude'?' selected':'' ?>>
          Claude (Anthropic) — Payant · console.anthropic.com
        </option>
      </select>
    </div>

    <!-- Info par fournisseur -->
    <div id="info-groq" class="ai-provider-info" style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:.82rem;color:#166534;<?= $settings['chatbot_ai_provider']!=='groq'?'display:none':'' ?>">
      <strong>Groq — API gratuite</strong><br>
      14 400 requêtes/jour · Réponses ultra-rapides (Llama 3)<br>
      Créez votre clé sur <strong>console.groq.com</strong> → API Keys → Create API key
    </div>
    <div id="info-gemini" class="ai-provider-info" style="background:#fefce8;border:1px solid #fde047;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:.82rem;color:#713f12;<?= $settings['chatbot_ai_provider']!=='gemini'?'display:none':'' ?>">
      <strong>Google Gemini — API gratuite</strong><br>
      15 requêtes/min · 1 million tokens/jour (Gemini 1.5 Flash)<br>
      Créez votre clé sur <strong>aistudio.google.com</strong> → Get API key
    </div>
    <div id="info-claude" class="ai-provider-info" style="background:#fdf2f8;border:1px solid #f0abfc;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:.82rem;color:#701a75;<?= $settings['chatbot_ai_provider']!=='claude'?'display:none':'' ?>">
      <strong>Claude (Anthropic) — API payante</strong><br>
      Très haute qualité · Tarif à l'usage<br>
      Obtenez votre clé sur <strong>console.anthropic.com</strong> → API Keys
    </div>

    <div class="form-group">
      <label class="form-label" for="chatbot_ai_key" id="ai-key-label">Clé API</label>
      <input type="password" id="chatbot_ai_key" name="chatbot_ai_key" class="form-control"
             value="<?= htmlspecialchars($settings['chatbot_ai_key']) ?>"
             placeholder="Collez votre clé API ici…"
             autocomplete="new-password">
      <small style="color:#6c7a91">La clé est stockée en base de données. Utilisez un compte dédié.</small>
    </div>

    <div class="form-group">
      <label class="form-label" for="chatbot_ai_model">Modèle</label>
      <select id="chatbot_ai_model" name="chatbot_ai_model" class="form-control">
        <?php
        $allModels = [
          'groq' => [
            'llama-3.1-8b-instant'     => 'Llama 3.1 8B Instant (le plus rapide — recommandé)',
            'llama-3.3-70b-versatile'  => 'Llama 3.3 70B Versatile (plus capable)',
            'mixtral-8x7b-32768'       => 'Mixtral 8x7B (grande fenêtre de contexte)',
          ],
          'gemini' => [
            'gemini-1.5-flash'   => 'Gemini 1.5 Flash (rapide — recommandé)',
            'gemini-2.0-flash'   => 'Gemini 2.0 Flash (plus récent)',
            'gemini-1.5-pro'     => 'Gemini 1.5 Pro (le plus capable)',
          ],
          'claude' => [
            'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (rapide, économique)',
            'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 (équilibré)',
            'claude-opus-4-6'           => 'Claude Opus 4.6 (le plus capable)',
          ],
        ];
        foreach ($allModels as $provider => $models) {
          foreach ($models as $mid => $mlabel): ?>
        <option value="<?= $mid ?>"
                data-provider="<?= $provider ?>"
                <?= $settings['chatbot_ai_model']===$mid?' selected':'' ?>>
          <?= $mlabel ?>
        </option>
        <?php endforeach; } ?>
      </select>
    </div>
  </div>

<script>
const modelsByProvider = {
  groq:   ['llama-3.1-8b-instant','llama-3.3-70b-versatile','mixtral-8x7b-32768'],
  gemini: ['gemini-1.5-flash','gemini-2.0-flash','gemini-1.5-pro'],
  claude: ['claude-haiku-4-5-20251001','claude-sonnet-4-6','claude-opus-4-6'],
};

function updateAIProvider(provider) {
  document.querySelectorAll('.ai-provider-info').forEach(el => el.style.display = 'none');
  var info = document.getElementById('info-' + provider);
  if (info) info.style.display = '';

  var sel = document.getElementById('chatbot_ai_model');
  var allowed = modelsByProvider[provider] || [];
  Array.from(sel.options).forEach(function(opt) {
    opt.style.display = allowed.includes(opt.value) ? '' : 'none';
  });
  // Sélectionner le premier modèle visible
  var first = Array.from(sel.options).find(o => allowed.includes(o.value));
  if (first && !allowed.includes(sel.value)) sel.value = first.value;

  var keyLabel = document.getElementById('ai-key-label');
  var labels = {groq:'Clé API Groq', gemini:'Clé API Google AI Studio', claude:'Clé API Anthropic'};
  if (keyLabel) keyLabel.textContent = labels[provider] || 'Clé API';
}

// Init au chargement
updateAIProvider(document.getElementById('chatbot_ai_provider').value);
</script>

  <div style="display:flex;gap:10px">
    <button type="submit" name="save_settings" value="1" class="btn btn-primary">
      <i class="fas fa-save"></i> Enregistrer les paramètres
    </button>
    <a href="?tab=settings" class="btn btn-outline">Annuler</a>
  </div>
</form>

<?php endif; // tabs ?>

</main>
</div>
</body>
</html>
