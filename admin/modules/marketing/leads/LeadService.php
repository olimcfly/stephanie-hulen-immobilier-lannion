<?php
/**
 * LeadService — Logique métier pour le module Leads
 * /admin/modules/marketing/leads/LeadService.php
 */

class LeadService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── GET ──────────────────────────────────────────────────────────────────

    public function getLead(int $id, string $tbl = 'leads'): array
    {
        $tbl = preg_replace('/[^a-z_]/', '', $tbl);
        $s = $this->pdo->prepare("SELECT * FROM `$tbl` WHERE id = ?");
        $s->execute([$id]);
        $row = $s->fetch();
        return ['success' => (bool)$row, 'lead' => $row ?: null];
    }

    // ── ADD ─────────────────────────────────────────────────────────────────

    public function addLead(array $data): array
    {
        $fn = trim($data['firstname'] ?? '');
        $ln = trim($data['lastname']  ?? '');
        if (!$fn && !$ln) {
            return ['success' => false, 'error' => 'Prénom ou nom requis'];
        }

        $this->pdo->prepare("INSERT INTO leads
            (firstname,lastname,email,phone,city,source,notes,status,temperature,next_action,next_action_date,created_at,updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())")
        ->execute([
            $fn, $ln,
            trim($data['email'] ?? '') ?: null,
            trim($data['phone'] ?? '') ?: null,
            trim($data['city']  ?? '') ?: null,
            $data['source']      ?? 'manuel',
            trim($data['notes'] ?? '') ?: null,
            $data['status']      ?? 'new',
            $data['temperature'] ?? 'warm',
            trim($data['next_action']      ?? '') ?: null,
            trim($data['next_action_date'] ?? '') ?: null,
        ]);

        return ['success' => true, 'id' => (int)$this->pdo->lastInsertId()];
    }

    // ── UPDATE ──────────────────────────────────────────────────────────────

    public function updateLead(int $id, string $tbl, array $data): array
    {
        $tbl = preg_replace('/[^a-z_]/', '', $tbl);
        if (!$id) {
            return ['success' => false, 'error' => 'ID manquant'];
        }

        switch ($tbl) {
            case 'leads':
                $this->pdo->prepare("UPDATE leads SET
                    firstname=?,lastname=?,email=?,phone=?,city=?,source=?,notes=?,
                    status=?,temperature=?,next_action=?,next_action_date=?,updated_at=NOW()
                    WHERE id=?")
                ->execute([
                    trim($data['firstname']??''), trim($data['lastname']??''),
                    trim($data['email']??'')           ?: null,
                    trim($data['phone']??'')           ?: null,
                    trim($data['city'] ??'')           ?: null,
                    $data['source']      ?? 'manuel',
                    trim($data['notes']??'')           ?: null,
                    $data['status']      ?? 'new',
                    $data['temperature'] ?? 'warm',
                    trim($data['next_action']     ??'') ?: null,
                    trim($data['next_action_date']??'') ?: null,
                    $id,
                ]);
                break;
            case 'capture_leads':
                $this->pdo->prepare("UPDATE capture_leads SET prenom=?,nom=?,email=?,tel=? WHERE id=?")
                    ->execute([trim($data['firstname']??''),trim($data['lastname']??''),trim($data['email']??''),trim($data['phone']??''),$id]);
                break;
            case 'demandes_estimation':
                $this->pdo->prepare("UPDATE demandes_estimation SET email=?,telephone=?,statut=? WHERE id=?")
                    ->execute([trim($data['email']??''),trim($data['phone']??''),$data['status']??'nouveau',$id]);
                break;
            case 'contacts':
                $this->pdo->prepare("UPDATE contacts SET firstname=?,lastname=?,email=?,phone=?,city=?,notes=?,status=?,updated_at=NOW() WHERE id=?")
                    ->execute([trim($data['firstname']??''),trim($data['lastname']??''),trim($data['email']??''),trim($data['phone']??''),trim($data['city']??''),trim($data['notes']??''),$data['status']??'actif',$id]);
                break;
            case 'financement_leads':
                $this->pdo->prepare("UPDATE financement_leads SET prenom=?,nom=?,email=?,telephone=?,statut=?,notes=?,updated_at=NOW() WHERE id=?")
                    ->execute([trim($data['firstname']??''),trim($data['lastname']??''),trim($data['email']??''),trim($data['phone']??''),$data['status']??'nouveau',trim($data['notes']??''),$id]);
                break;
        }

        return ['success' => true];
    }

    // ── DELETE ───────────────────────────────────────────────────────────────

    public function deleteLead(int $id, string $tbl = 'leads'): array
    {
        $tbl = preg_replace('/[^a-z_]/', '', $tbl);
        $this->pdo->prepare("DELETE FROM `$tbl` WHERE id=?")->execute([$id]);
        if ($tbl === 'leads') {
            try { $this->pdo->prepare("DELETE FROM lead_interactions WHERE lead_id=?")->execute([$id]); } catch(\Exception $e){}
        }
        return ['success' => true];
    }

    // ── INTERACTIONS ────────────────────────────────────────────────────────

    public function getInteractions(int $leadId): array
    {
        try {
            $s = $this->pdo->prepare("SELECT * FROM lead_interactions WHERE lead_id=? ORDER BY COALESCE(interaction_date,created_at) DESC");
            $s->execute([$leadId]);
            return ['success' => true, 'interactions' => $s->fetchAll()];
        } catch (\Exception $e) {
            return ['success' => true, 'interactions' => []];
        }
    }

    public function addInteraction(array $data): array
    {
        $lid  = (int)($data['lead_id'] ?? 0);
        $type = in_array($data['type']??'',['note','appel','email','rdv','sms','visite']) ? $data['type'] : 'note';

        $this->pdo->prepare("INSERT INTO lead_interactions (lead_id,type,subject,content,interaction_date,duration_minutes,outcome) VALUES (?,?,?,?,?,?,?)")
            ->execute([$lid,$type,trim($data['subject']??'')?:null,trim($data['content']??'')?:null,trim($data['interaction_date']??'')?:null,(int)($data['duration_minutes']??0)?:null,$data['outcome']??null]);
        try { $this->pdo->prepare("UPDATE leads SET updated_at=NOW() WHERE id=?")->execute([$lid]); } catch(\Exception $e){}

        return ['success' => true];
    }

    // ── EXPORT CSV ──────────────────────────────────────────────────────────

    public function exportCsv(): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="leads-'.date('Y-m-d').'.csv"');
        $out = fopen('php://output','w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['Source','Prénom','Nom','Email','Téléphone','Ville','Statut','Date'], ';');
        foreach ($this->getAllLeads('','','created_at','DESC','',0,99999)['rows'] as $r)
            fputcsv($out, [$r['_src_label'],$r['_fn'],$r['_ln'],$r['_email']??'',$r['_phone']??'',$r['_city']??'',$r['_status']??'',date('d/m/Y H:i',strtotime($r['created_at']))], ';');
        fclose($out);
    }

    // ── LISTE UNIFIÉE ───────────────────────────────────────────────────────

    public function getAllLeads(string $search, string $srcFilter, string $sort, string $order, string $statusFlt, int $offset, int $limit): array
    {
        $rows = [];

        if (!$srcFilter || in_array($srcFilter,['Manuel','Site web','GMB','Facebook','Google','Téléphone','Recommandation','Flyer','Boîtage','Salon'])) {
            try {
                $w=['1=1'];$p=[];
                if ($search) { $t="%$search%"; $w[]="(firstname LIKE ? OR lastname LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)"; $p=[$t,$t,$t,$t,$t]; }
                if ($statusFlt) { $w[]="status=?"; $p[]=$statusFlt; }
                $s=$this->pdo->prepare("SELECT *,'leads' AS _tbl FROM leads WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC");
                $s->execute($p);
                $srcMap=['site_web'=>'Site web','gmb'=>'GMB','pub_facebook'=>'Facebook','pub_google'=>'Google','recommandation'=>'Recommandation','telephone'=>'Téléphone','flyer'=>'Flyer','boitage'=>'Boîtage','salon'=>'Salon','estimation'=>'Estimation','capture'=>'Capture','financement'=>'Financement','manuel'=>'Manuel','autre'=>'Autre'];
                foreach ($s->fetchAll() as $r) {
                    $r['_fn']  = trim($r['firstname'] ?? '');
                    $r['_ln']  = trim($r['lastname']  ?? '');
                    if (!$r['_fn'] && !$r['_ln'] && !empty($r['full_name'])) {
                        $pts = explode(' ', trim($r['full_name']), 2);
                        $r['_fn'] = $pts[0]; $r['_ln'] = $pts[1] ?? '';
                    }
                    $r['_email']=$r['email']??null; $r['_phone']=$r['phone']??null; $r['_city']=$r['city']??null;
                    $r['_status']=$r['status']??''; $r['_score']=(int)($r['score']??0);
                    $src = $r['source'] ?? 'manuel';
                    $r['_src_label'] = $srcMap[$src] ?? ucfirst($src);
                    $r['_src_key']   = 'leads';
                    if ($srcFilter && $r['_src_label'] !== $srcFilter) continue;
                    $rows[] = $r;
                }
            } catch (\Exception $e) {}
        }

        if (!$srcFilter || $srcFilter === 'Capture') {
            try {
                $w=['1=1'];$p=[];
                if ($search) { $t="%$search%"; $w[]="(prenom LIKE ? OR nom LIKE ? OR email LIKE ? OR tel LIKE ?)"; $p=[$t,$t,$t,$t]; }
                $s=$this->pdo->prepare("SELECT *,'capture_leads' AS _tbl FROM capture_leads WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC");
                $s->execute($p);
                foreach ($s->fetchAll() as $r) {
                    $r['_fn']=$r['prenom']??''; $r['_ln']=$r['nom']??''; $r['_email']=$r['email']??null; $r['_phone']=$r['tel']??null;
                    $r['_city']=null; $r['_status']=$r['injected_crm']?'contacté':'nouveau'; $r['_score']=0;
                    $r['_src_label']='Capture'; $r['_src_key']='capture_leads';
                    $r['notes']=$r['message']??null;
                    $rows[]=$r;
                }
            } catch (\Exception $e) {}
        }

        if (!$srcFilter || $srcFilter === 'Estimation') {
            try {
                $w=['1=1'];$p=[];
                if ($search) { $t="%$search%"; $w[]="(email LIKE ? OR telephone LIKE ? OR ville LIKE ?)"; $p=[$t,$t,$t]; }
                $s=$this->pdo->prepare("SELECT *,'demandes_estimation' AS _tbl FROM demandes_estimation WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC");
                $s->execute($p);
                foreach ($s->fetchAll() as $r) {
                    $r['_fn']=''; $r['_ln']=trim(($r['type_bien']??'Bien').' '.($r['ville']??''));
                    $r['_email']=$r['email']??null; $r['_phone']=$r['telephone']??null; $r['_city']=$r['ville']??null;
                    $r['_status']=$r['statut']??'nouveau'; $r['_score']=0;
                    $r['_src_label']='Estimation'; $r['_src_key']='demandes_estimation';
                    $parts=array_filter([$r['type_bien']??'', $r['surface']?($r['surface'].'m²'):'', $r['estimation_moyenne']?('~'.number_format($r['estimation_moyenne'],0,',',' ').'€'):'']);
                    $r['notes']=implode(' — ',$parts);
                    $rows[]=$r;
                }
            } catch (\Exception $e) {}
        }

        if (!$srcFilter || $srcFilter === 'Contact') {
            try {
                $w=['1=1'];$p=[];
                if ($search) { $t="%$search%"; $w[]="(firstname LIKE ? OR lastname LIKE ? OR nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR phone LIKE ?)"; $p=[$t,$t,$t,$t,$t,$t]; }
                $s=$this->pdo->prepare("SELECT *,'contacts' AS _tbl FROM contacts WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC");
                $s->execute($p);
                foreach ($s->fetchAll() as $r) {
                    $r['_fn']=$r['firstname']??$r['prenom']??''; $r['_ln']=$r['lastname']??$r['nom']??'';
                    $r['_email']=$r['email']??null; $r['_phone']=$r['phone']??$r['telephone']??null; $r['_city']=$r['city']??null;
                    $r['_status']=$r['status']??'actif'; $r['_score']=(int)($r['rating']??0);
                    $r['_src_label']='Contact'; $r['_src_key']='contacts';
                    $rows[]=$r;
                }
            } catch (\Exception $e) {}
        }

        if (!$srcFilter || $srcFilter === 'Financement') {
            try {
                $w=['1=1'];$p=[];
                if ($search) { $t="%$search%"; $w[]="(prenom LIKE ? OR nom LIKE ? OR email LIKE ? OR telephone LIKE ?)"; $p=[$t,$t,$t,$t]; }
                $s=$this->pdo->prepare("SELECT *,'financement_leads' AS _tbl FROM financement_leads WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC");
                $s->execute($p);
                foreach ($s->fetchAll() as $r) {
                    $r['_fn']=$r['prenom']??''; $r['_ln']=$r['nom']??''; $r['_email']=$r['email']??null; $r['_phone']=$r['telephone']??null;
                    $r['_city']=null; $r['_status']=$r['statut']??'nouveau'; $r['_score']=0;
                    $r['_src_label']='Financement'; $r['_src_key']='financement_leads';
                    $r['notes']=trim(($r['type_projet']??'Projet').($r['montant_projet']?' — '.number_format($r['montant_projet'],0,',',' ').'€':'').($r['notes']?' | '.$r['notes']:''));
                    $rows[]=$r;
                }
            } catch (\Exception $e) {}
        }

        // Dédoublonnage email
        $seen=[]; $deduped=[];
        foreach ($rows as $r) {
            $key = strtolower(trim($r['_email'] ?? ''));
            if ($key && isset($seen[$key])) continue;
            if ($key) $seen[$key]=true;
            $deduped[]=$r;
        }

        usort($deduped, function($a,$b) use ($sort,$order) {
            $va = match($sort) { '_fn'=>strtolower($a['_fn'].$a['_ln']), '_email'=>strtolower($a['_email']??''), '_score'=>(int)$a['_score'], default=>$a['created_at']??'' };
            $vb = match($sort) { '_fn'=>strtolower($b['_fn'].$b['_ln']), '_email'=>strtolower($b['_email']??''), '_score'=>(int)$b['_score'], default=>$b['created_at']??'' };
            $cmp = is_int($va) ? ($va<=>$vb) : strcmp((string)$va,(string)$vb);
            return $order==='DESC' ? -$cmp : $cmp;
        });

        $total = count($deduped);
        return ['rows' => array_slice($deduped,$offset,$limit), 'total' => $total];
    }
}
