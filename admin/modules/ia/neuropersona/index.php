<?php
/**
 * MODULE NEUROPERSONA v2.0 - REFACTORISATION COMPLÈTE
 * ÉCOSYSTÈME IMMO LOCAL+
 * 
 * 5 onglets : Profil | M.E.R.E | Templates | Lead Magnets | Plan Com
 * CRUD complet + Liens contextuels vers modules + M.E.R.E éditable
 * 
 * Table : neuropersona_types (existante, colonnes ajoutées via migration)
 */

// ============================================================
// CONNEXION DB
// ============================================================
if (!isset($pdo)) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Exception $e) {
        echo '<div style="padding:2rem;text-align:center;color:#ef4444"><h3>❌ Erreur de connexion</h3></div>';
        return;
    }
}

// Vérifier si la table existe
$tableExists = false;
try {
    $tableExists = !empty($pdo->query("SHOW TABLES LIKE 'neuropersona_types'")->fetch());
} catch (Exception $e) {}

// ============================================================
// ACTIONS CRUD (POST)
// ============================================================
$message = $error = '';
$action = $_GET['action'] ?? 'cartographie';

// --- Installation ---
if ($action === 'install') {
    // Rediriger vers l'ancien install ou utiliser le SQL migration
    $message = "⚠️ Veuillez exécuter le fichier migration_neuropersona_v2.sql dans phpMyAdmin.";
}

// --- Sauvegarder un persona (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crud_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_POST['crud_action']) {
            
            case 'save':
                $id = (int)($_POST['persona_id'] ?? 0);
                
                // Préparer les données
                $data = [
                    'nom' => trim($_POST['nom'] ?? ''),
                    'code' => trim($_POST['code'] ?? ''),
                    'categorie' => $_POST['categorie'] ?? 'acheteur',
                    'icone' => $_POST['icone'] ?? '👤',
                    'couleur' => $_POST['couleur'] ?? '#6366f1',
                    'age_moyen' => trim($_POST['age_moyen'] ?? ''),
                    'situation_familiale' => trim($_POST['situation_familiale'] ?? ''),
                    'budget_moyen' => trim($_POST['budget_moyen'] ?? ''),
                    'cycle_decision' => trim($_POST['cycle_decision'] ?? ''),
                    'niveau_urgence' => (int)($_POST['niveau_urgence'] ?? 5),
                    'description' => trim($_POST['description'] ?? ''),
                    // JSON fields (textarea -> json array)
                    'motivations' => jsonEncodeLines($_POST['motivations'] ?? ''),
                    'problemes' => jsonEncodeLines($_POST['problemes'] ?? ''),
                    'solutions' => jsonEncodeLines($_POST['solutions'] ?? ''),
                    'objections' => jsonEncodeLines($_POST['objections'] ?? ''),
                    'messages_cles' => jsonEncodeLines($_POST['messages_cles'] ?? ''),
                    'contenus_recommandes' => jsonEncodeLines($_POST['contenus_recommandes'] ?? ''),
                    'canaux_prioritaires' => jsonEncodeLines($_POST['canaux_prioritaires'] ?? ''),
                    // M.E.R.E
                    'mere_magnetique' => trim($_POST['mere_magnetique'] ?? ''),
                    'mere_emotion' => trim($_POST['mere_emotion'] ?? ''),
                    'mere_raison' => trim($_POST['mere_raison'] ?? ''),
                    'mere_engagement' => trim($_POST['mere_engagement'] ?? ''),
                    'accroches' => jsonEncodeLines($_POST['accroches'] ?? ''),
                    'phrases_emotion' => jsonEncodeLines($_POST['phrases_emotion'] ?? ''),
                    'arguments_raison' => jsonEncodeLines($_POST['arguments_raison'] ?? ''),
                    'ctas' => jsonEncodeLines($_POST['ctas'] ?? ''),
                    // Templates
                    'template_facebook' => trim($_POST['template_facebook'] ?? ''),
                    'template_instagram' => trim($_POST['template_instagram'] ?? ''),
                    'template_gmb' => trim($_POST['template_gmb'] ?? ''),
                    'template_linkedin' => trim($_POST['template_linkedin'] ?? ''),
                    'template_email_subject' => trim($_POST['template_email_subject'] ?? ''),
                    'template_email_body' => trim($_POST['template_email_body'] ?? ''),
                    'template_sms' => trim($_POST['template_sms'] ?? ''),
                    // Lead Magnets (JSON brut depuis le formulaire)
                    'lead_magnets' => $_POST['lead_magnets_json'] ?? '[]',
                    // Fréquence com (JSON brut)
                    'frequence_com' => $_POST['frequence_com_json'] ?? '{}',
                ];
                
                if (empty($data['nom'])) {
                    echo json_encode(['success' => false, 'message' => 'Le nom est obligatoire.']);
                    exit;
                }
                
                // Auto-générer le code si vide
                if (empty($data['code'])) {
                    $data['code'] = slugify($data['nom']);
                }
                
                if ($id > 0) {
                    // UPDATE
                    $sets = [];
                    $vals = [];
                    foreach ($data as $k => $v) {
                        $sets[] = "`$k` = ?";
                        $vals[] = $v;
                    }
                    $vals[] = $id;
                    $stmt = $pdo->prepare("UPDATE neuropersona_types SET " . implode(', ', $sets) . " WHERE id = ?");
                    $stmt->execute($vals);
                    echo json_encode(['success' => true, 'message' => '✅ Persona mis à jour.', 'id' => $id]);
                } else {
                    // INSERT
                    $data['ordre'] = (int)$pdo->query("SELECT COALESCE(MAX(ordre),0)+1 FROM neuropersona_types")->fetchColumn();
                    $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
                    $phs = implode(', ', array_fill(0, count($data), '?'));
                    $stmt = $pdo->prepare("INSERT INTO neuropersona_types ($cols) VALUES ($phs)");
                    $stmt->execute(array_values($data));
                    echo json_encode(['success' => true, 'message' => '✅ Persona créé.', 'id' => $pdo->lastInsertId()]);
                }
                exit;
                
            case 'delete':
                $id = (int)($_POST['persona_id'] ?? 0);
                if ($id > 0) {
                    $pdo->prepare("DELETE FROM neuropersona_types WHERE id = ?")->execute([$id]);
                    echo json_encode(['success' => true, 'message' => '✅ Persona supprimé.']);
                }
                exit;
                
            case 'duplicate':
                $id = (int)($_POST['persona_id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("SELECT * FROM neuropersona_types WHERE id = ?");
                    $stmt->execute([$id]);
                    $p = $stmt->fetch();
                    if ($p) {
                        unset($p['id'], $p['created_at'], $p['updated_at']);
                        $p['nom'] .= ' (copie)';
                        $p['code'] .= '_copie_' . time();
                        $p['ordre'] = (int)$pdo->query("SELECT COALESCE(MAX(ordre),0)+1 FROM neuropersona_types")->fetchColumn();
                        
                        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($p)));
                        $phs = implode(', ', array_fill(0, count($p), '?'));
                        $stmt = $pdo->prepare("INSERT INTO neuropersona_types ($cols) VALUES ($phs)");
                        $stmt->execute(array_values($p));
                        echo json_encode(['success' => true, 'message' => '✅ Persona dupliqué.', 'id' => $pdo->lastInsertId()]);
                    }
                }
                exit;
                
            case 'toggle':
                $id = (int)($_POST['persona_id'] ?? 0);
                if ($id > 0) {
                    $pdo->prepare("UPDATE neuropersona_types SET actif = NOT actif WHERE id = ?")->execute([$id]);
                    echo json_encode(['success' => true, 'message' => '✅ Statut modifié.']);
                }
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '❌ ' . $e->getMessage()]);
        exit;
    }
}

// ============================================================
// CHARGEMENT DES DONNÉES
// ============================================================
$personas = $acheteurs = $vendeurs = [];
if ($tableExists) {
    try {
        $personas = $pdo->query("SELECT * FROM neuropersona_types ORDER BY categorie, ordre, id")->fetchAll();
        foreach ($personas as $p) {
            if ($p['categorie'] === 'acheteur') $acheteurs[] = $p;
            else $vendeurs[] = $p;
        }
    } catch (Exception $e) {
        $error = "Erreur chargement : " . $e->getMessage();
    }
}

// ============================================================
// FONCTIONS UTILITAIRES
// ============================================================
function jsonEncodeLines(string $text): string {
    $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
    return json_encode($lines, JSON_UNESCAPED_UNICODE) ?: '[]';
}

function jsonDecode($val): array {
    if (empty($val)) return [];
    if (is_array($val)) return $val;
    $arr = json_decode($val, true);
    return is_array($arr) ? $arr : [];
}

function jsonToLines($val): string {
    $arr = jsonDecode($val);
    return implode("\n", $arr);
}

function slugify(string $str): string {
    $str = strtolower(trim($str));
    $str = str_replace(['é','è','ê','ë','à','â','ä','ù','û','ü','ô','ö','î','ï','ç',' '],
                       ['e','e','e','e','a','a','a','u','u','u','o','o','i','i','c','_'], $str);
    return preg_replace('/[^a-z0-9_]/', '', $str);
}

