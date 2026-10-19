<?php defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or exit('Direct script access denied.');

// Ensure modules and stats are available
if (!isset($modules) || !is_array($modules)) {
    $modules = MerchantRegistry::modules();
}
$view = isset($view) ? (string) $view : 'dashboard';
$currentModule = $modules[$view] ?? null;
$isCurrentActive = $currentModule ? ((int)($currentModule['is_active'] ?? 1) === 1) : true;

$totalCount = isset($merchants) && is_array($merchants) ? count($merchants) : 0;
$activeCount = 0;
$suspendedCount = 0;
$totalBalance = 0.0;
if (!empty($merchants)) {
    foreach ($merchants as $m) {
        if ((int)($m['active'] ?? 0) === 1) {
            $activeCount++;
        } else {
            $suspendedCount++;
        }
        $totalBalance += (float)($m['balance'] ?? 0);
    }
}
$auditCount = isset($audit_logs) && is_array($audit_logs) ? count($audit_logs) : 0;

$viewTitles = array(
    'dashboard' => 'Dashboard Overview',
    'merchants' => 'Merchants & Stores',
    'databases' => 'MySQL Databases',
    'storage'   => 'Tenant Storage (mdata)',
    'mobile'    => 'Mobile App Codes',
    'security'  => 'Suspension & Guard',
    'audit'     => 'Audit Trail Logs',
);
$pageTitle = $viewTitles[$view] ?? 'Master Console';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> — ElintOM Super Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-w: 205px;
            --topbar-h: 42px;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --surface-subtle: #f1f5f9;
            --border: #e2e8f0;
            --border-hover: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-subtle: #94a3b8;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --success: #10b981;
            --success-light: #ecfdf5;
            --danger: #ef4444;
            --danger-light: #fef2f2;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --radius-sm: 4px;
            --radius-md: 6px;
            --radius-lg: 8px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.04);
            --shadow-md: 0 2px 4px -1px rgb(0 0 0 / 0.06);
            --shadow-lg: 0 6px 12px -2px rgb(0 0 0 / 0.08);
            --shadow-xl: 0 12px 20px -3px rgb(0 0 0 / 0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
            display: flex;
            min-height: 100vh;
            font-size: 12.5px;
        }

        /* Sidebar - Compact & Sleek */
        .sidebar {
            width: var(--sidebar-w);
            background: #0f172a;
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 50;
            transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }
        .sidebar-brand {
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            height: var(--topbar-h);
        }
        .brand-logo {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
            color: #fff;
            box-shadow: 0 2px 5px rgba(99, 102, 241, 0.35);
            flex-shrink: 0;
        }
        .brand-name {
            color: #fff;
            font-weight: 700;
            font-size: 12.5px;
            letter-spacing: -0.01em;
            line-height: 1.15;
        }
        .brand-tag {
            font-size: 9px;
            color: #818cf8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .sidebar-menu {
            flex: 1;
            padding: 4px 6px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }
        .menu-heading {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            padding: 6px 6px 2px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px 8px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
            transition: all 0.12s ease;
            border: 1px solid transparent;
            position: relative;
        }
        .nav-item-content { display: flex; align-items: center; gap: 8px; }
        .nav-item svg { width: 14px; height: 14px; stroke-width: 2; color: #64748b; flex-shrink: 0; }
        .nav-item:hover { color: #fff; background: rgba(255, 255, 255, 0.05); }
        .nav-item:hover svg { color: #cbd5e1; }
        .nav-item.active {
            color: #fff;
            background: rgba(99, 102, 241, 0.18);
            border-color: rgba(99, 102, 241, 0.32);
            font-weight: 600;
        }
        .nav-item.active svg { color: #818cf8; }
        .nav-item.module-disabled { opacity: 0.55; }
        .nav-badge-wrap { display: flex; align-items: center; gap: 5px; }
        .nav-badge {
            font-size: 9.5px;
            font-weight: 700;
            padding: 1px 4px;
            border-radius: 999px;
            background: rgba(99, 102, 241, 0.25);
            color: #c7d2fe;
            line-height: 1.2;
        }
        .nav-dot-status {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .nav-dot-active { background: #10b981; box-shadow: 0 0 5px rgba(16, 185, 129, 0.5); }
        .nav-dot-inactive { background: #f43f5e; box-shadow: 0 0 4px rgba(244, 63, 94, 0.4); }
        .sidebar-footer {
            padding: 6px 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            background: #090d16;
        }
        .user-card { display: flex; align-items: center; justify-content: space-between; }
        .user-meta { display: flex; align-items: center; gap: 7px; }
        .user-avatar {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 10px;
            color: #f1f5f9;
        }
        .user-name { font-size: 11.5px; font-weight: 600; color: #fff; line-height: 1.15; }
        .user-role { font-size: 9px; color: #64748b; }
        .btn-sidebar-logout {
            background: transparent;
            border: 0;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.12s ease;
        }
        .btn-sidebar-logout:hover { color: #f87171; background: rgba(239, 68, 68, 0.1); }

        /* Sidebar backdrop for mobile */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            z-index: 45;
        }
        .sidebar-backdrop.active { display: block; }

        /* Main Content Wrapper - Compact */
        .main-wrapper {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left 0.22s ease;
        }
        .topbar {
            height: var(--topbar-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 30;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .topbar-left { display: flex; align-items: center; gap: 10px; }
        .mobile-toggle {
            display: none;
            background: transparent;
            border: 0;
            padding: 4px;
            cursor: pointer;
            color: var(--text-main);
            border-radius: 4px;
        }
        .mobile-toggle:hover { background: var(--surface-subtle); }
        .breadcrumbs { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .breadcrumb-active { color: var(--text-main); font-weight: 600; }
        .topbar-right { display: flex; align-items: center; gap: 8px; }
        .sys-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 10.5px;
            font-weight: 500;
            color: #047857;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 2px 7px;
            border-radius: 999px;
        }
        .dot-pulse {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .content-body { padding: 10px 14px 28px; flex: 1; }

        /* Alerts - Compact */
        .alert {
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 500;
        }
        .alert-ok { background: var(--success-light); color: #065f46; border: 1px solid #a7f3d0; }
        .alert-err { background: var(--danger-light); color: #991b1b; border: 1px solid #fecaca; }

        /* Module Header Bar - Compact & Low-Profile */
        .module-header-bar {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 6px 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
            flex-wrap: wrap;
            gap: 8px;
        }
        .module-header-info { display: flex; align-items: center; gap: 8px; }
        .module-header-icon {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: #eef2ff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .module-header-icon svg { width: 14px; height: 14px; }
        .module-header-title { font-size: 13px; font-weight: 700; color: var(--text-main); margin: 0; line-height: 1.2; }
        .module-header-desc { font-size: 11px; color: var(--text-muted); margin: 1px 0 0; line-height: 1.2; }
        .module-toggle-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid var(--border);
            padding: 3px 8px;
            border-radius: 999px;
        }
        .module-toggle-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }
        .switch-toggle {
            position: relative;
            display: inline-block;
            width: 32px;
            height: 16px;
            margin: 0;
            flex-shrink: 0;
        }
        .switch-toggle input { opacity: 0; width: 0; height: 0; }
        .switch-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .2s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 16px;
        }
        .switch-slider:before {
            position: absolute;
            content: "";
            height: 12px;
            width: 12px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .2s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 50%;
            box-shadow: 0 1px 2px rgba(0,0,0,0.25);
        }
        .switch-toggle input:checked + .switch-slider { background-color: #10b981; }
        .switch-toggle input:checked + .switch-slider:before { transform: translateX(16px); }
        .badge-module-status {
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 999px;
            letter-spacing: 0.02em;
        }
        .badge-module-status.active { background: #dcfce7; color: #15803d; }
        .badge-module-status.inactive { background: #fee2e2; color: #b91c1c; }

        /* Inactive Screen Warning Banner - Compact */
        .module-disabled-banner {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-left: 3px solid #e11d48;
            border-radius: var(--radius-sm);
            padding: 6px 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            color: #9f1239;
            font-size: 11.5px;
            box-shadow: var(--shadow-sm);
        }
        .module-disabled-banner strong { font-weight: 700; }
        .btn-quick-activate {
            background: #e11d48;
            color: #fff;
            border: 0;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 10.5px;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.12s ease;
        }
        .btn-quick-activate:hover { background: #be123c; }

        /* Stats Grid - Compact & Responsive */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 8px;
            margin-bottom: 10px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 8px 12px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.15s ease;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); border-color: var(--border-hover); }
        .stat-label { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 2px; }
        .stat-value { font-size: 18px; font-weight: 800; color: var(--text-main); letter-spacing: -0.02em; line-height: 1; }
        .stat-icon { width: 30px; height: 30px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; }
        .stat-icon svg { width: 15px; height: 15px; }
        .icon-blue { background: #e0e7ff; color: #4338ca; }
        .icon-green { background: #dcfce7; color: #15803d; }
        .icon-rose { background: #ffe4e6; color: #be123c; }
        .icon-amber { background: #fef3c7; color: #b45309; }

        /* Controls Bar - Compact Single Row */
        .controls-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 6px 10px;
            margin-bottom: 10px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .search-box { position: relative; flex: 1; min-width: 180px; max-width: 340px; }
        .search-icon { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: var(--text-subtle); pointer-events: none; width: 12px; height: 12px; }
        .search-input {
            width: 100%;
            padding: 5px 8px 5px 28px;
            background: var(--surface-subtle);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 11.5px;
            color: var(--text-main);
            outline: none;
            transition: all 0.12s ease;
            font-family: inherit;
            height: 28px;
        }
        .search-input:focus { background: #fff; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.12); }
        .filter-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .filter-select {
            padding: 4px 8px;
            background: var(--surface-subtle);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 11.5px;
            font-family: inherit;
            color: var(--text-main);
            outline: none;
            cursor: pointer;
            height: 28px;
        }
        .btn-primary-action {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--primary);
            color: #fff;
            border: 0;
            padding: 5px 10px;
            border-radius: var(--radius-sm);
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(79, 70, 229, 0.25);
            transition: all 0.12s ease;
            font-family: inherit;
            text-decoration: none;
            height: 28px;
            white-space: nowrap;
        }
        .btn-primary-action:hover { background: var(--primary-hover); transform: translateY(-1px); }

        /* Card & Tables - Compact & Responsive */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 10px;
        }
        .card-header {
            padding: 8px 12px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            gap: 8px;
            flex-wrap: wrap;
        }
        .card-title { margin: 0; font-size: 12.5px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 6px; }
        .card-title svg { width: 14px; height: 14px; }
        .card-body { padding: 10px 12px; }
        .table-responsive {
            overflow-x: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 11.5px; }
        th {
            background: #f8fafc;
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 6px 5px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        td { padding: 5px 5px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: var(--text-main); font-size: 11.5px; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fbfdff; }
        tbody tr.suspended-row td { background: #fafafa; }
        tbody tr.suspended-row .merchant-name { color: #64748b; }

        .merchant-title { display: flex; align-items: center; gap: 6px; }
        .merchant-avatar {
            width: 22px;
            height: 22px;
            border-radius: 5px;
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: #3730a3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 9.5px;
            flex-shrink: 0;
            text-transform: uppercase;
        }
        .merchant-name { font-weight: 650; font-size: 12px; color: var(--text-main); line-height: 1.2; }
        .merchant-created { font-size: 10px; color: var(--text-subtle); margin-top: 1px; }
        .pill-code {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-size: 10.5px;
            background: #f1f5f9;
            color: #334155;
            padding: 1px 5px;
            border-radius: var(--radius-sm);
            border: 1px solid #e2e8f0;
            font-weight: 500;
        }
        .pill-db {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 10.5px;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 1px 5px;
            border-radius: var(--radius-sm);
            border: 1px solid #dbeafe;
        }
        .pill-android {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10.5px;
            font-weight: 600;
            background: #fef3c7;
            color: #92400e;
            padding: 1px 5px;
            border-radius: var(--radius-sm);
            border: 1px solid #fde68a;
            letter-spacing: 0.03em;
        }
        .link-url {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            max-width: 175px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 11.5px;
        }
        .link-url:hover { text-decoration: underline; color: #3730a3; }
        .subdomain-subtext {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            color: var(--text-subtle);
            margin-top: 3px;
        }
        .subdomain-label {
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-subtle);
            font-weight: 600;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 1px 6px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
        }
        .status-badge::before {
            content: '';
            width: 4px;
            height: 4px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-active { background: #ecfdf5; color: #047857; }
        .status-active::before { background: #10b981; }
        .status-suspended { background: #fef2f2; color: #b91c1c; }
        .status-suspended::before { background: #ef4444; }

        /* Modern Action Icon Buttons */
        .action-icon-group {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            justify-content: flex-end;
        }
        .action-btn {
            width: 26px;
            height: 26px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            background: #ffffff;
            cursor: pointer;
            transition: all 0.14s ease;
            text-decoration: none;
            padding: 0;
            margin: 0;
            line-height: 1;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            flex-shrink: 0;
        }
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        .action-btn svg { width: 13px; height: 13px; stroke-width: 2.2; }

        /* Color accents for each action */
        .action-btn-backup, .btn-action-backup {
            color: #0284c7;
            background: #f0f9ff;
            border-color: #bae6fd;
        }
        .action-btn-backup:hover, .btn-action-backup:hover {
            color: #0369a1;
            background: #e0f2fe;
            border-color: #7dd3fc;
        }

        .action-btn-edit, .btn-action-edit {
            color: #4f46e5;
            background: #eef2ff;
            border-color: #c7d2fe;
        }
        .action-btn-edit:hover, .btn-action-edit:hover {
            color: #4338ca;
            background: #e0e7ff;
            border-color: #a5b4fc;
        }

        .action-btn-clone, .btn-action-clone {
            color: #7c3aed;
            background: #f5f3ff;
            border-color: #ddd6fe;
        }
        .action-btn-clone:hover, .btn-action-clone:hover {
            color: #6d28d9;
            background: #ede9fe;
            border-color: #c4b5fd;
        }

        .action-btn-suspend, .btn-toggle-suspend {
            color: #e11d48;
            background: #fff1f2;
            border-color: #fecdd3;
        }
        .action-btn-suspend:hover, .btn-toggle-suspend:hover {
            color: #be123c;
            background: #ffe4e6;
            border-color: #fda4af;
        }

        .action-btn-activate, .btn-toggle-activate {
            color: #059669;
            background: #ecfdf5;
            border-color: #a7f3d0;
        }
        .action-btn-activate:hover, .btn-toggle-activate:hover {
            color: #047857;
            background: #d1fae5;
            border-color: #6ee7b7;
        }

        /* Architecture Flow - Compact */
        .arch-flow-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 8px; margin-top: 8px; }
        .arch-step-card { background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 10px; position: relative; }
        .arch-step-num { width: 18px; height: 18px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 9.5px; font-weight: 800; margin-bottom: 4px; }
        .arch-step-title { font-weight: 700; font-size: 11.5px; color: var(--text-main); margin-bottom: 2px; }
        .arch-step-desc { font-size: 10.5px; color: var(--text-muted); line-height: 1.35; }

        /* Reusable Responsive Grid Utilities */
        .two-col-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .module-cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 10px; }

        /* Modals - Compact */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(3px);
            z-index: 60;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }
        .modal-backdrop.open { display: flex; animation: fadeIn 0.15s ease; }
        .modal-card, .modal {
            background: #fff;
            border-radius: 10px;
            width: 100%;
            max-width: 580px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border);
            animation: modalScale 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalScale {
            from { transform: scale(0.97) translateY(6px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        .modal-header {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-title { font-size: 13.5px; font-weight: 700; color: var(--text-main); margin: 0; }
        .btn-modal-close { background: transparent; border: 0; color: var(--text-subtle); cursor: pointer; padding: 4px; border-radius: 4px; font-size: 18px; line-height: 1; }
        .btn-modal-close:hover { color: var(--text-main); background: var(--surface-subtle); }
        .modal-body { padding: 12px 14px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .form-full { grid-column: span 2; }
        .form-group { margin-bottom: 2px; }
        .form-label { display: block; font-size: 11px; font-weight: 600; color: #334155; margin-bottom: 3px; }
        .form-label span.req { color: #dc2626; }
        .form-input {
            width: 100%;
            padding: 5px 8px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 12px;
            color: var(--text-main);
            outline: none;
            transition: all 0.12s ease;
            font-family: inherit;
        }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15); }
        .form-input-code { font-family: 'JetBrains Mono', monospace; font-weight: 600; }
        .form-hint { font-size: 10px; color: var(--text-muted); margin-top: 2px; display: block; }
        .modal-footer {
            padding: 10px 14px;
            border-top: 1px solid var(--border);
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            border-radius: 0 0 10px 10px;
        }
        .btn-secondary {
            background: #fff;
            border: 1px solid var(--border);
            color: #334155;
            padding: 5px 12px;
            border-radius: var(--radius-sm);
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-secondary:hover { background: #f8fafc; }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            bottom: 14px;
            right: 14px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .toast {
            background: #1e293b;
            color: #fff;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 500;
            box-shadow: 0 6px 12px -2px rgba(0,0,0,0.25);
            display: flex;
            align-items: center;
            gap: 6px;
            animation: slideInToast 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            max-width: 320px;
        }
        .toast-success { border-left: 3px solid #10b981; }
        .toast-error { border-left: 3px solid #ef4444; }
        @keyframes slideInToast { from { transform: translateX(16px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        /* Responsive Breakpoints */
        @media (max-width: 960px) {
            .sidebar { transform: translateX(-100%); width: 220px; }
            .sidebar.mobile-open { transform: translateX(0); box-shadow: var(--shadow-xl); }
            .main-wrapper { margin-left: 0; }
            .mobile-toggle { display: block; }
            .content-body { padding: 10px 12px 24px; }
            .two-col-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .form-grid { grid-template-columns: 1fr; }
            .form-full { grid-column: span 1; }
        }
        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: 1fr; }
            .controls-card { flex-direction: column; align-items: stretch; }
            .search-box { max-width: none; min-width: 0; }
            .filter-group { justify-content: space-between; }
            .topbar-right .sys-status span:last-child { display: none; }
            .module-header-bar { flex-direction: column; align-items: flex-start; }
            .module-toggle-wrap { align-self: flex-start; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">E</div>
            <div>
                <div class="brand-name">ElintOM</div>
                <div class="brand-tag">Super Admin</div>
            </div>
        </div>

        <div class="sidebar-menu">
            <div class="menu-heading">Main Navigation</div>
            
            <a href="index.php?view=dashboard" class="nav-item <?php echo $view === 'dashboard' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>Dashboard</span>
                </div>
            </a>

            <a href="index.php?view=merchants" class="nav-item <?php echo $view === 'merchants' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span>Merchants &amp; Stores</span>
                </div>
                <span class="nav-badge"><?php echo $totalCount; ?></span>
            </a>

            <div class="menu-heading">Infrastructure &amp; SaaS</div>

            <a href="index.php?view=databases" class="nav-item <?php echo $view === 'databases' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                    <span>MySQL Databases</span>
                </div>
            </a>

            <a href="index.php?view=storage" class="nav-item <?php echo $view === 'storage' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    <span>Tenant Storage (mdata)</span>
                </div>
            </a>

            <div class="menu-heading">Integrations &amp; Security</div>

            <a href="index.php?view=mobile" class="nav-item <?php echo $view === 'mobile' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    <span>Mobile App Codes</span>
                </div>
            </a>

            <a href="index.php?view=security" class="nav-item <?php echo $view === 'security' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span>Suspension &amp; Guard</span>
                </div>
            </a>

            <a href="index.php?view=audit" class="nav-item <?php echo $view === 'audit' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                    <span>Audit Trail Logs</span>
                </div>
                <span class="nav-badge"><?php echo $auditCount; ?></span>
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-meta">
                    <div class="user-avatar">SA</div>
                    <div>
                        <div class="user-name">Super Admin</div>
                        <div class="user-role"><?php echo htmlspecialchars($adminUsername ?? 'master_control', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
                <a href="index.php?action=logout" class="btn-sidebar-logout" title="Sign Out">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </a>
            </div>
        </div>
    </aside>

    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" id="mobile-toggle-btn" aria-label="Toggle Navigation">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div class="breadcrumbs">
                    <span>Console</span>
                    <span>/</span>
                    <span class="breadcrumb-active" id="current-breadcrumb"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="topbar-right"></div>
        </header>

        <main class="content-body">
            <!-- Flash Message Alerts -->
            <?php if (!empty($ok)) { ?>
                <div class="alert alert-ok">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php } ?>
            <?php if (!empty($error)) { ?>
                <div class="alert alert-err">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php } ?>

            <!-- DEDICATED MODULE CONTROLLER HEADER WITH ACTIVE/INACTIVE TOGGLE SWITCH -->
            <?php if ($currentModule) { ?>
            <div class="module-header-bar" id="module-bar-<?php echo htmlspecialchars($view, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="module-header-info">
                    <div class="module-header-icon">
                        <?php if ($view === 'dashboard') { ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <?php } elseif ($view === 'merchants') { ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        <?php } elseif ($view === 'databases') { ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                        <?php } elseif ($view === 'storage') { ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        <?php } elseif ($view === 'mobile') { ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                        <?php } elseif ($view === 'security') { ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <?php } else { ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                        <?php } ?>
                    </div>
                    <div>
                        <h2 class="module-header-title"><?php echo htmlspecialchars($currentModule['module_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="module-header-desc"><?php echo htmlspecialchars($currentModule['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <?php if ($view === 'mobile') { ?>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 3px;">
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <input type="text" id="android-test-input" class="form-input form-input-code" placeholder="Test Android Code (e.g. ABCD)" style="text-transform: uppercase; width: 210px; height: 32px; font-size: 11.5px; padding: 4px 10px;">
                        <button type="button" class="btn-primary-action" id="android-test-btn" style="white-space: nowrap; height: 32px; padding: 0 12px; font-size: 11.5px;">Check Code</button>
                    </div>
                    <div id="android-test-result" style="font-size: 11px; font-weight: 600; text-align: right;"></div>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
