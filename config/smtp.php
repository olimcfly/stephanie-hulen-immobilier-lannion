<?php
/**
 * Configuration SMTP / IMAP
 * config/smtp.php
 *
 * Charge par loadSmtpConfig() dans :
 *   - admin/modules/system/modules.php
 *   - admin/api/marketing/emails.php
 */

return [
    // SMTP (envoi)
    'smtp_host'      => 'eduardo-desul-immobilier.fr',
    'smtp_port'      => 465,
    'smtp_secure'    => 'ssl',
    'smtp_user'      => 'admin@eduardo-desul-immobilier.fr',
    'smtp_pass'      => 'JQP_4}J)dNIy',
    'smtp_from'      => 'contact@eduardo-desul-immobilier.fr',
    'smtp_from_name' => 'Eduardo De Sul Immobilier',

    // IMAP (reception)
    'imap_host'   => 'eduardo-desul-immobilier.fr',
    'imap_port'   => 993,
    'imap_secure' => 'ssl',
    'imap_user'   => 'admin@eduardo-desul-immobilier.fr',
    'imap_pass'   => 'JQP_4}J)dNIy',

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
