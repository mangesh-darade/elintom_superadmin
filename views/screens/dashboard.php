<?php defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or exit('Direct script access denied.'); ?>

<!-- KPI STATS CARDS -->
<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">Total Registered Tenants</div>
            <div class="stat-value"><?php echo $totalCount; ?></div>
        </div>
        <div class="stat-icon icon-blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Active Shops</div>
            <div class="stat-value" style="color: #059669;"><?php echo $activeCount; ?></div>
        </div>
        <div class="stat-icon icon-green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Suspended Outlets</div>
            <div class="stat-value" style="color: #dc2626;"><?php echo $suspendedCount; ?></div>
        </div>
        <div class="stat-icon icon-rose">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Aggregated Float Balance</div>
            <div class="stat-value" style="color: #4338ca;">&#8377;<?php echo number_format($totalBalance, 2); ?></div>
        </div>
        <div class="stat-icon icon-amber">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
    </div>
</div>


<!-- ROUTING ARCHITECTURE -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span>Dynamic Multi-Tenant SaaS Routing Flow</span>
        </h3>
        <span class="sys-status" style="font-size: 10px; padding: 1px 6px;">100% Zero-File-Edit</span>
    </div>
    <div class="card-body">
        <p style="margin: 0 0 8px; color: var(--text-muted); font-size: 11.5px;">
            How incoming HTTP requests dynamically route to tenant databases and storage without editing static PHP files:
        </p>
        <div class="arch-flow-grid">
            <div class="arch-step-card">
                <div class="arch-step-num">1</div>
                <div class="arch-step-title">HTTP Request</div>
                <div class="arch-step-desc">Subdomain is parsed from <code>$_SERVER['HTTP_HOST']</code> (e.g. <code>hotelraj.elintpos.com</code>).</div>
            </div>
            <div class="arch-step-card">
                <div class="arch-step-num">2</div>
                <div class="arch-step-title">pre_controller Guard</div>
                <div class="arch-step-desc"><code>Merchant_suspend</code> checks <code>elint_master</code>. If suspended &amp; security active, halts with 403 Forbidden.</div>
            </div>
            <div class="arch-step-card">
                <div class="arch-step-num">3</div>
                <div class="arch-step-title">Dynamic DB Resolve</div>
                <div class="arch-step-desc"><code>database.php</code> fetches MySQL DB, host &amp; user from master DB on-the-fly. Zero file writing.</div>
            </div>
            <div class="arch-step-card">
                <div class="arch-step-num">4</div>
                <div class="arch-step-title">Tenant Assets Mount</div>
                <div class="arch-step-desc"><code>assets/mdata/{code}/</code> is auto-mounted for uploads, avatars, thumbs &amp; POS receipt images.</div>
            </div>
        </div>
    </div>
</div>

