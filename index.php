<?php
/**
 * Standalone Super Admin Console for Tenant & Merchant Management
 * Location: c:\wamp64\www\super_admin\index.php
 */

define('SUPER_ADMIN_ACCESS', true);
define('MERCHANTS_ADMIN_ACCESS', true);

// Enforce modern HTTP Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Restrict Super Admin access to authorized hostnames only
$masterConfigFile = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'master.php';
$allowedHosts = array('localhost', '127.0.0.1', 'qa18.elintom.us', '18qa.elintom.us');
if (is_file($masterConfigFile)) {
    $masterConfig = include $masterConfigFile;
    if (is_array($masterConfig) && !empty($masterConfig['allowed_hosts']) && is_array($masterConfig['allowed_hosts'])) {
        $allowedHosts = array_map('strtolower', array_map('trim', $masterConfig['allowed_hosts']));
    }
}

$rawHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$currentHost = strtolower(trim(explode(':', $rawHost)[0]));

$isHostAllowed = in_array($currentHost, $allowedHosts, true);
if (!$isHostAllowed) {
    if (filter_var($currentHost, FILTER_VALIDATE_IP) !== false) {
        $isHostAllowed = true;
    } else {
        foreach ($allowedHosts as $allowed) {
            if (strpos($allowed, '*.') === 0) {
                $suffix = substr($allowed, 2);
                if ($currentHost === $suffix || (strlen($currentHost) > strlen('.' . $suffix) && substr($currentHost, -strlen('.' . $suffix)) === '.' . $suffix)) {
                    $isHostAllowed = true;
                    break;
                }
            }
        }
    }
}

// Check for Mobile App API requests (attendance_api/setup, ordertakingapi/setup, client_log)
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$isAttendanceSetup = (strpos($requestUri, 'attendance_api/setup') !== false) || (isset($_GET['action']) && $_GET['action'] === 'attendance_setup');
$isOrderTakingSetup = (strpos($requestUri, 'ordertakingapi/setup') !== false) || (isset($_GET['action']) && $_GET['action'] === 'ordertaking_setup');
$isClientLog = (strpos($requestUri, 'attendance_api/client_log') !== false);

if ($isAttendanceSetup || $isOrderTakingSetup || $isClientLog) {
    header('Content-Type: application/json; charset=utf-8');
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'MerchantRegistry.php';

    if ($isClientLog) {
        http_response_code(200);
        exit(json_encode(array('status' => 'success', 'message' => 'Logged')));
    }

    $appType = $isOrderTakingSetup ? 'order_taking' : 'attendance';
    $rawJson = @file_get_contents('php://input');
    $jsonData = json_decode($rawJson, true);
    if (!is_array($jsonData)) {
        $jsonData = array();
    }

    $code = trim((string) ($_POST['android_code'] ?? ($_POST['order_taking_code'] ?? ($jsonData['android_code'] ?? ($jsonData['order_taking_code'] ?? '')))));
    if ($code === '') {
        http_response_code(400);
        exit(json_encode(array('status' => 'error', 'message' => 'Android code is required')));
    }

    $merchant = MerchantRegistry::findByAndroidCode($code, $appType);
    if (!$merchant) {
        http_response_code(200);
        exit(json_encode(array('status' => 'error', 'message' => 'Android code was not found.')));
    }

    // Check if Web store is suspended or expired
    if ((int) ($merchant['active'] ?? 1) !== 1) {
        exit(json_encode(array('status' => 'error', 'message' => 'URL suspended, need to contact customer care')));
    }
    if (!empty($merchant['plan_expiry']) && $merchant['plan_expiry'] !== '0000-00-00' && strtotime($merchant['plan_expiry']) < strtotime('today')) {
        exit(json_encode(array('status' => 'error', 'message' => 'URL suspended, need to contact customer care')));
    }

    // Check if master Android connection is disabled or specific app is disabled
    if ($appType === 'attendance') {
        if ((int) ($merchant['android_active'] ?? 1) !== 1 || (int) ($merchant['app_attendance_active'] ?? 1) !== 1) {
            exit(json_encode(array('status' => 'error', 'message' => 'Device is not ready for connect please contact to customer care')));
        }
    } else {
        if ((int) ($merchant['android_active'] ?? 1) !== 1 || (int) ($merchant['app_order_taking_active'] ?? 1) !== 1) {
            exit(json_encode(array('status' => 'error', 'message' => 'Device is not ready for connect please contact to customer care')));
        }
    }

    $url = trim((string) $merchant['url']);
    $parts = parse_url($url);
    $scheme = is_array($parts) && isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
    if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false || ($scheme !== 'http' && $scheme !== 'https')) {
        exit(json_encode(array('status' => 'error', 'message' => 'This merchant does not have a valid URL.')));
    }

    exit(json_encode(array(
        'status' => 'success',
        'message' => 'Setup ready',
        'data' => array(
            'url' => $url,
            'code' => (string) $merchant['code'],
            'name' => (string) $merchant['name'],
            'app' => $appType
        ),
        'url' => $url,
        'code' => (string) $merchant['code'],
        'name' => (string) $merchant['name']
    )));
}

