<?php
/**
 * PageDebugger.php
 * Outil de diagnostic par page admin — ÉCOSYSTÈME IMMO LOCAL+
 */

class PageDebugger
{
    public static function panel(
        array  $tables    = [],
        array  $endpoints = [],
        string $logPath   = ''
    ): void {
        if (empty($_SESSION['admin_id'])) return;

        $logPath = $logPath ?: dirname(__DIR__, 4) . '/logs/php_errors.log';

        $dbStatus  = 'ok';
        $dbMessage = 'Connectée';
        $tableResults = [];

        try {
            $pdo = getDB();
            foreach ($tables as $table) {
                try {
                    $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                    $tableResults[] = ['name' => $table, 'status' => 'ok', 'rows' => $count];
                } catch (Exception $e) {
                    $exists = $pdo->query("SHOW TABLES LIKE '{$table}'")->rowCount() > 0;
                    if (!$exists) {
                        $tableResults[] = ['name' => $table, 'status' => 'missing', 'rows' => null];
                        $dbStatus = 'error';
                    } else {
                        $tableResults[] = ['name' => $table, 'status' => 'error', 'rows' => null, 'msg' => $e->getMessage()];
                        $dbStatus = 'warning';
                    }
                }
            }
        } catch (Exception $e) {
            $dbStatus  = 'error';
            $dbMessage = $e->getMessage();
        }

        $logLines = [];
        if (file_exists($logPath)) {
            $all = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $filtered = array_filter($all, fn($l) =>
                str_contains($l, 'PHP Fatal') ||
                str_contains($l, 'PHP Parse') ||
                str_contains($l, 'PHP Warning') ||
                str_contains($l, 'PHP Deprecated') ||
                str_contains($l, 'SQLSTATE') ||
                str_contains($l, 'PHP Notice')
            );
            $logLines = array_slice(array_values($filtered), -15);
        }

        $epList = [];
        foreach ($endpoints as $i => $ep) {
            $epList[] = [
                'id'     => 'pdbg_ep_' . $i,
                'url'    => $ep['url']    ?? '',
                'label'  => $ep['label']  ?? $ep['url'] ?? "Endpoint {$i}",
                'body'   => $ep['body']   ?? [],
                'method' => $ep['method'] ?? 'POST',
            ];
        }

        $uid = 'pdbg_' . substr(md5(uniqid()), 0, 6);
        self::render($uid, $dbStatus, $dbMessage, $tableResults, $logLines, $epList);
    }

    private static function render(
        string $uid,
        string $dbStatus,
        string $dbMessage,
        array  $tableResults,
        array  $logLines,
        array  $epList
    ): void {
        $colors = [
            'ok'      => ['bg' => '#d1fae5', 'text' => '#065f46', 'dot' => '#10b981'],
            'warning' => ['bg' => '#fef3c7', 'text' => '#92400e', 'dot' => '#f59e0b'],
            'error'   => ['bg' => '#fee2e2', 'text' => '#991b1b', 'dot' => '#ef4444'],
            'missing' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'dot' => '#ef4444'],
            'pending' => ['bg' => '#f1f5f9', 'text' => '#475569', 'dot' => '#94a3b8'],
        ];

        $dbColor = $colors[$dbStatus] ?? $colors['pending'];
        $dbIcon  = $dbStatus === 'ok' ? '✓' : ($dbStatus === 'error' ? '✗' : '⚠');

        // Log HTML
        $logHtml = '';
        foreach (array_reverse($logLines) as $line) {
            $cls = str_contains($line, 'Fatal') || str_contains($line, 'Parse') || str_contains($line, 'SQLSTATE')
                ? '#ef4444'
                : (str_contains($line, 'Deprecated') || str_contains($line, 'Warning') || str_contains($line, 'Notice') ? '#f59e0b' : '#94a3b8');
            $safe = htmlspecialchars($line);
            $logHtml .= "<div style='color:{$cls};margin-bottom:2px;'>{$safe}</div>";
        }
        if (!$logHtml) $logHtml = "<div style='color:#94a3b8'>Aucune erreur récente ✓</div>";