function e($str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function urgenceLabel(int $score): string {
    if ($score >= 9) return 'urgent';
    if ($score >= 7) return 'eleve';
    if ($score >= 4) return 'moyen';
    return 'faible';
}

function urgenceText(int $score): string {
    if ($score >= 9) return 'Urgent';
    if ($score >= 7) return 'Élevé';
    if ($score >= 4) return 'Moyen';
    return 'Faible';
}

// Passer les personas en JSON pour JavaScript
$personasJSON = json_encode($personas, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<style>
/* ============================================================
   NEUROPERSONA v2.0 STYLES
   ============================================================ */
.np-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.np-title{display:flex;align-items:center;gap:.75rem}
.np-title h2{font-size:1.25rem;font-weight:700;color:#1e293b;margin:0}
.btn{padding:.625rem 1.25rem;border-radius:8px;font-weight:500;font-size:.875rem;cursor:pointer;transition:all .2s;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem}
.btn-primary{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(99,102,241,.4)}
.btn-secondary{background:#f1f5f9;color:#475569}
.btn-secondary:hover{background:#e2e8f0}
.btn-sm{padding:.4rem .75rem;font-size:.8rem}
.btn-action{background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:.75rem;padding:.5rem 1rem}
.btn-action:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(16,185,129,.3)}
.btn-danger{background:#fee2e2;color:#dc2626;font-size:.8rem;padding:.4rem .75rem}
.btn-danger:hover{background:#dc2626;color:#fff}
.btn-outline{background:#fff;border:1px solid #e2e8f0;color:#475569}
.btn-outline:hover{border-color:#6366f1;color:#6366f1}
.alert{padding:1rem;border-radius:8px;margin-bottom:1rem;font-size:.875rem}
.alert-success{background:#dcfce7;color:#166534}
.alert-error{background:#fee2e2;color:#991b1b}

/* Tabs */
.tabs{display:flex;gap:.5rem;margin-bottom:1.5rem;border-bottom:2px solid #e2e8f0;padding-bottom:.5rem}
.tab{padding:.75rem 1.5rem;border-radius:8px 8px 0 0;font-weight:600;cursor:pointer;transition:all .2s;color:#64748b;background:0 0;border:none;font-size:.9rem}
.tab:hover{color:#6366f1;background:rgba(99,102,241,.08)}
.tab.active{color:#fff;background:linear-gradient(135deg,#6366f1,#8b5cf6)}
.tab .count{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;border-radius:11px;font-size:.75rem;margin-left:.5rem;padding:0 6px}
.tab .count{background:rgba(255,255,255,.3)}
.tab:not(.active) .count{background:#e2e8f0;color:#64748b}

/* Section */
.section-title{font-size:1rem;font-weight:600;color:#374151;margin:1.5rem 0 1rem;display:flex;align-items:center;gap:.5rem}

/* Personas Grid */
.personas-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
.persona-card{background:#fff;border-radius:12px;border:2px solid #e2e8f0;padding:1.25rem;transition:all .3s;cursor:pointer;position:relative;overflow:hidden}
.persona-card:hover{transform:translateY(-4px);box-shadow:0 8px 25px rgba(0,0,0,.1)}
.persona-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--persona-color,#6366f1);opacity:0;transition:opacity .3s}
.persona-card:hover::before{opacity:1}
.persona-header{display:flex;align-items:center;gap:.75rem;margin-bottom:1rem}
.persona-icon{font-size:2rem;width:48px;height:48px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border-radius:10px}
.persona-info h4{font-size:1rem;font-weight:600;color:#1e293b;margin:0 0 .25rem}
.persona-info span{font-size:.75rem;color:#64748b}
.persona-tags{display:flex;flex-wrap:wrap;gap:.375rem;margin-bottom:.75rem}
.persona-tag{font-size:.7rem;padding:.25rem .5rem;border-radius:12px;background:#f1f5f9;color:#64748b}
.persona-stats{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;padding-top:.75rem;border-top:1px solid #f1f5f9}
.persona-stat{font-size:.75rem;color:#64748b}
.persona-stat strong{color:#374151;display:block}
.urgence-badge{position:absolute;top:1rem;right:1rem;font-size:.65rem;padding:.25rem .5rem;border-radius:10px;font-weight:600;text-transform:uppercase}
.urgence-urgent{background:#fee2e2;color:#dc2626}
.urgence-eleve{background:#ffedd5;color:#c2410c}
.urgence-moyen{background:#fef3c7;color:#b45309}
.urgence-faible{background:#dcfce7;color:#16a34a}
.persona-card-add{border:2px dashed #d1d5db;background:#f9fafb;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:180px;cursor:pointer;transition:all .2s}
.persona-card-add:hover{border-color:#6366f1;background:#eef2ff}
.persona-card-add .add-icon{font-size:2rem;color:#9ca3af;margin-bottom:.5rem}
.persona-card-add span{font-size:.85rem;color:#64748b;font-weight:500}

/* ============================================================
   MODAL STYLES
   ============================================================ */
.modal-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(4px)}
.modal-overlay.active{display:flex}
.modal-content{background:#fff;border-radius:16px;max-width:960px;width:100%;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,.25)}
.modal-header{padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8fafc,#f1f5f9);flex-shrink:0}
.modal-header h3{font-size:1.25rem;margin:0;display:flex;align-items:center;gap:.75rem}
.modal-header-actions{display:flex;gap:.5rem;align-items:center}
.modal-close{background:0 0;border:none;font-size:1.5rem;cursor:pointer;color:#64748b;padding:.5rem;border-radius:8px;transition:all .2s;line-height:1}
.modal-close:hover{background:#fee2e2;color:#dc2626}
.modal-tabs{display:flex;border-bottom:2px solid #e2e8f0;background:#f8fafc;overflow-x:auto;flex-shrink:0}
.modal-tab{flex:none;padding:.875rem 1.25rem;text-align:center;cursor:pointer;font-weight:500;color:#64748b;border:none;background:0 0;transition:all .2s;font-size:.8rem;white-space:nowrap}
.modal-tab:hover{color:#6366f1;background:rgba(99,102,241,.05)}
.modal-tab.active{color:#6366f1;border-bottom:2px solid #6366f1;margin-bottom:-2px;background:#fff}
.modal-body{overflow-y:auto;flex:1}
.modal-tab-content{display:none;padding:1.5rem}
.modal-tab-content.active{display:block;animation:npFade .3s ease}
@keyframes npFade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

/* Profil tab */
.detail-section{margin-bottom:1.5rem}
.detail-section h4{font-size:.9rem;font-weight:600;color:#6366f1;margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem}
.detail-list{list-style:none;padding:0;margin:0}
.detail-list li{padding:.5rem 0;border-bottom:1px solid #f1f5f9;font-size:.875rem;color:#475569;display:flex;align-items:flex-start;gap:.5rem}
.detail-list li::before{content:'•';color:#6366f1;font-weight:700;flex-shrink:0}
.detail-list li:last-child{border-bottom:none}
.channel-badges{display:flex;flex-wrap:wrap;gap:.5rem}
.channel-badge{padding:.375rem .75rem;border-radius:20px;font-size:.75rem;font-weight:500}
.channel-facebook{background:#dbeafe;color:#1d4ed8}
.channel-instagram{background:#fce7f3;color:#be185d}
.channel-linkedin{background:#dbeafe;color:#1e40af}
.channel-gmb{background:#dcfce7;color:#166534}
.channel-email{background:#f3e8ff;color:#7c3aed}
.channel-google-ads{background:#fef3c7;color:#b45309}

/* M.E.R.E tab */
.mere-guide{background:linear-gradient(135deg,#faf5ff,#f3e8ff);border-radius:12px;padding:1.25rem;margin-bottom:1.5rem}
.mere-guide h4{color:#7c3aed;margin:0 0 1rem;font-size:1rem}
.mere-steps{display:grid;gap:.75rem}
.mere-step{display:flex;gap:.75rem;align-items:flex-start;background:#fff;padding:.875rem;border-radius:8px;border-left:3px solid}
.mere-step.magnetique{border-color:#ef4444}.mere-step.emotion{border-color:#f59e0b}.mere-step.raison{border-color:#3b82f6}.mere-step.engagement{border-color:#10b981}
.mere-letter{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:.875rem;flex-shrink:0}
.mere-step.magnetique .mere-letter{background:#ef4444}.mere-step.emotion .mere-letter{background:#f59e0b}.mere-step.raison .mere-letter{background:#3b82f6}.mere-step.engagement .mere-letter{background:#10b981}
.mere-content h5{margin:0 0 .25rem;font-size:.875rem;color:#1e293b}
.mere-content p{margin:0;font-size:.8rem;color:#64748b}

/* Templates tab */
.template-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:1rem;overflow:hidden}
.template-header{display:flex;justify-content:space-between;align-items:center;padding:.875rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.template-header h5{margin:0;font-size:.9rem;color:#1e293b;display:flex;align-items:center;gap:.5rem}
.template-content{padding:1rem;font-size:.85rem;line-height:1.65;color:#475569;white-space:pre-wrap}
.copy-btn{background:#f1f5f9;border:none;padding:.4rem .75rem;border-radius:6px;font-size:.75rem;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:.375rem}
.copy-btn:hover{background:#e2e8f0}
.copy-btn.copied{background:#dcfce7;color:#166534}

/* Lead Magnets tab */
.lead-magnet-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;margin-bottom:1rem;display:flex;gap:1rem;align-items:flex-start}
.lead-magnet-icon{width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.lead-magnet-icon.guide{background:#dbeafe}.lead-magnet-icon.checklist{background:#dcfce7}.lead-magnet-icon.calculateur,.lead-magnet-icon.simulateur{background:#fef3c7}.lead-magnet-icon.video,.lead-magnet-icon.webinar{background:#fce7f3}.lead-magnet-icon.outil{background:#f3e8ff}.lead-magnet-icon.etude{background:#e0e7ff}.lead-magnet-icon.carte,.lead-magnet-icon.annuaire,.lead-magnet-icon.comparatif{background:#f1f5f9}
.lead-magnet-content{flex:1}
.lead-magnet-content h5{margin:0 0 .25rem;font-size:.9rem;color:#1e293b}
.lead-magnet-content p{margin:0 0 .5rem;font-size:.8rem;color:#64748b}
.lead-magnet-format{font-size:.7rem;background:#f1f5f9;padding:.25rem .5rem;border-radius:4px;color:#475569;display:inline-block}

/* Plan Com tab */
.plan-com-grid{display:grid;gap:.75rem}
.plan-com-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;display:flex;align-items:center;gap:1rem}
.plan-com-canal{display:flex;align-items:center;gap:.75rem;min-width:180px}
.plan-com-canal-icon{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.25rem}
.plan-com-canal-name{font-weight:600;font-size:.9rem;color:#1e293b}
.plan-com-freq{flex:1;text-align:center}
.plan-com-freq strong{display:block;font-size:1rem;color:#6366f1}
.plan-com-freq span{font-size:.75rem;color:#64748b}
.calendar-preview{background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-radius:12px;padding:1.25rem;margin-top:1.5rem}
.calendar-preview h5{margin:0 0 1rem;font-size:.9rem;color:#374151}
.calendar-week{display:grid;grid-template-columns:repeat(7,1fr);gap:.5rem;text-align:center}
.calendar-day{padding:.5rem;border-radius:8px;background:#fff;border:1px solid #e2e8f0}
.calendar-day-name{font-size:.7rem;color:#64748b;font-weight:600;margin-bottom:.25rem}
.calendar-day-content{min-height:40px;display:flex;flex-direction:column;gap:.25rem}
.calendar-post{font-size:.6rem;padding:.2rem .4rem;border-radius:4px;white-space:nowrap;overflow:hidden}
.calendar-post.fb{background:#dbeafe;color:#1d4ed8}
.calendar-post.ig{background:#fce7f3;color:#be185d}
.calendar-post.gmb{background:#dcfce7;color:#166534}
.calendar-post.email{background:#f3e8ff;color:#7c3aed}
.calendar-post.li{background:#dbeafe;color:#1e40af}
.calendar-post.ads{background:#fef3c7;color:#b45309}
.actions-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:1.5rem}
.action-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;text-align:center;transition:all .2s}
.action-card:hover{border-color:#6366f1;transform:translateY(-2px)}
.action-card .action-icon{font-size:1.5rem;margin-bottom:.5rem}
.action-card p{font-size:.8rem;color:#64748b;margin:0 0 .75rem}

/* ============================================================
   EDIT MODAL FORM STYLES
   ============================================================ */
.edit-form-group{margin-bottom:1rem}
.edit-form-group label{display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.375rem}
.edit-form-group input,.edit-form-group textarea,.edit-form-group select{width:100%;padding:.625rem .875rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;color:#1e293b;transition:border-color .2s;font-family:inherit;box-sizing:border-box}
.edit-form-group input:focus,.edit-form-group textarea:focus,.edit-form-group select:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.edit-form-group textarea{resize:vertical;min-height:80px}
.edit-form-group .hint{font-size:.7rem;color:#9ca3af;margin-top:.25rem}
.edit-form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.edit-form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem}
.edit-form-footer{display:flex;justify-content:space-between;align-items:center;padding-top:1.25rem;border-top:1px solid #e2e8f0;margin-top:1.5rem}

/* Lead Magnet Editor */
.lm-editor{border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:.75rem;background:#f9fafb}
.lm-editor .lm-row{display:grid;grid-template-columns:100px 1fr;gap:.5rem;margin-bottom:.5rem;align-items:center}
.lm-editor .lm-row label{font-size:.75rem;color:#64748b}
.lm-editor .lm-row input,.lm-editor .lm-row select{padding:.375rem .5rem;border:1px solid #e2e8f0;border-radius:6px;font-size:.8rem}
.lm-remove{color:#dc2626;cursor:pointer;font-size:.8rem;text-align:right}

/* Freq Editor */
.freq-row{display:grid;grid-template-columns:150px 1fr;gap:.75rem;margin-bottom:.5rem;align-items:center}
.freq-row label{font-size:.8rem;color:#374151;display:flex;align-items:center;gap:.375rem}
.freq-row input{padding:.375rem .5rem;border:1px solid #e2e8f0;border-radius:6px;font-size:.8rem}

/* Generate section */
.generate-section{background:#f8fafc;border-radius:12px;padding:1.25rem;margin-top:1rem}
.generate-section h5{margin:0 0 1rem;font-size:.9rem;color:#374151}
.generate-form{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.generate-form .form-group{margin:0}
.generate-form .form-group.full{grid-column:span 2}
.generate-form label{display:block;font-size:.8rem;font-weight:500;color:#64748b;margin-bottom:.375rem}
.generate-form select,.generate-form input{width:100%;padding:.5rem .75rem;border:1px solid #e2e8f0;border-radius:6px;font-size:.85rem;box-sizing:border-box}

/* Toast */
.np-toast{position:fixed;bottom:24px;right:24px;padding:14px 24px;background:#1e293b;color:#fff;border-radius:10px;font-size:.875rem;font-weight:500;z-index:99999;opacity:0;transform:translateY(20px);transition:all .3s;display:flex;align-items:center;gap:8px;pointer-events:none}
.np-toast.show{opacity:1;transform:translateY(0)}
.np-toast.success{background:#10b981}
.np-toast.error{background:#ef4444}

/* Responsive */
@media(max-width:768px){
    .personas-grid{grid-template-columns:1fr}
    .tabs{flex-wrap:wrap}
    .modal-content{max-height:95vh}
    .edit-form-row,.edit-form-row-3,.generate-form{grid-template-columns:1fr}
    .generate-form .form-group.full{grid-column:span 1}
    .lead-magnet-card{flex-direction:column}
    .plan-com-card{flex-direction:column;text-align:center}
    .calendar-week{grid-template-columns:repeat(4,1fr)}
    .actions-cards{grid-template-columns:1fr}
    .modal-header-actions{flex-wrap:wrap}
}
</style>
<?php if (!$tableExists): ?>
<div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;padding:2.5rem;color:#fff;text-align:center">
    <div style="font-size:3rem;margin-bottom:1rem">🧠</div>
    <h3 style="font-size:1.5rem;margin-bottom:.75rem">Module NeuroPersona v2.0</h3>
    <p style="opacity:.9;margin-bottom:1.5rem">5 outils : Profil | M.E.R.E | Templates | Lead Magnets | Plan Com<br>CRUD complet + Liens contextuels vers vos modules.</p>
    <p style="opacity:.8;font-size:.9rem">Exécutez <strong>migration_neuropersona_v2.sql</strong> dans phpMyAdmin pour installer.</p>
</div>
<?php else: ?>

<!-- ============================================================
     PAGE HEADER
     ============================================================ -->
<div class="np-header">
    <div class="np-title">
        <span style="font-size:1.5rem">🧠</span>
        <h2>NuroPersona - Centre Marketing Complet</h2>
    </div>
    <div style="display:flex;gap:.5rem">
        <button class="btn btn-primary" onclick="openEditModal(0,'acheteur')">➕ Nouveau persona</button>
    </div>
</div>

<?php if($message):?><div class="alert alert-success"><?=$message?></div><?php endif;?>
<?php if($error):?><div class="alert alert-error"><?=$error?></div><?php endif;?>

<!-- ============================================================
     TABS ACHETEURS / VENDEURS
     ============================================================ -->
<div class="tabs">
    <button class="tab active" onclick="showTypeTab('acheteurs',this)">🛒 Acheteurs <span class="count"><?=count($acheteurs)?></span></button>
    <button class="tab" onclick="showTypeTab('vendeurs',this)">🏷️ Vendeurs <span class="count"><?=count($vendeurs)?></span></button>
</div>

<!-- ACHETEURS -->
<div id="tab-acheteurs" class="tab-content">
    <div class="section-title">👥 Personas Acheteurs</div>
    <div class="personas-grid">
        <?php foreach ($acheteurs as $p): $urg = is_numeric($p['niveau_urgence']) ? (int)$p['niveau_urgence'] : 5; ?>
        <div class="persona-card" style="--persona-color:<?=e($p['couleur'])?>" onclick="openViewModal(<?=$p['id']?>)">
            <span class="urgence-badge urgence-<?=urgenceLabel($urg)?>"><?=urgenceText($urg)?></span>
            <div class="persona-header">
                <div class="persona-icon"><?=$p['icone']?></div>
                <div class="persona-info">
                    <h4><?=e($p['nom'])?></h4>
                    <span><?=e($p['age_moyen'])?></span>
                </div>
            </div>
            <div class="persona-tags">
                <span class="persona-tag"><?=e($p['situation_familiale'])?></span>
                <span class="persona-tag"><?=e($p['budget_moyen'])?></span>
            </div>
            <div class="persona-stats">
                <div class="persona-stat"><strong><?=e($p['cycle_decision'])?></strong>Cycle</div>
                <div class="persona-stat"><strong><?=urgenceText($urg)?></strong>Urgence</div>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="persona-card persona-card-add" onclick="openEditModal(0,'acheteur')">
            <div class="add-icon">➕</div>
            <span>Ajouter un persona acheteur</span>
        </div>
    </div>
</div>

<!-- VENDEURS -->
<div id="tab-vendeurs" class="tab-content" style="display:none">
    <div class="section-title">🏷️ Personas Vendeurs</div>
    <div class="personas-grid">
        <?php foreach ($vendeurs as $p): $urg = is_numeric($p['niveau_urgence']) ? (int)$p['niveau_urgence'] : 5; ?>
        <div class="persona-card" style="--persona-color:<?=e($p['couleur'])?>" onclick="openViewModal(<?=$p['id']?>)">
            <span class="urgence-badge urgence-<?=urgenceLabel($urg)?>"><?=urgenceText($urg)?></span>
            <div class="persona-header">
                <div class="persona-icon"><?=$p['icone']?></div>
                <div class="persona-info">
                    <h4><?=e($p['nom'])?></h4>
                    <span><?=e($p['age_moyen'])?></span>
                </div>
            </div>
            <div class="persona-tags">
                <span class="persona-tag"><?=e($p['situation_familiale'])?></span>
            </div>
            <div class="persona-stats">
                <div class="persona-stat"><strong><?=e($p['cycle_decision'])?></strong>Cycle</div>
                <div class="persona-stat"><strong><?=urgenceText($urg)?></strong>Urgence</div>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="persona-card persona-card-add" onclick="openEditModal(0,'vendeur')">
            <div class="add-icon">➕</div>
            <span>Ajouter un persona vendeur</span>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL CONSULTATION (5 onglets)
     ============================================================ -->
<div class="modal-overlay" id="viewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span id="vIcon">🧠</span> <span id="vName">Persona</span></h3>
            <div class="modal-header-actions">
                <button class="btn btn-sm btn-outline" onclick="editFromView()" title="Modifier">✏️ Modifier</button>
                <button class="btn btn-sm btn-outline" onclick="duplicatePersona()" title="Dupliquer">📋 Dupliquer</button>
                <button class="btn btn-sm btn-danger" onclick="deletePersona()" title="Supprimer">🗑️</button>
                <button class="modal-close" onclick="closeAllModals()">&times;</button>
            </div>
        </div>
        <div class="modal-tabs" id="viewTabs">
            <button class="modal-tab active" data-tab="profil" onclick="switchViewTab('profil',this)">👤 Profil</button>
            <button class="modal-tab" data-tab="mere" onclick="switchViewTab('mere',this)">✍️ M.E.R.E</button>
            <button class="modal-tab" data-tab="templates" onclick="switchViewTab('templates',this)">📝 Templates</button>
            <button class="modal-tab" data-tab="leadmagnets" onclick="switchViewTab('leadmagnets',this)">🧲 Lead Magnets</button>
            <button class="modal-tab" data-tab="plancom" onclick="switchViewTab('plancom',this)">📅 Plan Com</button>
        </div>
        <div class="modal-body">
            <!-- PROFIL -->
            <div class="modal-tab-content active" id="vTab-profil">
                <div class="detail-section"><h4>🎯 Motivations</h4><ul class="detail-list" id="vMotivations"></ul></div>
                <div class="detail-section"><h4>😰 Problèmes</h4><ul class="detail-list" id="vProblemes"></ul></div>
                <div class="detail-section"><h4>💡 Solutions</h4><ul class="detail-list" id="vSolutions"></ul></div>
                <div class="detail-section"><h4>🛡️ Objections</h4><ul class="detail-list" id="vObjections"></ul></div>
                <div class="detail-section"><h4>💬 Messages clés</h4><ul class="detail-list" id="vMessages"></ul></div>
                <div class="detail-section"><h4>📱 Canaux prioritaires</h4><div class="channel-badges" id="vCanaux"></div></div>
            </div>
            
            <!-- M.E.R.E -->
            <div class="modal-tab-content" id="vTab-mere">
                <div class="mere-guide">
                    <h4>📚 Structure M.E.R.E</h4>
                    <div class="mere-steps" id="vMERESteps"></div>
                </div>
                <div class="detail-section"><h4>🧲 Accroches Magnétiques</h4><ul class="detail-list" id="vAccroches"></ul></div>
                <div class="detail-section"><h4>💝 Phrases d'Émotion</h4><ul class="detail-list" id="vEmotions"></ul></div>
                <div class="detail-section"><h4>📊 Arguments Rationnels</h4><ul class="detail-list" id="vRaisons"></ul></div>
                <div class="detail-section"><h4>🚀 CTA Efficaces</h4><ul class="detail-list" id="vCTAs"></ul></div>
            </div>
            
            <!-- TEMPLATES -->
            <div class="modal-tab-content" id="vTab-templates">
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem">Templates M.E.R.E prêts à copier. Cliquez sur <strong>"Copier"</strong>.</p>
                <div id="vTemplates"></div>
                <div class="generate-section">
                    <h5>🤖 Générer un contenu personnalisé</h5>
                    <div class="generate-form">
                        <div class="form-group"><label>Canal</label><select id="genCanal"><option value="facebook">Facebook</option><option value="instagram">Instagram</option><option value="gmb">Google My Business</option><option value="linkedin">LinkedIn</option><option value="email">Email</option></select></div>
                        <div class="form-group"><label>Type</label><select id="genType"><option value="post">Publication</option><option value="story">Story</option><option value="article">Article</option><option value="ad">Publicité</option></select></div>
                        <div class="form-group full"><label>Contexte (optionnel)</label><input type="text" id="genContexte" placeholder="Ex: quartier Chartrons, programme neuf..."></div>
                        <div class="form-group full"><button type="button" class="btn btn-primary" onclick="generateContent()">✨ Générer</button></div>
                    </div>
                    <div id="generatedContent" style="margin-top:1rem;display:none"></div>
                </div>
            </div>
            
            <!-- LEAD MAGNETS -->
            <div class="modal-tab-content" id="vTab-leadmagnets">
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem">Lead Magnets recommandés pour ce persona. <strong>Créez-les dans le module Pages de Capture.</strong></p>
                <div id="vLeadMagnets"></div>
                <div style="margin-top:1.5rem;padding:1.25rem;background:linear-gradient(135deg,#dbeafe,#ede9fe);border-radius:12px;text-align:center">
                    <p style="margin:0 0 1rem;font-size:.9rem;color:#4f46e5">🎯 Prêt à créer vos Lead Magnets ?</p>
                    <a id="lmCreateLink" href="?page=pages-capture&action=create" class="btn btn-action">➕ Créer une Page de Capture</a>
                    <a href="?page=pages-capture" class="btn btn-secondary btn-sm" style="margin-left:.5rem">📋 Voir mes pages</a>
                </div>
            </div>
            
            <!-- PLAN COM -->
            <div class="modal-tab-content" id="vTab-plancom">
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem">Fréquence de publication recommandée pour ce persona.</p>
                <div id="vPlanCom"></div>
                <div class="calendar-preview">
                    <h5>📆 Aperçu Semaine Type</h5>
                    <div class="calendar-week" id="vCalendar"></div>
                </div>
                <div class="actions-cards" id="vActionCards">
                    <div class="action-card">
                        <div class="action-icon">📝</div>
                        <p>Rédiger des articles</p>
                        <a id="linkArticle" href="?page=articles&action=create" class="btn btn-action btn-sm">Créer un article</a>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">✉️</div>
                        <p>Envoyer des emails</p>
                        <a id="linkEmail" href="?page=emails" class="btn btn-action btn-sm">Créer un email</a>
                    </div>
                    <div class="action-card">
                        <div class="action-icon">📱</div>
                        <p>Posts réseaux sociaux</p>
                        <a id="linkSocial" href="?page=reseaux-sociaux" class="btn btn-action btn-sm">Planifier</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL ÉDITION (CRUD)
     ============================================================ -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="editTitle">➕ Créer un persona</h3>
            <button class="modal-close" onclick="closeAllModals()">&times;</button>
        </div>
        <div class="modal-tabs" id="editTabs">
            <button class="modal-tab active" onclick="switchEditTab('e-profil',this)">👤 Profil</button>
            <button class="modal-tab" onclick="switchEditTab('e-mere',this)">✍️ M.E.R.E</button>
            <button class="modal-tab" onclick="switchEditTab('e-templates',this)">📝 Templates</button>
            <button class="modal-tab" onclick="switchEditTab('e-leadmagnets',this)">🧲 Lead Magnets</button>
            <button class="modal-tab" onclick="switchEditTab('e-plancom',this)">📅 Plan Com</button>
        </div>
        <div class="modal-body">
            <form id="personaForm" onsubmit="savePersona(event)">
                <input type="hidden" name="crud_action" value="save">
                <input type="hidden" name="persona_id" id="eId" value="0">
                
                <!-- EDIT PROFIL -->
                <div class="modal-tab-content active" id="eTab-e-profil">
                    <div class="edit-form-row-3">
                        <div class="edit-form-group"><label>Catégorie</label><select name="categorie" id="eCategorie"><option value="acheteur">🛒 Acheteur</option><option value="vendeur">🏷️ Vendeur</option></select></div>
                        <div class="edit-form-group"><label>Icône</label><input type="text" name="icone" id="eIcone" maxlength="4" placeholder="🏠"></div>
                        <div class="edit-form-group"><label>Couleur</label><input type="color" name="couleur" id="eCouleur" value="#6366f1"></div>
                    </div>
                    <div class="edit-form-row">
                        <div class="edit-form-group"><label>Nom du persona *</label><input type="text" name="nom" id="eNom" required placeholder="Ex: Primo-Accédant"></div>
                        <div class="edit-form-group"><label>Code (auto)</label><input type="text" name="code" id="eCode" placeholder="Auto-généré"></div>
                    </div>
                    <div class="edit-form-row-3">
                        <div class="edit-form-group"><label>Tranche d'âge</label><input type="text" name="age_moyen" id="eAge" placeholder="25-35 ans"></div>
                        <div class="edit-form-group"><label>Situation familiale</label><input type="text" name="situation_familiale" id="eSituation" placeholder="Jeune couple"></div>
                        <div class="edit-form-group"><label>Urgence (1-10)</label><input type="number" name="niveau_urgence" id="eUrgence" min="1" max="10" value="5"></div>
                    </div>
                    <div class="edit-form-row">
                        <div class="edit-form-group"><label>Budget moyen</label><input type="text" name="budget_moyen" id="eBudget" placeholder="150 000€ - 280 000€"></div>
                        <div class="edit-form-group"><label>Cycle de décision</label><input type="text" name="cycle_decision" id="eCycle" placeholder="3 à 6 mois"></div>
                    </div>
                    <div class="edit-form-group"><label>Description</label><textarea name="description" id="eDescription" rows="2" placeholder="Description courte du persona..."></textarea></div>
                    <div class="edit-form-group"><label>🎯 Motivations (une par ligne)</label><textarea name="motivations" id="eMotivations" rows="5" placeholder="Devenir propriétaire&#10;Arrêter le loyer"></textarea></div>
                    <div class="edit-form-group"><label>😰 Problèmes (un par ligne)</label><textarea name="problemes" id="eProblemes" rows="5" placeholder="Méconnaissance du marché&#10;Peur de se tromper"></textarea></div>
                    <div class="edit-form-group"><label>💡 Solutions (une par ligne)</label><textarea name="solutions" id="eSolutions" rows="5" placeholder="Accompagnement étape par étape"></textarea></div>
                    <div class="edit-form-group"><label>🛡️ Objections (une par ligne)</label><textarea name="objections" id="eObjections" rows="5" placeholder="C'est trop cher&#10;Je ne suis pas prêt"></textarea></div>
                    <div class="edit-form-group"><label>💬 Messages clés (un par ligne)</label><textarea name="messages_cles" id="eMessages" rows="4" placeholder="Votre premier achat mérite un expert"></textarea></div>
                    <div class="edit-form-group"><label>📖 Contenus recommandés (un par ligne)</label><textarea name="contenus_recommandes" id="eContenus" rows="3" placeholder="Guide primo-accédant&#10;Simulateur PTZ"></textarea></div>
                    <div class="edit-form-group">
                        <label>📱 Canaux prioritaires (un par ligne)</label>
                        <textarea name="canaux_prioritaires" id="eCanaux" rows="3" placeholder="facebook&#10;instagram&#10;gmb"></textarea>
                        <div class="hint">Valeurs : facebook, instagram, linkedin, gmb, email, google-ads</div>
                    </div>
                </div>
                
                <!-- EDIT M.E.R.E -->
                <div class="modal-tab-content" id="eTab-e-mere">
                    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem">Structure M.E.R.E : <strong>M</strong>agnétique → <strong>É</strong>motion → <strong>R</strong>aison → <strong>E</strong>ngagement</p>
                    <div class="edit-form-group"><label>🔴 M - Magnétique</label><input type="text" name="mere_magnetique" id="eMereM" placeholder="Accroche percutante, question choc"></div>
                    <div class="edit-form-group"><label>🟡 É - Émotion</label><input type="text" name="mere_emotion" id="eMereE" placeholder="Empathie, connexion émotionnelle"></div>
                    <div class="edit-form-group"><label>🟢 R - Raison</label><input type="text" name="mere_raison" id="eMereR" placeholder="Preuves, chiffres, témoignages"></div>
                    <div class="edit-form-group"><label>🔵 E - Engagement</label><input type="text" name="mere_engagement" id="eMereEng" placeholder="CTA clair, action simple"></div>
                    <div class="edit-form-group"><label>🧲 Accroches magnétiques (une par ligne)</label><textarea name="accroches" id="eAccroches" rows="5" placeholder="Question choc 1&#10;Question choc 2"></textarea></div>
                    <div class="edit-form-group"><label>💝 Phrases d'émotion (une par ligne)</label><textarea name="phrases_emotion" id="ePhrasesEmotion" rows="4" placeholder="Je sais que..."></textarea></div>
                    <div class="edit-form-group"><label>📊 Arguments rationnels (un par ligne)</label><textarea name="arguments_raison" id="eArgsRaison" rows="4" placeholder="Mes clients trouvent en X mois."></textarea></div>
                    <div class="edit-form-group"><label>🚀 CTAs (un par ligne)</label><textarea name="ctas" id="eCTAs" rows="4" placeholder="📞 Appelez au [TEL]&#10;👉 Lien en bio"></textarea></div>
                </div>
                
                <!-- EDIT TEMPLATES -->
                <div class="modal-tab-content" id="eTab-e-templates">
                    <div class="edit-form-group"><label>📘 Post Facebook</label><textarea name="template_facebook" id="eTplFB" rows="8" placeholder="Template Facebook M.E.R.E..."></textarea></div>
                    <div class="edit-form-group"><label>📸 Post Instagram</label><textarea name="template_instagram" id="eTplIG" rows="8" placeholder="Template Instagram M.E.R.E..."></textarea></div>
                    <div class="edit-form-group"><label>📍 Post Google My Business</label><textarea name="template_gmb" id="eTplGMB" rows="6" placeholder="Template GMB..."></textarea></div>
                    <div class="edit-form-group"><label>💼 Post LinkedIn</label><textarea name="template_linkedin" id="eTplLI" rows="6" placeholder="Template LinkedIn..."></textarea></div>
                    <div class="edit-form-row">
                        <div class="edit-form-group"><label>✉️ Objet email</label><input type="text" name="template_email_subject" id="eTplEmailSubj" placeholder="Objet de l'email"></div>
                        <div class="edit-form-group"><label>📱 Template SMS</label><textarea name="template_sms" id="eTplSMS" rows="2" maxlength="320" placeholder="SMS max 160 car..."></textarea></div>
                    </div>
                    <div class="edit-form-group"><label>✉️ Corps email</label><textarea name="template_email_body" id="eTplEmailBody" rows="10" placeholder="Bonjour [PRÉNOM],..."></textarea></div>
                </div>
                
                <!-- EDIT LEAD MAGNETS -->
                <div class="modal-tab-content" id="eTab-e-leadmagnets">
                    <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem">Définissez les lead magnets recommandés pour ce persona.</p>
                    <div id="eLMContainer"></div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addLeadMagnet()" style="margin-top:.5rem">➕ Ajouter un lead magnet</button>
                    <input type="hidden" name="lead_magnets_json" id="eLMJson" value="[]">
                </div>
                
                <!-- EDIT PLAN COM -->
                <div class="modal-tab-content" id="eTab-e-plancom">
                    <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem">Fréquence de publication par canal :</p>
                    <div id="eFreqContainer">
                        <div class="freq-row"><label>📘 Facebook</label><input type="text" id="eFreqFB" placeholder="3x/semaine"></div>
                        <div class="freq-row"><label>📸 Instagram</label><input type="text" id="eFreqIG" placeholder="5x/semaine"></div>
                        <div class="freq-row"><label>💼 LinkedIn</label><input type="text" id="eFreqLI" placeholder="2x/semaine"></div>
                        <div class="freq-row"><label>📍 GMB</label><input type="text" id="eFreqGMB" placeholder="2x/semaine"></div>
                        <div class="freq-row"><label>✉️ Email</label><input type="text" id="eFreqEmail" placeholder="1x/semaine"></div>
                        <div class="freq-row"><label>📢 Google Ads</label><input type="text" id="eFreqAds" placeholder="continu"></div>
                    </div>
                    <input type="hidden" name="frequence_com_json" id="eFreqJson" value="{}">
                </div>
                
                <!-- FOOTER -->
                <div class="edit-form-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAllModals()">Annuler</button>
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="npToast" class="np-toast"></div>

<?php endif; ?>
<script>
// ============================================================
// DATA STORE
// ============================================================
const NP = {
    personas: <?=$personasJSON ?? '[]'?>,
    currentId: null,
    find(id) { return this.personas.find(p => p.id == id) || null; },
    jp(val) { if (!val) return []; if (Array.isArray(val)) return val; try { return JSON.parse(val) || []; } catch(e) { return []; } },
    jo(val) { if (!val) return {}; if (typeof val === 'object' && !Array.isArray(val)) return val; try { return JSON.parse(val) || {}; } catch(e) { return {}; } },
    esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
};

// ============================================================
// TABS
// ============================================================
function showTypeTab(tab, btn) {
    document.querySelectorAll('.tabs .tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    btn.classList.add('active');
    document.getElementById('tab-' + tab).style.display = 'block';
}

// ============================================================
// MODALS
// ============================================================
function closeAllModals() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
}
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) closeAllModals(); });
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAllModals(); });

function switchViewTab(tab, btn) {
    document.querySelectorAll('#viewModal .modal-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('#viewModal .modal-tab-content').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');
    else document.querySelector(`#viewTabs [data-tab="${tab}"]`)?.classList.add('active');
    const el = document.getElementById('vTab-' + tab);
    if (el) el.classList.add('active');
}

function switchEditTab(tab, btn) {
    document.querySelectorAll('#editModal .modal-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('#editModal .modal-tab-content').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');
    const el = document.getElementById('eTab-' + tab);
    if (el) el.classList.add('active');
}

// ============================================================
// RENDERING HELPERS
// ============================================================
function renderList(elId, items) {
    const el = document.getElementById(elId);
    if (!el) return;
    if (!items || !items.length) { el.innerHTML = '<li style="color:#9ca3af">Aucun élément défini.</li>'; return; }
    el.innerHTML = items.map(x => `<li>${NP.esc(x)}</li>`).join('');
}

// Fallback generators for MERE content
function genAccroches(p) {
    const pb = NP.jp(p.problemes), m = NP.jp(p.motivations);
    return [
        `${pb[0] || 'Un défi'} ? Vous n'êtes pas seul(e).`,
        `${m[0] || 'Votre projet'} : et si c'était maintenant ?`,
        `[${p.situation_familiale || 'Vous'}] Ceci est pour vous.`
    ];
}
function genEmotions(p) {
    const pb = NP.jp(p.problemes);
    return [
        `Je sais que ${(pb[0] || 'cette situation').toLowerCase()} peut sembler intimidant...`,
        `${p.situation_familiale || 'Vous'} ? Je comprends bien.`
    ];
}
function genRaisons(p) {
    const s = NP.jp(p.solutions);
    return [
        `Mes clients ${p.categorie==='acheteur'?'trouvent':'vendent'} en ${p.cycle_decision || '3 mois'}.`,
        `${s[0] || 'Accompagnement'} : la différence.`
    ];
}
function genCTAs(p) {
    return [`📞 Appelez au [TEL] - gratuit`, `👉 Lien en bio`, `💬 Envoyez "INFO" en MP`];
}

// ============================================================
// VIEW MODAL - OPEN
// ============================================================
function openViewModal(id) {
    const p = NP.find(id);
    if (!p) return;
    NP.currentId = id;

    document.getElementById('vIcon').textContent = p.icone;
    document.getElementById('vName').textContent = p.nom;

    const m = NP.jp(p.motivations), pb = NP.jp(p.problemes), s = NP.jp(p.solutions);
    const obj = NP.jp(p.objections), msg = NP.jp(p.messages_cles), c = NP.jp(p.canaux_prioritaires);
    const lm = NP.jp(p.lead_magnets), freq = NP.jo(p.frequence_com);

    // === PROFIL ===
    renderList('vMotivations', m);
    renderList('vProblemes', pb);
    renderList('vSolutions', s);
    renderList('vObjections', obj);
    renderList('vMessages', msg);

    const cLabels = {facebook:'Facebook',instagram:'Instagram',linkedin:'LinkedIn',gmb:'Google My Business',email:'Email','google-ads':'Google Ads'};
    document.getElementById('vCanaux').innerHTML = c.map(x => `<span class="channel-badge channel-${x}">${cLabels[x]||x}</span>`).join('');

    // === M.E.R.E ===
    const mM = p.mere_magnetique || 'Accroche percutante, question choc';
    const mE = p.mere_emotion || 'Empathie, connexion émotionnelle';
    const mR = p.mere_raison || 'Preuves, chiffres, témoignages';
    const mEng = p.mere_engagement || 'CTA clair, action simple';

    document.getElementById('vMERESteps').innerHTML = `
        <div class="mere-step magnetique"><div class="mere-letter">M</div><div class="mere-content"><h5>Magnétique</h5><p>${NP.esc(mM)}</p></div></div>
        <div class="mere-step emotion"><div class="mere-letter">É</div><div class="mere-content"><h5>Émotion</h5><p>${NP.esc(mE)}</p></div></div>
        <div class="mere-step raison"><div class="mere-letter">R</div><div class="mere-content"><h5>Raison</h5><p>${NP.esc(mR)}</p></div></div>
        <div class="mere-step engagement"><div class="mere-letter">E</div><div class="mere-content"><h5>Engagement</h5><p>${NP.esc(mEng)}</p></div></div>`;

    const accroches = NP.jp(p.accroches);
    const emotions = NP.jp(p.phrases_emotion);
    const raisons = NP.jp(p.arguments_raison);
    const ctas = NP.jp(p.ctas);

    renderList('vAccroches', accroches.length ? accroches : genAccroches(p));
    renderList('vEmotions', emotions.length ? emotions : genEmotions(p));
    renderList('vRaisons', raisons.length ? raisons : genRaisons(p));
    renderList('vCTAs', ctas.length ? ctas : genCTAs(p));

    // === TEMPLATES ===
    renderViewTemplates(p, c);

    // === LEAD MAGNETS ===
    renderViewLeadMagnets(lm, p);

    // === PLAN COM ===
    renderViewPlanCom(freq, c, p);

    // === Action Links ===
    updateActionLinks(p);

    // Reset first tab
    switchViewTab('profil', document.querySelector('#viewTabs .modal-tab'));
    document.getElementById('viewModal').classList.add('active');
}

// ============================================================
// VIEW: TEMPLATES
// ============================================================
function renderViewTemplates(p, canaux) {
    const tpls = [];
    if (p.template_facebook) tpls.push({icon:'📘',title:'Post Facebook',content:p.template_facebook});
    if (p.template_instagram) tpls.push({icon:'📸',title:'Post Instagram',content:p.template_instagram});
    if (p.template_gmb) tpls.push({icon:'📍',title:'Google My Business',content:p.template_gmb});
    if (p.template_linkedin) tpls.push({icon:'💼',title:'LinkedIn',content:p.template_linkedin});
    if (p.template_email_body) {
        const subj = p.template_email_subject ? ' — ' + NP.esc(p.template_email_subject) : '';
        tpls.push({icon:'✉️',title:'Email' + subj,content:p.template_email_body});
    }
    if (p.template_sms) tpls.push({icon:'📱',title:'SMS',content:p.template_sms});

    // Fallback si aucun template en base
    if (tpls.length === 0) {
        const m = NP.jp(p.motivations), pb = NP.jp(p.problemes), s = NP.jp(p.solutions), msg = NP.jp(p.messages_cles);
        canaux.forEach(canal => {
            const tpl = buildFallbackTemplate(canal, p, pb, s, msg, m);
            if (tpl) tpls.push(tpl);
        });
    }

    let html = '';
    tpls.forEach((t, i) => {
        html += `<div class="template-card">
            <div class="template-header"><h5>${t.icon} ${t.title}</h5><button class="copy-btn" onclick="copyTpl(${i})">📋 Copier</button></div>
            <div class="template-content" id="tpl-${i}">${NP.esc(t.content)}</div>
        </div>`;
    });

    document.getElementById('vTemplates').innerHTML = html || '<p style="color:#9ca3af">Aucun template. Modifiez le persona pour en ajouter.</p>';
}

function buildFallbackTemplate(canal, p, pb, s, msg, m) {
    const icons = {facebook:'📘',instagram:'📸',gmb:'📍',linkedin:'💼',email:'✉️'};
    const titles = {facebook:'Post Facebook',instagram:'Post Instagram',gmb:'Google My Business',linkedin:'LinkedIn',email:'Email'};
    if (!icons[canal]) return null;

    let content = '';
    if (canal === 'facebook') content = `${p.icone} ${pb[0]||''} ?\n\n${p.situation_familiale}, je comprends.\n\n✅ ${s[0]||'Accompagnement'}\n✅ ${s[1]||s[0]||''}\n\nRésultat en ${p.cycle_decision}.\n\n👉 ${msg[0]||''}\n📞 [NUMÉRO]`;
    else if (canal === 'instagram') content = `${m[0]||''} ? ${p.icone}\n\n${p.situation_familiale} - je comprends.\n\n1️⃣ ${s[0]||''}\n2️⃣ ${s[1]||''}\n\n💬 "INFO" en DM`;
    else if (canal === 'gmb') content = `🔑 ${msg[0]||''}\n\n${(p.situation_familiale||'').toLowerCase()} ?\n\n• ${s[0]||''}\n• Disponible 7j/7\n\n📞 [NUMÉRO]`;
    else if (canal === 'linkedin') content = `${pb[0]||''}\n\nDéfi n°1 de mes clients.\n\n→ ${s[0]||''}\n→ ${s[1]||''}\n\n📩 MP ou ☎️ [TEL]`;
    else if (canal === 'email') content = `Objet : ${m[0]||''} ?\n\nBonjour,\n\n${(pb[0]||'').toLowerCase()} ? Bonne nouvelle.\n\n✓ ${s[0]||''}\n✓ ${s[1]||''}\n\n📞 [TEL]\n\n[SIGNATURE]`;

    return {icon: icons[canal] + ' (auto)', title: titles[canal] + ' (auto-généré)', content};
}

// ============================================================
// VIEW: LEAD MAGNETS
// ============================================================
function renderViewLeadMagnets(lm, p) {
    const icons = {guide:'📘',checklist:'✅',calculateur:'🧮',simulateur:'🧮',video:'🎬',webinar:'🎬',outil:'🔧',etude:'📊',carte:'🗺️',annuaire:'📒',comparatif:'⚖️'};
    let html = '';

    if (lm && lm.length) {
        lm.forEach(l => {
            const createUrl = `?page=pages-capture&action=create&persona_id=${p.id}&persona_nom=${encodeURIComponent(p.nom)}&titre=${encodeURIComponent(l.titre||'')}&lm_type=${encodeURIComponent(l.type||'')}`;
            html += `<div class="lead-magnet-card">
                <div class="lead-magnet-icon ${l.type||''}">${icons[l.type]||'📄'}</div>
                <div class="lead-magnet-content">
                    <h5>${NP.esc(l.titre)}</h5>
                    <p>${NP.esc(l.description)}</p>
                    <span class="lead-magnet-format">${NP.esc(l.format)}</span>
                </div>
                <div style="flex-shrink:0">
                    <a href="${createUrl}" class="btn btn-action btn-sm">➕ Créer</a>
                </div>
            </div>`;
        });
    } else {
        html = '<p style="color:#9ca3af">Aucun lead magnet défini. Éditez le persona pour en ajouter.</p>';
    }

    document.getElementById('vLeadMagnets').innerHTML = html;

    // Update the CTA link
    const lmLink = document.getElementById('lmCreateLink');
    if (lmLink) lmLink.href = `?page=pages-capture&action=create&persona_id=${p.id}&persona_nom=${encodeURIComponent(p.nom)}`;
}

// ============================================================
// VIEW: PLAN COM
// ============================================================
function renderViewPlanCom(freq, canaux, p) {
    const icons = {facebook:'📘',instagram:'📸',linkedin:'💼',gmb:'📍',email:'✉️','google-ads':'📢'};
    const labels = {facebook:'Facebook',instagram:'Instagram',linkedin:'LinkedIn',gmb:'Google My Business',email:'Email Marketing','google-ads':'Google Ads'};
    const links = {facebook:'reseaux-sociaux',instagram:'reseaux-sociaux',linkedin:'reseaux-sociaux',gmb:'gmb',email:'emails','google-ads':'ads-launch'};
    const colors = {facebook:'#dbeafe',instagram:'#fce7f3',linkedin:'#dbeafe',gmb:'#dcfce7',email:'#f3e8ff','google-ads':'#fef3c7'};

    let html = '<div class="plan-com-grid">';
    const entries = Object.keys(freq).length ? Object.entries(freq) : canaux.map(c => [c, 'À définir']);

    for (const [canal, f] of entries) {
        const linkUrl = `?page=${links[canal]||'reseaux-sociaux'}&persona_id=${p.id}&persona_nom=${encodeURIComponent(p.nom)}`;
        html += `<div class="plan-com-card">
            <div class="plan-com-canal">
                <div class="plan-com-canal-icon" style="background:${colors[canal]||'#f1f5f9'}">${icons[canal]||'📱'}</div>
                <div class="plan-com-canal-name">${labels[canal]||canal}</div>
            </div>
            <div class="plan-com-freq"><strong>${NP.esc(f)}</strong><span>recommandé</span></div>
            <div><a href="${linkUrl}" class="btn btn-action btn-sm">📝 Créer</a></div>
        </div>`;
    }
    html += '</div>';
    document.getElementById('vPlanCom').innerHTML = html;

    // Calendar
    renderCalendar(freq);
}

function renderCalendar(freq) {
    const days = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
    // Simple schedule logic
    const schedule = {
        facebook:  [0,2,4],       // lun, mer, ven
        instagram: [0,1,3,4,6],   // lun, mar, jeu, ven, dim
        gmb:       [1,4],         // mar, ven
        email:     [2],           // mer
        linkedin:  [1,3],         // mar, jeu
        'google-ads': [0,1,2,3,4,5,6] // tous les jours
    };
    const shortNames = {facebook:'FB',instagram:'IG',gmb:'GMB',email:'Email',linkedin:'LI','google-ads':'Ads'};
    const classes = {facebook:'fb',instagram:'ig',gmb:'gmb',email:'email',linkedin:'li','google-ads':'ads'};

    let cal = '';
    days.forEach((d, i) => {
        cal += `<div class="calendar-day"><div class="calendar-day-name">${d}</div><div class="calendar-day-content">`;
        for (const [canal] of Object.entries(freq)) {
            if (schedule[canal] && schedule[canal].includes(i)) {
                cal += `<div class="calendar-post ${classes[canal]||''}">${shortNames[canal]||canal}</div>`;
            }
        }
        cal += '</div></div>';
    });
    document.getElementById('vCalendar').innerHTML = cal;
}

// ============================================================
// ACTION LINKS (contextual)
// ============================================================
function updateActionLinks(p) {
    const base = `persona_id=${p.id}&persona_nom=${encodeURIComponent(p.nom)}`;

    // Article link
    const linkA = document.getElementById('linkArticle');
    if (linkA) {
        const pb = NP.jp(p.problemes);
        const sugTitle = pb[0] ? encodeURIComponent(pb[0] + ' : guide complet') : '';
        linkA.href = `?page=articles&action=create&${base}${sugTitle ? '&titre=' + sugTitle : ''}`;
    }

    // Email link
    const linkE = document.getElementById('linkEmail');
    if (linkE) {
        linkE.href = `?page=emails&action=create&${base}`;
    }

    // Social link
    const linkS = document.getElementById('linkSocial');
    if (linkS) {
        linkS.href = `?page=reseaux-sociaux&${base}`;
    }
}

// ============================================================
// COPY TEMPLATE
// ============================================================
function copyTpl(i) {
    const el = document.getElementById('tpl-' + i);
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(() => {
        const btn = el.closest('.template-card').querySelector('.copy-btn');
        btn.classList.add('copied');
        btn.innerHTML = '✓ Copié !';
        setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = '📋 Copier'; }, 2000);
    });
}

// ============================================================
// GENERATE CONTENT (local)
// ============================================================
function generateContent() {
    const p = NP.find(NP.currentId);
    if (!p) return;
    const canal = document.getElementById('genCanal').value;
    const type = document.getElementById('genType').value;
    const ctx = document.getElementById('genContexte').value;
    const m = NP.jp(p.motivations), pb = NP.jp(p.problemes), s = NP.jp(p.solutions), msg = NP.jp(p.messages_cles);
    const loc = ctx ? ` à ${ctx}` : '';

    let c = '';
    if (type === 'post') {
        c = `${p.icone} ${pb[0]||'Besoin d\'aide'}${loc} ?\n\n${p.situation_familiale}, je comprends.\n\n✅ ${s[0]||'Accompagnement'}\n✅ ${s[1]||s[0]||''}\n\n${msg[0]||'Contactez-moi'}\n\n📞 [NUMÉRO]`;
    } else if (type === 'story') {
        c = `${p.icone} ${m[0]||'Votre projet'}${loc} ?\n\n👆 Swipe up pour en savoir plus !`;
    } else if (type === 'article') {
        c = `# ${m[0]||'Votre projet'}${loc}\n\n## Le problème\n${pb[0]||''}\n${pb[1]||''}\n\n## Nos solutions\n${s.map((x,i)=>(i+1)+'. '+x).join('\n')}\n\n## Conclusion\n${msg[0]||''}\n\n📞 [NUMÉRO]`;
    } else if (type === 'ad') {
        c = `🔥 ${pb[0]||''}${loc} ?\n\n${msg[0]||''}\n\n✓ ${s[0]||''}\n\n👉 [CTA BOUTON]`;
    }

    document.getElementById('generatedContent').innerHTML = `
        <div class="template-card">
            <div class="template-header"><h5>✨ Contenu généré (${canal} - ${type})</h5><button class="copy-btn" onclick="copyGen()">📋 Copier</button></div>
            <div class="template-content" id="gen-text">${NP.esc(c)}</div>
        </div>`;
    document.getElementById('generatedContent').style.display = 'block';
}

function copyGen() {
    const el = document.getElementById('gen-text');
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(() => {
        const btn = el.closest('.template-card').querySelector('.copy-btn');
        btn.classList.add('copied');
        btn.innerHTML = '✓ Copié !';
        setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = '📋 Copier'; }, 2000);
    });
}

// ============================================================
// CRUD: EDIT MODAL
// ============================================================
function openEditModal(id, defaultCat) {
    closeAllModals();
    const form = document.getElementById('personaForm');
    form.reset();

    if (id > 0) {
        const p = NP.find(id);
        if (!p) return;
        document.getElementById('editTitle').textContent = '✏️ Modifier : ' + p.nom;
        document.getElementById('eId').value = p.id;

        // Profil
        document.getElementById('eCategorie').value = p.categorie || 'acheteur';
        document.getElementById('eIcone').value = p.icone || '👤';
        document.getElementById('eCouleur').value = p.couleur || '#6366f1';
        document.getElementById('eNom').value = p.nom || '';
        document.getElementById('eCode').value = p.code || '';
        document.getElementById('eAge').value = p.age_moyen || '';
        document.getElementById('eSituation').value = p.situation_familiale || '';
        document.getElementById('eUrgence').value = p.niveau_urgence || 5;
        document.getElementById('eBudget').value = p.budget_moyen || '';
        document.getElementById('eCycle').value = p.cycle_decision || '';
        document.getElementById('eDescription').value = p.description || '';

        // JSON -> textarea lines
        document.getElementById('eMotivations').value = NP.jp(p.motivations).join('\n');
        document.getElementById('eProblemes').value = NP.jp(p.problemes).join('\n');
        document.getElementById('eSolutions').value = NP.jp(p.solutions).join('\n');
        document.getElementById('eObjections').value = NP.jp(p.objections).join('\n');
        document.getElementById('eMessages').value = NP.jp(p.messages_cles).join('\n');
        document.getElementById('eContenus').value = NP.jp(p.contenus_recommandes).join('\n');
        document.getElementById('eCanaux').value = NP.jp(p.canaux_prioritaires).join('\n');

        // M.E.R.E
        document.getElementById('eMereM').value = p.mere_magnetique || '';
        document.getElementById('eMereE').value = p.mere_emotion || '';
        document.getElementById('eMereR').value = p.mere_raison || '';
        document.getElementById('eMereEng').value = p.mere_engagement || '';
        document.getElementById('eAccroches').value = NP.jp(p.accroches).join('\n');
        document.getElementById('ePhrasesEmotion').value = NP.jp(p.phrases_emotion).join('\n');
        document.getElementById('eArgsRaison').value = NP.jp(p.arguments_raison).join('\n');
        document.getElementById('eCTAs').value = NP.jp(p.ctas).join('\n');

        // Templates
        document.getElementById('eTplFB').value = p.template_facebook || '';
        document.getElementById('eTplIG').value = p.template_instagram || '';
        document.getElementById('eTplGMB').value = p.template_gmb || '';
        document.getElementById('eTplLI').value = p.template_linkedin || '';
        document.getElementById('eTplEmailSubj').value = p.template_email_subject || '';
        document.getElementById('eTplEmailBody').value = p.template_email_body || '';
        document.getElementById('eTplSMS').value = p.template_sms || '';

        // Lead Magnets
        loadLeadMagnetsEditor(NP.jp(p.lead_magnets));

        // Freq
        const freq = NP.jo(p.frequence_com);
        document.getElementById('eFreqFB').value = freq.facebook || '';
        document.getElementById('eFreqIG').value = freq.instagram || '';
        document.getElementById('eFreqLI').value = freq.linkedin || '';
        document.getElementById('eFreqGMB').value = freq.gmb || '';
        document.getElementById('eFreqEmail').value = freq.email || '';
        document.getElementById('eFreqAds').value = freq['google-ads'] || '';
    } else {
        document.getElementById('editTitle').textContent = '➕ Nouveau persona';
        document.getElementById('eId').value = '0';
        if (defaultCat) document.getElementById('eCategorie').value = defaultCat;
        loadLeadMagnetsEditor([]);
    }

    // Reset to first edit tab
    switchEditTab('e-profil', document.querySelector('#editTabs .modal-tab'));
    document.getElementById('editModal').classList.add('active');
}

function editFromView() {
    if (NP.currentId) openEditModal(NP.currentId);
}

// ============================================================
// LEAD MAGNETS EDITOR
// ============================================================
let lmItems = [];

function loadLeadMagnetsEditor(items) {
    lmItems = items || [];
    renderLMEditor();
}

function renderLMEditor() {
    const container = document.getElementById('eLMContainer');
    if (!container) return;
    let html = '';
    lmItems.forEach((lm, i) => {
        html += `<div class="lm-editor" id="lm-${i}">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                <strong style="font-size:.8rem;color:#6366f1">Lead Magnet #${i+1}</strong>
                <span class="lm-remove" onclick="removeLM(${i})">🗑️ Supprimer</span>
            </div>
            <div class="lm-row"><label>Type</label><select onchange="updateLM(${i},'type',this.value)">
                <option value="guide" ${lm.type==='guide'?'selected':''}>📘 Guide</option>
                <option value="checklist" ${lm.type==='checklist'?'selected':''}>✅ Checklist</option>
                <option value="calculateur" ${lm.type==='calculateur'?'selected':''}>🧮 Calculateur</option>
                <option value="simulateur" ${lm.type==='simulateur'?'selected':''}>🧮 Simulateur</option>
                <option value="video" ${lm.type==='video'?'selected':''}>🎬 Vidéo</option>
                <option value="webinar" ${lm.type==='webinar'?'selected':''}>🎬 Webinar</option>
                <option value="outil" ${lm.type==='outil'?'selected':''}>🔧 Outil</option>
                <option value="etude" ${lm.type==='etude'?'selected':''}>📊 Étude</option>
                <option value="carte" ${lm.type==='carte'?'selected':''}>🗺️ Carte</option>
                <option value="annuaire" ${lm.type==='annuaire'?'selected':''}>📒 Annuaire</option>
                <option value="comparatif" ${lm.type==='comparatif'?'selected':''}>⚖️ Comparatif</option>
            </select></div>
            <div class="lm-row"><label>Titre</label><input type="text" value="${NP.esc(lm.titre||'')}" onchange="updateLM(${i},'titre',this.value)"></div>
            <div class="lm-row"><label>Description</label><input type="text" value="${NP.esc(lm.description||'')}" onchange="updateLM(${i},'description',this.value)"></div>
            <div class="lm-row"><label>Format</label><input type="text" value="${NP.esc(lm.format||'')}" onchange="updateLM(${i},'format',this.value)" placeholder="PDF 15 pages"></div>
        </div>`;
    });
    container.innerHTML = html;
}

function addLeadMagnet() {
    lmItems.push({type:'guide', titre:'', description:'', format:'PDF'});
    renderLMEditor();
}

function removeLM(i) {
    lmItems.splice(i, 1);
    renderLMEditor();
}

function updateLM(i, field, value) {
    if (lmItems[i]) lmItems[i][field] = value;
}

// ============================================================
// SAVE PERSONA
// ============================================================
function savePersona(e) {
    e.preventDefault();

    // Serialize lead magnets JSON
    document.getElementById('eLMJson').value = JSON.stringify(lmItems);

    // Serialize freq JSON
    const freq = {};
    const fFB = document.getElementById('eFreqFB').value.trim();
    const fIG = document.getElementById('eFreqIG').value.trim();
    const fLI = document.getElementById('eFreqLI').value.trim();
    const fGMB = document.getElementById('eFreqGMB').value.trim();
    const fEmail = document.getElementById('eFreqEmail').value.trim();
    const fAds = document.getElementById('eFreqAds').value.trim();
    if (fFB) freq.facebook = fFB;
    if (fIG) freq.instagram = fIG;
    if (fLI) freq.linkedin = fLI;
    if (fGMB) freq.gmb = fGMB;
    if (fEmail) freq.email = fEmail;
    if (fAds) freq['google-ads'] = fAds;
    document.getElementById('eFreqJson').value = JSON.stringify(freq);

    const form = document.getElementById('personaForm');
    const fd = new FormData(form);

    fetch(window.location.href, {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Erreur', 'error');
        }
    })
    .catch(err => showToast('Erreur réseau : ' + err.message, 'error'));
}

// ============================================================
// DELETE / DUPLICATE
// ============================================================
function deletePersona() {
    if (!NP.currentId) return;
    const p = NP.find(NP.currentId);
    if (!confirm(`Supprimer le persona "${p?.nom}" ? Cette action est irréversible.`)) return;

    const fd = new FormData();
    fd.append('crud_action', 'delete');
    fd.append('persona_id', NP.currentId);

    fetch(window.location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message, 'error');
        }
    });
}

function duplicatePersona() {
    if (!NP.currentId) return;
    const fd = new FormData();
    fd.append('crud_action', 'duplicate');
    fd.append('persona_id', NP.currentId);

    fetch(window.location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message, 'error');
        }
    });
}

// ============================================================
// TOAST
// ============================================================
function showToast(msg, type) {
    const t = document.getElementById('npToast');
    t.textContent = msg;
    t.className = 'np-toast ' + (type || '') + ' show';
    setTimeout(() => { t.classList.remove('show'); }, 3000);
}
</script>