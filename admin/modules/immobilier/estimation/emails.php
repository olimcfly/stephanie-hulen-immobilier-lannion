<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * Module ESTIMATION EMAILS — /admin/modules/estimation/emails.php
 * Gestion templates, envoi, test, historique
 * ═══════════════════════════════════════════════════════════════
 */

// DB héritée du dashboard via index.php
if (!isset($pdo) && !isset($db)) {
    $rootPath = realpath(__DIR__ . '/../../../');
    if (file_exists($rootPath.'/config/config.php')) require_once $rootPath.'/config/config.php';
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    } catch(Exception $e) { $pdo = null; }
}
if (isset($db) && !isset($pdo)) $pdo = $db;

function esc2($v){return htmlspecialchars($v??'',ENT_QUOTES,'UTF-8');}

$dbError = null; $tablesOk = false;
if (isset($pdo)) {
    try {
        $c1=$pdo->query("SHOW TABLES LIKE 'estimation_templates'")->rowCount();
        $c2=$pdo->query("SHOW TABLES LIKE 'estimation_contacts'")->rowCount();
        $c3=$pdo->query("SHOW TABLES LIKE 'estimations'")->rowCount();
        $tablesOk = ($c1>0 && $c2>0 && $c3>0);
        if (!$tablesOk) { $m=[]; if(!$c1)$m[]='estimation_templates'; if(!$c2)$m[]='estimation_contacts'; if(!$c3)$m[]='estimations'; $dbError="Tables manquantes: ".implode(', ',$m); }
    } catch(Exception $e) { $dbError = $e->getMessage(); }
}

