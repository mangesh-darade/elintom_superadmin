<?php defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or exit('Direct script access denied.'); ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
            <span>MySQL Tenant Databases &amp; Auto-Provisioning</span>
        </h3>
        <span class="sys-status" style="font-size: 11px;">Zero Static Configuration</span>
    </div>
    <div class="card-body">
        <p style="margin: 0 0 16px; color: var(--text-muted); font-size: 13.5px;">
            Each tenant can either share a MySQL server with a dedicated database or reside on external instances. CodeIgniter dynamically connects to the database via <code>MerchantRegistry</code> at bootstrap.
        </p>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>MySQL Database</th>
                    <th>Database Host</th>
                    <th>DB User</th>
                    <th>Associated Merchant</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($merchants)) {
                foreach ($merchants as $m) {
                    $dbName = (string) $m['database_name'];
                    $canConnect = MerchantRegistry::canConnect($m['hostname'] ?? 'localhost', $m['db_user'] ?? 'root', $m['db_password'] ?? '', $dbName);
            ?>
                <tr>
                    <td>
                        <span class="pill-db">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                            <?php echo htmlspecialchars($dbName, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td><code><?php echo htmlspecialchars((string) ($m['hostname'] ?? 'localhost'), ENT_QUOTES, 'UTF-8'); ?></code></td>
                    <td><code><?php echo htmlspecialchars((string) ($m['db_user'] ?? 'root'), ENT_QUOTES, 'UTF-8'); ?></code></td>
                    <td>
                        <strong><?php echo htmlspecialchars((string) $m['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span style="font-size: 12px; color: var(--text-muted);"> (<code><?php echo htmlspecialchars((string) $m['code'], ENT_QUOTES, 'UTF-8'); ?></code>)</span>
                    </td>
                    <td>
                        <?php if ($canConnect) { ?>
                            <span class="status-badge status-active">Online</span>
                        <?php } else { ?>
                            <span class="status-badge status-suspended">Unreachable</span>
                        <?php } ?>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a href="index.php?action=backup_db&id=<?php echo (int) $m['id']; ?>" class="action-btn action-btn-backup btn-action-backup" title="Download MySQL Backup (.sql)" aria-label="Download MySQL Backup">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        </a>
                    </td>
                </tr>
            <?php } } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Plesk Hosting Guide Card -->
<div class="card" style="border-left: 3px solid var(--primary); margin-top: 12px;">
    <div class="card-header" style="background: #faf5ff;">
        <h3 class="card-title" style="color: #6b21a8;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            <span>Plesk Server Deployment &amp; Auto-DB Creation Guide</span>
        </h3>
        <span class="sys-status" style="background:#f3e8ff; border-color:#d8b4fe; color:#6b21a8; font-size: 10px; padding: 1px 6px;">Plesk Ready</span>
    </div>
    <div class="card-body">
        <p style="margin: 0 0 10px; color: var(--text-muted); font-size: 11.5px;">
            When deploying on a Plesk hosting server, there are 2 standard methods to automate MySQL database creation for newly registered merchants:
        </p>
        <div class="two-col-grid">
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 12px;">
                <div style="font-weight: 700; font-size: 12.5px; color: #1e293b; margin-bottom: 6px; display:flex; align-items:center; gap:6px;">
                    <span style="width:18px; height:18px; background:#10b981; color:#fff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:800;">1</span>
                    Option A: MySQL Wildcard Privilege (Recommended — Fastest)
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin: 0 0 6px;">
                    Run this one-time SQL grant command in Plesk phpMyAdmin or MySQL console:
                </p>
                <pre style="background:#0f172a; color:#a5f3fc; padding:8px 10px; border-radius:4px; font-size:11px; font-family:'JetBrains Mono', monospace; margin:0 0 6px; overflow-x:auto;">GRANT ALL PRIVILEGES ON `stadmin_%`.* TO 'your_db_user'@'localhost';
FLUSH PRIVILEGES;</pre>
                <p style="font-size: 10.5px; color: #059669; margin: 0; font-weight: 600;">
                    &check; Allows the script to auto-create, clone, and manage any <code>stadmin_*</code> database without requiring external API calls.
                </p>
            </div>
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 12px;">
                <div style="font-weight: 700; font-size: 12.5px; color: #1e293b; margin-bottom: 6px; display:flex; align-items:center; gap:6px;">
                    <span style="width:18px; height:18px; background:#4f46e5; color:#fff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:800;">2</span>
                    Option B: Plesk REST API v2 Integration
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin: 0 0 6px;">
                    Generate an API key in Plesk Panel &rarr; Profile &rarr; <strong>API Keys</strong> and configure in <code>super_admin/config/master.php</code>:
                </p>
                <pre style="background:#0f172a; color:#fbcfe8; padding:8px 10px; border-radius:4px; font-size:11px; font-family:'JetBrains Mono', monospace; margin:0 0 6px; overflow-x:auto;">'plesk_enabled' =&gt; true,
'plesk_host'    =&gt; 'https://your-domain.com:8443',
'plesk_api_key' =&gt; 'your-secret-api-key',</pre>
                <p style="font-size: 10.5px; color: #4338ca; margin: 0; font-weight: 600;">
                    &check; Automatically provisions databases via Plesk REST API v2 and registers them directly inside your Plesk Panel.
                </p>
            </div>
        </div>
        <div style="margin-top: 10px; padding: 8px 12px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: var(--radius-sm); font-size: 11.5px; color: #92400e;">
            <strong>Wildcard Subdomain DNS Setting:</strong> In Plesk DNS Settings, point <code>*.yourdomain.com &rarr; A Record &rarr; Your Server IP</code>. Also ensure <code>ServerAlias *.yourdomain.com</code> is enabled in your Apache VirtualHost / Nginx configuration. Every new tenant subdomain (e.g. <code>shop1.yourdomain.com</code>) will automatically route to the main POS application.
        </div>
    </div>
</div>
