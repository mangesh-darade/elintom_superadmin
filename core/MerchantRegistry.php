<?php
/**
 * Super Admin Registry & Dynamic Multi-Tenant SaaS Engine
 * Location: c:\wamp64\www\super_admin\core\MerchantRegistry.php
 */

defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or defined('BASEPATH') or defined('DYNAMIC_DB_LOADER') or exit('Direct script access denied.');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'ElintApiClient.php';

class MerchantRegistry
{
    private static $schemaReady = false;

    /**
     * Get super_admin directory path
     */
    public static function root()
    {
        return dirname(__DIR__);
    }


    /**
     * Load master database configuration
     */
    public static function masterConfig()
    {
        $file = self::root() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'master.php';
        if (!is_file($file)) {
            throw new RuntimeException('Master database configuration file is missing: ' . $file);
        }

        if (!defined('DYNAMIC_DB_LOADER')) {
            define('DYNAMIC_DB_LOADER', true);
        }

        $config = include $file;
        if (!is_array($config) || !isset($config['hostname'], $config['username'], $config['database'])) {
            throw new RuntimeException('Master database configuration is missing or invalid.');
        }
        if (!isset($config['password'])) {
            $config['password'] = '';
        }
        return $config;
    }

    /**
     * Fetch all merchants ordered by newest first
     */
    public static function merchants()
    {
        $db = self::db();
        $result = $db->query('SELECT id, name, phone, email, balance, android_code, order_taking_code, url, code, database_name, hostname, db_user, db_password, plan_expiry, active, android_active, app_attendance_active, app_order_taking_active, created_at FROM merchants ORDER BY id DESC');
        if (!$result) {
            $db->close();
            throw new RuntimeException('Failed to retrieve merchants list.');
        }
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
        $db->close();
        return $rows;
    }

    /**
     * Retrieve single merchant record by ID including connection credentials
     */
    public static function getMerchantById($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }
        $db = self::db(false);
        $stmt = $db->prepare('SELECT id, name, phone, email, balance, android_code, order_taking_code, url, code, database_name, hostname, db_user, db_password, plan_expiry, active, android_active, app_attendance_active, app_order_taking_active, created_at FROM merchants WHERE id = ? LIMIT 1');
        if (!$stmt) {
            $db->close();
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        $db->close();
        return $row;
    }

    /**
     * Check if a merchant tenant is suspended
     */
    public static function isSuspended($code)
    {
        $code = strtolower(trim((string) $code));
        if (!preg_match('/^[a-z][a-z0-9_]{0,40}$/', $code)) {
            return false;
        }
        try {
            $db = self::db(false);
        } catch (Throwable $e) {
            return false;
        }
        $stmt = $db->prepare('SELECT active FROM merchants WHERE code = ? LIMIT 1');
        if (!$stmt) {
            $db->close();
            return false;
        }
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $stmt->bind_result($active);
        $found = $stmt->fetch();
        $stmt->close();
        $db->close();
        if (!$found) {
            return false;
        }
        return (int) $active !== 1;
    }

    /**
     * Check if a merchant subscription plan is expired
     */
    public static function isExpired($code)
    {
        $code = strtolower(trim((string) $code));
        if (!preg_match('/^[a-z][a-z0-9_]{0,40}$/', $code)) {
            return false;
        }
        try {
            $db = self::db(false);
        } catch (Throwable $e) {
            return false;
        }
        $stmt = $db->prepare('SELECT plan_expiry FROM merchants WHERE code = ? LIMIT 1');
        if (!$stmt) {
            $db->close();
            return false;
        }
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $stmt->bind_result($planExpiry);
        $found = $stmt->fetch();
        $stmt->close();
        $db->close();
        if (!$found || empty($planExpiry) || $planExpiry === '0000-00-00') {
            return false; // Lifetime / Unrestricted
        }
        return strtotime($planExpiry) < strtotime('today');
    }

    /**
     * Toggle merchant active / suspended status
     */
    public static function setStatus($id, $active)
    {
        $id = (int) $id;
        $active = ((int) $active === 1) ? 1 : 0;
        if ($id < 1) {
            return array('ok' => false, 'error' => 'Merchant record was not found.');
        }
        $db = self::db();
        $check = $db->prepare('SELECT id FROM merchants WHERE id = ? LIMIT 1');
        if (!$check) {
            $db->close();
            return array('ok' => false, 'error' => 'Database operation error.');
        }
        $check->bind_param('i', $id);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();
        if (!$exists) {
            $db->close();
            return array('ok' => false, 'error' => 'Merchant record was not found.');
        }
        $save = $db->prepare('UPDATE merchants SET active = ? WHERE id = ?');
        if (!$save) {
            $db->close();
            return array('ok' => false, 'error' => 'Database operation error.');
        }
        $save->bind_param('ii', $active, $id);
        $saved = $save->execute();
        $save->close();
        $db->close();
        if (!$saved) {
            return array('ok' => false, 'error' => 'Failed to update merchant status.');
        }
        return array('ok' => true, 'error' => '', 'active' => $active);
    }

    /**
     * Check if a specific mobile app or master android is suspended for a tenant
     */
    public static function isAppSuspendedByTenantCode($code, $appType = 'any')
    {
        $code = strtolower(trim((string) $code));
        if (!preg_match('/^[a-z][a-z0-9_]{0,40}$/', $code)) {
            return false;
        }
        try {
            $db = self::db(false);
        } catch (Throwable $e) {
            return false;
        }
        $stmt = $db->prepare('SELECT active, plan_expiry, android_active, app_attendance_active, app_order_taking_active FROM merchants WHERE code = ? LIMIT 1');
        if (!$stmt) {
            $db->close();
            return false;
        }
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $stmt->bind_result($active, $planExpiry, $androidActive, $appAttActive, $appOtActive);
        $found = $stmt->fetch();
        $stmt->close();
        $db->close();
        if (!$found) {
            return false;
        }

        // Store inactive or subscription expired
        if ((int) $active !== 1) {
            return true;
        }
        if (!empty($planExpiry) && $planExpiry !== '0000-00-00' && strtotime($planExpiry) < strtotime('today')) {
            return true;
        }

        // Master Android killswitch
        if (isset($androidActive) && (int) $androidActive !== 1) {
            return true;
        }

        // App-specific switches
        if ($appType === 'attendance' && isset($appAttActive) && (int) $appAttActive !== 1) {
            return true;
        }
        if ($appType === 'order_taking' && isset($appOtActive) && (int) $appOtActive !== 1) {
            return true;
        }

        return false;
    }

    /**
     * Toggle specific mobile application or master android connectivity for a merchant
     * Does NOT affect Web POS active status!
     */
    public static function setAppStatus($id, $appType, $active)
    {
        $id = (int) $id;
        $active = ((int) $active === 1) ? 1 : 0;
        if ($id < 1) {
            return array('ok' => false, 'error' => 'Merchant record was not found.');
        }

        $column = '';
        if ($appType === 'master_android' || $appType === 'android') {
            $column = 'android_active';
        } elseif ($appType === 'attendance') {
            $column = 'app_attendance_active';
        } elseif ($appType === 'order_taking') {
            $column = 'app_order_taking_active';
        } else {
            return array('ok' => false, 'error' => 'Invalid application type specified.');
        }

        $db = self::db();
        $save = $db->prepare("UPDATE merchants SET {$column} = ? WHERE id = ?");
        if (!$save) {
            $db->close();
            return array('ok' => false, 'error' => 'Database operation error.');
        }
        $save->bind_param('ii', $active, $id);
        $saved = $save->execute();
        $save->close();
        $db->close();

        if (!$saved) {
            return array('ok' => false, 'error' => 'Failed to update app status.');
        }
        return array('ok' => true, 'error' => '', 'active' => $active, 'app_type' => $appType);
    }

