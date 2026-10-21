<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$pdo  = db();
$lang = current_lang();

/* ── Filtres ───────────────────────────────────────────── */
$cat    = (int)($_GET['cat']    ?? 0);
$search = sanitize($_GET['q']   ?? '');
$sort   = in_array($_GET['sort'] ?? '', ['prix_asc','prix_desc','nouveau','vedette']) ? $_GET['sort'] : 'vedette';
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage = 12;

/* ── Catégories ─────────────────────────────────────────── */
$categories = $pdo->query("SELECT * FROM shop_categories ORDER BY ordre ASC")->fetchAll();

/* ── Produit unique (modal) ──────────────────────────────── */
$produit = null;
if (isset($_GET['produit'])) {
    $slug = sanitize($_GET['produit']);
    $stmt = $pdo->prepare("SELECT p.*, c.nom AS cat_nom FROM shop_produits p LEFT JOIN shop_categories c ON c.id=p.categorie_id WHERE p.slug=? AND p.est_publie=1");
    $stmt->execute([$slug]);
    $produit = $stmt->fetch();
    if ($produit) {
        $pdo->prepare("UPDATE shop_produits SET vues=vues+1 WHERE id=?")->execute([$produit['id']]);
    }
}

/* ── Liste produits ──────────────────────────────────────── */
$where  = 'p.est_publie=1';
$params = [];
if ($cat) {
    $where .= ' AND p.categorie_id=?';
    $params[] = $cat;
}
if ($search) {
    $where .= ' AND (p.nom LIKE ? OR p.description LIKE ? OR p.marque LIKE ? OR p.reference LIKE ?)';
    $params = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]);
}
$orderBy = match($sort) {
    'prix_asc'  => 'p.prix ASC',
    'prix_desc' => 'p.prix DESC NULLS LAST',
    'nouveau'   => 'p.est_nouveau DESC, p.created_at DESC',
    default     => 'p.est_vedette DESC, p.ordre ASC',
};

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM shop_produits p WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pg    = paginate($total, $page, $perPage);

