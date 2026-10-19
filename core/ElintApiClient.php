<?php
/**
 * ElintOm ERP API Client
 * 
 * Communicates with ElintOm_PHP_8.5 Superadmin API endpoints over HTTP/REST.
 * Handles storage syncing, tenant media provisioning, and diagnostics.
 */

defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or defined('BASEPATH') or defined('DYNAMIC_DB_LOADER') or exit('Direct script access denied.');

class ElintApiClient
{
    /**
     * Get API Endpoint URL (auto-appends /api/superadmin to base ERP URL)
     */
    public static function getApiUrl()
    {
        $cfg = MerchantRegistry::masterConfig();
        $rawUrl = !empty($cfg['elintom_api_url']) ? trim((string)$cfg['elintom_api_url']) : 'http://localhost/ElintOm_PHP_8.5';
        $rawUrl = rtrim($rawUrl, '/');

        // If user already typed /api/superadmin, keep it
        if (substr($rawUrl, -15) === '/api/superadmin') {
            return $rawUrl;
        }

        // Auto-append the superadmin API endpoint
        return $rawUrl . '/api/superadmin';
    }

    /**
     * Get API Secret Key from master config
     */
    public static function getApiKey()
    {
        $cfg = MerchantRegistry::masterConfig();
        return !empty($cfg['elintom_api_secret']) ? (string)$cfg['elintom_api_secret'] : 'elintom_superadmin_secret_key_85x92';
    }

    /**
     * Execute HTTP Request to ElintOm Superadmin API
     */
    public static function request($endpoint, $payload = array(), $method = 'POST')
    {
        if (!function_exists('curl_init')) {
            return array('ok' => false, 'error' => 'cURL PHP extension is required for API communication.');
        }

        $baseUrl = self::getApiUrl();
        $endpoint = ltrim($endpoint, '/');
        $url = $baseUrl . '/' . $endpoint;
        $apiKey = self::getApiKey();

        $ch = curl_init();
        $headers = array(
            'X-Superadmin-Key: ' . $apiKey,
            'Accept: application/json'
        );

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($payload)) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        } elseif (strtoupper($method) === 'GET' && !empty($payload)) {
            $url .= '?' . http_build_query($payload);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return array('ok' => false, 'error' => "cURL error ({$url}): " . $curlError, 'http_code' => $httpCode);
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return array('ok' => true, 'data' => $decoded['data'] ?? array(), 'message' => $decoded['message'] ?? '', 'http_code' => $httpCode);
        }

        $errMsg = "HTTP {$httpCode}";
        if (is_array($decoded) && !empty($decoded['message'])) {
            $errMsg .= ": " . $decoded['message'];
        } elseif ($response) {
            $errMsg .= ": " . strip_tags(substr($response, 0, 200));
        }

        return array('ok' => false, 'error' => $errMsg, 'http_code' => $httpCode, 'raw' => $response);
    }

    /**
     * Check if ElintOm API is online and responsive
     */
    public static function ping()
    {
        return self::request('ping', array(), 'GET');
    }

    /**
     * Ensure storage directory for a specific tenant code
     */
    public static function ensureStorage($code)
    {
        return self::request('ensure_storage', array('code' => $code), 'POST');
    }

    /**
     * Sync storage for multiple tenants or all tenants
     */
    public static function syncStorage($codes = array())
    {
        return self::request('sync_storage', array('codes' => $codes), 'POST');
    }

    /**
     * Get storage status for all tenants
     */
    public static function getStorageStatus($codes = array())
    {
        return self::request('storage_status', array('codes' => $codes), 'POST');
    }

    /**
     * Rename storage directory
     */
    public static function renameStorage($oldCode, $newCode)
    {
        return self::request('rename_storage', array('old_code' => $oldCode, 'new_code' => $newCode), 'POST');
    }

    /**
     * Clone tenant storage directory from source to target
     */
    public static function cloneStorage($sourceCode, $targetCode)
    {
        return self::request('clone_storage', array('source_code' => $sourceCode, 'target_code' => $targetCode), 'POST');
    }

    /**
     * Get diagnostics from ERP server
     */
    public static function getDiagnostics()
    {
        return self::request('diagnostics', array(), 'GET');
    }
}
