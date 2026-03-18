<?php
/**
 * tabs — Journal éditorial
 * Canal : Google My Business
 */
if (!defined('ADMIN_ROUTER')) { http_response_code(403); exit; }

$journal_channel      = 'gmb';
$journal_module_label = 'Google My Business';

require_once __DIR__ . '/../../../ai/journal/journal-widget.php';