        // Tables HTML
        $tablesHtml = '';
        foreach ($tableResults as $t) {
            $c    = $colors[$t['status']] ?? $colors['pending'];
            $icon = $t['status'] === 'ok' ? '✓' : '✗';
            $detail = $t['status'] === 'ok'
                ? "{$t['rows']} ligne" . ($t['rows'] > 1 ? 's' : '')
                : ($t['status'] === 'missing' ? 'TABLE ABSENTE' : ($t['msg'] ?? 'Erreur'));
            $tablesHtml .= "<div style='display:flex;align-items:center;justify-content:space-between;"
                . "padding:5px 8px;border-radius:6px;margin-bottom:4px;background:{$c['bg']};'>"
                . "<span style='font-weight:600;color:{$c['text']};font-size:11px;'>{$icon} {$t['name']}</span>"
                . "<span style='font-size:10px;color:{$c['text']};'>{$detail}</span>"
                . "</div>";
        }
        if (!$tablesHtml) $tablesHtml = "<div style='color:#94a3b8;font-size:11px;'>Aucune table déclarée</div>";

        // Endpoints HTML — body via data-body (evite conflit guillemets dans onclick)
        $epHtml = '';
        foreach ($epList as $ep) {
            $bodyAttr = htmlspecialchars(json_encode($ep['body']), ENT_QUOTES, 'UTF-8');
            $epHtml .= "<div style='display:flex;align-items:center;justify-content:space-between;"
                . "padding:5px 8px;border-radius:6px;margin-bottom:4px;background:#f8fafc;border:1px solid #e2e8f0;'>"
                . "<span style='font-size:11px;font-weight:600;color:#334155;'>{$ep['label']}</span>"
                . "<div style='display:flex;align-items:center;gap:6px;'>"
                . "<span id='{$ep['id']}_badge' style='font-size:10px;color:#94a3b8;'>—</span>"
                . "<button class='pdbg-test-btn'"
                . " data-id='{$ep['id']}'"
                . " data-url='{$ep['url']}'"
                . " data-body='{$bodyAttr}'"
                . " data-method='{$ep['method']}'"
                . " style='font-size:10px;padding:2px 8px;border-radius:4px;border:1px solid #cbd5e1;"
                . "background:#fff;cursor:pointer;color:#334155;'>Tester</button>"
                . "</div></div>";
        }
        if (!$epHtml) $epHtml = "<div style='color:#94a3b8;font-size:11px;'>Aucun endpoint déclaré</div>";

        $errCount  = count(array_filter($logLines, fn($l) =>
            str_contains($l, 'Fatal') || str_contains($l, 'Parse') || str_contains($l, 'SQLSTATE')
        ));
        $warnCount = count($logLines) - $errCount;

        // Badges compteurs
        $badgesHtml = '';
        if ($errCount  > 0) $badgesHtml .= "<span style='background:#ef4444;color:#fff;font-size:10px;padding:1px 6px;border-radius:99px;'>{$errCount} err</span>";
        if ($warnCount > 0) $badgesHtml .= "<span style='background:#f59e0b;color:#fff;font-size:10px;padding:1px 6px;border-radius:99px;'>{$warnCount} warn</span>";

        // Tabs buttons
        $tabsHtml = '';
        foreach (['db' => '🗄 DB', 'endpoints' => '⚡ API', 'log' => '📋 Log'] as $tab => $lbl) {
            $bg  = $tab === 'db' ? '#f8fafc' : '#fff';
            $bdr = $tab === 'db' ? '2px solid #6366f1' : '2px solid transparent';
            $tabsHtml .= "<button onclick=\"pdbgTab('{$uid}','{$tab}')\" id=\"{$uid}_tab_{$tab}\""
                . " style='flex:1;padding:8px 4px;border:none;background:{$bg};"
                . "font-size:11px;font-weight:700;cursor:pointer;color:#334155;"
                . "border-bottom:{$bdr};transition:all .15s;'>{$lbl}</button>";
        }

        echo '<!-- ── PageDebugger ── -->' . "\n";
        echo '<div id="' . $uid . '_wrap" style="position:fixed;bottom:20px;right:20px;z-index:99999;'
            . 'font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;width:360px;">' . "\n";

        // Toggle button
        echo '<button id="' . $uid . '_toggle"'
            . ' onclick="var p=document.getElementById(\'' . $uid . '_panel\');p.style.display=p.style.display===\'none\'?\'block\':\'none\'"'
            . ' style="width:100%;padding:8px 14px;background:#1e293b;color:#fff;border:none;border-radius:10px;cursor:pointer;'
            . 'display:flex;align-items:center;justify-content:space-between;'
            . 'font-size:12px;font-weight:700;letter-spacing:.03em;box-shadow:0 4px 16px rgba(0,0,0,.25);">'
            . '<span>🔧 PageDebugger</span>'
            . '<span style="display:flex;gap:6px;align-items:center;">'
            . '<span style="background:' . $dbColor['dot'] . ';width:8px;height:8px;border-radius:50%;display:inline-block;"></span>DB'
            . $badgesHtml
            . '</span></button>' . "\n";