if (!$isHostAllowed) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    exit('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body></html>');
}

// Configure secure session parameters before starting session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Generate or retrieve CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Load Central Merchant Engine
require_once __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'MerchantRegistry.php';

$action = isset($_GET['action']) ? strtolower(trim((string)$_GET['action'])) : '';
$adminId = isset($_SESSION['elint_master_admin_id']) ? (int)$_SESSION['elint_master_admin_id'] : 0;
$adminUsername = isset($_SESSION['elint_master_username']) ? (string)$_SESSION['elint_master_username'] : 'admin';

// Flash message helpers
function setFlash($key, $message) {
    $_SESSION['flash_' . $key] = $message;
}
function getFlash($key) {
    if (isset($_SESSION['flash_' . $key])) {
        $msg = $_SESSION['flash_' . $key];
        unset($_SESSION['flash_' . $key]);
        return $msg;
    }
    return '';
}

// CSRF validation helper
function verifyCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedToken = (string)($_POST['csrf_token'] ?? '');
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
            http_response_code(403);
            exit('<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style="font-family:sans-serif;padding:40px;background:#0f172a;color:#f87171;"><h2>403 Forbidden</h2><p>Security validation failed (Invalid or missing CSRF token). Please return and refresh the page.</p><p><a href="index.php" style="color:#818cf8;">Back to Console</a></p></body></html>');
        }
    }
}

// ---------------- ACTION ROUTER ----------------

