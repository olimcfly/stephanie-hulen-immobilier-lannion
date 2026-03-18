<?php
/**
 * crm-functions.php — /includes/functions/crm-functions.php
 * Fonctions utilitaires CRM — IMMO LOCAL+
 *
 * Fonctions disponibles :
 *   crm_get_lead($id)           — Récupère un lead par ID
 *   crm_save_lead($data)        — Crée ou met à jour un lead
 *   crm_update_score($id,$score)— Met à jour le score d'un lead
 *   crm_get_leads($filters)     — Liste filtrée/paginée des leads
 *   crm_add_note($lead_id,$note)— Ajoute une note à un lead
 *   crm_get_stats()             — KPIs CRM
 *   crm_score_lead($lead)       — Calcule un score automatique (0-100)
 *   crm_format_phone($phone)    — Formate un numéro FR
 *   crm_lead_status_label($s)   — Label lisible d'un statut
 */

if (!function_exists('crm_get_lead')) :

/**
 * Récupère un lead par son ID
 */
function crm_get_lead(int $id): ?array
{
    if (!class_exists('Database')) return null;
    try {
        $db   = Database::getInstance();
        $pdo  = method_exists($db,'getConnection') ? $db->getConnection() : $db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) { return null; }
}

/**
 * Crée ou met à jour un lead
 * Retourne l'ID inséré/mis à jour, ou 0 en cas d'erreur
 */
function crm_save_lead(array $data): int
{
    if (!class_exists('Database')) return 0;
    try {
        $db  = Database::getInstance();
        $pdo = method_exists($db,'getConnection') ? $db->getConnection() : $db->getPdo();

        $allowed = [
            'nom','prenom','email','telephone','source','statut','status',
            'projet','budget_min','budget_max','secteur','score','notes',
            'page_origine','utm_source','utm_medium','utm_campaign',
        ];
        $fields = []; $params = [];
        foreach ($allowed as $f) {
            if (!array_key_exists($f,$data)) continue;
            $fields[]        = "`$f`";
            $params[":$f"]   = $data[$f];
        }
        if (empty($fields)) return 0;

        $id = (int)($data['id'] ?? 0);
        if ($id > 0) {
            $set = implode(',', array_map(fn($f,$k)=>"$f=$k", $fields, array_keys($params)));
            $params[':id'] = $id;
            $pdo->prepare("UPDATE leads SET $set, updated_at=NOW() WHERE id=:id")->execute($params);
            return $id;
        }
        $cols = implode(',', $fields);
        $vals = implode(',', array_keys($params));
        $pdo->prepare("INSERT INTO leads ($cols, created_at) VALUES ($vals, NOW())")->execute($params);
        return (int) $pdo->lastInsertId();
    } catch (Exception $e) { return 0; }
}

/**
 * Met à jour le score d'un lead
 */
function crm_update_score(int $id, int $score): bool
{
    if (!class_exists('Database')) return false;
    try {
        $db  = Database::getInstance();
        $pdo = method_exists($db,'getConnection') ? $db->getConnection() : $db->getPdo();
        $pdo->prepare("UPDATE leads SET score=:s, updated_at=NOW() WHERE id=:id")->execute([':s'=>min(100,max(0,$score)),':id'=>$id]);
        return true;
    } catch (Exception $e) { return false; }
}

/**
 * Liste filtrée des leads
 * $filters : statut, source, score_min, search, page, limit, sort
 */
function crm_get_leads(array $filters = []): array
{
    if (!class_exists('Database')) return ['data'=>[],'total'=>0];
    try {
        $db  = Database::getInstance();
        $pdo = method_exists($db,'getConnection') ? $db->getConnection() : $db->getPdo();

        $page   = max(1, (int)($filters['page']  ?? 1));
        $limit  = min(100,(int)($filters['limit'] ?? 25));
        $offset = ($page - 1) * $limit;
        $where  = ['1=1']; $params = [];

        if (!empty($filters['statut'])) {
            $sc = $pdo->query("SHOW COLUMNS FROM leads LIKE 'statut'")->rowCount() ? 'statut' : 'status';
            $where[] = "$sc = :st"; $params[':st'] = $filters['statut'];
        }
        if (!empty($filters['source'])) { $where[]='source=:src'; $params[':src']=$filters['source']; }
        if (isset($filters['score_min'])) { $where[]='score>=:sm'; $params[':sm']=(int)$filters['score_min']; }
        if (!empty($filters['search'])) {
            $where[]="(nom LIKE :s OR prenom LIKE :s OR email LIKE :s OR telephone LIKE :s)";
            $params[':s']='%'.trim($filters['search']).'%';
        }
        $w    = implode(' AND ',$where);
        $sort = in_array($filters['sort']??'',['-score','created_at','score','nom']) ? $filters['sort'] : 'id DESC';

        $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE $w");
        $total_stmt->execute($params);
        $total = (int)$total_stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM leads WHERE $w ORDER BY $sort LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        return ['data'=>$stmt->fetchAll(PDO::FETCH_ASSOC), 'total'=>$total, 'page'=>$page, 'limit'=>$limit];
    } catch (Exception $e) { return ['data'=>[],'total'=>0,'error'=>$e->getMessage()]; }
}

