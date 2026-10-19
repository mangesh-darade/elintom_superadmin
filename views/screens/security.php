<?php defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or exit('Direct script access denied.'); ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <span>Tenant Suspension Guard &amp; Access Control</span>
        </h3>
        <span class="sys-status" style="font-size: 11px;">pre_controller Hook Enforcement</span>
    </div>
    <div class="card-body">
        <p style="margin: 0 0 8px; color: var(--text-muted); font-size: 11.5px;">
            When a merchant is suspended or their subscription plan has expired, CodeIgniter intercepts the request in <code>Merchant_suspend::check()</code> before any shop controller or database is touched, rendering an immediate HTTP 403 Forbidden screen.
        </p>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Shop Name</th>
                    <th>Subdomain</th>
                    <th>Store URL</th>
                    <th>Current Guard State</th>
                    <th style="text-align: right;">
                        <span style="display:inline-flex; align-items:center; gap:5px; justify-content:flex-end;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            Action
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php
                $anySuspended = false;
                if (!empty($merchants)) {
                    foreach ($merchants as $m) {
                        $isSuspended = ((int)$m['active'] !== 1);
                        if ($isSuspended) {
                            $anySuspended = true;
            ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars((string) $m['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                    <td><span class="pill-code"><?php echo htmlspecialchars((string) $m['code'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><code><?php echo htmlspecialchars((string) $m['url'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                    <td><span class="status-badge status-suspended">403 Intercepted &amp; Blocked</span></td>
                    <td style="text-align: right;">
                        <form action="index.php?action=suspend" method="POST" style="margin:0;" onsubmit="return confirm('Activate this merchant shop?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="merchant_id" value="<?php echo (int) $m['id']; ?>">
                            <input type="hidden" name="active" value="1">
                            <button type="submit" class="action-btn action-btn-activate btn-toggle-activate" title="Re-Activate Shop" aria-label="Activate Shop">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php } } }
            if (!$anySuspended) { ?>
                <tr>
                    <td colspan="5" style="padding: 30px; text-align: center; color: #059669; font-weight: 600;">
                        &bull; All tenant shops are currently active and unobstructed.
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
