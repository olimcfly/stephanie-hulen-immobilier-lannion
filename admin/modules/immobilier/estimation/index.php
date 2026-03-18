<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * Module ESTIMATION - /admin/modules/estimation/index.php
 * ═══════════════════════════════════════════════════════════════
 * ROUTEUR :
 *   ?page=estimation            → Liste des demandes
 *   ?page=estimation&sub=emails → Module gestion emails
 * ✅ Table : estimations
 * ✅ Tables liées : estimation_reports, estimation_rdv, estimation_contacts, estimation_templates
 * ═══════════════════════════════════════════════════════════════
 */

// ─── SOUS-MODULE ROUTAGE ────────────────────────────────────────
$subModule = $_GET['sub'] ?? '';
if ($subModule === 'emails') {
    $emailsFile = __DIR__ . '/emails.php';
    if (file_exists($emailsFile)) { include $emailsFile; return; }
}

// ─── INITIALISATION DB ──────────────────────────────────────────
if (!isset($pdo)) {
    $rootPath = realpath(__DIR__ . '/../../../');
    foreach (['/config/database.php','/includes/Database.php','/config/config.php'] as $f) {
        if (file_exists($rootPath . $f)) { require_once $rootPath . $f; break; }
    }
    if (class_exists('Database')) $pdo = Database::getInstance();
}

$tableExists = false; $dbError = null;
if (isset($pdo)) {
    try {
        $tableExists = ($pdo->query("SHOW TABLES LIKE 'estimations'")->rowCount() > 0);
    } catch (Exception $e) { $dbError = $e->getMessage(); }
}

// ─── ACTIONS AJAX ───────────────────────────────────────────────
if (isset($_GET['ajax_action']) && isset($pdo) && $tableExists) {
    header('Content-Type: application/json; charset=utf-8');
    switch ($_GET['ajax_action']) {
        case 'update_statut':
            try {
                $id = (int)($_POST['id'] ?? 0);
                $s = $_POST['statut'] ?? '';
                if ($id > 0 && in_array($s, ['en_attente','traitee','convertie'])) {
                    $pdo->prepare("UPDATE estimations SET statut=:s WHERE id=:id")->execute([':s'=>$s,':id'=>$id]);
                    echo json_encode(['success'=>true,'message'=>'Statut mis à jour']);
                } else echo json_encode(['success'=>false,'message'=>'Paramètres invalides']);
            } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
            exit;
        case 'update_notes':
            try {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $pdo->prepare("UPDATE estimations SET notes=:n WHERE id=:id")->execute([':n'=>trim($_POST['notes']??''),':id'=>$id]);
                    echo json_encode(['success'=>true]);
                }
            } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
            exit;
        case 'delete':
            try {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) { $pdo->prepare("DELETE FROM estimations WHERE id=:id")->execute([':id'=>$id]); echo json_encode(['success'=>true]); }
            } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
            exit;
        case 'send_quick_email':
            try {
                $eid = (int)($_POST['estimation_id'] ?? 0);
                $ttype = $_POST['template_type'] ?? 'confirmation';
                $mailerPath = realpath(__DIR__.'/../../../').'/includes/estimation_mailer.php';
                if ($eid > 0 && file_exists($mailerPath)) {
                    require_once $mailerPath;
                    $est = $pdo->prepare("SELECT * FROM estimations WHERE id=:id"); $est->execute([':id'=>$eid]);
                    $estimation = $est->fetch(PDO::FETCH_ASSOC);
                    if ($estimation && !empty($estimation['email'])) {
                        $tpl = $pdo->prepare("SELECT * FROM estimation_templates WHERE type=:t AND status='actif' LIMIT 1");
                        $tpl->execute([':t'=>$ttype]); $template = $tpl->fetch(PDO::FETCH_ASSOC);
                        if ($template) {
                            $vars = [
                                'prenom'=>$estimation['prenom']??'','nom'=>$estimation['nom']??'',
                                'email'=>$estimation['email']??'','telephone'=>$estimation['telephone']??'',
                                'type_bien'=>ucfirst($estimation['type_bien']??''),'surface'=>$estimation['surface']??'',
                                'pieces'=>$estimation['pieces']??'','adresse'=>$estimation['adresse']??'',
                                'ville'=>$estimation['ville']??'','code_postal'=>$estimation['code_postal']??'',
                                'estimation_basse'=>$estimation['estimation_basse']?number_format((float)$estimation['estimation_basse'],0,',',' '):'—',
                                'estimation_haute'=>$estimation['estimation_haute']?number_format((float)$estimation['estimation_haute'],0,',',' '):'—',
                                'date_creation'=>$estimation['date_creation']?date('d/m/Y',strtotime($estimation['date_creation'])):date('d/m/Y'),
                            ];
                            $subj = replaceVariables($template['subject'], $vars);
                            $body = replaceVariables($template['body'], $vars);
                            $sent = sendHtmlEmail($estimation['email'], $subj, $body, $pdo);
                            if ($sent) { logEmailContact($pdo, $eid, $subj, $body, 'out'); echo json_encode(['success'=>true,'message'=>'Email envoyé à '.$estimation['email']]); }
                            else echo json_encode(['success'=>false,'message'=>'Échec envoi']);
                        } else echo json_encode(['success'=>false,'message'=>'Aucun template actif "'.$ttype.'"']);
                    } else echo json_encode(['success'=>false,'message'=>'Email manquant']);
                } else echo json_encode(['success'=>false,'message'=>'Mailer non trouvé ou ID invalide']);
            } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
            exit;
    }
}

