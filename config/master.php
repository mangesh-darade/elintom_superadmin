<?php

/**
 * Master Registry Database Configuration
 * Superadmin merchant database credentials. This is not a tenant shop database.
 */
defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or defined('BASEPATH') or defined('DYNAMIC_DB_LOADER') or exit('Direct script access denied.');

return array(
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'elint_master',

    // Super Admin Allowed Hostnames / Domains
    // Merchants Admin can ONLY be accessed when visited through these domains.
    // Visiting via any tenant subdomain (e.g. attendancecaptured.elintom.us) returns 404 Not Found.
    'allowed_hosts'         => array(
        'localhost',
        '127.0.0.1',
        'qa18.elintom.us',
    ),

    // Plesk Server Deployment Settings (Optional)
    // If hosting on Plesk and MySQL user lacks direct CREATE DATABASE privilege,
    // enable Plesk REST API v2 to auto-create databases through Plesk.
    'plesk_enabled'         => false,
    'plesk_host'            => '', // e.g. 'https://your-server.com:8443'
    'plesk_api_key'         => '', // Generated in Plesk -> Profile -> API Keys
    'plesk_subscription_id' => 1,  // Subscription ID in Plesk
    'plesk_server_id'       => 1,  // MySQL Database Server ID in Plesk (default 1)

    // ElintOm ERP Base URL (e.g. 'http://localhost/ElintOm_PHP_8.5' or 'https://pos.yourdomain.com')
    // Standard endpoint '/api/superadmin' is auto-appended in code.
    'elintom_api_url'       => 'http://localhost/ElintOm_PHP_8.5',
    'elintom_api_secret'    => 'elintom_superadmin_secret_key_85x92',
);
