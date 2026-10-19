<?php defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or exit('Direct script access denied.'); ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline><path d="M12 2a10 10 0 1 0 10 10"></path></svg>
            <span>Super Admin Activity Audit Trail</span>
        </h3>
        <span class="sys-status" style="font-size: 11px;">Showing latest <?php echo is_array($audit_logs ?? null) ? count($audit_logs) : 0; ?> events</span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Timestamp (UTC)</th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Merchant ID</th>
                    <th>Event Description</th>
                    <th>Client IP</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($audit_logs)) {
                foreach ($audit_logs as $log) { ?>
                <tr>
                    <td><code style="font-size:12px;"><?php echo htmlspecialchars((string) $log['created_at'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                    <td><strong><?php echo htmlspecialchars((string) $log['admin_username'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                    <td><span class="pill-code"><?php echo htmlspecialchars((string) $log['action'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo !empty($log['merchant_id']) ? '#' . (int) $log['merchant_id'] : '<span style="color:var(--text-subtle);">&mdash;</span>'; ?></td>
                    <td><?php echo htmlspecialchars((string) $log['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><code><?php echo htmlspecialchars((string) $log['ip_address'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                </tr>
            <?php } } else { ?>
                <tr>
                    <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                        No audit trail entries recorded yet.
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