        // Panel
        echo '<div id="' . $uid . '_panel" style="display:none;margin-top:6px;">'
            . '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.15);">'
            . '<div style="display:flex;border-bottom:1px solid #e2e8f0;">' . $tabsHtml . '</div>'

            // Tab DB
            . '<div id="' . $uid . '_content_db" style="padding:12px;">'
            . '<div style="display:flex;align-items:center;justify-content:space-between;'
            . 'padding:6px 10px;border-radius:7px;margin-bottom:10px;background:' . $dbColor['bg'] . ';">'
            . '<span style="font-weight:700;color:' . $dbColor['text'] . ';">' . $dbIcon . ' Connexion MySQL</span>'
            . '<span style="font-size:11px;color:' . $dbColor['text'] . ';">' . $dbMessage . '</span>'
            . '</div>' . $tablesHtml . '</div>'

            // Tab Endpoints
            . '<div id="' . $uid . '_content_endpoints" style="padding:12px;display:none;">'
            . $epHtml
            . '<div id="' . $uid . '_ep_result" style="margin-top:8px;font-size:11px;color:#64748b;"></div>'
            . '</div>'

            // Tab Log
            . '<div id="' . $uid . '_content_log" style="padding:12px;display:none;">'
            . '<div style="background:#0f172a;border-radius:7px;padding:10px;max-height:220px;overflow-y:auto;'
            . 'font-family:\'Courier New\',monospace;font-size:10px;line-height:1.7;">' . $logHtml . '</div>'
            . '<div style="margin-top:6px;font-size:10px;color:#94a3b8;text-align:right;">'
            . $errCount . ' erreur(s) · ' . $warnCount . ' warning(s) · 15 dernières lignes</div>'
            . '</div>'

            . '</div></div>' . "\n";

        echo '</div>' . "\n";

        echo '<script>
function pdbgTab(uid,tab){
    ["db","endpoints","log"].forEach(t=>{
        document.getElementById(uid+"_content_"+t).style.display=t===tab?"block":"none";
        var b=document.getElementById(uid+"_tab_"+t);
        b.style.background=t===tab?"#f8fafc":"#fff";
        b.style.borderBottom=t===tab?"2px solid #6366f1":"2px solid transparent";
    });
}

// Delegation click sur boutons Tester (evite probleme echappement onclick)
document.addEventListener("click", function(e){
    var btn = e.target.closest(".pdbg-test-btn");
    if (!btn) return;
    var id     = btn.getAttribute("data-id");
    var url    = btn.getAttribute("data-url");
    var method = btn.getAttribute("data-method") || "POST";
    var body   = {};
    try { body = JSON.parse(btn.getAttribute("data-body")); } catch(err){}
    pdbgTestEp(id, url, body, method);
});

async function pdbgTestEp(id,url,body,method){
    var badge=document.getElementById(id+"_badge");
    if(!badge){console.error("[PageDebugger] badge introuvable:",id);return;}
    badge.textContent="…";badge.style.color="#6366f1";
    try{
        var opts={method:method,credentials:"include",headers:{}};
        if(method!=="GET"&&body&&Object.keys(body).length){
            
            var fd=new FormData(); Object.keys(body).forEach(function(k){fd.append(k,String(body[k]));}); opts.body=fd;
        }
        var res=await fetch(url,opts);
        var text=await res.text();
        var json=null;try{json=JSON.parse(text);}catch(e){}
        if(res.status===200&&json&&json.success!==false){badge.textContent="✓ "+res.status;badge.style.color="#10b981";}
        else if(json&&json.success===false){badge.textContent="⚠ "+(json.message||json.error||res.status);badge.style.color="#f59e0b";}
        else if(res.status===403){badge.textContent="✗ 403";badge.style.color="#ef4444";}
        else if(res.status===404){badge.textContent="✗ 404";badge.style.color="#ef4444";}
        else{badge.textContent="⚠ "+res.status+" "+text.substring(0,50);badge.style.color="#f59e0b";}
    }catch(e){
        badge.textContent="✗ "+e.message;
        badge.style.color="#ef4444";
        console.error("[PageDebugger] erreur fetch:",e,"url:",url);
    }
}
</script>
<!-- ── /PageDebugger ── -->' . "\n";
    }
}