// Insérer templates par défaut si vide
if ($tablesOk) {
    try {
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM estimation_templates")->fetchColumn();
        if ($cnt === 0) {
            $defs = [
                ['Confirmation demande (Client)','confirmation','actif',
                 'Votre demande d\'estimation a bien été reçue - {{type_bien}} à {{ville}}',
                 '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#1e40af;padding:24px;border-radius:12px 12px 0 0;text-align:center"><h1 style="color:white;margin:0;font-size:22px">📊 Estimation immobilière</h1><p style="color:#93c5fd;margin:6px 0 0;font-size:14px">Eduardo De Sul — eXp France</p></div><div style="background:white;padding:28px;border:1px solid #e5e7eb"><p style="font-size:15px;color:#111827">Bonjour <strong>{{prenom}}</strong>,</p><p style="font-size:14px;color:#374151;line-height:1.7">Merci pour votre demande d\'estimation de votre <strong>{{type_bien}}</strong> à <strong>{{adresse}}, {{code_postal}} {{ville}}</strong>.</p><table style="width:100%;border-collapse:collapse;margin:16px 0"><tr style="background:#f9fafb"><td style="padding:10px 14px;font-size:13px;font-weight:600;color:#6b7280;border:1px solid #e5e7eb">Type</td><td style="padding:10px 14px;font-size:13px;border:1px solid #e5e7eb">{{type_bien}}</td></tr><tr><td style="padding:10px 14px;font-size:13px;font-weight:600;color:#6b7280;border:1px solid #e5e7eb">Surface</td><td style="padding:10px 14px;font-size:13px;border:1px solid #e5e7eb">{{surface}} m²</td></tr><tr style="background:#f9fafb"><td style="padding:10px 14px;font-size:13px;font-weight:600;color:#6b7280;border:1px solid #e5e7eb">Pièces</td><td style="padding:10px 14px;font-size:13px;border:1px solid #e5e7eb">{{pieces}}</td></tr></table><p style="font-size:14px;color:#374151;line-height:1.7">Je reviendrai vers vous rapidement. N\'hésitez pas à me contacter.</p><p style="font-size:14px;color:#374151">Cordialement,<br><strong>Eduardo De Sul</strong><br>eXp France — Bordeaux</p></div></div>'],
                ['Alerte nouvelle demande (Admin)','rdv','actif',
                 '🆕 Nouvelle estimation — {{prenom}} {{nom}} — {{type_bien}} {{ville}}',
                 '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#dc2626;padding:20px;border-radius:12px 12px 0 0;text-align:center"><h1 style="color:white;margin:0;font-size:20px">🚨 Nouvelle demande</h1></div><div style="background:white;padding:24px;border:1px solid #e5e7eb"><table style="width:100%;border-collapse:collapse"><tr><td style="padding:8px 12px;font-weight:600;border:1px solid #e5e7eb;width:35%">Contact</td><td style="padding:8px 12px;border:1px solid #e5e7eb">{{prenom}} {{nom}} — <a href="mailto:{{email}}">{{email}}</a> — <a href="tel:{{telephone}}">{{telephone}}</a></td></tr><tr><td style="padding:8px 12px;font-weight:600;border:1px solid #e5e7eb">Bien</td><td style="padding:8px 12px;border:1px solid #e5e7eb">{{type_bien}} — {{surface}} m² — {{pieces}} pièces</td></tr><tr><td style="padding:8px 12px;font-weight:600;border:1px solid #e5e7eb">Adresse</td><td style="padding:8px 12px;border:1px solid #e5e7eb">{{adresse}}, {{code_postal}} {{ville}}</td></tr><tr><td style="padding:8px 12px;font-weight:600;border:1px solid #e5e7eb">Estimation</td><td style="padding:8px 12px;border:1px solid #e5e7eb;font-weight:700;color:#065f46">{{estimation_basse}} € → {{estimation_haute}} €</td></tr></table><div style="text-align:center;margin:20px 0"><a href="https://eduardo-desul-immobilier.fr/admin/dashboard.php?page=estimation" style="display:inline-block;padding:12px 28px;background:#dc2626;color:white;text-decoration:none;border-radius:8px;font-weight:600">📊 Voir dans l\'admin</a></div></div></div>'],
                ['Résultat estimation (Client)','estimation','actif',
                 'Votre estimation immobilière — {{type_bien}} à {{ville}}',
                 '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:linear-gradient(135deg,#1e40af,#7c3aed);padding:28px;border-radius:12px 12px 0 0;text-align:center"><h1 style="color:white;margin:0;font-size:24px">💰 Votre estimation</h1></div><div style="background:white;padding:28px;border:1px solid #e5e7eb"><p style="font-size:15px">Bonjour <strong>{{prenom}}</strong>,</p><p style="font-size:14px;color:#374151;line-height:1.7">Voici l\'estimation de votre <strong>{{type_bien}}</strong> à <strong>{{adresse}}, {{code_postal}} {{ville}}</strong> :</p><div style="background:#f0fdf4;border:2px solid #10b981;border-radius:12px;padding:24px;text-align:center;margin:20px 0"><p style="font-size:12px;font-weight:600;color:#065f46;margin:0 0 8px;text-transform:uppercase">Fourchette estimée</p><p style="font-size:28px;font-weight:800;color:#065f46;margin:0">{{estimation_basse}} € — {{estimation_haute}} €</p></div><p style="font-size:14px;color:#374151;line-height:1.7">Pour affiner, je vous propose un RDV :</p><div style="text-align:center;margin:24px 0"><a href="tel:+33XXXXXXXXX" style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#1e40af,#7c3aed);color:white;text-decoration:none;border-radius:8px;font-weight:700">📞 Prendre RDV</a></div><p style="font-size:14px;color:#374151">Cordialement,<br><strong>Eduardo De Sul</strong><br>eXp France — Bordeaux</p></div></div>'],
                ['Relance (Client)','followup','actif',
                 'Des nouvelles de votre projet ? — {{type_bien}} à {{ville}}',
                 '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#1e40af;padding:24px;border-radius:12px 12px 0 0;text-align:center"><h1 style="color:white;margin:0;font-size:20px">🏠 Votre projet immobilier</h1></div><div style="background:white;padding:28px;border:1px solid #e5e7eb"><p>Bonjour <strong>{{prenom}}</strong>,</p><p style="font-size:14px;color:#374151;line-height:1.7">Je reviens vers vous suite à votre estimation pour votre <strong>{{type_bien}}</strong> à <strong>{{ville}}</strong>. Avez-vous avancé dans votre réflexion ?</p><p style="font-size:14px;color:#374151">Je reste disponible pour affiner l\'estimation, planifier une visite, ou répondre à vos questions.</p><div style="text-align:center;margin:24px 0"><a href="tel:+33XXXXXXXXX" style="display:inline-block;padding:12px 28px;background:#1e40af;color:white;text-decoration:none;border-radius:8px;font-weight:600">📞 Me rappeler</a></div><p style="font-size:14px;color:#374151">Bien cordialement,<br><strong>Eduardo De Sul</strong><br>eXp France</p></div></div>'],
            ];
            $ins = $pdo->prepare("INSERT INTO estimation_templates (name,type,status,subject,body,variables) VALUES (?,?,?,?,?,?)");
            $allVars = json_encode(['prenom','nom','email','telephone','type_bien','surface','pieces','adresse','ville','code_postal','estimation_basse','estimation_haute','date_creation']);
            foreach ($defs as $d) $ins->execute([$d[0],$d[1],$d[2],$d[3],$d[4],$allVars]);
        }
    } catch(Exception $e) {}
}