// 1. LOGIN
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php');
        exit;
    }

    verifyCsrfToken();

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $lockKey = 'login_lock_' . md5($ip);
    $failKey = 'login_fails_' . md5($ip);

    // Rate limit check
    if (isset($_SESSION[$lockKey]) && time() < $_SESSION[$lockKey]) {
        $waitMins = max(1, (int) ceil(($_SESSION[$lockKey] - time()) / 60));
        setFlash('error', "Too many failed attempts. Locked out for {$waitMins} minute(s).");
        header('Location: index.php');
        exit;
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $verifiedId = 0;
    try {
        $verifiedId = MerchantRegistry::verifyAdmin($username, $password);
    } catch (Throwable $e) {
        $verifiedId = 0;
    }

    if ($verifiedId > 0) {
        unset($_SESSION[$failKey]);
        unset($_SESSION[$lockKey]);
        session_regenerate_id(true);

        $_SESSION['elint_master_admin_id'] = $verifiedId;
        $_SESSION['elint_master_username'] = $username;

        MerchantRegistry::logAudit($verifiedId, $username, 'LOGIN', 'Super Admin logged into console');

        setFlash('ok', 'Welcome back, ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '!');
        header('Location: index.php');
        exit;
    } else {
        $_SESSION[$failKey] = ($_SESSION[$failKey] ?? 0) + 1;
        if ($_SESSION[$failKey] >= 5) {
            $_SESSION[$lockKey] = time() + (15 * 60);
            $_SESSION[$failKey] = 0;
            setFlash('error', 'Too many failed login attempts. Locked for 15 minutes.');
        } else {
            $remaining = 5 - $_SESSION[$failKey];
            setFlash('error', "Invalid admin username or password. ({$remaining} attempts remaining)");
        }
        header('Location: index.php');
        exit;
    }
}

// 2. LOGOUT
if ($action === 'logout') {
    if ($adminId > 0) {
        MerchantRegistry::logAudit($adminId, $adminUsername, 'LOGOUT', 'Super Admin signed out');
    }
    unset($_SESSION['elint_master_admin_id']);
    unset($_SESSION['elint_master_username']);
    session_regenerate_id(true);
    setFlash('ok', 'Successfully signed out.');
    header('Location: index.php');
    exit;
}

// Protected Actions below require admin login
if ($adminId < 1) {
    $error = getFlash('error');
    $ok = getFlash('ok');
    require_once __DIR__ . '/views/login.php';
    exit;
}

// 3. ADD MERCHANT
if ($action === 'add') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php');
        exit;
    }

    verifyCsrfToken();

    $hostname = trim((string)($_POST['hostname'] ?? 'localhost'));
    if ($hostname === '') { $hostname = 'localhost'; }

    $name = (string)($_POST['name'] ?? '');
    $code = (string)($_POST['code'] ?? '');
    $database = (string)($_POST['database_name'] ?? '');
    $planExpiry = (string)($_POST['plan_expiry'] ?? '');
    $autoCreateDb = !empty($_POST['auto_create_db']);

    try {
        $result = MerchantRegistry::add(
            $name,
            $code,
            $database,
            (string)($_POST['db_user'] ?? 'root'),
            (string)($_POST['db_password'] ?? ''),
            $hostname,
            (string)($_POST['phone'] ?? ''),
            (string)($_POST['email'] ?? ''),
            (string)($_POST['balance'] ?? '0.00'),
            (string)($_POST['android_code'] ?? ''),
            (string)($_POST['url'] ?? ''),
            $planExpiry,
            $autoCreateDb
        );
    } catch (Throwable $e) {
        $result = array('ok' => false, 'error' => 'Could not save the merchant: ' . $e->getMessage());
    }

    if (empty($result['ok'])) {
        setFlash('error', $result['error'] ?? 'Could not save merchant.');
        setFlash('open_form', '1');
    } else {
        $desc = "Added merchant {$name} ({$code}) with DB {$database}";
        if ($autoCreateDb) {
            $desc .= " (auto-provisioned in MySQL)";
        }
        MerchantRegistry::logAudit($adminId, $adminUsername, 'ADD_MERCHANT', $desc, $result['id'] ?? null);
        setFlash('ok', 'New merchant tenant added successfully.');
    }
    header('Location: index.php?view=merchants');
    exit;
}

// 4. UPDATE MERCHANT
if ($action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php');
        exit;
    }

    verifyCsrfToken();

    $id = (int)($_POST['merchant_id'] ?? 0);
    $hostname = trim((string)($_POST['hostname'] ?? 'localhost'));
    if ($hostname === '') { $hostname = 'localhost'; }

    $name = (string)($_POST['name'] ?? '');
    $code = (string)($_POST['code'] ?? '');
    $planExpiry = (string)($_POST['plan_expiry'] ?? '');

    try {
        $result = MerchantRegistry::update(
            $id,
            $name,
            $code,
            (string)($_POST['database_name'] ?? ''),
            (string)($_POST['db_user'] ?? 'root'),
            (string)($_POST['db_password'] ?? ''),
            $hostname,
            (string)($_POST['phone'] ?? ''),
            (string)($_POST['email'] ?? ''),
            (string)($_POST['balance'] ?? '0.00'),
            (string)($_POST['android_code'] ?? ''),
            (string)($_POST['url'] ?? ''),
            $planExpiry
        );
    } catch (Throwable $e) {
        $result = array('ok' => false, 'error' => 'Could not update merchant: ' . $e->getMessage());
    }

    if (empty($result['ok'])) {
        setFlash('error', $result['error'] ?? 'Could not update merchant.');
    } else {
        MerchantRegistry::logAudit($adminId, $adminUsername, 'UPDATE_MERCHANT', "Updated merchant {$name} ({$code}) details", $id);
        setFlash('ok', 'Merchant details updated successfully.');
    }
    header('Location: index.php?view=merchants');
    exit;
}

