<?php
/**
 * Journal Blog — Onglet journal integre au module Articles
 * Fichier : admin/modules/articles/tabs/journal.php
 *
 * Inclusion dans articles/index.php :
 *   case 'journal': include __DIR__ . '/tabs/journal.php'; break;
 *
 * Sidebar : articles-journal → module articles, tab journal
 */

$journal_channel       = 'blog';
$journal_channel_label = 'Blog / Articles SEO';
$journal_channel_icon  = 'fas fa-pen-fancy';
$journal_channel_color = '#2c3e50';
$journal_create_url    = '?page=articles&action=create';

// Types de contenu specifiques au blog
$journal_content_types = ['article-pilier', 'article-satellite', 'lead-magnet'];

include __DIR__ . '/../../journal/journal-widget.php';