// ─── FILTRAGE & PAGINATION ──────────────────────────────────────
$filterStatut = $_GET['filter_statut'] ?? 'all';
$filterSearch = trim($_GET['q'] ?? '');
$filterType = $_GET['type_bien'] ?? '';
$currentPage = max(1, (int)($_GET['p'] ?? 1));
$perPage = 25;
$offset = ($currentPage - 1) * $perPage;

$requests = []; $total_requests = 0; $nb_en_attente = 0; $nb_traitee = 0; $nb_convertie = 0;
$filteredTotal = 0; $totalPages = 1; $rdvCount = 0; $reportCount = 0; $emailCount = 0;

if (isset($pdo) && $tableExists) {
    try {
        $stats = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN statut='en_attente' THEN 1 ELSE 0 END) as nb_ea, SUM(CASE WHEN statut='traitee' THEN 1 ELSE 0 END) as nb_tr, SUM(CASE WHEN statut='convertie' THEN 1 ELSE 0 END) as nb_cv FROM estimations")->fetch(PDO::FETCH_ASSOC);
        $total_requests = (int)($stats['total']??0); $nb_en_attente = (int)($stats['nb_ea']??0);
        $nb_traitee = (int)($stats['nb_tr']??0); $nb_convertie = (int)($stats['nb_cv']??0);
        try { $rdvCount = (int)$pdo->query("SELECT COUNT(*) FROM estimation_rdv WHERE status IN ('proposed','planifie','confirmed')")->fetchColumn(); } catch(Exception $e){}
        try { $reportCount = (int)$pdo->query("SELECT COUNT(*) FROM estimation_reports")->fetchColumn(); } catch(Exception $e){}
        try { $emailCount = (int)$pdo->query("SELECT COUNT(*) FROM estimation_contacts WHERE contact_type='email'")->fetchColumn(); } catch(Exception $e){}

        $where = "WHERE 1=1"; $params = [];
        if ($filterStatut !== 'all') { $where .= " AND statut=:fs"; $params[':fs'] = $filterStatut; }
        if ($filterType !== '') { $where .= " AND type_bien=:ft"; $params[':ft'] = $filterType; }
        if ($filterSearch !== '') { $where .= " AND (nom LIKE :q OR prenom LIKE :q OR email LIKE :q OR telephone LIKE :q OR adresse LIKE :q OR ville LIKE :q)"; $params[':q'] = '%'.$filterSearch.'%'; }

        $cs = $pdo->prepare("SELECT COUNT(*) FROM estimations $where"); $cs->execute($params);
        $filteredTotal = (int)$cs->fetchColumn(); $totalPages = max(1, ceil($filteredTotal/$perPage));

        $ds = $pdo->prepare("SELECT e.*, (SELECT COUNT(*) FROM estimation_rdv r WHERE r.request_id=e.id) as nb_rdv, (SELECT COUNT(*) FROM estimation_reports rp WHERE rp.request_id=e.id) as nb_reports, (SELECT COUNT(*) FROM estimation_contacts c WHERE c.request_id=e.id) as nb_contacts FROM estimations e $where ORDER BY FIELD(e.statut,'en_attente','traitee','convertie'), e.date_creation DESC LIMIT $perPage OFFSET $offset");
        $ds->execute($params); $requests = $ds->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $dbError = "Erreur SQL : " . $e->getMessage(); }
}

$baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
$moduleUrl = $baseUrl . '?page=estimation';
function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function fmtPrice($v) { if (!$v || $v == 0) return null; return number_format((float)$v, 0, ',', ' ') . ' €'; }
?>

<style>
.est-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.est-header h2{font-size:22px;font-weight:800;color:#111827;display:flex;align-items:center;gap:10px}
.est-header-actions{display:flex;gap:8px}
.hbtn{padding:8px 16px;background:white;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;cursor:pointer;color:#6b7280;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.hbtn:hover{border-color:#6366f1;color:#6366f1}
.hbtn.email-btn{border-color:#8b5cf6;color:#8b5cf6;background:#f5f3ff}
.hbtn.email-btn:hover{background:#8b5cf6;color:white}
.email-count{background:#8b5cf6;color:white;padding:1px 6px;border-radius:10px;font-size:10px;font-weight:700}

.est-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:12px;margin-bottom:22px}
.est-stat{background:white;padding:16px 18px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);border:1px solid #e5e7eb;border-left:4px solid #6366f1;cursor:pointer;transition:all .2s;text-decoration:none;display:block}
.est-stat:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.est-stat.en-attente{border-left-color:#f59e0b}.est-stat.traitee{border-left-color:#8b5cf6}
.est-stat.convertie{border-left-color:#10b981}.est-stat.rdv{border-left-color:#06b6d4}
.est-stat.reports{border-left-color:#ec4899}.est-stat.emails{border-left-color:#f97316}
.est-stat .num{font-size:26px;font-weight:800;margin-bottom:2px}
.est-stat.en-attente .num{color:#f59e0b}.est-stat.traitee .num{color:#8b5cf6}
.est-stat.convertie .num{color:#10b981}.est-stat.rdv .num{color:#06b6d4}
.est-stat.reports .num{color:#ec4899}.est-stat.emails .num{color:#f97316}
.est-stat .lbl{font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase}

.est-alert{padding:14px 18px;border-radius:10px;margin-bottom:18px;font-size:13px;line-height:1.6}
.est-alert.error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}

.est-toolbar{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px}
.est-filters{display:flex;gap:5px;flex-wrap:wrap}
.est-fbtn{padding:5px 12px;border:1px solid #e5e7eb;border-radius:8px;background:white;font-size:12px;font-weight:500;cursor:pointer;color:#4b5563;text-decoration:none;transition:all .2s}
.est-fbtn:hover,.est-fbtn.on{border-color:#6366f1;color:#6366f1;background:#eef2ff}
.est-search{display:flex;gap:6px}
.est-search input{padding:6px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;width:220px}
.est-search input:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.est-search button{padding:6px 14px;background:#6366f1;color:white;border:none;border-radius:8px;font-size:13px;cursor:pointer}

.est-wrap{background:white;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);border:1px solid #e5e7eb;overflow:hidden}
.est-title-bar{padding:12px 18px;border-bottom:1px solid #e5e7eb;background:#f9fafb;display:flex;justify-content:space-between;align-items:center;font-size:14px;font-weight:700;color:#111827}
.est-title-bar .cnt{font-size:12px;font-weight:500;color:#9ca3af}

table.est-tbl{width:100%;border-collapse:collapse}
table.est-tbl thead tr{background:#f9fafb;border-bottom:1px solid #e5e7eb}
table.est-tbl th{padding:9px 12px;text-align:left;font-weight:600;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap}
table.est-tbl td{padding:11px 12px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#374151;vertical-align:top}
table.est-tbl tbody tr{transition:background .15s}
table.est-tbl tbody tr:hover{background:#f5f3ff}
table.est-tbl tbody tr.en-attente-row{background:#fffbeb}

.b{display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap}
.b.en_attente{background:#fef3c7;color:#92400e}.b.traitee{background:#ede9fe;color:#5b21b6}.b.convertie{background:#d1fae5;color:#065f46}
.type-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;background:#f3f4f6;color:#374151}
.type-badge.maison{background:#fef3c7;color:#92400e}.type-badge.appartement{background:#dbeafe;color:#1e40af}
.type-badge.terrain{background:#d1fae5;color:#065f46}.type-badge.commerce{background:#fce7f3;color:#9d174d}
.info-pill{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:10px;font-size:10px;font-weight:600;background:#f3f4f6;color:#6b7280;margin-right:3px}
.info-pill.has{background:#dbeafe;color:#1e40af}
.est-range{display:flex;align-items:center;gap:6px}
.est-price{font-weight:700;font-size:12px}.est-price.low{color:#d97706}.est-price.high{color:#059669}
.est-price-sep{color:#d1d5db;font-size:11px}
.sub{font-size:11px;color:#9ca3af}

.est-acts{display:flex;gap:4px;flex-wrap:wrap}
.ebtn{padding:4px 8px;font-size:11px;border-radius:6px;border:none;cursor:pointer;transition:all .2s;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:3px}
.ebtn-view{background:#f3f4f6;color:#374151}.ebtn-view:hover{background:#e5e7eb}
.ebtn-call{background:#dbeafe;color:#1e40af}.ebtn-call:hover{background:#bfdbfe}
.ebtn-email{background:#f5f3ff;color:#7c3aed}.ebtn-email:hover{background:#ede9fe}
.ebtn-del{background:#fee2e2;color:#991b1b}.ebtn-del:hover{background:#fecaca}

.quick-email-dropdown{display:none;position:absolute;z-index:50;background:white;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:220px;padding:6px;margin-top:4px}
.quick-email-dropdown.open{display:block}
.quick-email-dropdown button{width:100%;text-align:left;padding:8px 12px;background:none;border:none;font-size:12px;cursor:pointer;border-radius:6px;color:#374151;display:flex;align-items:center;gap:8px}
.quick-email-dropdown button:hover{background:#f5f3ff;color:#6366f1}

.est-empty{text-align:center;padding:50px 20px;color:#9ca3af}
.est-empty i{font-size:40px;margin-bottom:12px;opacity:.4;display:block}

.drow{display:none}.drow.open{display:table-row}
.dcell{padding:0 12px 12px;background:#fafaff;border-bottom:2px solid #e5e7eb}
.dgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;padding:14px;background:white;border-radius:10px;border:1px solid #e5e7eb;margin-top:6px}
.dblock h4{font-size:11px;font-weight:700;text-transform:uppercase;color:#6366f1;margin:0 0 6px;letter-spacing:.3px}
.dblock p{font-size:12px;color:#374151;margin:2px 0;line-height:1.5}.dblock p strong{color:#111827}

.dactions{display:flex;gap:8px;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb;flex-wrap:wrap}
.dactions select{padding:6px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px}
.dactions button,.dactions a.dbtn{padding:6px 14px;background:#6366f1;color:white;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.dactions button:hover,.dactions a.dbtn:hover{background:#4f46e5}
.dactions .btn-sec{background:#f3f4f6;color:#374151}.dactions .btn-sec:hover{background:#e5e7eb}
.dactions .btn-email{background:#8b5cf6}.dactions .btn-email:hover{background:#7c3aed}

.dnotes{margin-top:10px;padding-top:10px;border-top:1px solid #e5e7eb}
.dnotes textarea{width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px;font-family:inherit;resize:vertical;min-height:60px;box-sizing:border-box}
.dnotes textarea:focus{outline:none;border-color:#6366f1}
.dnotes button{margin-top:6px;padding:5px 12px;background:#10b981;color:white;border:none;border-radius:5px;font-size:11px;cursor:pointer}

.est-pag{display:flex;justify-content:center;gap:4px;padding:14px;border-top:1px solid #f3f4f6}
.est-pag a,.est-pag span{padding:5px 11px;border-radius:6px;font-size:12px;text-decoration:none;border:1px solid #e5e7eb;color:#4b5563}
.est-pag span.cur{background:#6366f1;color:white;border-color:#6366f1}
.est-pag a:hover{border-color:#6366f1;color:#6366f1}

@media(max-width:768px){.est-stats{grid-template-columns:repeat(2,1fr)}.est-toolbar{flex-direction:column;align-items:stretch}.est-search input{width:100%}.dgrid{grid-template-columns:1fr}}
</style>

<!-- HEADER -->
<div class="est-header">
    <h2>📊 Estimations en ligne</h2>
    <div class="est-header-actions">
        <a href="<?php echo $baseUrl; ?>?page=estimation&sub=emails" class="hbtn email-btn">
            <i class="fas fa-envelope"></i> Gérer les emails
            <?php if ($emailCount > 0): ?><span class="email-count"><?php echo $emailCount; ?></span><?php endif; ?>
        </a>
        <a href="<?php echo $moduleUrl; ?>" class="hbtn"><i class="fas fa-sync-alt"></i> Actualiser</a>
    </div>
</div>

<?php if ($dbError): ?>
    <div class="est-alert error">❌ <strong>Erreur :</strong> <?php echo esc($dbError); ?></div>
<?php endif; ?>

<!-- STATS -->
<div class="est-stats">
    <a href="<?php echo $moduleUrl; ?>&filter_statut=en_attente" class="est-stat en-attente">
        <div class="num"><?php echo $nb_en_attente; ?></div><div class="lbl">🆕 En attente</div>
    </a>
    <a href="<?php echo $moduleUrl; ?>&filter_statut=traitee" class="est-stat traitee">
        <div class="num"><?php echo $nb_traitee; ?></div><div class="lbl">✅ Traitées</div>
    </a>
    <a href="<?php echo $moduleUrl; ?>&filter_statut=convertie" class="est-stat convertie">
        <div class="num"><?php echo $nb_convertie; ?></div><div class="lbl">🎯 Converties</div>
    </a>
    <?php if ($rdvCount > 0): ?><a href="#" class="est-stat rdv"><div class="num"><?php echo $rdvCount; ?></div><div class="lbl">📅 RDV</div></a><?php endif; ?>
    <?php if ($reportCount > 0): ?><a href="#" class="est-stat reports"><div class="num"><?php echo $reportCount; ?></div><div class="lbl">📄 Rapports</div></a><?php endif; ?>
    <?php if ($emailCount > 0): ?><a href="<?php echo $baseUrl; ?>?page=estimation&sub=emails&tab=history" class="est-stat emails"><div class="num"><?php echo $emailCount; ?></div><div class="lbl">✉️ Emails</div></a><?php endif; ?>
    <a href="<?php echo $moduleUrl; ?>" class="est-stat"><div class="num" style="color:#6366f1"><?php echo $total_requests; ?></div><div class="lbl">📋 Total</div></a>
</div>

<!-- TOOLBAR -->
<div class="est-toolbar">
    <div class="est-filters">
        <a href="<?php echo $moduleUrl; ?>" class="est-fbtn <?php echo $filterStatut==='all'&&!$filterType?'on':''; ?>">Tous</a>
        <a href="<?php echo $moduleUrl; ?>&filter_statut=en_attente" class="est-fbtn <?php echo $filterStatut==='en_attente'?'on':''; ?>">🆕 En attente</a>
        <a href="<?php echo $moduleUrl; ?>&filter_statut=traitee" class="est-fbtn <?php echo $filterStatut==='traitee'?'on':''; ?>">✅ Traitée</a>
        <a href="<?php echo $moduleUrl; ?>&filter_statut=convertie" class="est-fbtn <?php echo $filterStatut==='convertie'?'on':''; ?>">🎯 Convertie</a>
        <span style="border-left:1px solid #e5e7eb;margin:0 4px"></span>
        <a href="<?php echo $moduleUrl; ?>&type_bien=maison" class="est-fbtn <?php echo $filterType==='maison'?'on':''; ?>">🏠 Maison</a>
        <a href="<?php echo $moduleUrl; ?>&type_bien=appartement" class="est-fbtn <?php echo $filterType==='appartement'?'on':''; ?>">🏢 Appart</a>
        <a href="<?php echo $moduleUrl; ?>&type_bien=terrain" class="est-fbtn <?php echo $filterType==='terrain'?'on':''; ?>">🌿 Terrain</a>
        <a href="<?php echo $moduleUrl; ?>&type_bien=commerce" class="est-fbtn <?php echo $filterType==='commerce'?'on':''; ?>">🏪 Commerce</a>
    </div>
    <form class="est-search" method="get">
        <input type="hidden" name="page" value="estimation">
        <?php if ($filterStatut !== 'all'): ?><input type="hidden" name="filter_statut" value="<?php echo esc($filterStatut); ?>"><?php endif; ?>
        <input type="text" name="q" placeholder="Nom, email, adresse, ville, tél..." value="<?php echo esc($filterSearch); ?>">
        <button type="submit"><i class="fas fa-search"></i></button>
    </form>
</div>

<!-- TABLE -->
<div class="est-wrap">
    <div class="est-title-bar">
        <span>Demandes d'estimation</span>
        <span class="cnt"><?php echo $filteredTotal; ?> résultat<?php echo $filteredTotal>1?'s':''; ?></span>
    </div>

    <?php if (empty($requests) && $tableExists): ?>
        <div class="est-empty">
            <i class="fas fa-inbox"></i>
            <h3>Aucune demande<?php echo $filterStatut!=='all'?' avec ce filtre':''; ?></h3>
            <p>Les demandes d'estimation du formulaire public apparaîtront ici.</p>
        </div>
    <?php elseif (!empty($requests)): ?>
        <table class="est-tbl">
            <thead><tr><th>#</th><th>Contact</th><th>Bien</th><th>Localisation</th><th>Estimation</th><th>Statut</th><th>Infos</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r):
                $rid=(int)$r['id']; $isEA=($r['statut']??'')==='en_attente';
                $statutMap=['en_attente'=>'🆕 En attente','traitee'=>'✅ Traitée','convertie'=>'🎯 Convertie'];
                $statut=$r['statut']??'en_attente'; $statutLabel=$statutMap[$statut]??ucfirst($statut);
                $typeBien=$r['type_bien']??'';
                $typeLabels=['maison'=>'🏠 Maison','appartement'=>'🏢 Appartement','terrain'=>'🌿 Terrain','commerce'=>'🏪 Commerce'];
                $estB=$r['estimation_basse']??null; $estH=$r['estimation_haute']??null;
                $nbR=(int)($r['nb_rdv']??0); $nbRp=(int)($r['nb_reports']??0); $nbC=(int)($r['nb_contacts']??0);
                $fn=trim(($r['prenom']??'').' '.($r['nom']??'')); if(!$fn) $fn='—';
            ?>
                <tr id="row-<?php echo $rid; ?>" class="<?php echo $isEA?'en-attente-row':''; ?>">
                    <td><strong style="color:#6366f1">#<?php echo $rid; ?></strong></td>
                    <td>
                        <strong><?php echo esc($fn); ?></strong><br>
                        <?php if($r['email']??''):?><span class="sub"><a href="mailto:<?php echo esc($r['email']); ?>"><?php echo esc($r['email']); ?></a></span><br><?php endif; ?>
                        <?php if($r['telephone']??''):?><span class="sub"><a href="tel:<?php echo esc($r['telephone']); ?>"><?php echo esc($r['telephone']); ?></a></span><?php endif; ?>
                    </td>
                    <td>
                        <span class="type-badge <?php echo esc($typeBien); ?>"><?php echo $typeLabels[$typeBien]??ucfirst($typeBien?:'—'); ?></span><br>
                        <?php if($r['surface']??0):?><span class="sub"><?php echo esc($r['surface']); ?> m²</span><?php endif; ?>
                        <?php if($r['pieces']??0):?><span class="sub"> · <?php echo esc($r['pieces']); ?> p.</span><?php endif; ?>
                    </td>
                    <td><?php echo esc($r['adresse']??'—'); ?><br><span class="sub"><?php echo esc($r['code_postal']??''); ?> <?php echo esc($r['ville']??''); ?></span></td>
                    <td>
                        <?php if(fmtPrice($estB)||fmtPrice($estH)):?>
                            <div class="est-range"><span class="est-price low"><?php echo fmtPrice($estB)?:'—'; ?></span><span class="est-price-sep">→</span><span class="est-price high"><?php echo fmtPrice($estH)?:'—'; ?></span></div>
                        <?php else:?><span class="sub">Non estimé</span><?php endif; ?>
                    </td>
                    <td><span class="b <?php echo esc($statut); ?>"><?php echo $statutLabel; ?></span></td>
                    <td>
                        <?php if($nbR>0):?><span class="info-pill has">📅 <?php echo $nbR; ?></span><?php endif; ?>
                        <?php if($nbRp>0):?><span class="info-pill has">📄 <?php echo $nbRp; ?></span><?php endif; ?>
                        <?php if($nbC>0):?><span class="info-pill has">💬 <?php echo $nbC; ?></span><?php endif; ?>
                        <?php if($nbR===0&&$nbRp===0&&$nbC===0):?><span class="sub">—</span><?php endif; ?>
                    </td>
                    <td><span class="sub"><?php echo date('d/m/Y',strtotime($r['date_creation'])); ?></span><br><span class="sub"><?php echo date('H:i',strtotime($r['date_creation'])); ?></span></td>
                    <td style="position:relative">
                        <div class="est-acts">
                            <button class="ebtn ebtn-view" onclick="toggleD(<?php echo $rid; ?>)" title="Détails"><i class="fas fa-eye"></i></button>
                            <?php if($r['telephone']??''):?><a href="tel:<?php echo esc($r['telephone']); ?>" class="ebtn ebtn-call" title="Appeler"><i class="fas fa-phone"></i></a><?php endif; ?>
                            <?php if($r['email']??''):?><button class="ebtn ebtn-email" onclick="toggleQE(<?php echo $rid; ?>)" title="Email rapide"><i class="fas fa-envelope"></i></button><?php endif; ?>
                            <button class="ebtn ebtn-del" onclick="archiveReq(<?php echo $rid; ?>)" title="Supprimer"><i class="fas fa-trash"></i></button>
                        </div>
                        <?php if($r['email']??''):?>
                        <div class="quick-email-dropdown" id="qem-<?php echo $rid; ?>">
                            <button onclick="sendQE(<?php echo $rid; ?>,'confirmation')">🔔 Confirmation demande</button>
                            <button onclick="sendQE(<?php echo $rid; ?>,'estimation')">💰 Résultat estimation</button>
                            <button onclick="sendQE(<?php echo $rid; ?>,'followup')">🔄 Relance / Follow-up</button>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <!-- Détail -->
                <tr class="drow" id="d-<?php echo $rid; ?>">
                    <td colspan="9" class="dcell">
                        <div class="dgrid">
                            <div class="dblock"><h4>🏠 Le bien</h4>
                                <p><strong>Type :</strong> <?php echo esc(ucfirst($r['type_bien']??'—')); ?></p>
                                <p><strong>Adresse :</strong> <?php echo esc($r['adresse']??'—'); ?></p>
                                <p><strong>Ville :</strong> <?php echo esc($r['code_postal']??''); ?> <?php echo esc($r['ville']??'—'); ?></p>
                                <?php if($r['surface']??0):?><p><strong>Surface :</strong> <?php echo esc($r['surface']); ?> m²</p><?php endif; ?>
                                <?php if($r['pieces']??0):?><p><strong>Pièces :</strong> <?php echo esc($r['pieces']); ?></p><?php endif; ?>
                            </div>
                            <div class="dblock"><h4>👤 Contact</h4>
                                <p><strong>Prénom :</strong> <?php echo esc($r['prenom']??'—'); ?></p>
                                <p><strong>Nom :</strong> <?php echo esc($r['nom']??'—'); ?></p>
                                <?php if($r['email']??''):?><p><strong>Email :</strong> <a href="mailto:<?php echo esc($r['email']); ?>"><?php echo esc($r['email']); ?></a></p><?php endif; ?>
                                <?php if($r['telephone']??''):?><p><strong>Tél :</strong> <a href="tel:<?php echo esc($r['telephone']); ?>"><?php echo esc($r['telephone']); ?></a></p><?php endif; ?>
                            </div>
                            <div class="dblock"><h4>💰 Estimation</h4>
                                <?php if(fmtPrice($estB)):?><p><strong>Basse :</strong> <?php echo fmtPrice($estB); ?></p><?php endif; ?>
                                <?php if(fmtPrice($estH)):?><p><strong>Haute :</strong> <?php echo fmtPrice($estH); ?></p><?php endif; ?>
                                <?php if(!fmtPrice($estB)&&!fmtPrice($estH)):?><p class="sub">Pas encore estimé</p><?php endif; ?>
                            </div>
                        </div>
                        <div class="dactions">
                            <select id="ss-<?php echo $rid; ?>">
                                <?php foreach($statutMap as $k=>$v):?><option value="<?php echo $k; ?>" <?php echo $statut===$k?'selected':''; ?>><?php echo $v; ?></option><?php endforeach; ?>
                            </select>
                            <button onclick="updStatut(<?php echo $rid; ?>)"><i class="fas fa-save"></i> Maj statut</button>
                            <?php if($r['email']??''):?><a href="mailto:<?php echo esc($r['email']); ?>" class="dbtn btn-sec" style="text-decoration:none"><i class="fas fa-envelope"></i> Email</a><?php endif; ?>
                            <a href="<?php echo $baseUrl; ?>?page=estimation&sub=emails&tab=send" class="dbtn btn-email" style="text-decoration:none"><i class="fas fa-paper-plane"></i> Template email</a>
                        </div>
                        <div class="dnotes">
                            <textarea id="notes-<?php echo $rid; ?>" placeholder="Notes internes..."><?php echo esc($r['notes']??''); ?></textarea>
                            <button onclick="saveNotes(<?php echo $rid; ?>)">💾 Sauvegarder notes</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
            <div class="est-pag">
            <?php for($pg=1;$pg<=$totalPages;$pg++):
                $pgU=$moduleUrl.($filterStatut!=='all'?"&filter_statut=$filterStatut":'').($filterType?"&type_bien=$filterType":'').($filterSearch?"&q=".urlencode($filterSearch):'')."&p=$pg";
            ?>
                <?php if($pg===$currentPage):?><span class="cur"><?php echo $pg; ?></span>
                <?php else:?><a href="<?php echo $pgU; ?>"><?php echo $pg; ?></a><?php endif; ?>
            <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
const MU='<?php echo $moduleUrl; ?>';
function toggleD(id){document.getElementById('d-'+id)?.classList.toggle('open');document.querySelectorAll('.quick-email-dropdown.open').forEach(e=>e.classList.remove('open'))}
function toggleQE(id){const m=document.getElementById('qem-'+id);document.querySelectorAll('.quick-email-dropdown.open').forEach(e=>{if(e!==m)e.classList.remove('open')});m?.classList.toggle('open')}
document.addEventListener('click',e=>{if(!e.target.closest('.ebtn-email')&&!e.target.closest('.quick-email-dropdown'))document.querySelectorAll('.quick-email-dropdown.open').forEach(e=>e.classList.remove('open'))});

function ajax(a,b){return fetch(MU+'&ajax_action='+a,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:b}).then(r=>r.json())}

function updStatut(id){const v=document.getElementById('ss-'+id)?.value;if(!v)return;ajax('update_statut','id='+id+'&statut='+encodeURIComponent(v)).then(d=>{if(d.success){const b=document.querySelector('#row-'+id+' .b');const m={'en_attente':'🆕 En attente','traitee':'✅ Traitée','convertie':'🎯 Convertie'};if(b){b.className='b '+v;b.textContent=m[v]||v}toast('✅ Statut mis à jour')}else toast('❌ '+(d.message||'Erreur'),'e')}).catch(()=>toast('❌ Erreur réseau','e'))}

function saveNotes(id){const v=document.getElementById('notes-'+id)?.value||'';ajax('update_notes','id='+id+'&notes='+encodeURIComponent(v)).then(d=>{d.success?toast('💾 Notes sauvegardées'):toast('❌ Erreur','e')})}

function archiveReq(id){if(!confirm('Supprimer cette demande ?'))return;ajax('delete','id='+id).then(d=>{if(d.success){document.getElementById('row-'+id).style.display='none';document.getElementById('d-'+id).style.display='none';toast('✅ Supprimé')}})}

function sendQE(eid,type){if(!confirm('Envoyer email "'+type+'" ?'))return;document.querySelectorAll('.quick-email-dropdown.open').forEach(e=>e.classList.remove('open'));ajax('send_quick_email','estimation_id='+eid+'&template_type='+encodeURIComponent(type)).then(d=>{d.success?toast('✅ '+d.message):toast('❌ '+(d.message||'Erreur'),'e')}).catch(()=>toast('❌ Erreur réseau','e'))}

function toast(m,t){const o=document.querySelector('.et');if(o)o.remove();const e=document.createElement('div');e.className='et';e.style.cssText='position:fixed;bottom:20px;left:50%;transform:translateX(-50%);padding:11px 22px;border-radius:10px;font-size:13px;font-weight:600;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,.15);color:white;transition:opacity .3s';e.style.background=t==='e'?'#991b1b':'#065f46';e.textContent=m;document.body.appendChild(e);setTimeout(()=>{e.style.opacity='0';setTimeout(()=>e.remove(),300)},3000)}
</script>