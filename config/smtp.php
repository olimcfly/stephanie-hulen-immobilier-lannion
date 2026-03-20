<?php
/**
 * Configuration SMTP / IMAP
 * config/smtp.php
 *
 * Charge par loadSmtpConfig() dans :
 *   - admin/modules/system/modules.php
 *   - admin/api/marketing/emails.php
 */

require_once __DIR__ . '/../includes/functions/env.php';

return [
    // SMTP (envoi)
    'smtp_host'      => env('SMTP_HOST', ''),
    'smtp_port'      => (int) env('SMTP_PORT', 465),
    'smtp_secure'    => env('SMTP_SECURE', 'ssl'),
    'smtp_user'      => env('SMTP_USER', ''),
    'smtp_pass'      => env('SMTP_PASS', ''),
    'smtp_from'      => env('SMTP_FROM', ''),
    'smtp_from_name' => env('SMTP_FROM_NAME', ''),

    // IMAP (reception)
    'imap_host'   => env('IMAP_HOST', ''),
    'imap_port'   => (int) env('IMAP_PORT', 993),
    'imap_secure' => env('IMAP_SECURE', 'ssl'),
    'imap_user'   => env('IMAP_USER', ''),
    'imap_pass'   => env('IMAP_PASS', ''),

    // Comptes email du domaine
    'email_accounts' => [
        'admin@eduardo-desul-immobilier.fr',
        'contact@eduardo-desul-immobilier.fr',
        'info@eduardo-desul-immobilier.fr',
        'estimation@eduardo-desul-immobilier.fr',
        'guide@eduardo-desul-immobilier.fr',
        'support@eduardo-desul-immobilier.fr',
        'ne-pas-repondre@eduardo-desul-immobilier.fr',
        'bounce@eduardo-desul-immobilier.fr',
        'replit2@eduardo-desul-immobilier.fr',
    ],

    // Alias .com
    'email_aliases' => [
        'contact@eduardo-desul-immobilier.com',
        'info@eduardo-desul-immobilier.com',
        'support@eduardo-desul-immobilier.com',
        'ne-pas-repondre@eduardo-desul-immobilier.com',
        'bounce@eduardo-desul-immobilier.com',
    ],

    // Roles
    'email_roles' => [
        'primary' => 'contact@eduardo-desul-immobilier.fr',
        'system'  => 'ne-pas-repondre@eduardo-desul-immobilier.fr',
        'support' => 'support@eduardo-desul-immobilier.fr',
        'bounce'  => 'bounce@eduardo-desul-immobilier.fr',
    ],
];
