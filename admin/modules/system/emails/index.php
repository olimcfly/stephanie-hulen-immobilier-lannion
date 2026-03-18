<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE EMAILS — Gestion des templates v1.0
 *  /admin/modules/system/emails/index.php
 *  Acces : dashboard.php?page=system/emails
 *
 *  Fonctionnalites :
 *  - Liste tous les templates emails du systeme
 *  - Editeur de template avec variables dynamiques
 *  - Assistant IA (logo discret dans chaque champ)
 *  - Preview HTML en iframe temps reel
 *  - Envoi email de test avec rapport de livraison
 *  - SMTP herite de settings
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);

ob_start();

$rootPath = dirname(__DIR__, 4);
if (!defined('DB_HOST'))       require_once $rootPath . '/config/config.php';
if (!class_exists('Database')) require_once $rootPath . '/includes/classes/Database.php';

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    ob_end_clean();
    die('<div style="padding:20px;color:#dc2626;font-family:monospace">DB: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// ── Créer table email_templates si absente ──────────────────
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `email_templates` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `slug`        VARCHAR(80) NOT NULL UNIQUE,
        `category`    VARCHAR(40) NOT NULL DEFAULT 'general',
        `label`       VARCHAR(120) NOT NULL,
        `description` TEXT,
        `subject`     VARCHAR(255) NOT NULL DEFAULT '',
        `body_html`   LONGTEXT,
        `body_text`   TEXT,
        `variables`   TEXT COMMENT 'JSON list of available variables',
        `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
        `is_custom`   TINYINT(1) NOT NULL DEFAULT 0,
        `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// ── Catalogue par défaut (seeded si absents) ────────────────
$defaultTemplates = [

    // LEADS & CAPTURES
    'lead_welcome' => [
        'category'    => 'leads',
        'label'       => 'Accueil nouveau lead',
        'description' => 'Envoyé automatiquement à chaque nouveau contact entrant (formulaire contact, capture page)',
        'subject'     => 'Bonjour {{prenom}}, votre demande a bien été reçue',
        'variables'   => ['{{prenom}}','{{nom}}','{{email}}','{{telephone}}','{{message}}','{{source_page}}','{{date}}','{{conseiller_nom}}','{{conseiller_telephone}}','{{site_url}}'],
        'body_html'   => <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Georgia,serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">
  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#1a4d7a,#0f3560);padding:32px 40px;text-align:center">
    <p style="margin:0;color:#d4a574;font-size:11px;letter-spacing:3px;text-transform:uppercase;font-family:Arial,sans-serif">Conseiller Immobilier</p>
    <h1 style="margin:8px 0 0;color:#ffffff;font-size:26px;font-weight:700;letter-spacing:-0.5px">{{conseiller_nom}}</h1>
  </td></tr>
  <!-- Body -->
  <tr><td style="padding:40px">
    <p style="margin:0 0 16px;color:#57534e;font-size:14px;line-height:1.6">Bonjour <strong style="color:#1a1816">{{prenom}}</strong>,</p>
    <p style="margin:0 0 16px;color:#57534e;font-size:14px;line-height:1.6">Merci pour votre message. Votre demande a bien été enregistrée et je vous contacterai dans les plus brefs délais.</p>
    <div style="background:#fdf8f3;border-left:3px solid #d4a574;border-radius:0 8px 8px 0;padding:16px 20px;margin:24px 0">
      <p style="margin:0;font-size:13px;color:#57534e;font-style:italic">« {{message}} »</p>
    </div>
    <p style="margin:0 0 8px;color:#57534e;font-size:14px;line-height:1.6">En attendant, n'hésitez pas à me contacter directement :</p>
    <p style="margin:0;font-size:14px;color:#1a4d7a;font-weight:600">📞 {{conseiller_telephone}}</p>
  </td></tr>
  <!-- Footer -->
  <tr><td style="background:#f9f6f3;padding:20px 40px;border-top:1px solid #e8e6e1;text-align:center">
    <p style="margin:0;font-size:11px;color:#9ca3af">© {{date}} · {{conseiller_nom}} · <a href="{{site_url}}" style="color:#1a4d7a">{{site_url}}</a></p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML,
    ],

    'lead_rdv_confirm' => [
        'category'    => 'leads',
        'label'       => 'Confirmation RDV',
        'description' => 'Confirmation automatique après prise de rendez-vous',
        'subject'     => 'Votre rendez-vous est confirmé — {{date_rdv}}',
        'variables'   => ['{{prenom}}','{{nom}}','{{date_rdv}}','{{heure_rdv}}','{{lieu_rdv}}','{{type_rdv}}','{{conseiller_nom}}','{{conseiller_telephone}}','{{lien_annulation}}'],
        'body_html'   => <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Georgia,serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">
  <tr><td style="background:linear-gradient(135deg,#1a4d7a,#0f3560);padding:32px 40px;text-align:center">
    <p style="margin:0;color:#d4a574;font-size:11px;letter-spacing:3px;text-transform:uppercase;font-family:Arial,sans-serif">Rendez-vous confirmé</p>
    <h1 style="margin:8px 0 0;color:#fff;font-size:24px;font-weight:700">{{conseiller_nom}}</h1>
  </td></tr>
  <tr><td style="padding:40px">
    <p style="margin:0 0 24px;color:#57534e;font-size:14px">Bonjour <strong>{{prenom}}</strong>, votre rendez-vous est bien confirmé.</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f6f3;border-radius:10px;padding:20px;margin-bottom:24px">
      <tr><td style="padding:8px 0;border-bottom:1px solid #e8e6e1">
        <span style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:1px">Date</span><br>
        <strong style="color:#1a1816;font-size:15px">{{date_rdv}}</strong>
      </td></tr>
      <tr><td style="padding:8px 0;border-bottom:1px solid #e8e6e1">
        <span style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:1px">Heure</span><br>
        <strong style="color:#1a1816;font-size:15px">{{heure_rdv}}</strong>
      </td></tr>
      <tr><td style="padding:8px 0">
        <span style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:1px">Lieu</span><br>
        <strong style="color:#1a1816;font-size:15px">{{lieu_rdv}}</strong>
      </td></tr>
    </table>
    <p style="margin:0;font-size:12px;color:#9ca3af;text-align:center">
      <a href="{{lien_annulation}}" style="color:#dc2626">Annuler ou reporter ce rendez-vous</a>
    </p>
  </td></tr>
  <tr><td style="background:#f9f6f3;padding:20px 40px;border-top:1px solid #e8e6e1;text-align:center">
    <p style="margin:0;font-size:11px;color:#9ca3af">{{conseiller_nom}} · {{conseiller_telephone}}</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML,
    ],

    // ESTIMATIONS
    'estimation_receipt' => [
        'category'    => 'estimations',
        'label'       => 'Reçu demande d\'estimation',
        'description' => 'Envoyé au propriétaire après soumission du formulaire d\'estimation',
        'subject'     => 'Votre demande d\'avis de valeur pour {{adresse_bien}}',
        'variables'   => ['{{prenom}}','{{adresse_bien}}','{{type_bien}}','{{surface}}','{{nb_pieces}}','{{conseiller_nom}}','{{conseiller_telephone}}','{{date}}','{{delai_retour}}'],
        'body_html'   => <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Georgia,serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">
  <tr><td style="background:linear-gradient(135deg,#1a4d7a,#0f3560);padding:32px 40px;text-align:center">
    <p style="margin:0;color:#d4a574;font-size:11px;letter-spacing:3px;text-transform:uppercase;font-family:Arial,sans-serif">Avis de valeur</p>
    <h1 style="margin:8px 0 0;color:#fff;font-size:24px">{{conseiller_nom}}</h1>
  </td></tr>
  <tr><td style="padding:40px">
    <p style="margin:0 0 16px;color:#57534e;font-size:14px">Bonjour <strong>{{prenom}}</strong>,</p>
    <p style="margin:0 0 24px;color:#57534e;font-size:14px;line-height:1.6">
      Votre demande d'avis de valeur pour le bien situé <strong style="color:#1a4d7a">{{adresse_bien}}</strong> a bien été enregistrée.
      Je reviendrai vers vous sous <strong>{{delai_retour}}</strong> avec une estimation personnalisée.
    </p>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f6f3;border-radius:10px;padding:20px;margin-bottom:24px">
      <tr><td width="50%" style="padding:8px 12px;vertical-align:top">
        <span style="font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:1px">Type de bien</span><br>
        <strong style="color:#1a1816">{{type_bien}}</strong>
      </td><td width="50%" style="padding:8px 12px;vertical-align:top">
        <span style="font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:1px">Surface</span><br>
        <strong style="color:#1a1816">{{surface}} m²</strong>
      </td></tr>
    </table>
    <p style="margin:0;font-size:14px;color:#1a4d7a;font-weight:600;text-align:center">📞 {{conseiller_telephone}}</p>
  </td></tr>
  <tr><td style="background:#f9f6f3;padding:20px 40px;border-top:1px solid #e8e6e1;text-align:center">
    <p style="margin:0;font-size:11px;color:#9ca3af">{{conseiller_nom}} · {{date}}</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML,
    ],

    'estimation_delivery' => [
        'category'    => 'estimations',
        'label'       => 'Livraison avis de valeur',
        'description' => 'Email envoyé avec le rapport d\'estimation finalisé',
        'subject'     => 'Votre avis de valeur — {{adresse_bien}}',
        'variables'   => ['{{prenom}}','{{adresse_bien}}','{{fourchette_basse}}','{{fourchette_haute}}','{{prix_moyen}}','{{prix_m2}}','{{conseiller_nom}}','{{conseiller_telephone}}','{{lien_rapport}}','{{date}}'],
        'body_html'   => <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Georgia,serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">
  <tr><td style="background:linear-gradient(135deg,#1a4d7a,#0f3560);padding:32px 40px;text-align:center">
    <p style="margin:0;color:#d4a574;font-size:11px;letter-spacing:3px;text-transform:uppercase;font-family:Arial,sans-serif">Votre estimation est prête</p>
    <h1 style="margin:8px 0 0;color:#fff;font-size:24px">{{adresse_bien}}</h1>
  </td></tr>
  <tr><td style="padding:40px">
    <p style="margin:0 0 24px;color:#57534e;font-size:14px">Bonjour <strong>{{prenom}}</strong>, voici l'avis de valeur pour votre bien.</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#1a4d7a,#0f3560);border-radius:10px;padding:24px;margin-bottom:24px;text-align:center">
      <tr><td>
        <p style="margin:0;color:rgba(255,255,255,.7);font-size:11px;letter-spacing:2px;text-transform:uppercase">Fourchette estimée</p>
        <p style="margin:8px 0 0;color:#d4a574;font-size:28px;font-weight:900;letter-spacing:-1px">{{fourchette_basse}} — {{fourchette_haute}}</p>
        <p style="margin:6px 0 0;color:rgba(255,255,255,.6);font-size:12px">Soit {{prix_m2}} / m² · Prix central estimé : <strong style="color:#fff">{{prix_moyen}}</strong></p>
      </td></tr>
    </table>
    <p style="margin:0 0 16px;text-align:center">
      <a href="{{lien_rapport}}" style="display:inline-block;background:#d4a574;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-size:13px;font-weight:700">
        Consulter le rapport complet →
      </a>
    </p>
    <p style="margin:24px 0 0;font-size:14px;color:#1a4d7a;font-weight:600;text-align:center">📞 {{conseiller_telephone}}</p>
  </td></tr>
  <tr><td style="background:#f9f6f3;padding:20px 40px;border-top:1px solid #e8e6e1;text-align:center">
    <p style="margin:0;font-size:11px;color:#9ca3af">{{conseiller_nom}} · {{date}}</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML,
    ],

    // MANDATS
    'mandat_new' => [
        'category'    => 'mandats',
        'label'       => 'Nouveau mandat signé',
        'description' => 'Confirmation de signature de mandat au propriétaire vendeur',
        'subject'     => 'Votre mandat de vente — {{adresse_bien}} — est enregistré',
        'variables'   => ['{{prenom}}','{{adresse_bien}}','{{type_mandat}}','{{prix_mandat}}','{{date_signature}}','{{duree_mandat}}','{{date_expiration}}','{{conseiller_nom}}','{{conseiller_telephone}}'],
        'body_html'   => <<<HTML
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Georgia,serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">
  <tr><td style="background:linear-gradient(135deg,#1a4d7a,#0f3560);padding:32px 40px;text-align:center">
    <p style="margin:0;color:#d4a574;font-size:11px;letter-spacing:3px;text-transform:uppercase">Mandat signé</p>
    <h1 style="margin:8px 0 0;color:#fff;font-size:22px">{{adresse_bien}}</h1>
  </td></tr>
  <tr><td style="padding:40px">
    <p style="margin:0 0 24px;color:#57534e;font-size:14px;line-height:1.6">
      Bonjour <strong>{{prenom}}</strong>, votre mandat de vente <strong>{{type_mandat}}</strong> a bien été enregistré. Je me mobilise pour vendre votre bien dans les meilleures conditions.
    </p>
    <table width="100%" style="border-collapse:collapse;margin-bottom:24px">
      <tr style="background:#f9f6f3"><td style="padding:10px 14px;font-size:11px;color:#9ca3af;text-transform:uppercase">Prix de vente</td><td style="padding:10px 14px;font-size:14px;font-weight:700;color:#1a1816">{{prix_mandat}}</td></tr>
      <tr><td style="padding:10px 14px;font-size:11px;color:#9ca3af;text-transform:uppercase">Signé le</td><td style="padding:10px 14px;font-size:14px;font-weight:700;color:#1a1816">{{date_signature}}</td></tr>
      <tr style="background:#f9f6f3"><td style="padding:10px 14px;font-size:11px;color:#9ca3af;text-transform:uppercase">Expire le</td><td style="padding:10px 14px;font-size:14px;font-weight:700;color:#1a1816">{{date_expiration}}</td></tr>
    </table>
  </td></tr>
  <tr><td style="background:#f9f6f3;padding:20px 40px;border-top:1px solid #e8e6e1;text-align:center">
    <p style="margin:0;font-size:11px;color:#9ca3af">{{conseiller_nom}} · {{conseiller_telephone}}</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML,
    ],

    // NEWSLETTER & MARKETING
    'newsletter_monthly' => [
        'category'    => 'marketing',
        'label'       => 'Newsletter mensuelle',
        'description' => 'Template de newsletter mensuelle pour la base contacts',
        'subject'     => '📰 Actualités immobilières — {{mois_annee}} · {{conseiller_nom}}',
        'variables'   => ['{{prenom}}','{{mois_annee}}','{{titre_article_1}}','{{intro_article_1}}','{{lien_article_1}}','{{titre_article_2}}','{{intro_article_2}}','{{lien_article_2}}','{{tendance_marche}}','{{conseiller_nom}}','{{lien_desinscription}}'],
        'body_html'   => <<<HTML
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Georgia,serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">
  <tr><td style="background:linear-gradient(135deg,#1a4d7a,#0f3560);padding:32px 40px">
    <p style="margin:0;color:#d4a574;font-size:11px;letter-spacing:3px;text-transform:uppercase">Newsletter · {{mois_annee}}</p>
    <h1 style="margin:8px 0 0;color:#fff;font-size:22px">Le marché immobilier ce mois-ci</h1>
  </td></tr>
  <tr><td style="padding:32px 40px">
    <p style="margin:0 0 24px;color:#57534e;font-size:14px">Bonjour <strong>{{prenom}}</strong>,</p>
    <!-- Article 1 -->
    <table width="100%" style="border:1px solid #e8e6e1;border-radius:8px;overflow:hidden;margin-bottom:20px">
      <tr><td style="padding:20px">
        <p style="margin:0 0 8px;font-size:15px;font-weight:700;color:#1a1816">{{titre_article_1}}</p>
        <p style="margin:0 0 12px;font-size:13px;color:#57534e;line-height:1.6">{{intro_article_1}}</p>
        <a href="{{lien_article_1}}" style="color:#1a4d7a;font-size:12px;font-weight:600">Lire la suite →</a>
      </td></tr>
    </table>
    <!-- Article 2 -->
    <table width="100%" style="border:1px solid #e8e6e1;border-radius:8px;overflow:hidden;margin-bottom:20px">
      <tr><td style="padding:20px">
        <p style="margin:0 0 8px;font-size:15px;font-weight:700;color:#1a1816">{{titre_article_2}}</p>
        <p style="margin:0 0 12px;font-size:13px;color:#57534e;line-height:1.6">{{intro_article_2}}</p>
        <a href="{{lien_article_2}}" style="color:#1a4d7a;font-size:12px;font-weight:600">Lire la suite →</a>
      </td></tr>
    </table>
    <!-- Tendance marché -->
    <div style="background:#fdf8f3;border-left:3px solid #d4a574;padding:16px 20px;border-radius:0 8px 8px 0">
      <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#d4a574;text-transform:uppercase;letter-spacing:1px">Tendance du marché</p>
      <p style="margin:0;font-size:13px;color:#57534e;line-height:1.6">{{tendance_marche}}</p>
    </div>
  </td></tr>
  <tr><td style="background:#f9f6f3;padding:20px 40px;border-top:1px solid #e8e6e1;text-align:center">
    <p style="margin:0 0 8px;font-size:11px;color:#9ca3af">© {{conseiller_nom}}</p>
    <p style="margin:0;font-size:10px;color:#d1d5db">
      <a href="{{lien_desinscription}}" style="color:#9ca3af">Se désinscrire</a>
    </p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML,
    ],

    // SYSTÈME
    'admin_new_lead_notif' => [
        'category'    => 'systeme',
        'label'       => 'Notification admin — nouveau lead',
        'description' => 'Notification interne envoyée au conseiller à chaque nouveau lead',
        'subject'     => '🔔 Nouveau lead — {{prenom}} {{nom}} via {{source_page}}',
        'variables'   => ['{{prenom}}','{{nom}}','{{email}}','{{telephone}}','{{message}}','{{source_page}}','{{date}}','{{ip}}','{{lien_crm}}'],
        'body_html'   => <<<HTML
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#1e1b4b;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#1e1b4b;padding:40px 20px">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#2d2a52;border-radius:12px;overflow:hidden;border:1px solid #3730a3">
  <tr><td style="padding:24px 32px;border-bottom:1px solid #3730a3">
    <p style="margin:0;color:#818cf8;font-size:11px;letter-spacing:2px;text-transform:uppercase">Notification IMMO LOCAL+</p>
    <h1 style="margin:6px 0 0;color:#fff;font-size:20px;font-weight:800">🔔 Nouveau lead entrant</h1>
  </td></tr>
  <tr><td style="padding:24px 32px">
    <table width="100%" style="border-collapse:collapse">
      <tr><td style="padding:8px 0;border-bottom:1px solid #3730a3;color:#a5b4fc;font-size:11px;text-transform:uppercase;width:40%">Nom</td><td style="padding:8px 0;border-bottom:1px solid #3730a3;color:#fff;font-size:14px;font-weight:600">{{prenom}} {{nom}}</td></tr>
      <tr><td style="padding:8px 0;border-bottom:1px solid #3730a3;color:#a5b4fc;font-size:11px;text-transform:uppercase">Email</td><td style="padding:8px 0;border-bottom:1px solid #3730a3;color:#fff;font-size:14px"><a href="mailto:{{email}}" style="color:#818cf8">{{email}}</a></td></tr>
      <tr><td style="padding:8px 0;border-bottom:1px solid #3730a3;color:#a5b4fc;font-size:11px;text-transform:uppercase">Téléphone</td><td style="padding:8px 0;border-bottom:1px solid #3730a3;color:#fff;font-size:14px">{{telephone}}</td></tr>
      <tr><td style="padding:8px 0;border-bottom:1px solid #3730a3;color:#a5b4fc;font-size:11px;text-transform:uppercase">Source</td><td style="padding:8px 0;border-bottom:1px solid #3730a3;color:#fff;font-size:14px">{{source_page}}</td></tr>
      <tr><td style="padding:8px 0;color:#a5b4fc;font-size:11px;text-transform:uppercase">Message</td><td style="padding:8px 0;color:#e0e7ff;font-size:13px;font-style:italic">{{message}}</td></tr>
    </table>
    <div style="margin-top:20px;text-align:center">
      <a href="{{lien_crm}}" style="display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;padding:11px 24px;border-radius:8px;font-size:13px;font-weight:700">
        Voir dans le CRM →
      </a>
    </div>
  </td></tr>
  <tr><td style="padding:14px 32px;border-top:1px solid #3730a3;text-align:center">
    <p style="margin:0;font-size:10px;color:#6b7280">{{date}} · IP : {{ip}}</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML,
    ],

    'password_reset' => [
        'category'    => 'systeme',
        'label'       => 'Réinitialisation mot de passe',
        'description' => 'Envoyé à l\'admin pour réinitialiser son mot de passe',
        'subject'     => 'Réinitialisation de votre mot de passe — IMMO LOCAL+',
        'variables'   => ['{{prenom}}','{{lien_reset}}','{{expiration}}','{{ip}}'],
        'body_html'   => <<<HTML
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">
  <tr><td style="background:#1a1816;padding:24px 32px;text-align:center">
    <p style="margin:0;color:#d4a574;font-size:24px">🔐</p>
    <h1 style="margin:6px 0 0;color:#fff;font-size:20px">Réinitialisation mot de passe</h1>
  </td></tr>
  <tr><td style="padding:32px">
    <p style="margin:0 0 16px;color:#57534e;font-size:14px">Bonjour <strong>{{prenom}}</strong>,</p>
    <p style="margin:0 0 24px;color:#57534e;font-size:14px;line-height:1.6">
      Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour en définir un nouveau. Ce lien expire dans <strong>{{expiration}}</strong>.
    </p>
    <div style="text-align:center;margin-bottom:24px">
      <a href="{{lien_reset}}" style="display:inline-block;background:#1a4d7a;color:#fff;text-decoration:none;padding:13px 32px;border-radius:8px;font-size:14px;font-weight:700">
        Réinitialiser mon mot de passe
      </a>
    </div>
    <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center">
      Si vous n'avez pas fait cette demande, ignorez cet email.<br>
      Connexion depuis : {{ip}}
    </p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML,
    ],
];

// ── Seed les templates manquants en DB ──────────────────────
foreach ($defaultTemplates as $slug => $tpl) {
    try {
        $db->prepare("INSERT IGNORE INTO email_templates
            (slug, category, label, description, subject, body_html, variables, is_active, is_custom)
            VALUES (?,?,?,?,?,?,?,1,0)")
           ->execute([
               $slug,
               $tpl['category'],
               $tpl['label'],
               $tpl['description'],
               $tpl['subject'],
               $tpl['body_html'],
               json_encode($tpl['variables'] ?? []),
           ]);
    } catch (Exception $e) {}
}

// ── CSRF ────────────────────────────────────────────────────
$saveMsg = $saveErr = '';

// ── POST handler ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfOk = !empty($_SESSION['csrf_token']) && ($_POST['csrf_token'] ?? '') === $_SESSION['csrf_token'];

    if ($csrfOk) {
        $action = $_POST['em_action'] ?? '';

        // ── Sauvegarder template ──
        if ($action === 'save_template') {
            $slug    = preg_replace('/[^a-z0-9_]/', '', $_POST['slug'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $html    = $_POST['body_html'] ?? '';
            $active  = isset($_POST['is_active']) ? 1 : 0;
            if ($slug && $subject) {
                try {
                    $db->prepare("UPDATE email_templates SET subject=?, body_html=?, is_active=?, is_custom=1 WHERE slug=?")
                       ->execute([$subject, $html, $active, $slug]);
                    $saveMsg = 'Template "' . htmlspecialchars($slug) . '" sauvegardé.';
                } catch (Exception $e) { $saveErr = $e->getMessage(); }
            }
        }

        // ── Reset template par défaut ──
        if ($action === 'reset_template') {
            $slug = preg_replace('/[^a-z0-9_]/', '', $_POST['slug'] ?? '');
            if ($slug && isset($defaultTemplates[$slug])) {
                $tpl = $defaultTemplates[$slug];
                try {
                    $db->prepare("UPDATE email_templates SET subject=?, body_html=?, is_custom=0 WHERE slug=?")
                       ->execute([$tpl['subject'], $tpl['body_html'], $slug]);
                    $saveMsg = 'Template remis par défaut.';
                } catch (Exception $e) { $saveErr = $e->getMessage(); }
            }
        }

        // ── Envoyer email de test ──
        if ($action === 'send_test') {
            $slug    = preg_replace('/[^a-z0-9_]/', '', $_POST['slug'] ?? '');
            $testTo  = trim($_POST['test_email'] ?? '');
            $subject = trim($_POST['test_subject'] ?? 'Test email IMMO LOCAL+');
            $html    = $_POST['test_html'] ?? '';

            if (!$testTo) {
                $saveErr = 'Email destinataire requis pour le test.';
            } elseif (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
                $saveErr = 'Adresse email invalide.';
            } else {
                // Remplacer les variables par des valeurs de test
                $testVars = [
                    '{{prenom}}'             => 'Jean',
                    '{{nom}}'                => 'Dupont',
                    '{{email}}'              => $testTo,
                    '{{telephone}}'          => '06 12 34 56 78',
                    '{{message}}'            => 'Ceci est un message de test généré automatiquement.',
                    '{{source_page}}'        => 'Page d\'accueil (test)',
                    '{{date}}'               => date('d/m/Y à H:i'),
                    '{{date_rdv}}'           => date('d/m/Y', strtotime('+3 days')),
                    '{{heure_rdv}}'          => '14h30',
                    '{{lieu_rdv}}'           => '12A rue du test, 33000 Bordeaux',
                    '{{type_rdv}}'           => 'Visite de bien',
                    '{{adresse_bien}}'       => '12A rue de la Paix, Bordeaux',
                    '{{type_bien}}'          => 'Appartement T3',
                    '{{surface}}'            => '72',
                    '{{nb_pieces}}'          => '3',
                    '{{prix_mandat}}'        => '285 000 €',
                    '{{fourchette_basse}}'   => '265 000 €',
                    '{{fourchette_haute}}'   => '295 000 €',
                    '{{prix_moyen}}'         => '280 000 €',
                    '{{prix_m2}}'            => '3 900 €',
                    '{{type_mandat}}'        => 'Exclusif',
                    '{{date_signature}}'     => date('d/m/Y'),
                    '{{duree_mandat}}'       => '3 mois',
                    '{{date_expiration}}'    => date('d/m/Y', strtotime('+3 months')),
                    '{{delai_retour}}'       => '48 heures',
                    '{{mois_annee}}'         => date('F Y'),
                    '{{titre_article_1}}'    => 'Le marché immobilier bordelais au 1er trimestre',
                    '{{intro_article_1}}'    => 'Les prix se stabilisent dans la métropole avec des opportunités dans les quartiers périphériques...',
                    '{{lien_article_1}}'     => '#article-test-1',
                    '{{titre_article_2}}'    => 'Comment préparer son bien pour une vente rapide',
                    '{{intro_article_2}}'    => 'Quelques conseils pratiques pour maximiser la valeur de votre bien avant la mise en vente...',
                    '{{lien_article_2}}'     => '#article-test-2',
                    '{{tendance_marche}}'    => 'Le volume des transactions repart à la hausse sur Bordeaux Métropole. Les primo-accédants sont de retour grâce à la stabilisation des taux.',
                    '{{ip}}'                 => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    '{{lien_rapport}}'       => '#rapport-test',
                    '{{lien_crm}}'           => '#crm-test',
                    '{{lien_annulation}}'    => '#annulation-test',
                    '{{lien_reset}}'         => '#reset-test',
                    '{{expiration}}'         => '2 heures',
                    '{{lien_desinscription}}'=> '#desinscription-test',
                ];

                // Récupérer infos conseiller depuis settings
                try {
                    $stRows = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('advisor_name','advisor_telephone','advisor_phone','site_url')")->fetchAll();
                    foreach ($stRows as $r) {
                        if ($r['setting_key'] === 'advisor_name')      $testVars['{{conseiller_nom}}']       = $r['setting_value'];
                        if (in_array($r['setting_key'], ['advisor_phone','advisor_telephone'])) $testVars['{{conseiller_telephone}}'] = $r['setting_value'];
                        if ($r['setting_key'] === 'site_url')          $testVars['{{site_url}}']             = $r['setting_value'];
                    }
                } catch (Exception $e) {}
                $testVars['{{conseiller_nom}}']       = $testVars['{{conseiller_nom}}']       ?? 'Conseiller Test';
                $testVars['{{conseiller_telephone}}'] = $testVars['{{conseiller_telephone}}'] ?? '06 00 00 00 00';
                $testVars['{{site_url}}']             = $testVars['{{site_url}}']             ?? 'https://votresite.fr';

                $htmlFinal    = str_replace(array_keys($testVars), array_values($testVars), $html);
                $subjectFinal = str_replace(array_keys($testVars), array_values($testVars), $subject);

                // Récupérer SMTP depuis settings
                $smtp = ['host'=>'', 'port'=>587, 'user'=>'', 'pass'=>'', 'from_name'=>'IMMO LOCAL+ Test', 'from_email'=>''];
                try {
                    $smRows = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'")->fetchAll();
                    foreach ($smRows as $r) {
                        $k = str_replace('smtp_', '', $r['setting_key']);
                        if (isset($smtp[$k])) $smtp[$k] = $r['setting_value'];
                    }
                } catch (Exception $e) {}

                $sent = false; $sendErr = '';
                if ($smtp['host'] && $smtp['user']) {
                    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                        try {
                            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host       = $smtp['host'];
                            $mail->Port       = (int) $smtp['port'];
                            $mail->SMTPAuth   = true;
                            $mail->Username   = $smtp['user'];
                            $mail->Password   = $smtp['pass'];
                            $mail->SMTPSecure = ((int)$smtp['port'] === 465) ? 'ssl' : 'tls';
                            $mail->CharSet    = 'UTF-8';
                            $mail->setFrom($smtp['from_email'] ?: $smtp['user'], $smtp['from_name']);
                            $mail->addAddress($testTo);
                            $mail->Subject  = $subjectFinal;
                            $mail->isHTML(true);
                            $mail->Body     = $htmlFinal;
                            $mail->AltBody  = strip_tags($htmlFinal);
                            $mail->send();
                            $sent = true;
                        } catch (Exception $e) { $sendErr = $e->getMessage(); }
                    } else {
                        $headers  = "MIME-Version: 1.0\r\n";
                        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                        $headers .= "From: {$smtp['from_name']} <{$smtp['from_email']}>\r\n";
                        $sent = @mail($testTo, $subjectFinal, $htmlFinal, $headers);
                        if (!$sent) $sendErr = 'Échec mail() natif — configurez PHPMailer ou SMTP.';
                    }
                } else {
                    // Pas de SMTP → mail() natif basique
                    $headers  = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $sent = @mail($testTo, $subjectFinal, $htmlFinal, $headers);
                    $sendErr = $sent ? '' : 'Aucun SMTP configuré. Email envoyé via mail() natif.';
                }

                if ($sent) {
                    $saveMsg = "Email de test envoyé à {$testTo}" . ($sendErr ? " ($sendErr)" : '') . ".";
                } else {
                    $saveErr = "Échec envoi vers {$testTo}" . ($sendErr ? " : $sendErr" : '') . ".";
                }
            }
        }

        // ── Génération IA ──
        if ($action === 'ai_generate') {
            $slug    = preg_replace('/[^a-z0-9_]/', '', $_POST['slug'] ?? '');
            $field   = $_POST['field'] ?? '';   // subject | body_html
            $context = trim($_POST['ai_context'] ?? '');
            $apiKey  = '';

            // Récupérer clé Anthropic
            try { $apiKey = $db->query("SELECT setting_value FROM ai_settings WHERE setting_key='anthropic_api_key'")->fetchColumn(); } catch (Exception $e) {}
            if (!$apiKey && defined('ANTHROPIC_API_KEY')) $apiKey = ANTHROPIC_API_KEY;

            // Infos template courant
            $tplRow = null;
            try { $tplRow = $db->prepare("SELECT * FROM email_templates WHERE slug=?")->execute([$slug]) ? $db->prepare("SELECT * FROM email_templates WHERE slug=?")->execute([$slug]) : null; } catch (Exception $e) {}
            try { $s = $db->prepare("SELECT * FROM email_templates WHERE slug=?"); $s->execute([$slug]); $tplRow = $s->fetch(); } catch (Exception $e) {}

            // Infos conseiller
            $conseillerNom = 'le conseiller';
            try { $conseillerNom = $db->query("SELECT setting_value FROM settings WHERE setting_key='advisor_name'")->fetchColumn() ?: $conseillerNom; } catch (Exception $e) {}

            if ($apiKey && $tplRow) {
                if ($field === 'subject') {
                    $prompt = "Tu es un expert en email marketing immobilier. Génère UNIQUEMENT l'objet d'email (sujet) pour le template '". $tplRow['label'] ."' de '". htmlspecialchars($conseillerNom) ."', conseiller immobilier français. Contexte supplémentaire : {$context}. L'objet doit être accrocheur, personnalisé, inférieur à 60 caractères. Réponds en JSON : {\"subject\":\"...\"}";
                } else {
                    $prompt = "Tu es un expert en email marketing immobilier. Génère uniquement le corps HTML d'un email '". $tplRow['label'] ."' pour '". htmlspecialchars($conseillerNom) ."'. L'email doit être en français, professionnel, avec une mise en page HTML inline (tables, pas de CSS externe), palette navyblue #1a4d7a et or #d4a574, police Georgia pour les titres et Arial pour le corps. Variables disponibles : ". implode(', ', json_decode($tplRow['variables'] ?? '[]', true)) .". Contexte : {$context}. Réponds en JSON : {\"body_html\":\"...\"}";
                }

                $aiResp = '';
                try {
                    $ch = curl_init('https://api.anthropic.com/v1/messages');
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_HTTPHEADER     => [
                            'Content-Type: application/json',
                            'x-api-key: ' . $apiKey,
                            'anthropic-version: 2023-06-01',
                        ],
                        CURLOPT_POSTFIELDS => json_encode([
                            'model'      => 'claude-sonnet-4-6',
                            'max_tokens' => $field === 'subject' ? 200 : 3000,
                            'messages'   => [['role' => 'user', 'content' => $prompt]],
                        ]),
                    ]);
                    $raw = curl_exec($ch);
                    curl_close($ch);
                    $resp = json_decode($raw, true);
                    $text = $resp['content'][0]['text'] ?? '';
                    $clean = preg_replace('/^```json\s*|\s*```$/', '', trim($text));
                    $data = json_decode($clean, true);
                    if ($data) {
                        $aiResp = $data[$field] ?? '';
                        if ($aiResp) {
                            $db->prepare("UPDATE email_templates SET " . ($field === 'subject' ? 'subject' : 'body_html') . "=?, is_custom=1 WHERE slug=?")
                               ->execute([$aiResp, $slug]);
                            $saveMsg = 'IA : ' . ($field === 'subject' ? 'Objet' : 'Template HTML') . ' généré et sauvegardé.';
                        } else {
                            $saveErr = 'La réponse IA ne contient pas de champ valide.';
                        }
                    } else {
                        $saveErr = 'Réponse IA non parseable. Réessayez.';
                    }
                } catch (Exception $e) {
                    $saveErr = 'Erreur IA : ' . $e->getMessage();
                }
            } else {
                $saveErr = $apiKey ? 'Template introuvable.' : 'Clé API Anthropic non configurée (Paramètres → API).';
            }
        }
    } else {
        $saveErr = 'Token CSRF invalide.';
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_token'];
}

// ── Charger tous les templates ──────────────────────────────
$templates = [];
try {
    $rows = $db->query("SELECT * FROM email_templates ORDER BY category, label")->fetchAll();
    foreach ($rows as $r) $templates[$r['slug']] = $r;
} catch (Exception $e) {}

// ── Onglet / template actif ─────────────────────────────────
$activeSlug = preg_replace('/[^a-z0-9_]/', '', $_GET['template'] ?? '');
if (!$activeSlug || !isset($templates[$activeSlug])) {
    $activeSlug = array_key_first($templates) ?: '';
}

$categories = [
    'leads'       => ['label' => 'Leads & Contacts', 'icon' => 'fa-user-plus',       'color' => '#0891b2'],
    'estimations' => ['label' => 'Estimations',       'icon' => 'fa-calculator',      'color' => '#d97706'],
    'mandats'     => ['label' => 'Mandats',            'icon' => 'fa-file-signature',  'color' => '#059669'],
    'marketing'   => ['label' => 'Marketing',          'icon' => 'fa-bullhorn',         'color' => '#7c3aed'],
    'systeme'     => ['label' => 'Système',            'icon' => 'fa-server',           'color' => '#dc2626'],
];

// Stats
$totalTemplates  = count($templates);
$customTemplates = count(array_filter($templates, fn($t) => $t['is_custom']));
$activeTemplates = count(array_filter($templates, fn($t) => $t['is_active']));

// Récupérer email conseiller pour défaut
$defaultTestEmail = '';
try { $defaultTestEmail = $db->query("SELECT setting_value FROM settings WHERE setting_key='advisor_email'")->fetchColumn() ?: ''; } catch (Exception $e) {}

ob_end_clean();
?>

<style>
/* ══ Module Emails v1.0 — harmonisé layout ══════════════════ */

/* Layout deux colonnes */
.em-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 16px;
    align-items: start;
}