$stmt = $pdo->prepare("SELECT p.*, c.nom AS cat_nom, c.slug AS cat_slug
    FROM shop_produits p LEFT JOIN shop_categories c ON c.id=p.categorie_id
    WHERE $where ORDER BY $orderBy LIMIT $perPage OFFSET {$pg['offset']}");
$stmt->execute($params);
$produits = $stmt->fetchAll();

/* ── Produits vedette (sidebar) ──────────────────────────── */
$vedettes = $pdo->query("SELECT * FROM shop_produits WHERE est_publie=1 AND est_vedette=1 ORDER BY ordre ASC LIMIT 4")->fetchAll();

/* ── SEO ─────────────────────────────────────────────────── */
$pageSeo = ['meta_title'=>'IgoShop — Matériel Informatique & Solaire | IgoTech','meta_desc'=>'Achetez ou demandez un devis pour votre matériel informatique, équipements solaires et accessoires réseau. Livraison à N\'Djamena, Tchad.'];
?>

<style>
/* ── IgoShop styles ───────────────────────────────── */
.shop-hero{background:linear-gradient(135deg,#053067 0%,#0a4f9e 100%);padding:56px 0 40px;color:#fff;text-align:center}
.shop-hero h1{font-size:2.2rem;font-weight:800;margin-bottom:10px}
.shop-hero p{font-size:1rem;opacity:.85;max-width:600px;margin:0 auto 24px}
.shop-search-bar{display:flex;max-width:520px;margin:0 auto;background:#fff;border-radius:50px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.2)}
.shop-search-bar input{flex:1;padding:13px 20px;border:none;outline:none;font-size:.95rem;color:#1a2c4e}
.shop-search-bar button{padding:12px 22px;background:#e63946;color:#fff;border:none;cursor:pointer;font-weight:700;font-size:.9rem;transition:.2s}
.shop-search-bar button:hover{background:#c1121f}

.shop-layout{display:grid;grid-template-columns:240px 1fr;gap:28px;padding:40px 0 60px;align-items:start}
@media(max-width:900px){.shop-layout{grid-template-columns:1fr}.shop-sidebar{display:none}}

/* Sidebar */
.shop-sidebar{}
.sidebar-box{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:20px}
.sidebar-box h4{font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#6c7a91;margin-bottom:14px}
.cat-list{list-style:none;padding:0;margin:0}
.cat-list li a{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;color:#374151;font-size:.88rem;text-decoration:none;transition:.15s}
.cat-list li a:hover,.cat-list li a.active{background:#eff6ff;color:#053067;font-weight:600}
.cat-list li a i{width:18px;text-align:center;color:#053067}
.cat-badge{margin-left:auto;background:#e0e7ff;color:#3730a3;font-size:.68rem;font-weight:700;padding:1px 7px;border-radius:8px}

/* Toolbar */
.shop-toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px}
.shop-count{font-size:.88rem;color:#6c7a91}
.shop-sort select{padding:7px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.85rem;color:#374151;outline:none;cursor:pointer}

/* Grille produits */
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px}
.product-card{background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);transition:transform .2s,box-shadow .2s;cursor:pointer;border:1px solid #f1f5f9}
.product-card:hover{transform:translateY(-4px);box-shadow:0 8px 28px rgba(5,48,103,.14)}
.product-img{position:relative;height:180px;overflow:hidden;background:#f8fafc}
.product-img img{width:100%;height:100%;object-fit:contain;padding:12px;transition:transform .3s}
.product-card:hover .product-img img{transform:scale(1.05)}
.product-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem;color:#cbd5e1}
.badge-new{position:absolute;top:10px;left:10px;background:#10b981;color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:10px;text-transform:uppercase}
.badge-promo{position:absolute;top:10px;right:10px;background:#e63946;color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:10px}
.badge-vedette{position:absolute;top:10px;left:10px;background:#f59e0b;color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:10px}
.product-body{padding:14px}
.product-cat{font-size:.7rem;color:#6c7a91;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}
.product-name{font-size:.9rem;font-weight:700;color:#1a2c4e;margin-bottom:6px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.product-brand{font-size:.75rem;color:#6c7a91;margin-bottom:8px}
.product-price{font-size:1.05rem;font-weight:800;color:#053067}
.product-price-devis{font-size:.88rem;font-weight:700;color:#f59e0b;font-style:italic}
.product-price-old{font-size:.78rem;color:#9ca3af;text-decoration:line-through;margin-left:6px}
.product-stock{font-size:.72rem;margin-top:6px}
.stock-ok{color:#059669}.stock-cmd{color:#f59e0b}.stock-rupture{color:#e63946}
.btn-devis{display:block;width:100%;margin-top:12px;padding:9px;background:#053067;color:#fff;border:none;border-radius:8px;font-size:.82rem;font-weight:700;cursor:pointer;text-align:center;transition:.2s;text-decoration:none}
.btn-devis:hover{background:#0a4f9e;color:#fff}

/* Modal devis */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:3000;align-items:center;justify-content:center;padding:20px}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.modal-head{background:linear-gradient(135deg,#053067,#0a4f9e);color:#fff;padding:20px 24px;border-radius:16px 16px 0 0;display:flex;align-items:flex-start;gap:16px}
.modal-prod-img{width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
.modal-prod-img img{width:100%;height:100%;object-fit:contain;padding:4px}
.modal-head-info h3{font-size:1rem;font-weight:700;margin:0 0 4px}
.modal-head-info p{font-size:.8rem;opacity:.8;margin:0}
.modal-close{margin-left:auto;background:none;border:none;color:#fff;font-size:1.3rem;cursor:pointer;padding:4px;line-height:1;opacity:.8}
.modal-close:hover{opacity:1}
.modal-body{padding:24px}
.modal-body .form-group{margin-bottom:14px}
.modal-body label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:5px}
.modal-body input,.modal-body textarea,.modal-body select{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.88rem;color:#1a2c4e;outline:none;transition:.15s}
.modal-body input:focus,.modal-body textarea:focus{border-color:#053067;box-shadow:0 0 0 3px rgba(5,48,103,.1)}
.modal-body textarea{resize:vertical;min-height:80px}
.modal-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.modal-submit{width:100%;padding:12px;background:#053067;color:#fff;border:none;border-radius:10px;font-size:.95rem;font-weight:700;cursor:pointer;transition:.2s;margin-top:4px}
.modal-submit:hover{background:#0a4f9e}
.modal-msg{padding:12px;border-radius:8px;font-size:.875rem;margin-top:12px;display:none}
.modal-msg.success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
.modal-msg.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}

/* Vedettes sidebar */
.vedette-item{display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9}
.vedette-item:last-child{border-bottom:none}
.vedette-img{width:48px;height:48px;border-radius:8px;object-fit:contain;background:#f8fafc;flex-shrink:0;padding:4px}
.vedette-info .vname{font-size:.8rem;font-weight:600;color:#1a2c4e;line-height:1.3}
.vedette-info .vprice{font-size:.78rem;color:#053067;font-weight:700;margin-top:2px}

/* Vide */
.shop-empty{text-align:center;padding:60px 20px;color:#6c7a91}
.shop-empty i{font-size:3rem;margin-bottom:16px;opacity:.4}
</style>

<!-- HERO SHOP -->
<section class="shop-hero">
  <div class="container">
    <div class="section-label" style="color:rgba(255,255,255,.7)">IgoShop</div>
    <h1><?= t('Matériel Informatique & Énergie Solaire', 'IT Equipment & Solar Energy') ?></h1>
    <p><?= t('Équipements professionnels pour entreprises et particuliers — livraison à N\'Djamena', 'Professional equipment for businesses and individuals — delivery in N\'Djamena') ?></p>
    <form class="shop-search-bar" method="GET" action="/shop.php">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
             placeholder="<?= t('Rechercher un produit, une marque…', 'Search a product, brand…') ?>">
      <button type="submit"><i class="fas fa-search"></i> <?= t('Chercher', 'Search') ?></button>
    </form>
  </div>
</section>

<div class="container">
  <div class="shop-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="shop-sidebar">

      <!-- Catégories -->
      <div class="sidebar-box">
        <h4><?= t('Catégories', 'Categories') ?></h4>
        <ul class="cat-list">
          <li>
            <a href="/shop.php<?= $search ? '?q='.urlencode($search) : '' ?>"
               class="<?= !$cat ? 'active' : '' ?>">
              <i class="fas fa-th-large"></i>
              <?= t('Tous les produits', 'All products') ?>
              <span class="cat-badge"><?= $total ?></span>
            </a>
          </li>
          <?php foreach ($categories as $c):
            $cCount = (int)$pdo->prepare("SELECT COUNT(*) FROM shop_produits WHERE categorie_id=? AND est_publie=1")->execute([$c['id']]) ? $pdo->prepare("SELECT COUNT(*) FROM shop_produits WHERE categorie_id=? AND est_publie=1")->execute([$c['id']]) : 0;
            $cs = $pdo->prepare("SELECT COUNT(*) FROM shop_produits WHERE categorie_id=? AND est_publie=1");
            $cs->execute([$c['id']]);
            $cCount = $cs->fetchColumn();
          ?>
          <li>
            <a href="/shop.php?cat=<?= $c['id'] ?><?= $search ? '&q='.urlencode($search) : '' ?>"
               class="<?= $cat === (int)$c['id'] ? 'active' : '' ?>">
              <i class="<?= htmlspecialchars($c['icone']) ?>"></i>
              <?= t(htmlspecialchars($c['nom']), htmlspecialchars($c['nom_en'] ?? $c['nom'])) ?>
              <span class="cat-badge"><?= $cCount ?></span>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Produits vedettes -->
      <?php if (!empty($vedettes)): ?>
      <div class="sidebar-box">
        <h4><?= t('Nos vedettes', 'Featured') ?></h4>
        <?php foreach ($vedettes as $v): ?>
        <div class="vedette-item" style="cursor:pointer" onclick="openDevisModal(<?= $v['id'] ?>,'<?= addslashes(htmlspecialchars($v['nom'])) ?>','<?= htmlspecialchars($v['image'] ?? '') ?>')">
          <?php if ($v['image']): ?>
          <img src="<?= htmlspecialchars($v['image']) ?>" alt="" class="vedette-img">
          <?php else: ?>
          <div class="vedette-img" style="display:flex;align-items:center;justify-content:center"><i class="fas fa-box" style="color:#cbd5e1"></i></div>
          <?php endif; ?>
          <div class="vedette-info">
            <div class="vname"><?= t(htmlspecialchars($v['nom']), htmlspecialchars($v['nom_en'] ?? $v['nom'])) ?></div>
            <div class="vprice">
              <?= $v['prix'] ? number_format($v['prix'], 0, ',', ' ') . ' FCFA' : t('Sur devis', 'On request') ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- CTA contact -->
      <div class="sidebar-box" style="background:linear-gradient(135deg,#053067,#0a4f9e);color:#fff;text-align:center">
        <i class="fas fa-headset" style="font-size:2rem;margin-bottom:10px;opacity:.9"></i>
        <h4 style="color:#fff;margin-bottom:8px"><?= t('Besoin de conseil ?', 'Need advice?') ?></h4>
        <p style="font-size:.8rem;opacity:.85;margin-bottom:14px"><?= t('Notre équipe vous aide à choisir le bon équipement.', 'Our team helps you choose the right equipment.') ?></p>
        <a href="tel:+23563038780" class="btn" style="background:#fff;color:#053067;font-size:.82rem;padding:8px 16px;border-radius:8px;font-weight:700;text-decoration:none">
          <i class="fas fa-phone"></i> +235 63 03 87 80
        </a>
      </div>

    </aside>

    <!-- ── CONTENU ── -->
    <div>

      <!-- Toolbar -->
      <div class="shop-toolbar">
        <p class="shop-count">
          <?php if ($search): ?>
          <strong><?= $total ?></strong> <?= t('résultat(s) pour', 'result(s) for') ?> "<em><?= htmlspecialchars($search) ?></em>"
          <?php else: ?>
          <strong><?= $total ?></strong> <?= t('produit(s)', 'product(s)') ?>
          <?php if ($cat): ?>
          — <?= htmlspecialchars($categories[array_search($cat, array_column($categories,'id'))]['nom'] ?? '') ?>
          <?php endif; ?>
          <?php endif; ?>
        </p>
        <div class="shop-sort">
          <select onchange="window.location=this.value">
            <?php foreach ([
              'vedette'   => t('Mis en avant','Featured'),
              'nouveau'   => t('Nouveautés','New arrivals'),
              'prix_asc'  => t('Prix croissant','Price: low to high'),
              'prix_desc' => t('Prix décroissant','Price: high to low'),
            ] as $val => $label): ?>
            <option value="/shop.php?<?= http_build_query(array_merge($_GET, ['sort'=>$val])) ?>"
                    <?= $sort===$val?' selected':'' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Grille -->
      <?php if (empty($produits)): ?>
      <div class="shop-empty">
        <i class="fas fa-box-open"></i>
        <p><?= t('Aucun produit trouvé.','No products found.') ?></p>
        <a href="/shop.php" class="btn btn-outline" style="margin-top:12px"><?= t('Voir tout','See all') ?></a>
      </div>
      <?php else: ?>
      <div class="products-grid">
        <?php foreach ($produits as $p):
          $hasPromo = $p['prix_promo'] && $p['prix_promo'] < $p['prix'];
          $displayPrice = $hasPromo ? $p['prix_promo'] : $p['prix'];
        ?>
        <div class="product-card" data-aos="fade-up">
          <div class="product-img">
            <?php if ($p['image']): ?>
            <img src="<?= htmlspecialchars($p['image']) ?>"
                 alt="<?= htmlspecialchars($p['nom']) ?>"
                 onerror="this.parentNode.innerHTML='<div class=product-img-placeholder><i class=fas fa-box></i></div>'">
            <?php else: ?>
            <div class="product-img-placeholder"><i class="fas fa-box"></i></div>
            <?php endif; ?>
            <?php if ($p['est_nouveau']): ?>
            <span class="badge-new">Nouveau</span>
            <?php elseif ($p['est_vedette']): ?>
            <span class="badge-vedette"><i class="fas fa-star"></i> Vedette</span>
            <?php endif; ?>
            <?php if ($hasPromo): ?>
            <span class="badge-promo">-<?= round((1-$p['prix_promo']/$p['prix'])*100) ?>%</span>
            <?php endif; ?>
          </div>
          <div class="product-body">
            <?php if ($p['cat_nom']): ?>
            <div class="product-cat"><?= htmlspecialchars($p['cat_nom']) ?></div>
            <?php endif; ?>
            <div class="product-name"><?= t(htmlspecialchars($p['nom']), htmlspecialchars($p['nom_en'] ?? $p['nom'])) ?></div>
            <?php if ($p['marque']): ?>
            <div class="product-brand"><i class="fas fa-tag" style="font-size:.65rem"></i> <?= htmlspecialchars($p['marque']) ?></div>
            <?php endif; ?>
            <div>
              <?php if ($displayPrice): ?>
              <span class="product-price"><?= number_format($displayPrice, 0, ',', ' ') ?> <small>FCFA</small></span>
              <?php if ($hasPromo): ?>
              <span class="product-price-old"><?= number_format($p['prix'], 0, ',', ' ') ?></span>
              <?php endif; ?>
              <?php else: ?>
              <span class="product-price-devis"><i class="fas fa-file-invoice"></i> <?= t('Prix sur devis','Quote only') ?></span>
              <?php endif; ?>
            </div>
            <div class="product-stock">
              <?php if ($p['stock'] === 'disponible'): ?>
              <i class="fas fa-check-circle stock-ok"></i> <span class="stock-ok"><?= t('En stock','In stock') ?></span>
              <?php elseif ($p['stock'] === 'commande'): ?>
              <i class="fas fa-clock stock-cmd"></i> <span class="stock-cmd"><?= t('Sur commande','On order') ?></span>
              <?php else: ?>
              <i class="fas fa-times-circle stock-rupture"></i> <span class="stock-rupture"><?= t('Rupture','Out of stock') ?></span>
              <?php endif; ?>
            </div>
            <button class="btn-devis"
                    onclick="openDevisModal(<?= $p['id'] ?>,'<?= addslashes(htmlspecialchars($p['nom'])) ?>','<?= htmlspecialchars($p['image'] ?? '') ?>')">
              <i class="fas fa-file-invoice"></i> <?= t('Demander un devis','Request a quote') ?>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pg['total_pages'] > 1): ?>
      <div class="pagination" style="margin-top:32px">
        <?php for ($i=1; $i<=$pg['total_pages']; $i++): ?>
        <a href="/shop.php?<?= http_build_query(array_merge($_GET,['p'=>$i])) ?>"
           class="page-btn<?= $i===$page?' active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- ═══════════ MODAL DEVIS ═══════════ -->
<div class="modal-overlay" id="devisModal" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-prod-img">
        <img src="" alt="" id="modalProdImg" onerror="this.style.display='none'">
      </div>
      <div class="modal-head-info">
        <h3><?= t('Demande de devis','Quote Request') ?></h3>
        <p id="modalProdNom">—</p>
      </div>
      <button class="modal-close" onclick="closeDevisModal()" aria-label="Fermer">&times;</button>
    </div>
    <div class="modal-body">
      <form id="devisForm" novalidate>
        <input type="hidden" name="produit_id" id="modalProduitId">
        <input type="hidden" name="produit_nom" id="modalProduitNom">

        <div class="modal-grid-2">
          <div class="form-group">
            <label><?= t('Nom complet','Full name') ?> *</label>
            <input type="text" name="nom" required placeholder="<?= t('Votre nom','Your name') ?>">
          </div>
          <div class="form-group">
            <label><?= t('Organisation','Organization') ?></label>
            <input type="text" name="organisation" placeholder="<?= t('Entreprise (optionnel)','Company (optional)') ?>">
          </div>
        </div>
        <div class="modal-grid-2">
          <div class="form-group">
            <label><?= t('Email','Email') ?> *</label>
            <input type="email" name="email" required placeholder="email@exemple.com">
          </div>
          <div class="form-group">
            <label><?= t('Téléphone','Phone') ?> *</label>
            <input type="tel" name="telephone" required placeholder="+235 XX XX XX XX">
          </div>
        </div>
        <div class="form-group">
          <label><?= t('Quantité souhaitée','Desired quantity') ?></label>
          <input type="number" name="quantite" value="1" min="1" max="9999"
                 style="max-width:120px">
        </div>
        <div class="form-group">
          <label><?= t('Message / précisions','Message / details') ?></label>
          <textarea name="message" placeholder="<?= t('Décrivez votre besoin, configuration souhaitée…','Describe your need, desired configuration…') ?>"></textarea>
        </div>
        <button type="submit" class="modal-submit" id="devisBtnSubmit">
          <i class="fas fa-paper-plane"></i> <?= t('Envoyer ma demande','Send my request') ?>
        </button>
        <div class="modal-msg" id="devisMsg"></div>
      </form>
    </div>
  </div>
</div>

<script>
/* ── Modal devis ─────────────────────────────────────────── */
function openDevisModal(id, nom, img) {
  document.getElementById('modalProduitId').value  = id;
  document.getElementById('modalProduitNom').value = nom;
  document.getElementById('modalProdNom').textContent = nom;
  var imgEl = document.getElementById('modalProdImg');
  imgEl.src = img || '';
  imgEl.style.display = img ? 'block' : 'none';
  document.getElementById('devisMsg').style.display = 'none';
  document.getElementById('devisForm').reset();
  document.getElementById('modalProduitId').value  = id;
  document.getElementById('modalProduitNom').value = nom;
  document.getElementById('devisModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDevisModal() {
  document.getElementById('devisModal').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('devisModal').addEventListener('click', function(e) {
  if (e.target === this) closeDevisModal();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeDevisModal();
});

/* ── Envoi devis ─────────────────────────────────────────── */
document.getElementById('devisForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var btn = document.getElementById('devisBtnSubmit');
  var msg = document.getElementById('devisMsg');
  var data = Object.fromEntries(new FormData(this));

  if (!data.nom || !data.email || !data.telephone) {
    msg.className = 'modal-msg error'; msg.style.display = 'block';
    msg.textContent = '<?= t('Veuillez remplir les champs obligatoires.','Please fill in required fields.') ?>';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= t('Envoi…','Sending…') ?>';

  fetch('/api/devis.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(data)
  })
  .then(r => r.json())
  .then(function(d) {
    msg.style.display = 'block';
    if (d.success) {
      msg.className = 'modal-msg success';
      msg.textContent = d.message;
      document.getElementById('devisForm').reset();
      setTimeout(closeDevisModal, 3000);
    } else {
      msg.className = 'modal-msg error';
      msg.textContent = d.message || '<?= t('Erreur. Réessayez.','Error. Try again.') ?>';
    }
  })
  .catch(function() {
    msg.className = 'modal-msg error'; msg.style.display = 'block';
    msg.textContent = '<?= t('Erreur réseau. Réessayez.','Network error. Try again.') ?>';
  })
  .finally(function() {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> <?= t('Envoyer ma demande','Send my request') ?>';
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