// Charger données
$templates=[]; $estimations=[]; $emailHistory=[];
if ($tablesOk) {
    try {
        $templates = $pdo->query("SELECT * FROM estimation_templates ORDER BY FIELD(type,'confirmation','rdv','estimation','followup'),name")->fetchAll();
        $estimations = $pdo->query("SELECT id,nom,prenom,email,type_bien,ville,statut,date_creation FROM estimations ORDER BY date_creation DESC LIMIT 50")->fetchAll();
        $emailHistory = $pdo->query("SELECT c.*,e.prenom,e.nom,e.email as lead_email FROM estimation_contacts c LEFT JOIN estimations e ON e.id=c.request_id WHERE c.contact_type='email' ORDER BY c.created_at DESC LIMIT 30")->fetchAll();
    } catch(Exception $e) { $dbError = $dbError ?: $e->getMessage(); }
}

$typeLabels = [
    'confirmation'=>['🔔 Confirmation','#dbeafe','#1e40af'],
    'rdv'=>['🚨 Alerte Admin','#fee2e2','#991b1b'],
    'estimation'=>['💰 Résultat','#d1fae5','#065f46'],
    'followup'=>['🔄 Relance','#fef3c7','#92400e'],
];

$baseUrl = strtok($_SERVER['REQUEST_URI'],'?');
$apiUrl = '/admin/modules/estimation/api.php';
$activeTab = $_GET['tab'] ?? 'templates';
?>

