<?php
/**
 * tabs — Journal éditorial
 * Canal : Blog / Articles SEO
 */
if (!defined('ADMIN_ROUTER')) { http_response_code(403); exit; }

$journal_channel      = 'blog';
$journal_module_label = 'Blog / Articles SEO';

require_once __DIR__ . '/../../../ai/journal/journal-widget.php';