<!-- SERVER ENVIRONMENT & HOSTING DIAGNOSTICS -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
            <span>Server Environment &amp; Hosting Diagnostics</span>
        </h3>
        <span class="sys-status" style="font-size: 11px;">Environment Verified</span>
    </div>
    <div class="card-body">
        <div class="arch-flow-grid" style="margin-top: 0; margin-bottom: 0;">
            <div class="arch-step-card">
                <div class="arch-step-title" style="display:flex; justify-content:space-between; align-items:center;">
                    <span>PHP Runtime</span>
                    <span style="font-size:11px; padding:2px 6px; background:#dcfce7; color:#15803d; border-radius:4px; font-weight:700;">OK</span>
                </div>
                <div style="font-size: 20px; font-weight:800; color:var(--text-main); margin: 6px 0;">PHP <?php echo htmlspecialchars($diagnostics['php_version'] ?? PHP_VERSION, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="arch-step-desc">Memory: <?php echo htmlspecialchars($diagnostics['memory_limit'] ?? '', ENT_QUOTES, 'UTF-8'); ?> | Max Exec: <?php echo htmlspecialchars($diagnostics['max_execution_time'] ?? '', ENT_QUOTES, 'UTF-8'); ?>s</div>
            </div>
            <div class="arch-step-card">
                <div class="arch-step-title" style="display:flex; justify-content:space-between; align-items:center;">
                    <span>MySQL Server</span>
                    <span style="font-size:11px; padding:2px 6px; background:<?php echo !empty($diagnostics['mysql_connected']) ? '#dcfce7; color:#15803d;' : '#fee2e2; color:#b91c1c;'; ?> border-radius:4px; font-weight:700;">
                        <?php echo !empty($diagnostics['mysql_connected']) ? 'CONNECTED' : 'DISCONNECTED'; ?>
                    </span>
                </div>
                <div style="font-size: 14px; font-weight:700; color:var(--text-main); margin: 6px 0; font-family:'JetBrains Mono', monospace; word-break:break-all;">
                    <?php echo htmlspecialchars($diagnostics['mysql_version'] ?? 'MySQL', ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="arch-step-desc">Master DB: <code>elint_master</code></div>
            </div>
            <div class="arch-step-card">
                <div class="arch-step-title" style="display:flex; justify-content:space-between; align-items:center;">
                    <span>Tenant Storage</span>
                    <span style="font-size:11px; padding:2px 6px; background:<?php echo !empty($diagnostics['mdata_writable']) ? '#dcfce7; color:#15803d;' : '#fee2e2; color:#b91c1c;'; ?> border-radius:4px; font-weight:700;">
                        <?php echo !empty($diagnostics['mdata_writable']) ? 'WRITABLE' : 'READ ONLY'; ?>
                    </span>
                </div>
                <div style="font-size: 15px; font-weight:700; color:var(--text-main); margin: 6px 0;">assets/mdata/</div>
                <div class="arch-step-desc">Upload Max: <?php echo htmlspecialchars($diagnostics['upload_max_filesize'] ?? '', ENT_QUOTES, 'UTF-8'); ?> | Post: <?php echo htmlspecialchars($diagnostics['post_max_size'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="arch-step-card">
                <div class="arch-step-title" style="display:flex; justify-content:space-between; align-items:center;">
                    <span>ElintOm ERP API</span>
                    <span style="font-size:11px; padding:2px 6px; background:<?php echo !empty($diagnostics['api_connected']) ? '#dcfce7; color:#15803d;' : '#fee2e2; color:#b91c1c;'; ?> border-radius:4px; font-weight:700;">
                        <?php echo !empty($diagnostics['api_connected']) ? 'CONNECTED' : 'OFFLINE'; ?>
                    </span>
                </div>
                <div style="font-size: 14px; font-weight:700; color:var(--text-main); margin: 6px 0;">
                    <?php echo htmlspecialchars($diagnostics['api_service'] ?? 'ElintOm API', ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="arch-step-desc">
                    <?php if (!empty($diagnostics['api_connected'])) { ?>
                        ERP PHP: <?php echo htmlspecialchars($diagnostics['api_erp_php'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?> | Storage Sync: Active
                    <?php } else { ?>
                        <span style="color:#dc2626;"><?php echo htmlspecialchars($diagnostics['api_error'] ?? 'Unreachable', ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php } ?>
                </div>
            </div>
            <div class="arch-step-card">
                <div class="arch-step-title" style="display:flex; justify-content:space-between; align-items:center;">
                    <span>Plesk DB Mode</span>
                    <span style="font-size:11px; padding:2px 6px; background:<?php echo !empty($diagnostics['plesk_enabled']) ? '#e0e7ff; color:#3730a3;' : '#f1f5f9; color:#475569;'; ?> border-radius:4px; font-weight:700;">
                        <?php echo !empty($diagnostics['plesk_enabled']) ? 'REST API' : 'Direct MySQL'; ?>
                    </span>
                </div>
                <div style="font-size: 13px; font-weight:600; color:var(--text-main); margin: 6px 0;">
                    <?php echo !empty($diagnostics['plesk_enabled']) ? 'Plesk API v2 Active' : 'Direct MySQL Mode'; ?>
                </div>
                <div class="arch-step-desc"><?php echo htmlspecialchars($diagnostics['plesk_host'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
    </div>
</div>
