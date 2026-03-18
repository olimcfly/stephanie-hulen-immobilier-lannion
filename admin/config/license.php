<?php
$_licenseConfig = [
  'installation_id'   => '1',
  'license_key'       => 'COLLE_LA_LICENSE_KEY',
  'client_secret_b64' => 'COLLE_LE_SECRET_CLIENT_BASE64',
  'subdomain'         => 'eduardo-desul-immobilier',
  'target_domain'     => 'https://eduardo-desul-immobilier.fr',
];

defined('SITE_URL')  || define('SITE_URL',  $_licenseConfig['target_domain']);
defined('SITE_NAME') || define('SITE_NAME', 'Eduardo De Sul Immobilier');