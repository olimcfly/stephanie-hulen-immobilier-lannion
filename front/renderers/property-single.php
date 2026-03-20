<?php
/**
 * Renderer — Property Single
 * Dispatches to template t11-bien-single.php
 * Called from front/page.php with $db and $slug available
 */

$pdo      = $db ?? $pdo ?? null;
$editMode = !empty($_GET['edit_mode']) && !empty($_GET['edit_token']);
$fields   = [];
$advisor  = [];
$site     = [];

// Build advisor array
if ($pdo) {
    try {
        $keys = ['advisor_name','advisor_phone','advisor_email','advisor_city','advisor_address','advisor_card'];
        $in   = implode(',', array_fill(0, count($keys), '?'));
        $st   = $pdo->prepare("SELECT field_key, field_value FROM advisor_context WHERE field_key IN ($in) LIMIT " . count($keys));
        $st->execute($keys);
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $k = preg_replace('/^advisor_/', '', $row['field_key']);
            $advisor[$k] = $row['field_value'];
        }
    } catch (Exception $e) {}

    if (empty($advisor['name'])) {
        try {
            $st = $pdo->prepare("SELECT key_name, value FROM settings WHERE key_name IN ('site_name','phone','email_support','address') LIMIT 4");
            $st->execute();
            $map = ['site_name'=>'name','phone'=>'phone','email_support'=>'email','address'=>'address'];
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                $k = $map[$row['key_name']] ?? $row['key_name'];
                if (empty($advisor[$k])) $advisor[$k] = $row['value'];
            }
        } catch (Exception $e) {}
    }
}

// Header / Footer
$headerData = null;
$footerData = null;
if ($pdo) {
    if (function_exists('getHeaderFooter')) {
        $hf = getHeaderFooter($pdo, $slug ?? '');
        $headerData = $hf['header'] ?? null;
        $footerData = $hf['footer'] ?? null;
    } else {
        foreach (['headers','site_headers'] as $tbl) {
            try {
                $h = $pdo->query("SELECT * FROM `$tbl` WHERE status='active' ORDER BY is_default DESC, id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($h) { $headerData = $h; break; }
            } catch (Exception $e) {}
        }
        foreach (['footers','site_footers'] as $tbl) {
            try {
                $f = $pdo->query("SELECT * FROM `$tbl` WHERE status='active' ORDER BY is_default DESC, id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($f) { $footerData = $f; break; }
            } catch (Exception $e) {}
        }
    }
}

$bien_slug = $slug ?? $_GET['slug'] ?? '';

$tplFile = __DIR__ . '/../templates/pages/t11-bien-single.php';
if (file_exists($tplFile)) {
    include $tplFile;
} else {
    echo '<h1>Template introuvable</h1>';
}
