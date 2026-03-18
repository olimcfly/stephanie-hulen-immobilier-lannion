<?php
/**
 * Module Contacts — /admin/modules/contact/index.php
 */

if (!isset($pdo) && !isset($db)) {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (PDOException $e) {
        echo '<div class="mod-flash mod-flash-error"><i class="fas fa-exclamation-circle"></i> '.$e->getMessage().'</div>';
        return;
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db = $pdo;

function updateContactsTable($pdo) {
    $cols = array_column($pdo->query("SHOW COLUMNS FROM contacts")->fetchAll(), 'Field');
    $new = [
        'civility'=>"ADD COLUMN civility ENUM('M.','Mme','Dr','Me') DEFAULT 'M.'",
        'firstname'=>"ADD COLUMN firstname VARCHAR(100)",
        'lastname'=>"ADD COLUMN lastname VARCHAR(100)",
        'phone'=>"ADD COLUMN phone VARCHAR(50)",
        'mobile'=>"ADD COLUMN mobile VARCHAR(50)",
        'address'=>"ADD COLUMN address VARCHAR(255)",
        'city'=>"ADD COLUMN city VARCHAR(100)",
        'postal_code'=>"ADD COLUMN postal_code VARCHAR(20)",
        'country'=>"ADD COLUMN country VARCHAR(100) DEFAULT 'France'",
        'company'=>"ADD COLUMN company VARCHAR(200)",
        'job_title'=>"ADD COLUMN job_title VARCHAR(150)",
        'category'=>"ADD COLUMN category ENUM('client','prospect','partenaire','notaire','banque','artisan','fournisseur','presse','autre') DEFAULT 'client'",
        'status'=>"ADD COLUMN status ENUM('active','inactive','vip','blacklist') DEFAULT 'active'",
        'birthday'=>"ADD COLUMN birthday DATE",
        'tags'=>"ADD COLUMN tags VARCHAR(255)",
        'rating'=>"ADD COLUMN rating INT DEFAULT 0",
        'notes'=>"ADD COLUMN notes TEXT",
        'updated_at'=>"ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];
    foreach ($new as $c => $sql) { if (!in_array($c, $cols)) { try { $pdo->exec("ALTER TABLE contacts {$sql}"); } catch(PDOException $e) {} } }
    if (in_array('prenom', $cols)) $pdo->exec("UPDATE contacts SET firstname=prenom WHERE (firstname IS NULL OR firstname='') AND prenom IS NOT NULL");
    if (in_array('nom', $cols)) $pdo->exec("UPDATE contacts SET lastname=nom WHERE (lastname IS NULL OR lastname='') AND nom IS NOT NULL");
    if (in_array('telephone', $cols)) $pdo->exec("UPDATE contacts SET phone=telephone WHERE (phone IS NULL OR phone='') AND telephone IS NOT NULL");
}
try { updateContactsTable($pdo); } catch(Exception $e) {}

$search = $_GET['search'] ?? '';
$filterCat = $_GET['category'] ?? '';
$filterSt = $_GET['status'] ?? '';
$filterCity = $_GET['city'] ?? '';
$letter = $_GET['letter'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ['1=1']; $params = [];
if ($search) { $where[] = "(COALESCE(firstname,prenom,'') LIKE ? OR COALESCE(lastname,nom,'') LIKE ? OR email LIKE ? OR COALESCE(phone,telephone,'') LIKE ? OR company LIKE ? OR city LIKE ?)"; $st = "%{$search}%"; $params = array_merge($params, [$st,$st,$st,$st,$st,$st]); }
if ($filterCat) { $where[] = "category=?"; $params[] = $filterCat; }
if ($filterSt) { $where[] = "status=?"; $params[] = $filterSt; }
if ($filterCity) { $where[] = "city LIKE ?"; $params[] = "%{$filterCity}%"; }
if ($letter) { $where[] = "COALESCE(lastname,nom,'') LIKE ?"; $params[] = "{$letter}%"; }
$wc = implode(' AND ', $where);

$cs = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE {$wc}"); $cs->execute($params);
$totalContacts = (int)$cs->fetchColumn();
$totalPages = max(1, ceil($totalContacts / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$allowed = ['created_at','lastname','company','category','status','city'];
$sortBy = in_array($sortBy, $allowed) ? $sortBy : 'created_at';
$ob = $sortBy === 'lastname' ? "COALESCE(lastname,nom,'')" : $sortBy;

$stmt = $pdo->prepare("SELECT * FROM contacts WHERE {$wc} ORDER BY {$ob} {$sortOrder} LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params); $contacts = $stmt->fetchAll();

$stats = ['total'=>0,'clients'=>0,'partenaires'=>0,'vip'=>0,'this_month'=>0];
try {
    $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
    $stats['clients'] = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE category='client'")->fetchColumn();
    $stats['partenaires'] = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE category='partenaire'")->fetchColumn();
    $stats['vip'] = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE status='vip'")->fetchColumn();
    $stats['this_month'] = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE MONTH(created_at)=MONTH(CURRENT_DATE()) AND YEAR(created_at)=YEAR(CURRENT_DATE())")->fetchColumn();
} catch(Exception $e) {}

$cities = []; try { $cities = $pdo->query("SELECT DISTINCT city FROM contacts WHERE city IS NOT NULL AND city!='' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN); } catch(Exception $e) {}

$categoryLabels = [
    'client'=>['label'=>'Client','color'=>'active','icon'=>'user-check'],
    'prospect'=>['label'=>'Prospect','color'=>'info','icon'=>'user-clock'],
    'partenaire'=>['label'=>'Partenaire','color'=>'draft','icon'=>'handshake'],
    'notaire'=>['label'=>'Notaire','color'=>'inactive','icon'=>'balance-scale'],
    'banque'=>['label'=>'Banque','color'=>'info','icon'=>'university'],
    'artisan'=>['label'=>'Artisan','color'=>'warning','icon'=>'tools'],
    'fournisseur'=>['label'=>'Fournisseur','color'=>'error','icon'=>'truck'],
    'presse'=>['label'=>'Presse','color'=>'info','icon'=>'newspaper'],
    'autre'=>['label'=>'Autre','color'=>'inactive','icon'=>'user'],
];
$statusLabels = ['active'=>['label'=>'Actif','c'=>'active'],'inactive'=>['label'=>'Inactif','c'=>'inactive'],'vip'=>['label'=>'VIP','c'=>'warning'],'blacklist'=>['label'=>'Blacklisté','c'=>'error']];
$catColors = ['client'=>'#10b981','prospect'=>'#6366f1','partenaire'=>'#8b5cf6','notaire'=>'#1e293b','banque'=>'#0891b2','artisan'=>'#f59e0b','fournisseur'=>'#ec4899','presse'=>'#06b6d4','autre'=>'#64748b'];
?>

<style>
.ct-avatar{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;position:relative}
.ct-avatar.vip::after{content:'⭐';position:absolute;bottom:-4px;right:-4px;font-size:10px}
.ct-contact-info{display:flex;align-items:center;gap:10px}
.ct-name{font-weight:600;color:var(--text);font-size:.85rem}
.ct-company{font-size:.7rem;color:var(--text-3);display:flex;align-items:center;gap:3px}
.ct-methods{display:flex;flex-direction:column;gap:3px}
.ct-method{display:flex;align-items:center;gap:5px;font-size:.78rem}
.ct-method i{width:14px;text-align:center;color:var(--text-3);font-size:.65rem}
.ct-method a{color:var(--accent);text-decoration:none}
.ct-method a:hover{text-decoration:underline}
.ct-rating i{font-size:.65rem;color:var(--border)}
.ct-rating i.on{color:var(--amber)}
.ct-alpha{display:flex;gap:3px;flex-wrap:wrap;margin-top:14px;padding-top:14px;border-top:1px solid var(--border)}
.ct-alpha a{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:5px;font-size:.68rem;font-weight:600;text-decoration:none;color:var(--text-3);background:var(--surface-2);transition:all .15s}
.ct-alpha a:hover,.ct-alpha a.active{background:var(--accent);color:#fff}
.ct-form-section{margin-bottom:20px}
.ct-form-title{font-size:.8rem;font-weight:700;color:var(--text);margin-bottom:12px;padding-bottom:6px;border-bottom:2px solid var(--border);display:flex;align-items:center;gap:6px}
.ct-form-title i{color:var(--accent)}
@media(max-width:1024px){.ct-alpha{display:none}}
</style>

<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-address-book"></i> Répertoire Contacts</h1>
        <p>Clients, partenaires, notaires, banques — tous vos contacts professionnels</p>
    </div>
    <div class="mod-hero-actions">
        <button class="mod-btn mod-btn-hero" onclick="exportContacts()"><i class="fas fa-download"></i> Exporter</button>
        <button class="mod-btn mod-btn-hero" onclick="openAddModal()"><i class="fas fa-plus"></i> Nouveau</button>
    </div>
</div>

<div class="mod-toolbar" style="flex-wrap:wrap">
    <div class="mod-toolbar-left mod-flex mod-gap">
        <div class="mod-stat"><div class="mod-stat-value"><?= $stats['total'] ?></div><div class="mod-stat-label">Total</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $stats['clients'] ?></div><div class="mod-stat-label">Clients</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $stats['partenaires'] ?></div><div class="mod-stat-label">Partenaires</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $stats['vip'] ?></div><div class="mod-stat-label">VIP</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $stats['this_month'] ?></div><div class="mod-stat-label">Ce mois</div></div>
    </div>
</div>

<div class="mod-toolbar" style="flex-wrap:wrap">
    <div class="mod-toolbar-left" style="flex:1">
        <form class="mod-flex mod-gap mod-wrap" method="GET" style="width:100%">
            <input type="hidden" name="page" value="contact">
            <div class="mod-search" style="flex:1;min-width:200px"><i class="fas fa-search"></i><input type="text" name="search" placeholder="Nom, email, entreprise..." value="<?= htmlspecialchars($search) ?>"></div>
            <select name="category" onchange="this.form.submit()" style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:.78rem;font-family:var(--font);background:var(--surface)">
                <option value="">Catégories</option>
                <?php foreach ($categoryLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filterCat===$k?'selected':'' ?>><?= $v['label'] ?></option><?php endforeach; ?>
            </select>
            <select name="status" onchange="this.form.submit()" style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:.78rem;font-family:var(--font);background:var(--surface)">
                <option value="">Statuts</option>
                <?php foreach ($statusLabels as $k=>$v): ?><option value="<?= $k ?>" <?= $filterSt===$k?'selected':'' ?>><?= $v['label'] ?></option><?php endforeach; ?>
            </select>
            <?php if (!empty($cities)): ?>
            <select name="city" onchange="this.form.submit()" style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:.78rem;font-family:var(--font);background:var(--surface)">
                <option value="">Villes</option>
                <?php foreach ($cities as $v): ?><option value="<?= htmlspecialchars($v) ?>" <?= $filterCity===$v?'selected':'' ?>><?= htmlspecialchars($v) ?></option><?php endforeach; ?>
            </select>
            <?php endif; ?>
            <?php if ($search||$filterCat||$filterSt||$filterCity||$letter): ?><a href="?page=contact" class="mod-btn mod-btn-secondary mod-btn-sm"><i class="fas fa-times"></i> Reset</a><?php endif; ?>
        </form>
        <div class="ct-alpha">
            <a href="?page=contact" class="<?= !$letter?'active':'' ?>">Tous</a>
            <?php foreach (range('A','Z') as $l): ?><a href="?page=contact&letter=<?= $l ?>" class="<?= $letter===$l?'active':'' ?>"><?= $l ?></a><?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (empty($contacts)): ?>
<div class="mod-empty"><i class="fas fa-address-book"></i><h3>Aucun contact trouvé</h3><p>Ajoutez votre premier contact ou modifiez vos filtres.</p><button class="mod-btn mod-btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Ajouter</button></div>
<?php else: ?>
<div class="mod-table-wrap">
    <table class="mod-table">
        <thead><tr>
            <th style="width:36px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="width:auto;accent-color:var(--accent)"></th>
            <th><a href="?page=contact&sort=lastname&order=<?= $sortBy==='lastname'&&$sortOrder==='ASC'?'DESC':'ASC' ?>">Contact</a></th>
            <th>Coordonnées</th>
            <th>Catégorie</th>
            <th>Statut</th>
            <th>Ville</th>
            <th>Note</th>
            <th>Ajouté</th>
            <th class="col-actions">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($contacts as $c):
            $catI = $categoryLabels[$c['category'] ?? 'autre'] ?? $categoryLabels['autre'];
            $stI = $statusLabels[$c['status'] ?? 'active'] ?? $statusLabels['active'];
            $fn = $c['firstname'] ?? $c['prenom'] ?? '';
            $ln = $c['lastname'] ?? $c['nom'] ?? '';
            $ph = $c['phone'] ?? $c['telephone'] ?? '';
            $init = mb_strtoupper(mb_substr($fn,0,1).mb_substr($ln,0,1)) ?: '?';
            $isVip = ($c['status'] ?? '')==='vip';
            $col = $catColors[$c['category'] ?? 'autre'] ?? '#64748b';
        ?>
        <tr>
            <td><input type="checkbox" class="contact-cb" value="<?= $c['id'] ?>" style="width:auto;accent-color:var(--accent)"></td>
            <td>
                <div class="ct-contact-info">
                    <div class="ct-avatar <?= $isVip?'vip':'' ?>" style="background:<?= $col ?>"><?= $init ?></div>
                    <div>
                        <div class="ct-name"><?= htmlspecialchars(trim(($c['civility'] ?? '').' '.$fn.' '.$ln)) ?></div>
                        <?php if ($c['company'] ?? ''): ?><div class="ct-company"><i class="fas fa-building"></i> <?= htmlspecialchars($c['company']) ?></div><?php endif; ?>
                    </div>
                </div>
            </td>
            <td>
                <div class="ct-methods">
                    <?php if ($c['email'] ?? ''): ?><div class="ct-method"><i class="fas fa-envelope"></i><a href="mailto:<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></a></div><?php endif; ?>
                    <?php if (($c['mobile'] ?? '')||$ph): ?><div class="ct-method"><i class="fas fa-phone"></i><a href="tel:<?= htmlspecialchars($c['mobile'] ?: $ph) ?>"><?= htmlspecialchars($c['mobile'] ?: $ph) ?></a></div><?php endif; ?>
                </div>
            </td>
            <td><span class="mod-badge mod-badge-<?= $catI['color'] ?>"><i class="fas fa-<?= $catI['icon'] ?>" style="margin-right:3px"></i><?= $catI['label'] ?></span></td>
            <td><span class="mod-badge mod-badge-<?= $stI['c'] ?>"><?= $stI['label'] ?></span></td>
            <td class="mod-text-sm"><?= !empty($c['city'])?htmlspecialchars($c['city']):'—' ?></td>
            <td><div class="ct-rating"><?php for ($i=1;$i<=5;$i++): ?><i class="fas fa-star <?= $i<=($c['rating']??0)?'on':'' ?>"></i><?php endfor; ?></div></td>
            <td><span class="mod-date"><?= date('d/m/Y', strtotime($c['created_at'])) ?></span></td>
            <td class="col-actions">
                <div class="mod-actions">
                    <button class="mod-btn-icon" onclick="viewContact(<?= $c['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                    <button class="mod-btn-icon" onclick="editContact(<?= $c['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                    <?php if ($c['email'] ?? ''): ?><a href="mailto:<?= htmlspecialchars($c['email']) ?>" class="mod-btn-icon" title="Email"><i class="fas fa-envelope"></i></a><?php endif; ?>
                    <?php if (($c['mobile'] ?? '')||$ph): ?><a href="tel:<?= htmlspecialchars($c['mobile'] ?: $ph) ?>" class="mod-btn-icon" title="Appeler"><i class="fas fa-phone"></i></a><?php endif; ?>
                    <button class="mod-btn-icon danger" onclick="deleteContact(<?= $c['id'] ?>)" title="Supprimer"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="mod-flex mod-items-center" style="justify-content:space-between;margin-top:16px">
    <span class="mod-text-xs mod-text-muted"><?= min($offset+1,$totalContacts) ?>–<?= min($offset+$perPage,$totalContacts) ?> sur <?= $totalContacts ?></span>
    <div class="mod-pagination">
        <?php if ($page>1): $p=$_GET;$p['p']=$page-1; ?><a href="?<?= http_build_query($p) ?>" class="mod-page-btn"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
        <?php for ($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): $p=$_GET;$p['p']=$i; ?><a href="?<?= http_build_query($p) ?>" class="mod-page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a><?php endfor; ?>
        <?php if ($page<$totalPages): $p=$_GET;$p['p']=$page+1; ?><a href="?<?= http_build_query($p) ?>" class="mod-page-btn"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
    </div>
</div>
<?php endif; endif; ?>

<!-- Modal Add/Edit -->
<div class="mod-overlay" id="contactModal">
    <div class="mod-modal" style="max-width:750px">
        <div class="mod-modal-header"><h3><i class="fas fa-user-plus" style="color:var(--accent)"></i> <span id="modalTitle">Nouveau contact</span></h3><button class="mod-modal-close" onclick="closeModal()">×</button></div>
        <form id="contactForm">
        <div class="mod-modal-body">
            <input type="hidden" id="contactId" name="id">
            <div class="ct-form-section"><div class="ct-form-title"><i class="fas fa-user"></i> Identité</div>
                <div class="mod-form-grid" style="grid-template-columns:auto 1fr 1fr 1fr">
                    <div class="mod-form-group"><label>Civilité</label><select id="civility" name="civility"><option value="M.">M.</option><option value="Mme">Mme</option><option value="Dr">Dr</option><option value="Me">Me</option></select></div>
                    <div class="mod-form-group"><label>Prénom *</label><input type="text" id="firstname" name="firstname" required></div>
                    <div class="mod-form-group"><label>Nom *</label><input type="text" id="lastname" name="lastname" required></div>
                    <div class="mod-form-group"><label>Anniversaire</label><input type="date" id="birthday" name="birthday"></div>
                </div>
                <div class="mod-form-grid"><div class="mod-form-group"><label>Entreprise</label><input type="text" id="company" name="company"></div><div class="mod-form-group"><label>Fonction</label><input type="text" id="job_title" name="job_title"></div></div>
            </div>
            <div class="ct-form-section"><div class="ct-form-title"><i class="fas fa-phone"></i> Coordonnées</div>
                <div class="mod-form-grid" style="grid-template-columns:1fr 1fr 1fr"><div class="mod-form-group"><label>Email</label><input type="email" id="email" name="email"></div><div class="mod-form-group"><label>Téléphone</label><input type="tel" id="phone" name="phone"></div><div class="mod-form-group"><label>Mobile</label><input type="tel" id="mobile" name="mobile"></div></div>
            </div>
            <div class="ct-form-section"><div class="ct-form-title"><i class="fas fa-map-marker-alt"></i> Adresse</div>
                <div class="mod-form-group"><label>Adresse</label><input type="text" id="address" name="address"></div>
                <div class="mod-form-grid" style="grid-template-columns:1fr 1fr 1fr"><div class="mod-form-group"><label>Code postal</label><input type="text" id="postal_code" name="postal_code"></div><div class="mod-form-group"><label>Ville</label><input type="text" id="city" name="city"></div><div class="mod-form-group"><label>Pays</label><input type="text" id="country" name="country" value="France"></div></div>
            </div>
            <div class="ct-form-section"><div class="ct-form-title"><i class="fas fa-tags"></i> Classification</div>
                <div class="mod-form-grid" style="grid-template-columns:1fr 1fr 1fr">
                    <div class="mod-form-group"><label>Catégorie</label><select id="category" name="category"><?php foreach ($categoryLabels as $k=>$v): ?><option value="<?= $k ?>"><?= $v['label'] ?></option><?php endforeach; ?></select></div>
                    <div class="mod-form-group"><label>Statut</label><select id="status" name="status"><?php foreach ($statusLabels as $k=>$v): ?><option value="<?= $k ?>"><?= $v['label'] ?></option><?php endforeach; ?></select></div>
                    <div class="mod-form-group"><label>Note</label><select id="rating" name="rating"><option value="0">Non noté</option><?php for($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= str_repeat('⭐',$i) ?></option><?php endfor; ?></select></div>
                </div>
            </div>
            <div class="ct-form-section"><div class="ct-form-title"><i class="fas fa-sticky-note"></i> Notes</div><div class="mod-form-group"><textarea id="notes" name="notes" rows="3" placeholder="Informations complémentaires..."></textarea></div></div>
        </div>
        <div class="mod-modal-footer"><button type="button" class="mod-btn mod-btn-secondary" onclick="closeModal()">Annuler</button><button type="submit" class="mod-btn mod-btn-primary"><i class="fas fa-save"></i> Enregistrer</button></div>
        </form>
    </div>
</div>

<!-- Modal View -->
<div class="mod-overlay" id="viewModal">
    <div class="mod-modal" style="max-width:600px">
        <div class="mod-modal-header"><h3><i class="fas fa-user" style="color:var(--accent)"></i> Fiche contact</h3><button class="mod-modal-close" onclick="closeViewModal()">×</button></div>
        <div class="mod-modal-body" id="viewModalContent"></div>
        <div class="mod-modal-footer"><button class="mod-btn mod-btn-secondary" onclick="closeViewModal()">Fermer</button><button class="mod-btn mod-btn-primary" id="editFromViewBtn"><i class="fas fa-edit"></i> Modifier</button></div>
    </div>
</div>

<script>
const API_URL='/admin/modules/contact/api.php';
const catLabels=<?= json_encode($categoryLabels) ?>;
const stLabels=<?= json_encode($statusLabels) ?>;
const catColors=<?= json_encode($catColors) ?>;

function openAddModal(){document.getElementById('modalTitle').textContent='Nouveau contact';document.getElementById('contactForm').reset();document.getElementById('contactId').value='';document.getElementById('contactModal').classList.add('show')}
function closeModal(){document.getElementById('contactModal').classList.remove('show')}
function closeViewModal(){document.getElementById('viewModal').classList.remove('show')}

function editContact(id){
    fetch(API_URL+'?action=get_contact&id='+id).then(r=>r.json()).then(d=>{
        if(!d.success||!d.contact)return;const c=d.contact;
        document.getElementById('modalTitle').textContent='Modifier le contact';
        document.getElementById('contactId').value=c.id;
        ['civility','firstname','lastname','email','phone','mobile','address','city','postal_code','country','company','job_title','category','status','rating','birthday','notes'].forEach(f=>{const el=document.getElementById(f);if(el&&c[f]!=null)el.value=c[f]});
        if(!c.firstname&&c.prenom)document.getElementById('firstname').value=c.prenom;
        if(!c.lastname&&c.nom)document.getElementById('lastname').value=c.nom;
        if(!c.phone&&c.telephone)document.getElementById('phone').value=c.telephone;
        document.getElementById('contactModal').classList.add('show');
    });
}

function viewContact(id){
    fetch(API_URL+'?action=get_contact&id='+id).then(r=>r.json()).then(d=>{
        if(!d.success||!d.contact)return;const c=d.contact;
        const fn=c.firstname||c.prenom||'',ln=c.lastname||c.nom||'',ph=c.phone||c.telephone||'';
        const catI=catLabels[c.category]||catLabels.autre,stI=stLabels[c.status]||stLabels.active,col=catColors[c.category]||'#64748b';
        let stars='';for(let i=1;i<=5;i++)stars+=`<i class="fas fa-star" style="color:${i<=(c.rating||0)?'var(--amber)':'var(--border)'}"></i>`;
        document.getElementById('viewModalContent').innerHTML=`
            <div style="display:flex;gap:16px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--border)">
                <div style="width:64px;height:64px;border-radius:14px;background:${col};display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff">${(fn.charAt(0)+ln.charAt(0)).toUpperCase()||'?'}</div>
                <div style="flex:1"><div style="font-size:1.2rem;font-weight:700;color:var(--text)">${c.civility||''} ${fn} ${ln}</div>${c.company?`<div class="mod-text-sm mod-text-muted"><i class="fas fa-building"></i> ${c.company}${c.job_title?' — '+c.job_title:''}</div>`:''}<div class="mod-flex mod-gap-sm" style="margin-top:8px"><span class="mod-badge mod-badge-${catI.color}">${catI.label}</span><span class="mod-badge mod-badge-${stI.c}">${stI.label}</span><span>${stars}</span></div></div>
            </div>
            <div class="mod-form-grid">${c.email?`<div style="padding:12px;background:var(--surface-2);border-radius:var(--radius)"><div class="mod-text-xs mod-text-muted">Email</div><a href="mailto:${c.email}" style="color:var(--accent);font-weight:500;font-size:.85rem">${c.email}</a></div>`:''} ${c.mobile?`<div style="padding:12px;background:var(--surface-2);border-radius:var(--radius)"><div class="mod-text-xs mod-text-muted">Mobile</div><a href="tel:${c.mobile}" style="color:var(--accent);font-weight:500;font-size:.85rem">${c.mobile}</a></div>`:''} ${ph?`<div style="padding:12px;background:var(--surface-2);border-radius:var(--radius)"><div class="mod-text-xs mod-text-muted">Téléphone</div><a href="tel:${ph}" style="color:var(--accent);font-weight:500;font-size:.85rem">${ph}</a></div>`:''} ${c.city?`<div style="padding:12px;background:var(--surface-2);border-radius:var(--radius)"><div class="mod-text-xs mod-text-muted">Adresse</div><span class="mod-text-sm">${[c.address,c.postal_code,c.city].filter(Boolean).join(', ')}</span></div>`:''}</div>
            ${c.notes?`<div style="margin-top:14px;padding:12px;background:var(--amber-bg);border-radius:var(--radius)"><div class="mod-text-xs" style="color:var(--amber);font-weight:600;margin-bottom:3px">Notes</div><div class="mod-text-sm" style="white-space:pre-wrap">${c.notes}</div></div>`:''}`;
        document.getElementById('editFromViewBtn').onclick=()=>{closeViewModal();editContact(id)};
        document.getElementById('viewModal').classList.add('show');
    });
}

function deleteContact(id){
    if(!confirm('Supprimer ce contact ?'))return;
    const fd=new FormData();fd.append('action','delete_contact');fd.append('id',id);
    fetch(API_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success){showNotif('Contact supprimé','success');setTimeout(()=>location.reload(),500)}else showNotif(d.error||'Erreur','error');
    });
}

document.getElementById('contactForm').addEventListener('submit',function(e){
    e.preventDefault();const id=document.getElementById('contactId').value,fd=new FormData(this);
    fd.append('action',id?'update_contact':'add_contact');
    fetch(API_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success){showNotif(id?'Modifié':'Créé','success');closeModal();setTimeout(()=>location.reload(),500)}else showNotif(d.error||'Erreur','error');
    });
});

function toggleSelectAll(){const ck=document.getElementById('selectAll').checked;document.querySelectorAll('.contact-cb').forEach(c=>c.checked=ck)}
function exportContacts(){window.location.href=API_URL+'?action=export'}
function showNotif(msg,type='info'){const c={success:'var(--green)',error:'var(--red)',info:'var(--accent)'},n=document.createElement('div');n.style.cssText=`position:fixed;top:20px;right:20px;padding:14px 20px;background:${c[type]};color:#fff;border-radius:var(--radius);font-size:.85rem;font-weight:500;z-index:99999;box-shadow:var(--shadow-lg);transition:opacity .3s`;n.textContent=msg;document.body.appendChild(n);setTimeout(()=>{n.style.opacity='0';setTimeout(()=>n.remove(),300)},2500)}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();closeViewModal()}});
document.querySelectorAll('.mod-overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this){closeModal();closeViewModal()}}));
</script>