/* Sidebar liste templates */
.em-sidebar {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden;
    box-shadow: var(--shadow-sm); position: sticky; top: 16px;
}
.em-sidebar-hd {
    padding: 12px 16px; background: var(--surface-2);
    border-bottom: 1px solid var(--border);
    font-size: 10px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .08em; color: var(--text-3);
    display: flex; align-items: center; gap: 7px;
}
.em-cat-label {
    padding: 8px 16px 4px; font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .08em; color: var(--text-3);
    display: flex; align-items: center; gap: 6px;
}
.em-cat-dot { width: 6px; height: 6px; border-radius: 50%; }
.em-tpl-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px; cursor: pointer;
    border-left: 3px solid transparent;
    transition: all .12s; text-decoration: none; color: inherit;
}
.em-tpl-item:hover   { background: var(--surface-2); }
.em-tpl-item.active  { background: var(--accent-bg); border-left-color: var(--accent); }
.em-tpl-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; color: #fff; flex-shrink: 0;
}
.em-tpl-name { font-size: 11px; font-weight: 700; color: var(--text); line-height: 1.3; }
.em-tpl-cat  { font-size: 9px; color: var(--text-3); }
.em-tpl-badges { display: flex; gap: 4px; margin-top: 2px; flex-wrap: wrap; }