    /**
     * Update mobile setup codes for a merchant (Attendance & Order Taking)
     */
    public static function updateAppCodes($id, $androidCode, $orderTakingCode)
    {
        $id = (int) $id;
        $androidCode = strtoupper(trim((string) $androidCode));
        $orderTakingCode = strtoupper(trim((string) $orderTakingCode));

        if ($id < 1) {
            return array('ok' => false, 'error' => 'Invalid merchant ID.');
        }
        if ($androidCode !== '' && !preg_match('/^[A-Za-z0-9_-]{3,64}$/', $androidCode)) {
            return array('ok' => false, 'error' => 'Attendance Android Code must be 3 to 64 letters, numbers, hyphens or underscores.');
        }
        if ($orderTakingCode !== '' && !preg_match('/^[A-Za-z0-9_-]{3,64}$/', $orderTakingCode)) {
            return array('ok' => false, 'error' => 'Order Taking Code must be 3 to 64 letters, numbers, hyphens or underscores.');
        }

        $db = self::db();

        // Check for duplicate android_code
        if ($androidCode !== '') {
            $chk = $db->prepare('SELECT id FROM merchants WHERE id <> ? AND UPPER(android_code) = ? LIMIT 1');
            if ($chk) {
                $chk->bind_param('is', $id, $androidCode);
                $chk->execute();
                $chk->store_result();
                if ($chk->num_rows > 0) {
                    $chk->close();
                    $db->close();
                    return array('ok' => false, 'error' => "Attendance Code '{$androidCode}' is already assigned to another store.");
                }
                $chk->close();
            }
        }

        // Check for duplicate order_taking_code
        if ($orderTakingCode !== '') {
            $chk = $db->prepare('SELECT id FROM merchants WHERE id <> ? AND UPPER(order_taking_code) = ? LIMIT 1');
            if ($chk) {
                $chk->bind_param('is', $id, $orderTakingCode);
                $chk->execute();
                $chk->store_result();
                if ($chk->num_rows > 0) {
                    $chk->close();
                    $db->close();
                    return array('ok' => false, 'error' => "Order Taking Code '{$orderTakingCode}' is already assigned to another store.");
                }
                $chk->close();
            }
        }

        $save = $db->prepare('UPDATE merchants SET android_code = ?, order_taking_code = ? WHERE id = ?');
        if (!$save) {
            $db->close();
            return array('ok' => false, 'error' => 'Database operation error.');
        }
        $save->bind_param('ssi', $androidCode, $orderTakingCode, $id);
        $saved = $save->execute();
        $save->close();
        $db->close();

        if (!$saved) {
            return array('ok' => false, 'error' => 'Failed to save updated mobile codes.');
        }
        return array('ok' => true, 'error' => '');
    }