// 4b. CLONE / DUPLICATE MERCHANT INSTANCE
if ($action === 'clone') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php');
        exit;
    }

    verifyCsrfToken();

    $sourceId = (int)($_POST['source_id'] ?? 0);
    $newName = (string)($_POST['name'] ?? '');
    $newCode = (string)($_POST['code'] ?? '');
    $newDatabase = (string)($_POST['database_name'] ?? '');
    $newPhone = (string)($_POST['phone'] ?? '');
    $newEmail = (string)($_POST['email'] ?? '');
    $newAndroidCode = (string)($_POST['android_code'] ?? '');
    $newUrl = (string)($_POST['url'] ?? '');
    $planExpiry = (string)($_POST['plan_expiry'] ?? '');
    $cloneMode = (string)($_POST['clone_mode'] ?? 'clean_sales');

    try {
        $result = MerchantRegistry::cloneMerchant(
            $sourceId,
            $newName,
            $newCode,
            $newDatabase,
            $newPhone,
            $newEmail,
            $newAndroidCode,
            $newUrl,
            $planExpiry,
            $cloneMode
        );
    } catch (Throwable $e) {
        $result = array('ok' => false, 'error' => 'Could not clone merchant: ' . $e->getMessage());
    }

    if (empty($result['ok'])) {
        setFlash('error', $result['error'] ?? 'Could not clone merchant.');
    } else {
        $desc = "Cloned merchant to '{$newName}' (slug: {$newCode}, db: {$newDatabase}) [Mode: {$cloneMode}]";
        MerchantRegistry::logAudit($adminId, $adminUsername, 'CLONE_MERCHANT', $desc, $result['id'] ?? null);
        setFlash('ok', "Merchant instance successfully cloned to '{$newName}'!");
    }
    header('Location: index.php?view=merchants');
    exit;
}

// 5. SUSPEND / ACTIVATE
if ($action === 'suspend') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php');
        exit;
    }

    verifyCsrfToken();

    $id = (int)($_POST['merchant_id'] ?? 0);
    $active = ((int)($_POST['active'] ?? 0) === 1) ? 1 : 0;

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1');

    try {
        $result = MerchantRegistry::setStatus($id, $active);
    } catch (Throwable $e) {
        $result = array('ok' => false, 'error' => 'Could not update status: ' . $e->getMessage());
    }

    if (empty($result['ok'])) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(400);
            echo json_encode(array('status' => 'error', 'message' => $result['error'] ?? 'Status update failed.'));
            exit;
        }
        setFlash('error', $result['error'] ?? 'Status update failed.');
    } else {
        $actionTag = $active === 1 ? 'ACTIVATE_MERCHANT' : 'SUSPEND_MERCHANT';
        MerchantRegistry::logAudit($adminId, $adminUsername, $actionTag, "Marked merchant ID {$id} status to " . ($active === 1 ? 'Active' : 'Suspended'), $id);
        $msg = $active === 1 ? 'Merchant shop is now ACTIVE.' : 'Merchant shop has been SUSPENDED.';
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array(
                'status' => 'success',
                'message' => $msg,
                'merchant_id' => $id,
                'active' => $active
            ));
            exit;
        }
        setFlash('ok', $msg);
    }
    header('Location: index.php?view=merchants');
    exit;
}

// 5b. TOGGLE MODULE / SCREEN ACTIVE-INACTIVE
if ($action === 'toggle_module') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php');
        exit;
    }

    verifyCsrfToken();

    $modKey = trim((string)($_POST['module_key'] ?? ''));
    $isActive = ((int)($_POST['is_active'] ?? 0) === 1) ? 1 : 0;

    $result = MerchantRegistry::setModuleStatus($modKey, $isActive);

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1');

    if (!empty($result['ok'])) {
        $statusLabel = $isActive === 1 ? 'ACTIVE' : 'INACTIVE';
        MerchantRegistry::logAudit($adminId, $adminUsername, 'TOGGLE_MODULE', "Set module '{$modKey}' to {$statusLabel}");
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array(
                'status' => 'success',
                'message' => "Module '{$modKey}' is now {$statusLabel}.",
                'module_key' => $modKey,
                'is_active' => $isActive
            ));
            exit;
        }
        setFlash('ok', "Module '{$modKey}' status updated to {$statusLabel}.");
    } else {
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(400);
            echo json_encode(array('status' => 'error', 'message' => $result['error'] ?? 'Failed to update module status.'));
            exit;
        }
        setFlash('error', $result['error'] ?? 'Failed to update module status.');
    }

    $redirectView = isset($_POST['current_view']) ? urlencode($_POST['current_view']) : 'dashboard';
    header("Location: index.php?view={$redirectView}");
    exit;
}