/* Zone éditeur principale */
.em-editor {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.em-editor-hd {
    padding: 16px 20px; background: var(--surface-2);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.em-editor-title { font-size: 14px; font-weight: 800; color: var(--text); flex: 1; min-width: 0; }
.em-editor-desc  { font-size: 11px; color: var(--text-2); margin-top: 2px; }
.em-editor-body  { padding: 20px; }

/* Tabs preview/edit */
.em-view-tabs { display: flex; gap: 3px; margin-bottom: 16px; background: var(--surface-2); padding: 4px; border-radius: var(--radius-lg); border: 1px solid var(--border); width: fit-content; }
.em-view-tab  { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: var(--radius); border: none; font-size: 11px; font-weight: 700; cursor: pointer; transition: all .14s; background: transparent; color: var(--text-3); font-family: var(--font); }
.em-view-tab:hover  { color: var(--accent); }
.em-view-tab.active { background: var(--accent); color: #fff; box-shadow: 0 2px 8px rgba(79,70,229,.25); }

/* Champ avec assistant IA */
.em-field { margin-bottom: 16px; }
.em-field-label {
    font-size: 9px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .08em; color: var(--text-3); margin-bottom: 6px;
    display: flex; align-items: center; justify-content: space-between;
}
.em-field-label-left { display: flex; align-items: center; gap: 6px; }
.em-input-wr { position: relative; }
.em-input, .em-textarea {
    width: 100%; padding: 9px 40px 9px 12px;
    border: 1.5px solid var(--border); border-radius: var(--radius);
    font-size: 12px; color: var(--text); background: var(--surface);
    transition: border .14s; outline: none; font-family: var(--font);
    box-sizing: border-box;
}
.em-textarea {
    resize: vertical; min-height: 360px;
    font-family: var(--mono); font-size: 11px; line-height: 1.6;
    padding-right: 12px;
}
.em-input:focus, .em-textarea:focus {
    border-color: var(--accent); box-shadow: 0 0 0 3px rgba(79,70,229,.08);
}

/* Bouton IA discret */
.em-ai-btn {
    position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
    width: 24px; height: 24px; border-radius: 6px;
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    border: none; cursor: pointer; display: flex; align-items: center;
    justify-content: center; font-size: 10px; color: #fff;
    transition: all .15s; opacity: .7;
    box-shadow: 0 1px 4px rgba(124,58,237,.3);
}
.em-ai-btn:hover { opacity: 1; transform: translateY(-50%) scale(1.08); }
.em-ai-btn .em-ai-tooltip {
    position: absolute; right: 30px; top: 50%; transform: translateY(-50%);
    background: #1a1816; color: #fff; font-size: 9px; font-weight: 700;
    padding: 3px 8px; border-radius: 4px; white-space: nowrap;
    opacity: 0; pointer-events: none; transition: opacity .15s;
}
.em-ai-btn:hover .em-ai-tooltip { opacity: 1; }

/* Context IA popover */
.em-ai-popover {
    display: none; background: var(--surface); border: 1.5px solid var(--accent);
    border-radius: var(--radius-lg); padding: 14px; margin-top: 8px;
    box-shadow: 0 4px 20px rgba(79,70,229,.12);
}
.em-ai-popover.open { display: block; }
.em-ai-popover-title { font-size: 11px; font-weight: 800; color: #7c3aed; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.em-ai-ctx {
    width: 100%; padding: 7px 10px; border: 1px solid var(--border);
    border-radius: var(--radius); font-size: 11px; resize: none; min-height: 50px;
    font-family: var(--font); color: var(--text); background: var(--surface);
    outline: none; box-sizing: border-box;
}
.em-ai-ctx:focus { border-color: var(--accent); }

/* Variables badge */
.em-vars-wrap { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 14px; }
.em-var-tag {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 4px; font-size: 10px; font-family: var(--mono); color: var(--accent);
    cursor: pointer; transition: all .12s;
}
.em-var-tag:hover { background: var(--accent-bg); border-color: var(--accent); }

/* Section test email */
.em-test-box {
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--radius-lg); padding: 16px 20px; margin-top: 16px;
}
.em-test-hd { font-size: 11px; font-weight: 800; color: var(--text-2); margin-bottom: 12px; display: flex; align-items: center; gap: 7px; }
.em-test-row { display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; }
.em-test-row .em-input { padding-right: 12px; }

/* Preview iframe */
.em-preview-wrap {
    border: 1px solid var(--border); border-radius: var(--radius-lg);
    overflow: hidden; background: #fff;
}
.em-preview-toolbar {
    background: var(--surface-2); padding: 8px 14px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.em-preview-toolbar span { font-size: 11px; color: var(--text-3); font-family: var(--mono); flex: 1; overflow: hidden; text-overflow: ellipsis; }
.em-preview-device { display: flex; gap: 4px; }
.em-preview-device button {
    width: 28px; height: 28px; border: 1px solid var(--border); border-radius: var(--radius);
    background: var(--surface); cursor: pointer; font-size: 11px; color: var(--text-3);
    display: flex; align-items: center; justify-content: center; transition: all .12s;
}
.em-preview-device button.active,
.em-preview-device button:hover { background: var(--accent-bg); color: var(--accent); border-color: var(--accent); }
.em-preview-frame { width: 100%; height: 500px; border: none; background: #fff; transition: width .2s; }

/* Panel tabs */
.em-panel { display: none; }
.em-panel.active { display: block; }

/* Badges */
.em-badge {
    font-size: 9px; font-weight: 800; padding: 1px 6px;
    border-radius: 3px; text-transform: uppercase;
}

/* Alerts */
.em-alert { padding: 10px 14px; border-radius: var(--radius); margin-bottom: 14px; display: flex; align-items: flex-start; gap: 9px; font-size: 11px; font-weight: 600; }
.em-alert.ok  { background: var(--green-bg); color: var(--green); border: 1px solid rgba(5,150,105,.2); }
.em-alert.err { background: var(--red-bg);   color: var(--red);   border: 1px solid rgba(220,38,38,.2); }

/* Spinner IA */
.em-ai-spin {
    display: none; align-items: center; gap: 8px;
    font-size: 11px; color: #7c3aed; font-weight: 700;
}
.em-ai-spin.show { display: flex; }

@media(max-width:900px) {
    .em-layout { grid-template-columns: 1fr; }
    .em-sidebar { position: static; }
}
</style>

<!-- PAGE HEADER -->
<div class="page-hd">
    <div>
        <h1>Templates Emails</h1>
        <div class="page-hd-sub">Gestion des emails · <?= $totalTemplates ?> templates · <?= $customTemplates ?> personnalisés · <?= $activeTemplates ?> actifs</div>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        <a href="?page=settings&tab=email" class="btn btn-s btn-sm">
            <i class="fas fa-server"></i> Config SMTP
        </a>
        <a href="?page=system" class="btn btn-s btn-sm">
            <i class="fas fa-arrow-left"></i> Système
        </a>
    </div>
</div>

<!-- Stat cards -->
<div class="syshub-scores anim" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e0f2fe;color:#0891b2"><i class="fas fa-envelope"></i></div>
        <div class="stat-info"><div class="stat-val"><?= $totalTemplates ?></div><div class="stat-label">Templates total</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--accent-bg);color:var(--accent)"><i class="fas fa-wand-magic-sparkles"></i></div>
        <div class="stat-info"><div class="stat-val"><?= $customTemplates ?></div><div class="stat-label">Personnalisés</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--green-bg);color:var(--green)"><i class="fas fa-toggle-on"></i></div>
        <div class="stat-info"><div class="stat-val"><?= $activeTemplates ?></div><div class="stat-label">Actifs</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#faf5ff;color:#7c3aed"><i class="fas fa-robot"></i></div>
        <div class="stat-info"><div class="stat-val"><?= count($categories) ?></div><div class="stat-label">Catégories</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--surface-2);color:var(--text-3)"><i class="fas fa-toggle-off"></i></div>
        <div class="stat-info"><div class="stat-val"><?= $totalTemplates - $activeTemplates ?></div><div class="stat-label">Inactifs</div></div>
    </div>
</div>

<!-- Alertes globales -->
<?php if ($saveMsg): ?>
<div class="em-alert ok anim"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($saveMsg) ?></div>
<?php endif; ?>
<?php if ($saveErr): ?>
<div class="em-alert err anim"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($saveErr) ?></div>
<?php endif; ?>

<!-- LAYOUT DEUX COLONNES -->
<div class="em-layout anim">

    <!-- ── SIDEBAR ── -->
    <aside class="em-sidebar">
        <div class="em-sidebar-hd">
            <i class="fas fa-list"></i> Templates
        </div>
        <?php foreach ($categories as $catKey => $cat):
            $catTemplates = array_filter($templates, fn($t) => $t['category'] === $catKey);
            if (!$catTemplates) continue;
        ?>
        <div class="em-cat-label">
            <span class="em-cat-dot" style="background:<?= $cat['color'] ?>"></span>
            <?= $cat['label'] ?>
        </div>
        <?php foreach ($catTemplates as $slug => $tpl): ?>
        <a href="?page=system/emails&template=<?= $slug ?>"
           class="em-tpl-item<?= $slug === $activeSlug ? ' active' : '' ?>"
           onclick="emSelectTemplate('<?= $slug ?>');return false;">
            <div class="em-tpl-icon" style="background:<?= $cat['color'] ?>">
                <i class="fas <?= $cat['icon'] ?>"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div class="em-tpl-name"><?= htmlspecialchars($tpl['label']) ?></div>
                <div class="em-tpl-badges">
                    <?php if ($tpl['is_custom']): ?>
                    <span class="em-badge" style="background:#7c3aed;color:#fff">Perso</span>
                    <?php endif; ?>
                    <?php if (!$tpl['is_active']): ?>
                    <span class="em-badge" style="background:var(--surface-3);color:var(--text-3)">Inactif</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endforeach; ?>
    </aside>

    <!-- ── ÉDITEUR ── -->
    <div class="em-editor" id="em-editor-wrap">
        <?php
        $tpl = $templates[$activeSlug] ?? null;
        $tplVars = $tpl ? (json_decode($tpl['variables'] ?? '[]', true) ?: []) : [];
        $catInfo  = $tpl ? ($categories[$tpl['category']] ?? ['label'=>'','icon'=>'fa-envelope','color'=>'#6366f1']) : null;
        ?>
        <?php if ($tpl): ?>
        <div class="em-editor-hd">
            <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0">
                <div style="width:36px;height:36px;border-radius:var(--radius);background:<?= $catInfo['color'] ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;flex-shrink:0">
                    <i class="fas <?= $catInfo['icon'] ?>"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <div class="em-editor-title"><?= htmlspecialchars($tpl['label']) ?></div>
                    <div class="em-editor-desc"><?= htmlspecialchars($tpl['description'] ?? '') ?></div>
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                <?php if ($tpl['is_custom']): ?>
                <span class="em-badge" style="background:#faf5ff;color:#7c3aed;border:1px solid #c4b5fd">Personnalisé</span>
                <?php else: ?>
                <span class="em-badge" style="background:var(--surface-3);color:var(--text-3);border:1px solid var(--border)">Par défaut</span>
                <?php endif; ?>
                <label class="toggle-sw" style="position:relative;width:40px;height:22px" title="Activer/désactiver">
                    <input type="checkbox" id="em-toggle-active" <?= $tpl['is_active'] ? 'checked' : '' ?>
                           onchange="emToggleActive('<?= $activeSlug ?>', this.checked)">
                    <span class="toggle-sw-slider"></span>
                </label>
            </div>
        </div>

        <!-- Onglets éditeur / preview -->
        <div class="em-editor-body">
            <div class="em-view-tabs">
                <button class="em-view-tab active" onclick="emViewTab('edit',this)">
                    <i class="fas fa-code"></i> Éditeur
                </button>
                <button class="em-view-tab" onclick="emViewTab('preview',this)">
                    <i class="fas fa-eye"></i> Aperçu
                </button>
                <button class="em-view-tab" onclick="emViewTab('test',this)">
                    <i class="fas fa-paper-plane"></i> Tester
                </button>
            </div>

            <!-- ═══ PANEL ÉDITEUR ═══ -->
            <div class="em-panel active" id="em-panel-edit">
            <form method="POST" action="?page=system/emails&template=<?= $activeSlug ?>" id="em-form-save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="em_action"  value="save_template">
            <input type="hidden" name="slug"       value="<?= $activeSlug ?>">

            <!-- Objet email -->
            <div class="em-field">
                <div class="em-field-label">
                    <div class="em-field-label-left">
                        <i class="fas fa-heading" style="color:var(--accent)"></i>
                        Objet de l'email
                    </div>
                    <span style="font-size:9px;color:var(--text-3)" id="em-subj-len"><?= mb_strlen($tpl['subject']) ?> car.</span>
                </div>
                <div class="em-input-wr">
                    <input type="text" name="subject" id="em-subject-input"
                           class="em-input"
                           value="<?= htmlspecialchars($tpl['subject']) ?>"
                           oninput="document.getElementById('em-subj-len').textContent=this.value.length+' car.';emUpdatePreview()"
                           placeholder="Objet de l'email…">
                    <button type="button" class="em-ai-btn" onclick="emAiToggle('subject',event)"
                            title="Générer avec l'IA">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <span class="em-ai-tooltip">Générer avec IA</span>
                    </button>
                </div>
                <!-- Popover IA sujet -->
                <div class="em-ai-popover" id="em-aipop-subject">
                    <div class="em-ai-popover-title">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        Assistant IA — Générer l'objet
                    </div>
                    <div style="margin-bottom:8px">
                        <div class="em-field-label" style="margin-bottom:4px"><div class="em-field-label-left">Contexte / instructions</div></div>
                        <textarea class="em-ai-ctx" id="em-ai-ctx-subject" rows="2"
                                  placeholder="Ex: Objet urgent pour un lead chaud, ton professionnel mais chaleureux…"></textarea>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div class="em-ai-spin" id="em-ai-spin-subject">
                            <i class="fas fa-circle-notch fa-spin"></i> Génération en cours…
                        </div>
                        <div style="display:flex;gap:6px">
                            <button type="button" class="btn btn-s btn-sm" onclick="emAiClose('subject')">Annuler</button>
                            <button type="button" class="btn btn-p btn-sm" onclick="emAiGenerate('subject')">
                                <i class="fas fa-wand-magic-sparkles"></i> Générer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variables disponibles -->
            <?php if ($tplVars): ?>
            <div style="margin-bottom:16px">
                <div class="em-field-label" style="margin-bottom:6px">
                    <div class="em-field-label-left"><i class="fas fa-code"></i> Variables disponibles</div>
                    <span style="font-size:9px;color:var(--text-3)">Clic pour insérer dans l'éditeur</span>
                </div>
                <div class="em-vars-wrap">
                    <?php foreach ($tplVars as $v): ?>
                    <span class="em-var-tag" onclick="emInsertVar(<?= json_encode($v) ?>)">
                        <?= htmlspecialchars($v) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Corps HTML -->
            <div class="em-field">
                <div class="em-field-label">
                    <div class="em-field-label-left">
                        <i class="fas fa-code" style="color:var(--accent)"></i>
                        Corps HTML
                    </div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="font-size:9px;color:var(--text-3)" id="em-html-len"><?= mb_strlen($tpl['body_html']) ?> car.</span>
                        <button type="button" class="btn btn-s btn-sm" style="font-size:10px;padding:3px 8px"
                                onclick="emFormatHTML()">
                            <i class="fas fa-indent"></i> Formater
                        </button>
                    </div>
                </div>
                <div class="em-input-wr" style="position:relative">
                    <textarea name="body_html" id="em-html-input"
                              class="em-textarea"
                              oninput="document.getElementById('em-html-len').textContent=this.value.length+' car.';emUpdatePreview()"
                              spellcheck="false"><?= htmlspecialchars($tpl['body_html']) ?></textarea>
                    <!-- Bouton IA pour le corps HTML -->
                    <button type="button"
                            style="position:absolute;bottom:10px;right:10px;display:flex;align-items:center;gap:5px;padding:5px 12px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:6px;font-size:10px;font-weight:700;cursor:pointer;opacity:.85;transition:all .15s;font-family:var(--font)"
                            onmouseenter="this.style.opacity='1'"
                            onmouseleave="this.style.opacity='.85'"
                            onclick="emAiToggle('body_html',event)">
                        <i class="fas fa-wand-magic-sparkles"></i> IA
                    </button>
                </div>
                <!-- Popover IA corps -->
                <div class="em-ai-popover" id="em-aipop-body_html">
                    <div class="em-ai-popover-title">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        Assistant IA — Générer le template HTML complet
                    </div>
                    <div style="background:var(--amber-bg);border:1px solid var(--amber);border-radius:var(--radius);padding:8px 12px;margin-bottom:10px;font-size:10px;color:var(--amber);font-weight:600">
                        <i class="fas fa-triangle-exclamation"></i>
                        Ceci remplacera le template actuel. Assurez-vous d'avoir sauvegardé si nécessaire.
                    </div>
                    <div style="margin-bottom:8px">
                        <div class="em-field-label" style="margin-bottom:4px"><div class="em-field-label-left">Instructions pour l'IA</div></div>
                        <textarea class="em-ai-ctx" id="em-ai-ctx-body_html" rows="3"
                                  placeholder="Ex: Ton plus formel, ajout d'une section FAQ, inclure logo en haut à gauche, fond sombre…"></textarea>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div class="em-ai-spin" id="em-ai-spin-body_html">
                            <i class="fas fa-circle-notch fa-spin"></i> Génération en cours (peut prendre 15-30 sec)…
                        </div>
                        <div style="display:flex;gap:6px">
                            <button type="button" class="btn btn-s btn-sm" onclick="emAiClose('body_html')">Annuler</button>
                            <button type="button" class="btn btn-p btn-sm" onclick="emAiGenerate('body_html')">
                                <i class="fas fa-wand-magic-sparkles"></i> Générer template
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex;gap:8px;align-items:center;padding-top:14px;border-top:1px solid var(--border);flex-wrap:wrap">
                <button type="submit" class="btn btn-p">
                    <i class="fas fa-save"></i> Sauvegarder
                </button>
                <?php if ($tpl['is_custom']): ?>
                <form method="POST" style="margin:0;display:inline" id="em-form-reset">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="em_action"  value="reset_template">
                    <input type="hidden" name="slug"       value="<?= $activeSlug ?>">
                    <button type="submit" class="btn btn-s"
                            style="border-color:var(--amber);color:var(--amber)"
                            onclick="return confirm('Remettre le template par défaut ? Vos modifications seront perdues.')">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </button>
                </form>
                <?php endif; ?>
                <button type="button" class="btn btn-s" onclick="emViewTab('preview',document.querySelector('.em-view-tab:nth-child(2)'))">
                    <i class="fas fa-eye"></i> Aperçu
                </button>
            </div>

            </form>
            </div><!-- /panel-edit -->

            <!-- ═══ PANEL APERÇU ═══ -->
            <div class="em-panel" id="em-panel-preview">
                <div class="em-preview-wrap">
                    <div class="em-preview-toolbar">
                        <i class="fas fa-envelope" style="color:var(--text-3);font-size:11px"></i>
                        <span id="em-preview-subject"><?= htmlspecialchars($tpl['subject']) ?></span>
                        <div class="em-preview-device">
                            <button onclick="emPreviewDevice('desktop',this)" class="active" title="Desktop">
                                <i class="fas fa-desktop"></i>
                            </button>
                            <button onclick="emPreviewDevice('mobile',this)" title="Mobile">
                                <i class="fas fa-mobile-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div style="overflow:auto;background:#e5e7eb;padding:20px;min-height:540px;display:flex;justify-content:center">
                        <iframe id="em-preview-frame"
                                style="width:600px;max-width:100%;height:520px;border:none;background:#fff;box-shadow:0 4px 20px rgba(0,0,0,.12);border-radius:4px;transition:width .2s"
                                srcdoc=""></iframe>
                    </div>
                </div>
                <div style="margin-top:10px;padding:10px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);font-size:10px;color:var(--text-3)">
                    <i class="fas fa-info-circle"></i>
                    L'aperçu affiche les variables non remplacées (<code style="font-family:var(--mono)">{{variable}}</code>). Dans l'email réel, elles seront remplacées par les vraies valeurs.
                </div>
            </div><!-- /panel-preview -->

            <!-- ═══ PANEL TEST ═══ -->
            <div class="em-panel" id="em-panel-test">
                <div class="em-test-box">
                    <div class="em-test-hd">
                        <i class="fas fa-flask" style="color:var(--accent)"></i>
                        Envoyer un email de test
                    </div>
                    <p style="font-size:12px;color:var(--text-2);margin:0 0 16px;line-height:1.6">
                        Les variables (<code style="font-family:var(--mono);font-size:11px">{{prenom}}</code>, etc.) seront remplacées par des valeurs fictives réalistes.
                        L'email est envoyé via le SMTP configuré dans les paramètres.
                    </p>
                    <form method="POST" action="?page=system/emails&template=<?= $activeSlug ?>" id="em-form-test">
                        <input type="hidden" name="csrf_token"    value="<?= $csrf ?>">
                        <input type="hidden" name="em_action"     value="send_test">
                        <input type="hidden" name="slug"          value="<?= $activeSlug ?>">
                        <input type="hidden" name="test_subject"  id="em-test-subject-input" value="<?= htmlspecialchars($tpl['subject']) ?>">
                        <input type="hidden" name="test_html"     id="em-test-html-input"    value="<?= htmlspecialchars($tpl['body_html']) ?>">

                        <div class="em-test-row">
                            <div class="em-field" style="flex:1;min-width:220px;margin-bottom:0">
                                <div class="em-field-label" style="margin-bottom:6px">
                                    <div class="em-field-label-left">
                                        <i class="fas fa-at" style="color:var(--accent)"></i>
                                        Adresse email de test
                                    </div>
                                </div>
                                <input type="email" name="test_email" class="em-input"
                                       style="padding-right:12px"
                                       value="<?= htmlspecialchars($defaultTestEmail) ?>"
                                       placeholder="votre@email.fr" required>
                            </div>
                            <button type="submit" class="btn btn-p" style="align-self:flex-end">
                                <i class="fas fa-paper-plane"></i> Envoyer le test
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Info SMTP -->
                <div style="margin-top:12px;padding:12px 16px;background:var(--accent-bg);border:1px solid var(--accent);border-radius:var(--radius);font-size:11px;color:var(--accent);display:flex;gap:8px;align-items:flex-start">
                    <i class="fas fa-info-circle" style="margin-top:1px;flex-shrink:0"></i>
                    <span>
                        L'envoi utilise la configuration SMTP de
                        <a href="?page=settings&tab=email" style="color:var(--accent);font-weight:700">Paramètres → Email / SMTP</a>.
                        Si aucun SMTP n'est configuré, PHP mail() natif sera utilisé (moins fiable).
                    </span>
                </div>

                <!-- Preview compacte de ce qui sera envoyé -->
                <div style="margin-top:16px">
                    <div class="em-field-label" style="margin-bottom:8px">
                        <div class="em-field-label-left"><i class="fas fa-eye"></i> Aperçu avec données de test</div>
                    </div>
                    <div class="em-preview-wrap">
                        <div class="em-preview-toolbar">
                            <span id="em-test-preview-subject"><?= htmlspecialchars($tpl['subject']) ?></span>
                        </div>
                        <div style="overflow:auto;background:#e5e7eb;padding:16px;display:flex;justify-content:center">
                            <iframe id="em-test-preview-frame"
                                    style="width:560px;max-width:100%;height:420px;border:none;background:#fff;box-shadow:0 2px 12px rgba(0,0,0,.1);border-radius:4px"
                                    srcdoc=""></iframe>
                        </div>
                    </div>
                </div>
            </div><!-- /panel-test -->

        </div><!-- /em-editor-body -->
        <?php else: ?>
        <div style="padding:60px;text-align:center;color:var(--text-3)">
            <i class="fas fa-envelope" style="font-size:40px;margin-bottom:16px;display:block;opacity:.3"></i>
            <p>Sélectionnez un template dans la liste</p>
        </div>
        <?php endif; ?>
    </div><!-- /em-editor -->

</div><!-- /em-layout -->

<script>
const emSlug      = <?= json_encode($activeSlug) ?>;
const emCsrf      = <?= json_encode($csrf) ?>;
const emBaseUrl   = '?page=system/emails';

// ── Sélection template (SPA) ──────────────────────────────
function emSelectTemplate(slug) {
    history.pushState(null, '', emBaseUrl + '&template=' + slug);
    // Rechargement léger (page complète ici, en prod on ferait AJAX)
    location.href = emBaseUrl + '&template=' + slug;
}

// ── Onglets éditeur ───────────────────────────────────────
function emViewTab(tab, btn) {
    document.querySelectorAll('.em-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.em-view-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('em-panel-' + tab)?.classList.add('active');
    if (btn) btn.classList.add('active');
    if (tab === 'preview') emUpdatePreview();
    if (tab === 'test') {
        emUpdateTestPreview();
        // Synchroniser les hidden inputs du form test
        const subj = document.getElementById('em-subject-input');
        const html = document.getElementById('em-html-input');
        if (subj) document.getElementById('em-test-subject-input').value = subj.value;
        if (html) document.getElementById('em-test-html-input').value    = html.value;
    }
}

// ── Preview temps réel ────────────────────────────────────
function emUpdatePreview() {
    const frame = document.getElementById('em-preview-frame');
    if (!frame) return;
    const html  = document.getElementById('em-html-input')?.value || '';
    const subj  = document.getElementById('em-subject-input')?.value || '';
    document.getElementById('em-preview-subject').textContent = subj;
    frame.srcdoc = html;
}

function emUpdateTestPreview() {
    const frame = document.getElementById('em-test-preview-frame');
    if (!frame) return;
    const html  = document.getElementById('em-html-input')?.value || '';
    const subj  = document.getElementById('em-subject-input')?.value || '';
    // Substituer les variables par des valeurs de test
    const testVals = {
        '{{prenom}}':'Jean','{{nom}}':'Dupont','{{email}}':'jean.dupont@test.fr',
        '{{telephone}}':'06 12 34 56 78','{{message}}':'Message de test.',
        '{{date}}': new Date().toLocaleDateString('fr-FR'),
        '{{adresse_bien}}':'12 rue de la Paix, Bordeaux',
        '{{type_bien}}':'Appartement T3','{{surface}}':'72',
        '{{fourchette_basse}}':'265 000 €','{{fourchette_haute}}':'295 000 €',
        '{{prix_moyen}}':'280 000 €','{{prix_m2}}':'3 900 €',
        '{{conseiller_nom}}':'Votre Conseiller','{{conseiller_telephone}}':'06 00 00 00 00',
        '{{source_page}}':'Page accueil (test)','{{site_url}}':'https://votresite.fr',
        '{{date_rdv}}': new Date(Date.now()+3*86400000).toLocaleDateString('fr-FR'),
        '{{heure_rdv}}':'14h30','{{lieu_rdv}}':'12A rue du test, Bordeaux',
        '{{lien_rapport}}':'#','{{lien_crm}}':'#','{{lien_annulation}}':'#',
        '{{lien_reset}}':'#','{{lien_desinscription}}':'#',
        '{{expiration}}':'2 heures','{{ip}}':'127.0.0.1',
        '{{titre_article_1}}':'Le marché immobilier ce mois',
        '{{intro_article_1}}':'Les prix se stabilisent avec de belles opportunités…',
        '{{lien_article_1}}':'#','{{tendance_marche}}':'Marché dynamique, taux stables.',
    };
    let previewHtml = html;
    Object.entries(testVals).forEach(([k, v]) => {
        previewHtml = previewHtml.split(k).join(v);
    });
    document.getElementById('em-test-preview-subject').textContent = subj;
    frame.srcdoc = previewHtml;
}

// ── Device switcher ───────────────────────────────────────
function emPreviewDevice(device, btn) {
    document.querySelectorAll('.em-preview-device button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const frame = document.getElementById('em-preview-frame');
    if (frame) frame.style.width = device === 'mobile' ? '375px' : '600px';
}

// ── Insérer variable dans textarea ───────────────────────
function emInsertVar(v) {
    const ta = document.getElementById('em-html-input');
    if (!ta) return;
    const s = ta.selectionStart, e = ta.selectionEnd;
    ta.value = ta.value.slice(0, s) + v + ta.value.slice(e);
    ta.selectionStart = ta.selectionEnd = s + v.length;
    ta.focus();
    emUpdatePreview();
}

// ── Formater HTML basique ─────────────────────────────────
function emFormatHTML() {
    const ta = document.getElementById('em-html-input');
    if (!ta) return;
    // Indentation minimale des balises de block
    let h = ta.value;
    h = h.replace(/>\s*</g, '>\n<');
    h = h.replace(/\n{3,}/g, '\n\n');
    ta.value = h.trim();
    emUpdatePreview();
}

// ── Activer/désactiver template ───────────────────────────
function emToggleActive(slug, checked) {
    const fd = new FormData();
    fd.append('csrf_token', emCsrf);
    fd.append('em_action',  'save_template');
    fd.append('slug',       slug);
    fd.append('subject',    document.getElementById('em-subject-input')?.value || '');
    fd.append('body_html',  document.getElementById('em-html-input')?.value || '');
    if (checked) fd.append('is_active', '1');
    fetch(emBaseUrl + '&template=' + slug, { method: 'POST', body: fd })
        .then(() => {
            // Mettre à jour le badge dans la sidebar
            const item = document.querySelector('.em-tpl-item.active .em-tpl-badges');
            if (item) {
                const inactBadge = item.querySelector('.em-badge');
                if (!checked) {
                    if (!inactBadge?.textContent?.includes('Inactif')) {
                        const b = document.createElement('span');
                        b.className = 'em-badge';
                        b.style.cssText = 'background:var(--surface-3);color:var(--text-3)';
                        b.textContent = 'Inactif';
                        item.appendChild(b);
                    }
                } else {
                    if (inactBadge?.textContent?.includes('Inactif')) inactBadge.remove();
                }
            }
        });
}

// ── Assistant IA ─────────────────────────────────────────
function emAiToggle(field, evt) {
    evt.preventDefault();
    const pop = document.getElementById('em-aipop-' + field);
    if (!pop) return;
    // Fermer tous les autres
    document.querySelectorAll('.em-ai-popover').forEach(p => { if (p !== pop) p.classList.remove('open'); });
    pop.classList.toggle('open');
}

function emAiClose(field) {
    document.getElementById('em-aipop-' + field)?.classList.remove('open');
}

function emAiGenerate(field) {
    const ctx   = document.getElementById('em-ai-ctx-' + field)?.value || '';
    const spin  = document.getElementById('em-ai-spin-' + field);
    if (spin) spin.classList.add('show');

    const fd = new FormData();
    fd.append('csrf_token',  emCsrf);
    fd.append('em_action',   'ai_generate');
    fd.append('slug',        emSlug);
    fd.append('field',       field);
    fd.append('ai_context',  ctx);

    fetch(emBaseUrl + '&template=' + emSlug, { method: 'POST', body: fd })
        .then(r => r.text())
        .then(html => {
            // Parser la réponse pour trouver em-alert.ok ou err
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const ok  = tmp.querySelector('.em-alert.ok');
            const err = tmp.querySelector('.em-alert.err');

            if (ok) {
                // Recharger la page pour afficher le résultat généré
                location.href = emBaseUrl + '&template=' + emSlug;
            } else if (err) {
                if (spin) spin.classList.remove('show');
                const msg = err.textContent.trim();
                // Afficher en inline
                const alertDiv = document.createElement('div');
                alertDiv.className = 'em-alert err';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
                document.getElementById('em-aipop-' + field)?.after(alertDiv);
                setTimeout(() => alertDiv.remove(), 5000);
            } else {
                // Pas de feedback clair → recharger quand même
                location.href = emBaseUrl + '&template=' + emSlug;
            }
        })
        .catch(() => {
            if (spin) spin.classList.remove('show');
        });
}

// ── Init preview au chargement ────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    emUpdatePreview();
    // Synchroniser hidden inputs form test
    const subj = document.getElementById('em-subject-input');
    const html = document.getElementById('em-html-input');
    if (subj) subj.addEventListener('input', () => {
        document.getElementById('em-test-subject-input').value = subj.value;
    });
    if (html) html.addEventListener('input', () => {
        document.getElementById('em-test-html-input').value = html.value;
    });

    // Ouvrir le bon panel depuis URL ?view=preview
    const up = new URLSearchParams(location.search);
    const view = up.get('view');
    if (view) {
        const btn = document.querySelector(`.em-view-tab[onclick*="'${view}'"]`);
        emViewTab(view, btn);
    }
});
</script>