    /**
     * Lookup merchant by Android connection code (supports both Attendance & Order Taking codes)
     */
    public static function findByAndroidCode($androidCode, $appType = 'any')
    {
        $androidCode = strtoupper(trim((string) $androidCode));
        if (!preg_match('/^[A-Za-z0-9_-]{3,64}$/', $androidCode)) {
            return null;
        }
        $db = self::db();

        $sql = 'SELECT id, name, url, code, active, android_code, order_taking_code, android_active, app_attendance_active, app_order_taking_active, plan_expiry FROM merchants ';
        if ($appType === 'attendance') {
            $sql .= 'WHERE UPPER(android_code) = ? LIMIT 1';
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                $db->close();
                return null;
            }
            $stmt->bind_param('s', $androidCode);
        } elseif ($appType === 'order_taking') {
            $sql .= 'WHERE UPPER(order_taking_code) = ? LIMIT 1';
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                $db->close();
                return null;
            }
            $stmt->bind_param('s', $androidCode);
        } else {
            $sql .= 'WHERE UPPER(android_code) = ? OR UPPER(order_taking_code) = ? LIMIT 1';
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                $db->close();
                return null;
            }
            $stmt->bind_param('ss', $androidCode, $androidCode);
        }

        $stmt->execute();
        $stmt->bind_result($id, $name, $url, $code, $active, $aCode, $otCode, $androidActive, $appAttActive, $appOtActive, $planExpiry);
        $found = $stmt->fetch();
        $stmt->close();
        $db->close();
        if (!$found) {
            return null;
        }

        $matchedApp = (strtoupper($otCode) === $androidCode && strtoupper($aCode) !== $androidCode) ? 'order_taking' : 'attendance';
        if ($appType === 'order_taking') {
            $matchedApp = 'order_taking';
        }

        return array(
            'id' => (int) $id,
            'name' => (string) $name,
            'url' => (string) $url,
            'code' => (string) $code,
            'active' => (int) $active,
            'android_code' => (string) $aCode,
            'order_taking_code' => (string) $otCode,
            'android_active' => isset($androidActive) ? (int) $androidActive : 1,
            'app_attendance_active' => isset($appAttActive) ? (int) $appAttActive : 1,
            'app_order_taking_active' => isset($appOtActive) ? (int) $appOtActive : 1,
            'plan_expiry' => (string) $planExpiry,
            'matched_app' => $matchedApp,
        );
    }

    /**
     * Verify super admin credentials
     */
    public static function verifyAdmin($username, $password)
    {
        if (!is_string($username) || !is_string($password)) {
            return 0;
        }
        if (!preg_match('/^[A-Za-z0-9_]{3,60}$/', $username)) {
            return 0;
        }
        $db = self::db();
        $stmt = $db->prepare('SELECT id, password_hash, active FROM admins WHERE username = ? LIMIT 1');
        if (!$stmt) {
            $db->close();
            return 0;
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->bind_result($id, $passwordHash, $active);
        $found = $stmt->fetch();
        $stmt->close();
        $db->close();
        if (!$found || (int) $active !== 1) {
            return 0;
        }
        if (!password_verify($password, (string) $passwordHash)) {
            return 0;
        }
        return (int) $id;
    }

    /**
     * Provision new database in MySQL and clone base POS template schema
     */
    public static function autoProvisionDatabase($hostname, $dbUser, $dbPass, $targetDb, $cloneTemplate = 'stadmin_saturdayclub')
    {
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $targetDb)) {
            return array('ok' => false, 'error' => 'Invalid database name format.');
        }
        $mysqli = @new mysqli($hostname, $dbUser, $dbPass);
        if ($mysqli->connect_error) {
            return array('ok' => false, 'error' => 'Cannot connect to MySQL server: ' . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8mb4');

        if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$targetDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
            $err = $mysqli->error;
            $errNo = $mysqli->errno;

            // Check if Plesk API fallback is configured
            $mCfg = self::masterConfig();
            $pleskHandled = false;
            if (!empty($mCfg['plesk_enabled']) && !empty($mCfg['plesk_api_key'])) {
                $pleskRes = self::provisionViaPleskApi($targetDb, $mCfg);
                if (!empty($pleskRes['ok'])) {
                    $pleskHandled = true;
                } else {
                    $mysqli->close();
                    return array('ok' => false, 'error' => "Direct MySQL CREATE DATABASE failed ({$err}) and Plesk REST API also failed: " . ($pleskRes['error'] ?? 'Unknown error'));
                }
            }

            if (!$pleskHandled) {
                $mysqli->close();
                if ($errNo == 1044 || $errNo == 1045 || stripos($err, 'Access denied') !== false) {
                    return array('ok' => false, 'error' => "Access denied creating MySQL database '{$targetDb}'. If on Plesk Server, run SQL wildcard grant: `GRANT ALL PRIVILEGES ON \`stadmin_%\`.* TO '{$dbUser}'@'localhost'; FLUSH PRIVILEGES;` or enable Plesk API in super_admin/config/master.php.");
                }
                return array('ok' => false, 'error' => 'Could not create database in MySQL: ' . $err);
            }
        }

        // If template database exists, clone schema structure
        if (!empty($cloneTemplate) && preg_match('/^[A-Za-z0-9_]{1,64}$/', $cloneTemplate)) {
            $chkDb = $mysqli->query("SHOW DATABASES LIKE '{$cloneTemplate}'");
            if ($chkDb && $chkDb->num_rows > 0) {
                $chkDb->free();
                $tablesRes = $mysqli->query("SHOW TABLES FROM `{$cloneTemplate}`");
                if ($tablesRes && $tablesRes->num_rows > 0) {
                    while ($tRow = $tablesRes->fetch_row()) {
                        $table = $tRow[0];
                        $mysqli->query("CREATE TABLE IF NOT EXISTS `{$targetDb}`.`{$table}` LIKE `{$cloneTemplate}`.`{$table}`");
                    }
                    $tablesRes->free();

                    // Seed baseline core records from template
                    $seedTables = array('sma_settings', 'sma_groups', 'sma_permissions', 'sma_pos_settings');
                    foreach ($seedTables as $st) {
                        $chk = $mysqli->query("SHOW TABLES FROM `{$cloneTemplate}` LIKE '{$st}'");
                        if ($chk && $chk->num_rows > 0) {
                            $mysqli->query("INSERT IGNORE INTO `{$targetDb}`.`{$st}` SELECT * FROM `{$cloneTemplate}`.`{$st}`");
                            $chk->free();
                        }
                    }
                }
            }
        }
        $mysqli->close();
        return array('ok' => true, 'error' => '');
    }

    /**
     * Add new merchant tenant
     */
    public static function add($name, $code, $database, $dbUser, $dbPass, $hostname, $phone, $email, $balance, $androidCode, $url, $planExpiry = '', $autoCreateDb = false)
    {
        $fields = self::profile($name, $code, $database, $dbUser, $dbPass, $hostname, $phone, $email, $balance, $androidCode, $url, $planExpiry);
        if (empty($fields['ok'])) {
            return $fields;
        }
        $name = $fields['name'];
        $code = $fields['code'];
        $database = $fields['database'];
        $dbUser = $fields['db_user'];
        $dbPass = $fields['db_password'];
        $hostname = $fields['hostname'];
        $phone = $fields['phone'];
        $email = $fields['email'];
        $balance = $fields['balance'];
        $androidCode = $fields['android_code'];
        $url = $fields['url'];
        $planExpiryVal = $fields['plan_expiry'];

        $db = self::db();
        $check = $db->prepare('SELECT code, phone, email, android_code FROM merchants WHERE code = ? OR phone = ? OR email = ? OR android_code = ? LIMIT 1');
        if (!$check) {
            $db->close();
            return array('ok' => false, 'error' => 'Database error while checking uniqueness.');
        }
        $check->bind_param('ssss', $code, $phone, $email, $androidCode);
        $check->execute();
        $check->bind_result($existingCode, $existingPhone, $existingEmail, $existingAndroid);
        $exists = $check->fetch();
        $check->close();
        if ($exists) {
            $db->close();
            if ((string) $existingCode === $code) {
                return array('ok' => false, 'error' => 'Merchant code / subdomain `' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '` is already taken.');
            }
            if ((string) $existingPhone === $phone) {
                return array('ok' => false, 'error' => 'Mobile number `' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '` is already registered with another merchant.');
            }
            if ((string) $existingEmail === $email) {
                return array('ok' => false, 'error' => 'Email address `' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '` is already in use by another merchant.');
            }
            return array('ok' => false, 'error' => 'Android unique code `' . htmlspecialchars($androidCode, ENT_QUOTES, 'UTF-8') . '` is already assigned.');
        }

        // Auto create database in MySQL if requested or missing
        if ($autoCreateDb && !self::canConnect($hostname, $dbUser, $dbPass, $database)) {
            $prov = self::autoProvisionDatabase($hostname, $dbUser, $dbPass, $database);
            if (empty($prov['ok'])) {
                $db->close();
                return $prov;
            }
        }

        if (!self::canConnect($hostname, $dbUser, $dbPass, $database)) {
            $db->close();
            return array('ok' => false, 'error' => 'Cannot connect to database `' . htmlspecialchars($database, ENT_QUOTES, 'UTF-8') . '`. Please check MySQL or enable "Auto-create database".');
        }

        $insert = $db->prepare('INSERT INTO merchants (name, phone, email, balance, android_code, url, code, database_name, hostname, db_user, db_password, plan_expiry, active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())');
        if (!$insert) {
            $db->close();
            return array('ok' => false, 'error' => 'Database query preparation failed.');
        }
        $insert->bind_param('ssssssssssss', $name, $phone, $email, $balance, $androidCode, $url, $code, $database, $hostname, $dbUser, $dbPass, $planExpiryVal);
        $saved = $insert->execute();
        $newId = $insert->insert_id;
        $insert->close();
        $db->close();
        if (!$saved) {
            return array('ok' => false, 'error' => 'Failed to save merchant tenant.');
        }
        self::ensureTenantAssets($code);
        return array('ok' => true, 'error' => '', 'id' => $newId);
    }

    /**
     * Update existing merchant tenant
     */
    public static function update($id, $name, $code, $database, $dbUser, $dbPass, $hostname, $phone, $email, $balance, $androidCode, $url, $planExpiry = '')
    {
        $id = (int) $id;
        if ($id < 1) {
            return array('ok' => false, 'error' => 'Merchant record was not found.');
        }
        $db = self::db();
        $current = $db->prepare('SELECT id, name, phone, email, balance, android_code, url, code, database_name FROM merchants WHERE id = ? LIMIT 1');
        if (!$current) {
            $db->close();
            return array('ok' => false, 'error' => 'Database error.');
        }
        $current->bind_param('i', $id);
        $current->execute();
        $current->bind_result($oldId, $oldName, $oldPhone, $oldEmail, $oldBalance, $oldAndroid, $oldUrl, $oldCode, $oldDatabase);
        $found = $current->fetch();
        $current->close();
        if (!$found) {
            $db->close();
            return array('ok' => false, 'error' => 'Merchant record was not found.');
        }
        $oldConn = self::connection($oldCode);
        if (trim((string) $dbPass) === '' && is_array($oldConn) && isset($oldConn['password'])) {
            $dbPass = (string) $oldConn['password'];
        }
        if (trim((string) $hostname) === '' && is_array($oldConn) && isset($oldConn['hostname'])) {
            $hostname = (string) $oldConn['hostname'];
        }
        $fields = self::profile($name, $code, $database, $dbUser, $dbPass, $hostname, $phone, $email, $balance, $androidCode, $url, $planExpiry);
        if (empty($fields['ok'])) {
            $db->close();
            return $fields;
        }
        $name = $fields['name'];
        $code = $fields['code'];
        $database = $fields['database'];
        $dbUser = $fields['db_user'];
        $dbPass = $fields['db_password'];
        $hostname = $fields['hostname'];
        $phone = $fields['phone'];
        $email = $fields['email'];
        $balance = $fields['balance'];
        $androidCode = $fields['android_code'];
        $url = $fields['url'];
        $planExpiryVal = $fields['plan_expiry'];

        if (!self::canConnect($hostname, $dbUser, $dbPass, $database)) {
            $db->close();
            return array('ok' => false, 'error' => 'Cannot connect to database `' . htmlspecialchars($database, ENT_QUOTES, 'UTF-8') . '`. Please check MySQL credentials.');
        }
        $check = $db->prepare('SELECT code, phone, email, android_code FROM merchants WHERE id <> ? AND (code = ? OR phone = ? OR email = ? OR android_code = ?) LIMIT 1');
        if (!$check) {
            $db->close();
            return array('ok' => false, 'error' => 'Database check error.');
        }
        $check->bind_param('issss', $id, $code, $phone, $email, $androidCode);
        $check->execute();
        $check->bind_result($existingCode, $existingPhone, $existingEmail, $existingAndroid);
        $exists = $check->fetch();
        $check->close();
        if ($exists) {
            $db->close();
            if ((string) $existingCode === $code) {
                return array('ok' => false, 'error' => 'Merchant code `' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '` is used by another merchant.');
            }
            if ((string) $existingPhone === $phone) {
                return array('ok' => false, 'error' => 'Mobile number `' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '` is already registered with another merchant.');
            }
            if ((string) $existingEmail === $email) {
                return array('ok' => false, 'error' => 'Email address `' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '` is used by another merchant.');
            }
            return array('ok' => false, 'error' => 'Android unique code `' . htmlspecialchars($androidCode, ENT_QUOTES, 'UTF-8') . '` is used by another merchant.');
        }
        $save = $db->prepare('UPDATE merchants SET name = ?, phone = ?, email = ?, balance = ?, android_code = ?, url = ?, code = ?, database_name = ?, hostname = ?, db_user = ?, db_password = ?, plan_expiry = ? WHERE id = ?');
        if (!$save) {
            $db->close();
            return array('ok' => false, 'error' => 'Database update preparation failed.');
        }
        $save->bind_param('ssssssssssssi', $name, $phone, $email, $balance, $androidCode, $url, $code, $database, $hostname, $dbUser, $dbPass, $planExpiryVal, $id);
        $saved = $save->execute();
        $save->close();
        $db->close();
        if (!$saved) {
            return array('ok' => false, 'error' => 'Failed to update merchant details.');
        }
        if ($code !== (string) $oldCode) {
            self::renameTenantAssets($oldCode, $code);
        } else {
            self::ensureTenantAssets($code);
        }
        return array('ok' => true, 'error' => '');
    }

    /**
     * Clone an existing merchant tenant instance
     */
    public static function cloneMerchant($sourceId, $newName, $newCode, $newDatabase, $newPhone, $newEmail, $newAndroidCode, $newUrl, $planExpiry = '', $cloneMode = 'clean_sales')
    {
        $sourceId = (int) $sourceId;
        if ($sourceId < 1) {
            return array('ok' => false, 'error' => 'Source merchant was not found.');
        }

        $source = self::getMerchantById($sourceId);
        if (!$source) {
            return array('ok' => false, 'error' => 'Source merchant record was not found.');
        }

        $srcDbName = $source['database_name'];
        $srcHost = !empty($source['hostname']) ? $source['hostname'] : 'localhost';
        $srcUser = !empty($source['db_user']) ? $source['db_user'] : 'root';
        $srcPass = (string) ($source['db_password'] ?? '');

        $tgtHost = $srcHost;
        $tgtUser = $srcUser;
        $tgtPass = $srcPass;

        $fields = self::profile($newName, $newCode, $newDatabase, $tgtUser, $tgtPass, $tgtHost, $newPhone, $newEmail, '0.00', $newAndroidCode, $newUrl, $planExpiry);
        if (empty($fields['ok'])) {
            return $fields;
        }

        $newName = $fields['name'];
        $newCode = $fields['code'];
        $newDatabase = $fields['database'];
        $newPhone = $fields['phone'];
        $newEmail = $fields['email'];
        $newAndroidCode = $fields['android_code'];
        $newUrl = $fields['url'];
        $planExpiryVal = $fields['plan_expiry'];

        // 1. Create target DB in MySQL
        $mysqli = @new mysqli($tgtHost, $tgtUser, $tgtPass);
        if ($mysqli->connect_error) {
            return array('ok' => false, 'error' => 'Cannot connect to MySQL server: ' . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8mb4');

        if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$newDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
            $err = $mysqli->error;
            $errNo = $mysqli->errno;

            // Check if Plesk API fallback is configured
            $mCfg = self::masterConfig();
            $pleskHandled = false;
            if (!empty($mCfg['plesk_enabled']) && !empty($mCfg['plesk_api_key'])) {
                $pleskRes = self::provisionViaPleskApi($newDatabase, $mCfg);
                if (!empty($pleskRes['ok'])) {
                    $pleskHandled = true;
                } else {
                    $mysqli->close();
                    return array('ok' => false, 'error' => "Direct MySQL CREATE DATABASE failed ({$err}) and Plesk REST API also failed: " . ($pleskRes['error'] ?? 'Unknown error'));
                }
            }

            if (!$pleskHandled) {
                $mysqli->close();
                if ($errNo == 1044 || $errNo == 1045 || stripos($err, 'Access denied') !== false) {
                    return array('ok' => false, 'error' => "Access denied creating MySQL database '{$newDatabase}'. If on Plesk Server, run SQL wildcard grant: `GRANT ALL PRIVILEGES ON \`stadmin_%\`.* TO '{$tgtUser}'@'localhost'; FLUSH PRIVILEGES;` or enable Plesk API in super_admin/config/master.php.");
                }
                return array('ok' => false, 'error' => 'Could not create new database in MySQL: ' . $err);
            }
        }

        // 2. Clone tables from source DB to target DB
        $tablesRes = $mysqli->query("SHOW TABLES FROM `{$srcDbName}`");
        if ($tablesRes && $tablesRes->num_rows > 0) {
            $mysqli->query("SET FOREIGN_KEY_CHECKS=0");
            while ($tRow = $tablesRes->fetch_row()) {
                $table = $tRow[0];
                $mysqli->query("CREATE TABLE IF NOT EXISTS `{$newDatabase}`.`{$table}` LIKE `{$srcDbName}`.`{$table}`");
                $mysqli->query("INSERT IGNORE INTO `{$newDatabase}`.`{$table}` SELECT * FROM `{$srcDbName}`.`{$table}`");
            }
            $tablesRes->free();

            // 3. If clean_sales, truncate transaction logs
            if ($cloneMode === 'clean_sales') {
                $txTables = array(
                    // Sales & Billing Transactions
                    'sma_sales', 'sma_sale_items', 'sma_sales_items_tax',
                    'sma_orders', 'sma_order_items', 'sma_orders_items_tax',
                    'sma_payments',
                    'sma_pos_register',
                    'sma_suspended_bills', 'sma_suspended_items',
                    'sma_bill_log', 'sma_kot_log',
                    
                    // Purchases & Expenses
                    'sma_purchases', 'sma_purchase_items', 'sma_purchase_items_tax',
                    'sma_expenses',
                    
                    // Quotations & Credit Notes / Returns
                    'sma_quotes', 'sma_quote_items', 'sma_quote_items_tax', 'sma_quote_gst',
                    'sma_credit_note', 'sma_credit_note_topups',
                    
                    // Deliveries & Challans
                    'sma_deliveries', 'sma_delivery_challan', 'sma_delivery_challan_items', 'sma_delivery_challan_items_tax',
                    
                    // Stock Adjustments, Counts & Transfers
                    'sma_adjustments', 'sma_adjustment_items',
                    'sma_stock_counts', 'sma_stock_count_items',
                    'sma_transfers', 'sma_transfer_items', 'sma_transfer_item_new', 'sma_transfer_request', 'sma_transfer_request_items',
                    'sma_products_transaction_history',
                    
                    // Customer Loyalty, Wallets, E-shop Carts & Orders
                    'sma_customer_wallet_transactions',
                    'sma_award_point_log',
                    'sma_gift_cards', 'sma_gift_card_topups',
                    'sma_eshop_order', 'sma_eshop_cart', 'sma_webshop_cart_items',
                    'sma_coinage_transactions', 'sma_coinage_transactions_master'
                );
                foreach ($txTables as $tbl) {
                    $chk = $mysqli->query("SHOW TABLES FROM `{$newDatabase}` LIKE '{$tbl}'");
                    if ($chk && $chk->num_rows > 0) {
                        $mysqli->query("TRUNCATE TABLE `{$newDatabase}`.`{$tbl}`");
                        $chk->free();
                    }
                }

                // Reset warehouse inventory to 0 for the new branch
                $chkStock = $mysqli->query("SHOW TABLES FROM `{$newDatabase}` LIKE 'sma_warehouses_products'");
                if ($chkStock && $chkStock->num_rows > 0) {
                    $mysqli->query("UPDATE `{$newDatabase}`.`sma_warehouses_products` SET quantity = 0, rack = NULL");
                    $chkStock->free();
                }

                // Reset invoice & order sequence counters so bill starts from #1
                $chkRef = $mysqli->query("SHOW TABLES FROM `{$newDatabase}` LIKE 'sma_order_ref'");
                if ($chkRef && $chkRef->num_rows > 0) {
                    $mysqli->query("UPDATE `{$newDatabase}`.`sma_order_ref` SET so=1, qu=1, po=1, `to`=1, pos=1, `do`=1, pay=1, re=1, rep=1, ex=1, eshop=1, ordr=1, treq=1, challan=1, pu=1");
                    $chkRef->free();
                }
            }
            $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
        }
        $mysqli->close();

        // 4. Check uniqueness in merchants master table (phone and email are permitted duplicates in clone)
        $db = self::db();
        $check = $db->prepare('SELECT code, android_code FROM merchants WHERE code = ? OR android_code = ? LIMIT 1');
        if (!$check) {
            $db->close();
            return array('ok' => false, 'error' => 'Database error while checking uniqueness.');
        }
        $check->bind_param('ss', $newCode, $newAndroidCode);
        $check->execute();
        $check->bind_result($existingCode, $existingAndroid);
        $exists = $check->fetch();
        $check->close();
        if ($exists) {
            $db->close();
            if ((string) $existingCode === $newCode) {
                return array('ok' => false, 'error' => 'Subdomain code `' . htmlspecialchars($newCode, ENT_QUOTES, 'UTF-8') . '` is already taken.');
            }
            return array('ok' => false, 'error' => 'Android unique code `' . htmlspecialchars($newAndroidCode, ENT_QUOTES, 'UTF-8') . '` is already assigned.');
        }

        // 5. Insert new merchant record
        $insert = $db->prepare('INSERT INTO merchants (name, phone, email, balance, android_code, url, code, database_name, hostname, db_user, db_password, plan_expiry, active, created_at) VALUES (?, ?, ?, 0.00, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())');
        if (!$insert) {
            $db->close();
            return array('ok' => false, 'error' => 'Failed to prepare new merchant insert.');
        }
        $insert->bind_param('sssssssssss', $newName, $newPhone, $newEmail, $newAndroidCode, $newUrl, $newCode, $newDatabase, $tgtHost, $tgtUser, $tgtPass, $planExpiryVal);
        $saved = $insert->execute();
        $newMerchantId = $insert->insert_id;
        $insert->close();
        $db->close();

        if (!$saved) {
            return array('ok' => false, 'error' => 'Failed to save new merchant clone record.');
        }

        // 6. Clone tenant media storage via REST API
        ElintApiClient::cloneStorage($source['code'], $newCode);

        return array('ok' => true, 'error' => '', 'id' => $newMerchantId);
    }

    /**
     * Strict validation & sanitization of merchant input profile
     */
    private static function profile($name, $code, $database, $dbUser, $dbPass, $hostname, $phone, $email, $balance, $androidCode, $url, $planExpiry = '')
    {
        $name = trim((string) $name);
        $code = strtolower(trim((string) $code));
        $database = trim((string) $database);
        $dbUser = trim((string) $dbUser);
        $dbPass = (string) $dbPass;
        $hostname = trim((string) $hostname);
        $phone = trim((string) $phone);
        $email = strtolower(trim((string) $email));
        $balance = trim((string) $balance);
        $androidCode = strtoupper(trim((string) $androidCode));
        $url = trim((string) $url);
        $planExpiry = trim((string) $planExpiry);

        if ($code === '' && $url !== '') {
            $parsed = parse_url($url);
            if (!empty($parsed['host'])) {
                $hOnly = explode(':', $parsed['host'])[0];
                $extracted = strtolower(explode('.', $hOnly)[0]);
                if (preg_match('/^[a-z][a-z0-9_]{0,40}$/', $extracted)) {
                    $code = $extracted;
                }
            }
        }
        if ($hostname === '') {
            $hostname = 'localhost';
        }
        if ($name === '' || strlen($name) > 120) {
            return array('ok' => false, 'error' => 'Merchant business name is required (max 120 chars).');
        }
        $cleanPhone = (string) preg_replace('/\D/', '', $phone);
        if (!preg_match('/^[0-9+\- ]{10,20}$/', $phone) || strlen($cleanPhone) < 10 || strlen($cleanPhone) > 15) {
            return array('ok' => false, 'error' => 'Please enter a valid mobile number (10 to 15 digits).');
        }
        if (strlen($email) > 120 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return array('ok' => false, 'error' => 'Valid email address is required.');
        }
        if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $balance)) {
            return array('ok' => false, 'error' => 'Balance must be a valid amount with up to 2 decimal places.');
        }
        if (!preg_match('/^[A-Z0-9]{4,64}$/', $androidCode)) {
            return array('ok' => false, 'error' => 'Android unique code must be 4 to 64 alphanumeric characters.');
        }
        $urlParts = parse_url($url);
        $urlScheme = is_array($urlParts) && isset($urlParts['scheme']) ? strtolower((string) $urlParts['scheme']) : '';
        if (strlen($url) > 255 || filter_var($url, FILTER_VALIDATE_URL) === false || ($urlScheme !== 'http' && $urlScheme !== 'https')) {
            return array('ok' => false, 'error' => 'Valid URL starting with http:// or https:// is required.');
        }
        if (!preg_match('/^[a-z][a-z0-9_]{0,40}$/', $code)) {
            return array('ok' => false, 'error' => 'Subdomain code must start with a letter and contain only lowercase letters, digits, or underscore.');
        }
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $database)) {
            return array('ok' => false, 'error' => 'Database name must contain only letters, numbers, or underscore.');
        }
        if (!preg_match('/^[A-Za-z0-9_]{1,32}$/', $dbUser)) {
            return array('ok' => false, 'error' => 'Database user must contain only letters, numbers, or underscore.');
        }
        if (strlen($dbPass) > 128 || strpos($dbPass, "\0") !== false) {
            return array('ok' => false, 'error' => 'Database password is invalid.');
        }
        if (!preg_match('/^[A-Za-z0-9.\-]{1,253}$/', $hostname)) {
            return array('ok' => false, 'error' => 'Database hostname is invalid.');
        }

        $planExpiryVal = null;
        if ($planExpiry !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $planExpiry)) {
                return array('ok' => false, 'error' => 'Plan expiry date format must be YYYY-MM-DD.');
            }
            $planExpiryVal = $planExpiry;
        }

        return array(
            'ok' => true,
            'error' => '',
            'name' => $name,
            'code' => $code,
            'database' => $database,
            'db_user' => $dbUser,
            'db_password' => $dbPass,
            'hostname' => $hostname,
            'phone' => $phone,
            'email' => $email,
            'balance' => $balance,
            'android_code' => $androidCode,
            'url' => $url,
            'plan_expiry' => $planExpiryVal,
        );
    }

    /**
     * Resolve database connection configuration for a tenant by subdomain code
     */
    public static function connection($code)
    {
        $code = strtolower(trim((string) $code));
        if (!preg_match('/^[a-z][a-z0-9_]{0,40}$/', $code)) {
            return null;
        }
        $db = self::db(false);
        $stmt = $db->prepare('SELECT database_name, hostname, db_user, db_password FROM merchants WHERE code = ? LIMIT 1');
        if (!$stmt) {
            $db->close();
            return null;
        }
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $stmt->bind_result($tDb, $tHost, $tUser, $tPass);
        $found = $stmt->fetch();
        $stmt->close();
        $db->close();
        if (!$found) {
            return null;
        }
        return array(
            'dsn'      => '',
            'hostname' => !empty($tHost) ? (string) $tHost : 'localhost',
            'username' => !empty($tUser) ? (string) $tUser : 'root',
            'password' => (string) $tPass,
            'database' => (string) $tDb,
            'dbdriver' => 'mysqli',
            'dbprefix' => 'sma_',
            'pconnect' => FALSE,
            'db_debug' => FALSE,
            'cache_on' => FALSE,
            'cachedir' => '',
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
            'swap_pre' => '',
            'encrypt'  => FALSE,
            'compress' => FALSE,
            'stricton' => FALSE,
            'failover' => array(),
            'save_queries' => FALSE,
        );
    }

    /**
     * Connect to master database
     */
    private static function db($ensure = true)
    {
        $config = self::masterConfig();
        $database = (string) $config['database'];
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $database)) {
            throw new RuntimeException('Master database identifier is invalid.');
        }
        $mysqli = @new mysqli($config['hostname'], $config['username'], $config['password']);
        if ($mysqli->connect_error) {
            throw new RuntimeException('Cannot connect to master MySQL server: ' . $mysqli->connect_error);
        }
        if (!$mysqli->query('CREATE DATABASE IF NOT EXISTS `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci')) {
            $mysqli->close();
            throw new RuntimeException('Master database creation failed.');
        }
        if (!$mysqli->select_db($database)) {
            $mysqli->close();
            throw new RuntimeException('Failed to select master database.');
        }
        $mysqli->set_charset('utf8mb4');
        if ($ensure && !self::$schemaReady) {
            self::ensureSchema($mysqli);
            self::$schemaReady = true;
        }
        return $mysqli;
    }

    /**
     * Ensure required tables and columns exist in master database
     */
    private static function ensureSchema($mysqli)
    {
        $mysqli->query("CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            UNIQUE KEY username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $mysqli->query("CREATE TABLE IF NOT EXISTS merchants (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            phone VARCHAR(20) NOT NULL DEFAULT '',
            email VARCHAR(120) NOT NULL DEFAULT '',
            balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            android_code VARCHAR(64) NOT NULL DEFAULT '',
            url VARCHAR(255) NOT NULL DEFAULT '',
            code VARCHAR(41) NOT NULL,
            database_name VARCHAR(64) NOT NULL,
            plan_expiry DATE DEFAULT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            UNIQUE KEY code (code),
            UNIQUE KEY merchants_android_code (android_code),
            INDEX idx_email (email),
            INDEX idx_phone (phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $mysqli->query("CREATE TABLE IF NOT EXISTS audit_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED NOT NULL DEFAULT 0,
            admin_username VARCHAR(60) NOT NULL DEFAULT '',
            action VARCHAR(60) NOT NULL,
            merchant_id INT UNSIGNED DEFAULT NULL,
            description TEXT,
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            INDEX idx_action (action),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $columns = array(
            'phone' => "VARCHAR(20) NOT NULL DEFAULT ''",
            'email' => "VARCHAR(120) NOT NULL DEFAULT ''",
            'balance' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
            'android_code' => "VARCHAR(64) NOT NULL DEFAULT ''",
            'order_taking_code' => "VARCHAR(64) NOT NULL DEFAULT ''",
            'android_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'app_attendance_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'app_order_taking_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'url' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'hostname' => "VARCHAR(255) NOT NULL DEFAULT 'localhost'",
            'db_user' => "VARCHAR(64) NOT NULL DEFAULT 'root'",
            'db_password' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'plan_expiry' => 'DATE DEFAULT NULL',
        );
        $have = array();
        $shown = $mysqli->query('SHOW COLUMNS FROM merchants');
        if ($shown) {
            while ($row = $shown->fetch_assoc()) {
                $have[$row['Field']] = true;
            }
            $shown->free();
        }
        foreach ($columns as $name => $definition) {
            if (empty($have[$name])) {
                $mysqli->query('ALTER TABLE merchants ADD COLUMN `' . $name . '` ' . $definition);
            }
        }

        $haveIndexes = array();
        $indexed = $mysqli->query('SHOW INDEX FROM merchants');
        if ($indexed) {
            while ($row = $indexed->fetch_assoc()) {
                $haveIndexes[$row['Key_name']] = true;
            }
            $indexed->free();
        }

        // Drop legacy unique index on email so clone operations allow duplicate email
        if (!empty($haveIndexes['merchants_email'])) {
            $mysqli->query('ALTER TABLE merchants DROP INDEX merchants_email');
        }
        if (empty($haveIndexes['idx_email'])) {
            $mysqli->query('ALTER TABLE merchants ADD INDEX idx_email (email)');
        }
        if (empty($haveIndexes['idx_phone'])) {
            $mysqli->query('ALTER TABLE merchants ADD INDEX idx_phone (phone)');
        }
        if (empty($haveIndexes['idx_order_taking_code'])) {
            $mysqli->query('ALTER TABLE merchants ADD INDEX idx_order_taking_code (order_taking_code)');
        }

        $wantedUniqueIndexes = array(
            'code' => 'code',
            'merchants_android_code' => 'android_code',
        );
        foreach ($wantedUniqueIndexes as $keyName => $column) {
            if (empty($haveIndexes[$keyName])) {
                $mysqli->query('ALTER TABLE merchants ADD UNIQUE KEY `' . $keyName . '` (`' . $column . '`)');
            }
        }

        $mysqli->query("CREATE TABLE IF NOT EXISTS system_modules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            module_key VARCHAR(64) NOT NULL,
            module_name VARCHAR(100) NOT NULL,
            description VARCHAR(255) NOT NULL DEFAULT '',
            icon VARCHAR(64) NOT NULL DEFAULT '',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_deleted TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_module_key (module_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $defaultModules = array(
            array('dashboard', 'Dashboard Overview', 'Central KPI metrics, server health & module control panel', 'grid', 1),
            array('merchants', 'Merchants & Stores', 'Tenant provisioning, configuration, clone and store status management', 'home', 1),
            array('databases', 'MySQL Databases', 'Multi-tenant database provisioning, auto-creation and Plesk hosting integration', 'database', 1),
            array('storage', 'Tenant Storage (mdata)', 'Isolated tenant media storage directories and asset synchronization', 'folder', 1),
            array('mobile', 'Mobile App Codes', 'Android unique code resolver endpoint for mobile POS & attendance terminals', 'smartphone', 1),
            array('security', 'Suspension & Guard', 'Pre-controller interception guard for expired or suspended merchant stores', 'shield', 1),
            array('audit', 'Audit Trail Logs', 'High-security immutable audit logging for all super admin operations', 'clock', 1),
        );
        $modStmt = $mysqli->prepare("INSERT IGNORE INTO system_modules (module_key, module_name, description, icon, is_active) VALUES (?, ?, ?, ?, ?)");
        if ($modStmt) {
            foreach ($defaultModules as $dm) {
                $modStmt->bind_param('ssssi', $dm[0], $dm[1], $dm[2], $dm[3], $dm[4]);
                $modStmt->execute();
            }
            $modStmt->close();
        }

        $mysqli->query("UPDATE merchants SET order_taking_code = CONCAT('OT_', android_code) WHERE (order_taking_code IS NULL OR order_taking_code = '') AND android_code <> ''");
        $mysqli->query('UPDATE merchants SET android_code = UPPER(android_code) WHERE android_code <> UPPER(android_code)');
        $mysqli->query('UPDATE merchants SET order_taking_code = UPPER(order_taking_code) WHERE order_taking_code <> UPPER(order_taking_code)');
    }

    private static $moduleCache = null;

    /**
     * Retrieve all system modules with active/inactive flags
     */
    public static function modules()
    {
        try {
            $db = self::db(false);
            $res = $db->query('SELECT id, module_key, module_name, description, icon, is_active FROM system_modules WHERE is_deleted = 0 ORDER BY id ASC');
            if (!$res) {
                $db->close();
                return array();
            }
            $list = array();
            while ($row = $res->fetch_assoc()) {
                $list[$row['module_key']] = $row;
            }
            $res->free();
            $db->close();
            self::$moduleCache = $list;
            return $list;
        } catch (Throwable $e) {
            return array();
        }
    }

    /**
     * Check if a specific screen or system module is active
     */
    public static function isModuleActive($key)
    {
        $key = trim((string) $key);
        if (self::$moduleCache === null) {
            self::modules();
        }
        if (is_array(self::$moduleCache) && isset(self::$moduleCache[$key])) {
            return (int) self::$moduleCache[$key]['is_active'] === 1;
        }
        return true;
    }

    /**
     * Set active/inactive state for a screen or module
     */
    public static function setModuleStatus($key, $active)
    {
        $key = trim((string) $key);
        $active = ((int) $active === 1) ? 1 : 0;
        try {
            $db = self::db(false);
            $stmt = $db->prepare('UPDATE system_modules SET is_active = ?, updated_at = NOW() WHERE module_key = ?');
            if (!$stmt) {
                $db->close();
                return array('ok' => false, 'error' => 'Database statement preparation failed.');
            }
            $stmt->bind_param('is', $active, $key);
            $stmt->execute();
            $stmt->close();
            $db->close();
            if (is_array(self::$moduleCache) && isset(self::$moduleCache[$key])) {
                self::$moduleCache[$key]['is_active'] = $active;
            }
            return array('ok' => true, 'is_active' => $active);
        } catch (Throwable $e) {
            return array('ok' => false, 'error' => $e->getMessage());
        }
    }

    /**
     * Record master super admin action to audit log
     */
    public static function logAudit($adminId, $adminUsername, $action, $description, $merchantId = null)
    {
        if (!self::isModuleActive('audit')) {
            return;
        }
        try {
            $db = self::db(false);
            $stmt = $db->prepare('INSERT INTO audit_logs (admin_id, admin_username, action, merchant_id, description, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            if ($stmt) {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $stmt->bind_param('ississ', $adminId, $adminUsername, $action, $merchantId, $description, $ip);
                $stmt->execute();
                $stmt->close();
            }
            $db->close();
        } catch (Throwable $e) {
            // Non-blocking
        }
    }

    /**
     * Retrieve recent audit trail logs
     */
    public static function auditLogs($limit = 100)
    {
        $limit = max(1, min(500, (int) $limit));
        $db = self::db();
        $result = $db->query("SELECT id, admin_id, admin_username, action, merchant_id, description, ip_address, created_at FROM audit_logs ORDER BY id DESC LIMIT {$limit}");
        if (!$result) {
            $db->close();
            return array();
        }
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
        $db->close();
        return $rows;
    }

    /**
     * Generate complete native SQL database dump for backup
     */
    public static function exportDatabaseSql($hostname, $dbUser, $dbPass, $database)
    {
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $database)) {
            throw new RuntimeException('Invalid database identifier.');
        }

        // Fallback to master credentials if password is empty and user matches master config
        if (trim((string) $dbPass) === '') {
            try {
                $mCfg = self::masterConfig();
                if (!empty($mCfg['password'])) {
                    if (empty($dbUser) || $dbUser === 'root' || (isset($mCfg['username']) && $dbUser === $mCfg['username'])) {
                        $dbPass = (string) $mCfg['password'];
                        if (empty($dbUser) || $dbUser === 'root') {
                            $dbUser = (string) $mCfg['username'];
                        }
                    }
                }
            } catch (Throwable $ignore) {
            }
        }

        $mysqli = @new mysqli($hostname, $dbUser, $dbPass, $database);
        if ($mysqli->connect_error) {
            throw new RuntimeException('Cannot connect to database: ' . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8mb4');

        $output = "-- ========================================================\n";
        $output .= "-- Elint POS Tenant Database Backup\n";
        $output .= "-- Database: `{$database}`\n";
        $output .= "-- Generated: " . gmdate('Y-m-d H:i:s') . " UTC\n";
        $output .= "-- ========================================================\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $output .= "SET NAMES utf8mb4;\n\n";

        $tablesRes = $mysqli->query('SHOW TABLES');
        if (!$tablesRes) {
            $mysqli->close();
            throw new RuntimeException('Could not retrieve tables list.');
        }

        while ($tRow = $tablesRes->fetch_row()) {
            $table = $tRow[0];
            $createRes = $mysqli->query("SHOW CREATE TABLE `{$table}`");
            if ($createRes) {
                $cRow = $createRes->fetch_row();
                $output .= "-- Table structure for table `{$table}`\n";
                $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $output .= $cRow[1] . ";\n\n";
                $createRes->free();
            }

            $rowsRes = $mysqli->query("SELECT * FROM `{$table}`");
            if ($rowsRes && $rowsRes->num_rows > 0) {
                $output .= "-- Dumping data for table `{$table}`\n";
                $fieldCount = $rowsRes->field_count;
                $batch = array();
                while ($row = $rowsRes->fetch_row()) {
                    $values = array();
                    for ($i = 0; $i < $fieldCount; $i++) {
                        if (is_null($row[$i])) {
                            $values[] = "NULL";
                        } else {
                            $values[] = "'" . $mysqli->real_escape_string($row[$i]) . "'";
                        }
                    }
                    $batch[] = "(" . implode(", ", $values) . ")";
                    if (count($batch) >= 100) {
                        $output .= "INSERT INTO `{$table}` VALUES " . implode(",\n", $batch) . ";\n";
                        $batch = array();
                    }
                }
                if (count($batch) > 0) {
                    $output .= "INSERT INTO `{$table}` VALUES " . implode(",\n", $batch) . ";\n";
                }
                $output .= "\n";
                $rowsRes->free();
            }
        }
        $tablesRes->free();
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $mysqli->close();
        return $output;
    }

    /**
     * Verify test connection to a target tenant database
     */
    public static function canConnect($hostname, $dbUser = 'root', $dbPass = '', $database = '')
    {
        if ($hostname && ($database === 'localhost' || $database === '127.0.0.1')) {
            $tmp = $hostname;
            $hostname = $database;
            $database = $tmp;
        }

        // Fallback to master credentials if password is empty
        if (trim((string) $dbPass) === '') {
            try {
                $mCfg = self::masterConfig();
                if (!empty($mCfg['password'])) {
                    if (empty($dbUser) || $dbUser === 'root' || (isset($mCfg['username']) && $dbUser === $mCfg['username'])) {
                        $dbPass = (string) $mCfg['password'];
                        if (empty($dbUser) || $dbUser === 'root') {
                            $dbUser = (string) $mCfg['username'];
                        }
                    }
                }
            } catch (Throwable $ignore) {
            }
        }

        try {
            $mysqli = @new mysqli($hostname, $dbUser, $dbPass, $database);
            if ($mysqli->connect_error) {
                return false;
            }
            $mysqli->close();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Auto-create tenant asset folders in assets/mdata/{code}
     */
    public static function ensureTenantAssets($code)
    {
        $code = strtolower(trim((string) $code));
        if (!preg_match('/^[a-z][a-z0-9_]{0,40}$/', $code)) {
            return false;
        }

        // Decoupled REST API Call: Request ElintOm ERP API to create storage
        $apiRes = ElintApiClient::ensureStorage($code);
        return !empty($apiRes['ok']);
    }

    /**
     * Rename tenant asset directory on subdomain change via REST API
     */
    public static function renameTenantAssets($oldCode, $newCode)
    {
        $oldCode = strtolower(trim((string) $oldCode));
        $newCode = strtolower(trim((string) $newCode));
        if ($oldCode === $newCode) {
            return self::ensureTenantAssets($newCode);
        }

        $apiRes = ElintApiClient::renameStorage($oldCode, $newCode);
        return !empty($apiRes['ok']);
    }

    /**
     * Synchronize all active tenant storage structures via REST API
     */
    public static function syncAllTenantAssets()
    {
        $merchants = self::merchants();
        $codes = array();
        foreach ($merchants as $m) {
            if (!empty($m['code'])) {
                $codes[] = $m['code'];
            }
        }

        $apiRes = ElintApiClient::syncStorage($codes);
        if (!empty($apiRes['ok']) && isset($apiRes['data']['synced_count'])) {
            return (int)$apiRes['data']['synced_count'];
        }
        return 0;
    }

    /**
     * Retrieve live storage status map for merchants via REST API
     */
    public static function getTenantStorageMap($merchants)
    {
        $codes = array();
        foreach ($merchants as $m) {
            if (!empty($m['code'])) {
                $codes[] = (string)$m['code'];
            }
        }

        $apiRes = ElintApiClient::getStorageStatus($codes);
        if (!empty($apiRes['ok']) && !empty($apiRes['data']['tenants'])) {
            $map = array();
            foreach ($apiRes['data']['tenants'] as $t) {
                $map[$t['code']] = $t;
            }
            return array('source' => 'api', 'map' => $map, 'mdata_base' => $apiRes['data']['mdata_base'] ?? 'assets/mdata/');
        }

        $map = array();
        foreach ($merchants as $m) {
            $mCode = (string) $m['code'];
            $map[$mCode] = array(
                'code' => $mCode,
                'mounted' => false,
                'writable' => false,
                'subdirs_present' => 0,
                'subdirs_total' => 23,
                'relative_path' => 'assets/mdata/' . $mCode . '/'
            );
        }
        return array('source' => 'offline', 'map' => $map, 'mdata_base' => 'assets/mdata/');
    }

    /**
     * Create database via Plesk REST API v2
     */
    public static function provisionViaPleskApi($targetDb, $masterConfig = null)
    {
        if ($masterConfig === null) {
            $masterConfig = self::masterConfig();
        }
        if (empty($masterConfig['plesk_enabled']) || empty($masterConfig['plesk_host']) || empty($masterConfig['plesk_api_key'])) {
            return array('ok' => false, 'error' => 'Plesk API is not enabled or credentials are missing in master.php.');
        }

        $pleskHost = rtrim((string)$masterConfig['plesk_host'], '/');
        $apiKey = (string)$masterConfig['plesk_api_key'];
        $subId = (int)($masterConfig['plesk_subscription_id'] ?? 1);
        $serverId = (int)($masterConfig['plesk_server_id'] ?? 1);

        $endpoint = $pleskHost . '/api/v2/databases';
        $payload = json_encode(array(
            'name' => $targetDb,
            'type' => 'mysql',
            'subscription_id' => $subId,
            'server_id' => $serverId
        ));

        if (!function_exists('curl_init')) {
            return array('ok' => false, 'error' => 'cURL PHP extension is required for Plesk REST API calls.');
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey,
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($code === 200 || $code === 201) {
            return array('ok' => true, 'response' => json_decode($resp, true));
        }
        $msg = "Plesk API HTTP {$code}";
        if ($curlErr) {
            $msg .= ": {$curlErr}";
        } elseif ($resp) {
            $parsed = json_decode($resp, true);
            $msg .= ": " . ($parsed['message'] ?? $resp);
        }
        return array('ok' => false, 'error' => $msg);
    }

    /**
     * Get system environment, PHP, MySQL, and storage diagnostics
     */
    public static function systemDiagnostics()
    {
        $mCfg = self::masterConfig();
        $dbConnected = false;
        $mysqlVersion = 'Unknown';
        try {
            $db = self::db();
            $dbConnected = true;
            $mysqlVersion = $db->server_info ?? 'Unknown';
            $db->close();
        } catch (Throwable $e) {
            $dbConnected = false;
        }

        // Live check of ElintOm ERP API connectivity
        $apiPing = ElintApiClient::ping();
        $apiConnected = !empty($apiPing['ok']);
        $apiData = $apiConnected ? ($apiPing['data'] ?? array()) : array();

        return array(
            'php_version' => PHP_VERSION,
            'mysql_connected' => $dbConnected,
            'mysql_version' => $mysqlVersion,
            'mdata_writable' => !empty($apiData['mdata_writable']),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'plesk_enabled' => !empty($mCfg['plesk_enabled']),
            'plesk_host' => !empty($mCfg['plesk_host']) ? $mCfg['plesk_host'] : 'Not configured (Direct MySQL mode)',
            'api_connected' => $apiConnected,
            'api_url' => ElintApiClient::getApiUrl(),
            'api_service' => $apiData['service'] ?? 'Offline / Unreachable',
            'api_erp_php' => $apiData['php_version'] ?? 'N/A',
            'api_mdata_writable' => $apiData['mdata_writable'] ?? false,
            'api_error' => $apiConnected ? '' : ($apiPing['error'] ?? 'API connection failed')
        );
    }
}