// 5c. TOGGLE MOBILE APP OR MASTER ANDROID STATUS (Does NOT affect Web POS)
if ($action === 'toggle_app_status') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?view=mobile');
        exit;
    }

    verifyCsrfToken();

    $merchantId = (int)($_POST['merchant_id'] ?? 0);
    $appType = trim((string)($_POST['app_type'] ?? ''));
    $isActive = ((int)($_POST['is_active'] ?? 0) === 1) ? 1 : 0;

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1');

    try {
        $result = MerchantRegistry::setAppStatus($merchantId, $appType, $isActive);
    } catch (Throwable $e) {
        $result = array('ok' => false, 'error' => 'Could not update app status: ' . $e->getMessage());
    }

    $appName = ($appType === 'attendance') ? 'Capture Attendance' : (($appType === 'order_taking') ? 'Order Taking' : 'Master Android Connection');

    if (empty($result['ok'])) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(400);
            echo json_encode(array('status' => 'error', 'message' => $result['error'] ?? 'App status update failed.'));
            exit;
        }
        setFlash('error', $result['error'] ?? 'App status update failed.');
    } else {
        $statusStr = $isActive === 1 ? 'ACTIVE' : 'INACTIVE';
        MerchantRegistry::logAudit($adminId, $adminUsername, 'TOGGLE_APP_STATUS', "Set {$appName} to {$statusStr} for Merchant ID {$merchantId}", $merchantId);
        $msg = "{$appName} is now {$statusStr}. (Web POS remains unaffected)";
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array(
                'status' => 'success',
                'message' => $msg,
                'merchant_id' => $merchantId,
                'app_type' => $appType,
                'is_active' => $isActive
            ));
            exit;
        }
        setFlash('ok', $msg);
    }
    header('Location: index.php?view=mobile');
    exit;
}

// 5d. UPDATE MOBILE APP CODES
if ($action === 'update_app_codes') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?view=mobile');
        exit;
    }

    verifyCsrfToken();

    $merchantId = (int)($_POST['merchant_id'] ?? 0);
    $androidCode = trim((string)($_POST['android_code'] ?? ''));
    $orderTakingCode = trim((string)($_POST['order_taking_code'] ?? ''));

    try {
        $result = MerchantRegistry::updateAppCodes($merchantId, $androidCode, $orderTakingCode);
    } catch (Throwable $e) {
        $result = array('ok' => false, 'error' => 'Could not update app codes: ' . $e->getMessage());
    }

    if (empty($result['ok'])) {
        setFlash('error', $result['error'] ?? 'Could not update app codes.');
    } else {
        MerchantRegistry::logAudit($adminId, $adminUsername, 'UPDATE_APP_CODES', "Updated Android & Order Taking codes for Merchant ID {$merchantId}", $merchantId);
        setFlash('ok', "Mobile application connection codes updated successfully.");
    }
    header('Location: index.php?view=mobile');
    exit;
}

// 6. SYNC ALL STORAGE
if ($action === 'sync_storage') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();
    }
    try {
        $synced = MerchantRegistry::syncAllTenantAssets();
        MerchantRegistry::logAudit($adminId, $adminUsername, 'SYNC_STORAGE', "Verified & synchronized storage directories for {$synced} tenants");
        setFlash('ok', 'All ' . $synced . ' tenant storage directories in assets/mdata/ verified and mounted.');
    } catch (Throwable $e) {
        setFlash('error', 'Storage sync failed: ' . $e->getMessage());
    }
    header('Location: index.php?view=storage');
    exit;
}