<style>
.em-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.em-header h2{font-size:22px;font-weight:800;color:#111827;display:flex;align-items:center;gap:10px}
.em-back{padding:8px 16px;background:white;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#6b7280;text-decoration:none;transition:all .2s}
.em-back:hover{border-color:#6366f1;color:#6366f1}
.em-tabs{display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #e5e7eb;padding-bottom:0}
.em-tab{padding:10px 18px;font-size:13px;font-weight:600;color:#6b7280;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s;cursor:pointer;background:none;border-top:none;border-left:none;border-right:none}
.em-tab:hover{color:#6366f1}.em-tab.active{color:#6366f1;border-bottom-color:#6366f1}
.em-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:22px}
.em-st{background:white;padding:14px 16px;border-radius:10px;border:1px solid #e5e7eb;text-align:center}
.em-st .num{font-size:24px;font-weight:800;color:#6366f1}.em-st .lbl{font-size:11px;color:#6b7280;font-weight:600;margin-top:2px}
.em-alert{padding:14px 18px;border-radius:10px;margin-bottom:18px;font-size:13px;line-height:1.6}
.em-alert.error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.em-alert.info{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af}
.em-alert.warning{background:#fffbeb;border:1px solid #fde68a;color:#92400e}

.tpl-list{display:grid;gap:14px}
.tpl-card{background:white;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;transition:all .2s}
.tpl-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.06)}
.tpl-head{padding:14px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f3f4f6;cursor:pointer}
.tpl-head:hover{background:#f9fafb}
.tpl-info{display:flex;align-items:center;gap:10px}
.tpl-badge{padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700}
.tpl-name{font-size:14px;font-weight:700;color:#111827}
.tpl-subj{font-size:12px;color:#6b7280;margin-top:2px}
.tpl-st{padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700}
.tpl-st.actif{background:#d1fae5;color:#065f46}.tpl-st.inactif{background:#fee2e2;color:#991b1b}

.tpl-ed{display:none;padding:18px;border-top:1px solid #e5e7eb}.tpl-ed.open{display:block}
.tf{margin-bottom:14px}
.tf label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px}
.tf input,.tf select{width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box}
.tf input:focus,.tf select:focus,.tf textarea:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.tf textarea{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:12px;font-family:monospace;min-height:280px;resize:vertical;box-sizing:border-box;line-height:1.6}
.tv{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.tv span{padding:3px 8px;background:#eef2ff;border:1px solid #c7d2fe;border-radius:6px;font-size:10px;font-weight:600;color:#4338ca;cursor:pointer;transition:all .15s}
.tv span:hover{background:#6366f1;color:white}
.tfa{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding-top:14px;border-top:1px solid #f3f4f6}
.bp{padding:8px 18px;background:#6366f1;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}
.bp:hover{background:#4f46e5}
.bs{padding:8px 18px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}
.bs:hover{background:#e5e7eb}
.bw{padding:8px 18px;background:#f59e0b;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}
.bw:hover{background:#d97706}
.bg2{padding:8px 18px;background:#10b981;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}

.test-sec{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:16px;margin-top:14px;display:none}
.test-sec h4{font-size:13px;font-weight:700;color:#92400e;margin:0 0 10px}
.test-row{display:flex;gap:8px;align-items:end}
.test-row input{flex:1;padding:8px 12px;border:1px solid #fde68a;border-radius:8px;font-size:13px}

.prev-panel{background:white;border-radius:12px;border:1px solid #e5e7eb;margin-top:14px;overflow:hidden;display:none}
.prev-head{padding:12px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:13px;font-weight:700;display:flex;justify-content:space-between;align-items:center}
.prev-subj{padding:10px 16px;background:#eff6ff;border-bottom:1px solid #bfdbfe;font-size:13px;color:#1e40af;font-weight:600}
.prev-body iframe{width:100%;height:450px;border:none}

.sp{background:white;border-radius:12px;border:1px solid #e5e7eb;padding:20px;margin-bottom:18px}
.sp h3{font-size:16px;font-weight:700;color:#111827;margin:0 0 14px;display:flex;align-items:center;gap:8px}
.sg{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.sg .fd label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px}
.sg .fd select,.sg .fd input{width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box}
.sa{display:flex;gap:8px;margin-top:16px;flex-wrap:wrap}

.hw{background:white;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden}
.ht{padding:12px 18px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:700;display:flex;justify-content:space-between;align-items:center}
table.htbl{width:100%;border-collapse:collapse}
table.htbl th{padding:9px 12px;text-align:left;font-weight:600;font-size:11px;color:#6b7280;text-transform:uppercase;background:#f9fafb;border-bottom:1px solid #e5e7eb}
table.htbl td{padding:10px 12px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#374151}
table.htbl tbody tr:hover{background:#f5f3ff}
.dir-b{display:inline-block;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700}
.dir-b.out{background:#dbeafe;color:#1e40af}
.he{text-align:center;padding:40px;color:#9ca3af}

@media(max-width:768px){.sg{grid-template-columns:1fr}}
</style>

<div class="em-header">
    <h2>✉️ Emails — Estimations</h2>
    <a href="<?php echo $baseUrl; ?>?page=estimation" class="em-back"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<?php if($dbError):?><div class="em-alert error">❌ <?php echo esc2($dbError); ?></div><?php endif; ?>

<div class="em-stats">
    <div class="em-st"><div class="num"><?php echo count($templates); ?></div><div class="lbl">📝 Templates</div></div>
    <div class="em-st"><div class="num"><?php echo count(array_filter($templates,fn($t)=>$t['status']==='actif')); ?></div><div class="lbl">✅ Actifs</div></div>
    <div class="em-st"><div class="num"><?php echo count($emailHistory); ?></div><div class="lbl">📤 Envoyés</div></div>
    <div class="em-st"><div class="num"><?php echo count($estimations); ?></div><div class="lbl">📊 Demandes</div></div>
</div>

<div class="em-tabs">
    <button class="em-tab <?php echo $activeTab==='templates'?'active':''; ?>" onclick="swTab('templates')">📝 Templates</button>
    <button class="em-tab <?php echo $activeTab==='send'?'active':''; ?>" onclick="swTab('send')">📤 Envoyer</button>
    <button class="em-tab <?php echo $activeTab==='history'?'active':''; ?>" onclick="swTab('history')">📋 Historique</button>
</div>

<!-- TAB TEMPLATES -->
<div id="tab-templates" class="tc" style="<?php echo $activeTab!=='templates'?'display:none':''; ?>">
    <?php if(empty($templates)):?>
        <div class="em-alert info">ℹ️ Aucun template trouvé. Rechargez la page.</div>
    <?php else:?>
        <div class="tpl-list">
        <?php foreach($templates as $tpl):
            $tid=(int)$tpl['id']; $tt=$tpl['type']??'confirmation';
            $tL=$typeLabels[$tt]??['📧 Email','#f3f4f6','#374151'];
        ?>
            <div class="tpl-card" id="tpl-<?php echo $tid; ?>">
                <div class="tpl-head" onclick="document.getElementById('ed-<?php echo $tid; ?>').classList.toggle('open')">
                    <div class="tpl-info">
                        <span class="tpl-badge" style="background:<?php echo $tL[1]; ?>;color:<?php echo $tL[2]; ?>"><?php echo $tL[0]; ?></span>
                        <div><div class="tpl-name"><?php echo esc2($tpl['name']); ?></div><div class="tpl-subj"><?php echo esc2(mb_strimwidth($tpl['subject']??'',0,80,'...')); ?></div></div>
                    </div>
                    <span class="tpl-st <?php echo esc2($tpl['status']); ?>"><?php echo $tpl['status']==='actif'?'✅ Actif':'⏸ Inactif'; ?></span>
                </div>
                <div class="tpl-ed" id="ed-<?php echo $tid; ?>">
                    <div class="tf"><label>Nom</label><input type="text" id="tn-<?php echo $tid; ?>" value="<?php echo esc2($tpl['name']); ?>"></div>
                    <div class="tf"><label>Sujet</label><input type="text" id="ts-<?php echo $tid; ?>" value="<?php echo esc2($tpl['subject']); ?>"></div>
                    <div class="tf"><label>Statut</label><select id="tst-<?php echo $tid; ?>"><option value="actif" <?php echo $tpl['status']==='actif'?'selected':''; ?>>✅ Actif</option><option value="inactif" <?php echo $tpl['status']==='inactif'?'selected':''; ?>>⏸ Inactif</option></select></div>
                    <div class="tf"><label>Corps (HTML)</label><textarea id="tb-<?php echo $tid; ?>"><?php echo esc2($tpl['body']); ?></textarea></div>
                    <div class="tf"><label>📌 Variables <span style="font-weight:400;color:#9ca3af">(cliquer pour insérer)</span></label>
                        <div class="tv"><?php foreach(['prenom','nom','email','telephone','type_bien','surface','pieces','adresse','ville','code_postal','estimation_basse','estimation_haute','date_creation'] as $v):?><span onclick="insVar(<?php echo $tid; ?>,'<?php echo $v; ?>')">{{<?php echo $v; ?>}}</span><?php endforeach; ?></div>
                    </div>
                    <div class="tfa">
                        <button class="bp" onclick="saveTpl(<?php echo $tid; ?>)"><i class="fas fa-save"></i> Sauvegarder</button>
                        <button class="bs" onclick="prevTpl(<?php echo $tid; ?>)"><i class="fas fa-eye"></i> Prévisualiser</button>
                        <button class="bw" onclick="document.getElementById('tst-<?php echo $tid; ?>-sec').style.display=document.getElementById('tst-<?php echo $tid; ?>-sec').style.display==='none'?'block':'none'"><i class="fas fa-paper-plane"></i> Test email</button>
                    </div>
                    <div class="test-sec" id="tst-<?php echo $tid; ?>-sec">
                        <h4>📧 Envoyer un test</h4>
                        <div class="test-row">
                            <input type="email" id="te-<?php echo $tid; ?>" placeholder="votre-email@test.fr">
                            <button class="bw" onclick="sendTest(<?php echo $tid; ?>)"><i class="fas fa-paper-plane"></i> Envoyer</button>
                        </div>
                    </div>
                    <div class="prev-panel" id="pv-<?php echo $tid; ?>">
                        <div class="prev-head"><span>👁️ Prévisualisation</span><button class="bs" onclick="document.getElementById('pv-<?php echo $tid; ?>').style.display='none'" style="padding:4px 10px;font-size:11px">✕</button></div>
                        <div class="prev-subj" id="pvs-<?php echo $tid; ?>"></div>
                        <div class="prev-body"><iframe id="pvf-<?php echo $tid; ?>"></iframe></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- TAB SEND -->
<div id="tab-send" class="tc" style="<?php echo $activeTab!=='send'?'display:none':''; ?>">
    <div class="sp">
        <h3>📤 Envoyer un email</h3>
        <div class="sg">
            <div class="fd"><label>Template</label>
                <select id="s-tpl"><option value="">— Choisir —</option>
                    <?php foreach($templates as $t): if($t['status']!=='actif')continue; $tL=$typeLabels[$t['type']??'']??['📧','','']; ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo $tL[0]; ?> <?php echo esc2($t['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fd"><label>Demande d'estimation</label>
                <select id="s-est"><option value="">— Choisir —</option>
                    <?php foreach($estimations as $e):?>
                        <option value="<?php echo $e['id']; ?>">#<?php echo $e['id']; ?> — <?php echo esc2($e['prenom'].' '.$e['nom']); ?> — <?php echo esc2($e['email']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="sa">
            <button class="bs" onclick="prevSend()"><i class="fas fa-eye"></i> Prévisualiser</button>
            <button class="bp" onclick="sendLead()"><i class="fas fa-paper-plane"></i> Envoyer</button>
        </div>
    </div>
    <div class="prev-panel" id="send-pv">
        <div class="prev-head"><span>👁️ Prévisualisation</span><button class="bs" onclick="document.getElementById('send-pv').style.display='none'" style="padding:4px 10px;font-size:11px">✕</button></div>
        <div class="prev-subj" id="send-pvs"></div>
        <div class="prev-body"><iframe id="send-pvf"></iframe></div>
    </div>
</div>

<!-- TAB HISTORY -->
<div id="tab-history" class="tc" style="<?php echo $activeTab!=='history'?'display:none':''; ?>">
    <div class="hw">
        <div class="ht"><span>📋 Emails envoyés</span><span style="font-size:12px;color:#9ca3af"><?php echo count($emailHistory); ?></span></div>
        <?php if(empty($emailHistory)):?>
            <div class="he"><h3>Aucun email envoyé</h3><p>L'historique apparaîtra après vos premiers envois.</p></div>
        <?php else:?>
            <table class="htbl"><thead><tr><th>Date</th><th>Direction</th><th>Destinataire</th><th>Sujet</th><th>Actions</th></tr></thead><tbody>
                <?php foreach($emailHistory as $h):?>
                <tr>
                    <td><strong><?php echo date('d/m/Y',strtotime($h['created_at'])); ?></strong><br><span style="font-size:11px;color:#9ca3af"><?php echo date('H:i',strtotime($h['created_at'])); ?></span></td>
                    <td><span class="dir-b out">📤 Envoyé</span></td>
                    <td><strong><?php echo esc2(($h['prenom']??'').' '.($h['nom']??'')); ?></strong><br><span style="font-size:11px;color:#6b7280"><?php echo esc2($h['lead_email']??''); ?></span></td>
                    <td><?php echo esc2(mb_strimwidth($h['subject']??'(sans sujet)',0,60,'...')); ?></td>
                    <td><button class="bs" onclick="viewMail(<?php echo (int)$h['id']; ?>)" style="padding:4px 10px;font-size:11px"><i class="fas fa-eye"></i></button></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL -->
<div id="emodal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:9999;justify-content:center;align-items:center" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:white;border-radius:14px;width:90%;max-width:700px;max-height:85vh;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="padding:14px 20px;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center">
            <strong>📧 Email</strong><button onclick="document.getElementById('emodal').style.display='none'" style="background:none;border:none;font-size:18px;cursor:pointer">✕</button>
        </div>
        <div id="emodal-subj" style="padding:10px 20px;background:#eff6ff;font-size:13px;font-weight:600;color:#1e40af;border-bottom:1px solid #bfdbfe"></div>
        <div><iframe id="emodal-frame" style="width:100%;height:450px;border:none"></iframe></div>
    </div>
</div>

<script>
const EAPI = '<?php echo $apiUrl; ?>';

function swTab(t){document.querySelectorAll('.tc').forEach(e=>e.style.display='none');document.querySelectorAll('.em-tab').forEach(e=>e.classList.remove('active'));document.getElementById('tab-'+t).style.display='block';event.target.classList.add('active')}

function insVar(tid,v){const ta=document.getElementById('tb-'+tid);if(!ta)return;const s=ta.selectionStart,e=ta.selectionEnd,i='{{'+v+'}}';ta.value=ta.value.substring(0,s)+i+ta.value.substring(e);ta.selectionStart=ta.selectionEnd=s+i.length;ta.focus()}

function eapi(action,body){
    return fetch(EAPI+'?action='+action,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body}).then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json()});
}

function saveTpl(id){
    const n=document.getElementById('tn-'+id)?.value||'', s=document.getElementById('ts-'+id)?.value||'',
          b=document.getElementById('tb-'+id)?.value||'', st=document.getElementById('tst-'+id)?.value||'actif';
    if(!n||!s){toast('❌ Nom et sujet requis','e');return}
    eapi('save_template','id='+id+'&name='+encodeURIComponent(n)+'&subject='+encodeURIComponent(s)+'&body='+encodeURIComponent(b)+'&status='+st).then(d=>{
        if(d.success){toast('✅ Sauvegardé');const badge=document.querySelector('#tpl-'+id+' .tpl-st');if(badge){badge.className='tpl-st '+st;badge.textContent=st==='actif'?'✅ Actif':'⏸ Inactif'}}
        else toast('❌ '+(d.message||'Erreur'),'e');
    }).catch(e=>toast('❌ '+e.message,'e'));
}

function prevTpl(tid){
    const b=document.getElementById('tb-'+tid)?.value||'', s=document.getElementById('ts-'+tid)?.value||'';
    const td={prenom:'Jean',nom:'Dupont',email:'jean.dupont@test.fr',telephone:'06 12 34 56 78',type_bien:'Appartement',surface:'85',pieces:'4',adresse:'15 rue Sainte-Catherine',ville:'Bordeaux',code_postal:'33000',estimation_basse:'285 000',estimation_haute:'320 000',date_creation:new Date().toLocaleDateString('fr-FR')};
    let ps=s,pb=b;for(const[k,v]of Object.entries(td)){ps=ps.replaceAll('{{'+k+'}}',v);pb=pb.replaceAll('{{'+k+'}}',v)}
    const p=document.getElementById('pv-'+tid);p.style.display='block';
    document.getElementById('pvs-'+tid).textContent='📧 '+ps;
    document.getElementById('pvf-'+tid).srcdoc=pb;
}

function sendTest(tid){
    const email=document.getElementById('te-'+tid)?.value||'';
    if(!email){toast('❌ Email requis','e');return}
    saveTpl(tid);
    setTimeout(()=>{
        eapi('send_test','template_id='+tid+'&test_email='+encodeURIComponent(email)).then(d=>{
            d.success?toast('✅ '+d.message):toast('❌ '+(d.message||'Erreur'),'e');
        }).catch(e=>toast('❌ '+e.message,'e'));
    },500);
}

function prevSend(){
    const tid=document.getElementById('s-tpl')?.value, eid=document.getElementById('s-est')?.value;
    if(!tid){toast('❌ Choisissez un template','e');return}
    eapi('preview','template_id='+tid+'&estimation_id='+(eid||0)).then(d=>{
        if(d.success){const p=document.getElementById('send-pv');p.style.display='block';document.getElementById('send-pvs').textContent='📧 '+d.subject;document.getElementById('send-pvf').srcdoc=d.body}
        else toast('❌ '+(d.message||'Erreur'),'e');
    }).catch(e=>toast('❌ '+e.message,'e'));
}

function sendLead(){
    const tid=document.getElementById('s-tpl')?.value, eid=document.getElementById('s-est')?.value;
    if(!tid||!eid){toast('❌ Template ET demande requis','e');return}
    if(!confirm('Envoyer cet email ?'))return;
    eapi('send_to_lead','template_id='+tid+'&estimation_id='+eid).then(d=>{
        d.success?toast('✅ '+d.message):toast('❌ '+(d.message||'Erreur'),'e');
    }).catch(e=>toast('❌ '+e.message,'e'));
}

// Historique modal
<?php $hjs=[]; foreach($emailHistory as $h) $hjs[$h['id']]=['subject'=>$h['subject']??'','message'=>$h['message']??'']; ?>
const eData=<?php echo json_encode($hjs,JSON_HEX_TAG|JSON_HEX_APOS); ?>;
function viewMail(id){const d=eData[id];if(!d)return;const m=document.getElementById('emodal');m.style.display='flex';document.getElementById('emodal-subj').textContent='📧 '+d.subject;document.getElementById('emodal-frame').srcdoc=d.message}
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.getElementById('emodal').style.display='none'});

function toast(m,t){const o=document.querySelector('.et3');if(o)o.remove();const e=document.createElement('div');e.className='et3';e.style.cssText='position:fixed;bottom:20px;left:50%;transform:translateX(-50%);padding:11px 22px;border-radius:10px;font-size:13px;font-weight:600;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,.15);color:white;transition:opacity .3s';e.style.background=t==='e'?'#991b1b':'#065f46';e.textContent=m;document.body.appendChild(e);setTimeout(()=>{e.style.opacity='0';setTimeout(()=>e.remove(),300)},3000)}
</script>