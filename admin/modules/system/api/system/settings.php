<?php
/**
 *  /admin/api/system/settings.php
 *  Configuration du site
 *  Miroir de : modules/system/settings/
 *  Table : settings
 *  actions: list, get, save, save-batch, delete, test-api-key
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

if ($action==='list' || $action==='index') {
    $group = $p['group'] ?? null;
    $sql = "SELECT * FROM settings";
    $params = [];
    if ($group) { $sql .= " WHERE setting_key LIKE ?"; $params[] = "{$group}%"; }
    $sql .= " ORDER BY setting_key ASC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $settings = [];
    while ($r = $stmt->fetch()) $settings[$r['setting_key']] = $r['setting_value'];
    return ['success'=>true,'settings'=>$settings];
}

if ($action==='get') {
    $key = $p['key'] ?? '';
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?"); $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return ['success'=>true,'key'=>$key,'value'=>$val !== false ? $val : null];
}

if ($action==='save' && $method==='POST') {
    $key = $p['key'] ?? '';
    $value = $p['value'] ?? '';
    if (empty($key)) return ['success'=>false,'error'=>'key requis'];
    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
        ->execute([$key, $value]);
    return ['success'=>true,'message'=>"Setting '{$key}' sauvegardé"];
}

if ($action==='save-batch' && $method==='POST') {
    $settings = $p['settings'] ?? [];
    if (!is_array($settings)) return ['success'=>false,'error'=>'settings{} requis'];
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $saved = 0;
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
        $saved++;
    }
    return ['success'=>true,'saved'=>$saved];
}

if ($action==='delete' && $method==='POST') {
    $key = $p['key'] ?? '';
    $pdo->prepare("DELETE FROM settings WHERE setting_key = ?")->execute([$key]);
    return ['success'=>true,'message'=>'Supprimé'];
}

if ($action==='test-api-key' && $method==='POST') {
    $provider = $p['provider'] ?? '';
    $apiKey   = $p['api_key'] ?? '';
    if (empty($apiKey)) return ['success'=>false,'error'=>'api_key requis'];

    $valid = false; $detail = '';
    if ($provider === 'openai') {
        $ch = curl_init('https://api.openai.com/v1/models');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>["Authorization: Bearer {$apiKey}"]]);
        $resp = json_decode(curl_exec($ch), true); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $valid = $code === 200;
        $detail = $valid ? count($resp['data']??[]).' modèles disponibles' : ($resp['error']['message'] ?? "HTTP {$code}");
    } elseif ($provider === 'claude' || $provider === 'anthropic') {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
            CURLOPT_HTTPHEADER=>["x-api-key: {$apiKey}","Content-Type: application/json","anthropic-version: 2023-06-01"],
            CURLOPT_POSTFIELDS=>json_encode(['model'=>'claude-sonnet-4-20250514','max_tokens'=>10,'messages'=>[['role'=>'user','content'=>'ping']]])]);
        $resp = json_decode(curl_exec($ch), true); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $valid = $code === 200;
        $detail = $valid ? 'Clé valide' : ($resp['error']['message'] ?? "HTTP {$code}");
    } elseif ($provider === 'perplexity') {
        $ch = curl_init('https://api.perplexity.ai/chat/completions');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
            CURLOPT_HTTPHEADER=>["Authorization: Bearer {$apiKey}","Content-Type: application/json"],
            CURLOPT_POSTFIELDS=>json_encode(['model'=>'llama-3.1-sonar-small-128k-online','messages'=>[['role'=>'user','content'=>'ping']]])]);
        $code = curl_getinfo(curl_exec($ch) ? $ch : $ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $valid = $code === 200;
        $detail = $valid ? 'Clé valide' : "HTTP {$code}";
    } else {
        return ['success'=>false,'error'=>'Provider non supporté (openai, claude, perplexity)'];
    }

    return ['success'=>true,'valid'=>$valid,'provider'=>$provider,'detail'=>$detail];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['list','get','save','save-batch','delete','test-api-key']];
