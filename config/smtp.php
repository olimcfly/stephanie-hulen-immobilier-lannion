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
    'smtp_host'      => 'stephanie-hulen-immobilier-lannion.fr',
    'smtp_port'      => 465,
    'smtp_secure'    => 'ssl',
    'smtp_user'      => 'admin@stephanie-hulen-immobilier-lannion.fr',
    'smtp_pass'      => 'JQP_4}J)dNIy',
    'smtp_from'      => 'contact@stephanie-hulen-immobilier-lannion.fr',
    'smtp_from_name' => 'Stephanie Hulen Immobilier Lannion',

    // IMAP (reception)
    'imap_host'   => 'stephanie-hulen-immobilier-lannion.fr',
    'imap_port'   => 993,
    'imap_secure' => 'ssl',
    'imap_user'   => 'admin@stephanie-hulen-immobilier-lannion.fr',
    'imap_pass'   => 'JQP_4}J)dNIy',

    // Comptes email du domaine
    'email_accounts' => [
        'admin@stephanie-hulen-immobilier-lannion.fr',
        'contact@stephanie-hulen-immobilier-lannion.fr',
        'info@stephanie-hulen-immobilier-lannion.fr',
        'estimation@stephanie-hulen-immobilier-lannion.fr',
        'guide@stephanie-hulen-immobilier-lannion.fr',
        'support@stephanie-hulen-immobilier-lannion.fr',
        'ne-pas-repondre@stephanie-hulen-immobilier-lannion.fr',
        'bounce@stephanie-hulen-immobilier-lannion.fr',
        'replit2@stephanie-hulen-immobilier-lannion.fr',
    ],

    // Alias .com
    'email_aliases' => [
        'contact@stephanie-hulen-immobilier-lannion.com',
        'info@stephanie-hulen-immobilier-lannion.com',
        'support@stephanie-hulen-immobilier-lannion.com',
        'ne-pas-repondre@stephanie-hulen-immobilier-lannion.com',
        'bounce@stephanie-hulen-immobilier-lannion.com',
    ],

    // Roles
    'email_roles' => [
        'primary' => 'contact@stephanie-hulen-immobilier-lannion.fr',
        'system'  => 'ne-pas-repondre@stephanie-hulen-immobilier-lannion.fr',
        'support' => 'support@stephanie-hulen-immobilier-lannion.fr',
        'bounce'  => 'bounce@stephanie-hulen-immobilier-lannion.fr',
    ],
];