/**
 * Ajoute une note à un lead (champ JSON ou texte)
 */
function crm_add_note(int $lead_id, string $note, string $author = 'Admin'): bool
{
    if (!class_exists('Database') || !$note) return false;
    try {
        $db  = Database::getInstance();
        $pdo = method_exists($db,'getConnection') ? $db->getConnection() : $db->getPdo();
        // Vérifier si table crm_notes existe
        if ($pdo->query("SHOW TABLES LIKE 'crm_notes'")->rowCount()) {
            $pdo->prepare("INSERT INTO crm_notes (lead_id,note,author,created_at) VALUES (:l,:n,:a,NOW())")
                ->execute([':l'=>$lead_id,':n'=>$note,':a'=>$author]);
        } else {
            // Fallback : append dans le champ notes
            $stmt = $pdo->prepare("UPDATE leads SET notes = CONCAT(IFNULL(notes,''), :n), updated_at=NOW() WHERE id=:id");
            $stmt->execute([':n'=>"\n[".date('d/m/Y H:i')."] $author : $note", ':id'=>$lead_id]);
        }
        return true;
    } catch (Exception $e) { return false; }
}

/**
 * KPIs CRM
 */
function crm_get_stats(): array
{
    $stats = ['total'=>0,'this_month'=>0,'hot'=>0,'by_source'=>[],'by_status'=>[]];
    if (!class_exists('Database')) return $stats;
    try {
        $db  = Database::getInstance();
        $pdo = method_exists($db,'getConnection') ? $db->getConnection() : $db->getPdo();
        if ($pdo->query("SHOW TABLES LIKE 'leads'")->rowCount() === 0) return $stats;

        $stats['total']      = (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
        $stats['this_month'] = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")->fetchColumn();

        $sc = $pdo->query("SHOW COLUMNS FROM leads LIKE 'score'")->rowCount() ? 'score' : null;
        if ($sc) $stats['hot'] = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE $sc >= 70")->fetchColumn();

        foreach ($pdo->query("SELECT IFNULL(source,'direct') as s, COUNT(*) as c FROM leads GROUP BY source ORDER BY c DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $stats['by_source'][$r['s']] = (int)$r['c'];
        }
        $stCol = $pdo->query("SHOW COLUMNS FROM leads LIKE 'statut'")->rowCount() ? 'statut' : 'status';
        if ($stCol) {
            foreach ($pdo->query("SELECT $stCol as s, COUNT(*) as c FROM leads GROUP BY $stCol")->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $stats['by_status'][$r['s']??'inconnu'] = (int)$r['c'];
            }
        }
    } catch (Exception $e) {}
    return $stats;
}

/**
 * Calcule un score automatique basé sur les données du lead (0-100)
 */
function crm_score_lead(array $lead): int
{
    $score = 0;
    if (!empty($lead['telephone'])) $score += 20;
    if (!empty($lead['email']))     $score += 15;
    if (!empty($lead['projet']))    $score += 15;
    if (!empty($lead['budget_max']) && (int)$lead['budget_max'] > 0) $score += 20;
    if (!empty($lead['secteur']))   $score += 10;
    // Source de qualité
    $hot_sources = ['estimation','valuation','rdv','contact'];
    if (!empty($lead['source']) && in_array(strtolower($lead['source']), $hot_sources)) $score += 20;
    return min(100, $score);
}

/**
 * Formate un numéro de téléphone français
 */
function crm_format_phone(string $phone): string
{
    $clean = preg_replace('/\D/','',$phone);
    if (strlen($clean) === 10) return implode(' ', str_split($clean, 2));
    return $phone;
}

/**
 * Retourne le label lisible d'un statut lead
 */
function crm_lead_status_label(string $status): string
{
    $labels = [
        'nouveau'        => 'Nouveau',
        'new'            => 'Nouveau',
        'contacté'       => 'Contacté',
        'contacted'      => 'Contacté',
        'rdv_planifié'   => 'RDV planifié',
        'rdv_effectué'   => 'RDV effectué',
        'en_négociation' => 'En négociation',
        'gagné'          => 'Gagné',
        'won'            => 'Gagné',
        'perdu'          => 'Perdu',
        'lost'           => 'Perdu',
        'archivé'        => 'Archivé',
    ];
    return $labels[strtolower($status)] ?? ucfirst($status);
}

endif; // !function_exists