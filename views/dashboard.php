<?php defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or exit('Direct script access denied.');
$view = isset($view) ? $view : 'dashboard';
$screenFile = __DIR__ . '/screens/' . $view . '.php';
if (!is_file($screenFile)) {
    $screenFile = __DIR__ . '/screens/dashboard.php';
}
require_once __DIR__ . '/layout/header.php';
require_once $screenFile;
require_once __DIR__ . '/layout/footer.php';