// 7. 1-CLICK TENANT DATABASE BACKUP (.sql download)
if ($action === 'backup_db') {
    $id = (int)($_GET['id'] ?? 0);
    $target = MerchantRegistry::getMerchantById($id);

    if (!$target) {
        setFlash('error', 'Target merchant record was not found for database backup.');
        header('Location: index.php?view=databases');
        exit;
    }

    try {
        $sql = MerchantRegistry::exportDatabaseSql(
            $target['hostname'] ?? 'localhost',
            $target['db_user'] ?? 'root',
            $target['db_password'] ?? '',
            $target['database_name']
        );

        MerchantRegistry::logAudit($adminId, $adminUsername, 'BACKUP_DB', "Exported SQL backup for {$target['name']} ({$target['database_name']})", $id);

        $safeCode = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$target['code']);
        $filename = 'backup_' . $safeCode . '_' . date('Ymd_His') . '.sql';

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $sql;
        exit;
    } catch (Throwable $e) {
        setFlash('error', 'Database backup failed: ' . $e->getMessage());
        header('Location: index.php?view=databases');
        exit;
    }
}

// 8. EXPORT MERCHANTS REGISTRY (CSV Download)
if ($action === 'export_csv') {
    try {
        $all = MerchantRegistry::merchants();
        MerchantRegistry::logAudit($adminId, $adminUsername, 'EXPORT_CSV', 'Exported merchants registry to CSV');

        $filename = 'merchants_registry_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($out, array('ID', 'Name', 'Subdomain Code', 'Database Name', 'Host', 'DB User', 'Phone', 'Email', 'Balance', 'Android Code', 'Plan Expiry', 'Status', 'Created At'));
        foreach ($all as $m) {
            fputcsv($out, array(
                $m['id'],
                $m['name'],
                $m['code'],
                $m['database_name'],
                !empty($m['hostname']) ? $m['hostname'] : 'localhost',
                !empty($m['db_user']) ? $m['db_user'] : 'root',
                $m['phone'],
                $m['email'],
                $m['balance'],
                $m['android_code'],
                !empty($m['plan_expiry']) ? $m['plan_expiry'] : 'Lifetime',
                ((int)$m['active'] === 1) ? 'Active' : 'Suspended',
                $m['created_at']
            ));
        }
        fclose($out);
        exit;
    } catch (Throwable $e) {
        setFlash('error', 'Export failed: ' . $e->getMessage());
        header('Location: index.php?view=merchants');
        exit;
    }
}

// DEFAULT: RENDER REQUESTED SCREEN VIEW
$merchants = array();
$fetchError = '';
try {
    $merchants = MerchantRegistry::merchants();
} catch (Throwable $e) {
    $fetchError = 'Could not load merchant list from master database.';
}

$audit_logs = array();
try {
    $audit_logs = MerchantRegistry::auditLogs(100);
} catch (Throwable $e) {
    $audit_logs = array();
}

$diagnostics = array();
try {
    $diagnostics = MerchantRegistry::systemDiagnostics();
} catch (Throwable $e) {
    $diagnostics = array();
}

$error = getFlash('error');
if ($fetchError !== '') {
    $error = $fetchError;
}
$ok = getFlash('ok');
$open_form = getFlash('open_form') === '1';

// Determine separate screen view
$validViews = array('dashboard', 'merchants', 'databases', 'storage', 'mobile', 'security', 'audit');
$view = isset($_GET['view']) ? trim((string)$_GET['view']) : 'dashboard';
if (!in_array($view, $validViews, true)) {
    $view = 'dashboard';
}

$modules = array();
try {
    $modules = MerchantRegistry::modules();
} catch (Throwable $e) {
    $modules = array();
}

$screenFile = __DIR__ . '/views/screens/' . $view . '.php';
if (!is_file($screenFile)) {
    $view = 'dashboard';
    $screenFile = __DIR__ . '/views/screens/dashboard.php';
}

require_once __DIR__ . '/views/layout/header.php';
require_once $screenFile;
require_once __DIR__ . '/views/layout/footer.php';
