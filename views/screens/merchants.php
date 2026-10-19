<?php defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or exit('Direct script access denied.'); ?>

<div class="controls-card">
    <div class="search-box">
        <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" id="live-search" class="search-input" placeholder="Search merchant name, subdomain, database, phone...">
    </div>
    <div class="filter-group">
        <select id="status-filter" class="filter-select">
            <option value="all">All Statuses (<?php echo $totalCount; ?>)</option>
            <option value="active">Active Only (<?php echo $activeCount; ?>)</option>
            <option value="suspended">Suspended Only (<?php echo $suspendedCount; ?>)</option>
        </select>
        <a href="index.php?action=export_csv" class="btn-primary-action" style="background:#0f766e; text-decoration:none;" title="Download CSV spreadsheet of all registered merchants">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Export CSV</span>
        </a>
        <button type="button" class="btn-primary-action" id="open-add-merchant-btn" onclick="document.getElementById('modal-add-merchant').classList.add('open');">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>Add Merchant</span>
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table id="merchants-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Merchant / Shop Name</th>
                    <th style="width: 22%;">Store URL &amp; Subdomain</th>
                    <th style="width: 17%;">Database Target</th>
                    <th style="width: 9%;">Android Code</th>
                    <th style="width: 11%;">Plan Validity</th>
                    <th style="width: 11%;">Status</th>
                    <th style="width: 10%; text-align: right; white-space: nowrap;">
                        <span style="display: inline-flex; align-items: center; gap: 4px; justify-content: flex-end;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            Action
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($merchants)) { ?>
                <tr>
                    <td colspan="7" style="padding: 40px; text-align: center; color: var(--text-muted);">
                        No merchants registered yet. Click "+ Add Merchant" above to provision your first store.
                    </td>
                </tr>
            <?php } else {
                foreach ($merchants as $m) {
                    $isSuspended = ((int)$m['active'] !== 1);
                    $initials = strtoupper(substr(trim((string)$m['name']), 0, 2));
                    if ($initials === '') { $initials = 'MC'; }

                    $expiryText = 'Lifetime';
                    $expiryClass = 'status-active';
                    if (!empty($m['plan_expiry']) && $m['plan_expiry'] !== '0000-00-00') {
                        $expTs = strtotime($m['plan_expiry']);
                        $nowTs = time();
                        $diffDays = (int) ceil(($expTs - $nowTs) / 86400);
                        if ($diffDays < 0) {
                            $expiryText = 'Expired: ' . htmlspecialchars($m['plan_expiry'], ENT_QUOTES, 'UTF-8');
                            $expiryClass = 'status-suspended';
                        } else {
                            $expiryText = htmlspecialchars($m['plan_expiry'], ENT_QUOTES, 'UTF-8') . " ({$diffDays}d left)";
                            $expiryClass = $diffDays <= 7 ? 'status-suspended' : 'status-active';
                        }
                    }
            ?>
                <tr class="<?php echo $isSuspended ? 'suspended-row' : ''; ?>">
                    <td>
                        <div class="merchant-title">
                            <div class="merchant-avatar"><?php echo $initials; ?></div>
                            <div>
                                <div class="merchant-name"><?php echo htmlspecialchars((string) $m['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="merchant-created">
                                    ID #<?php echo (int) $m['id']; ?> &bull; Phone: <?php echo htmlspecialchars((string) ($m['phone'] ?: 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($m['url'])) { ?>
                            <div>
                                <a class="link-url" href="<?php echo htmlspecialchars((string) $m['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Open Storefront in new tab">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                    <span><?php echo htmlspecialchars((string) $m['url'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:0.6; flex-shrink:0;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                </a>
                            </div>
                        <?php } else { ?>
                            <span style="color: var(--text-subtle); font-size: 11px;">&mdash;</span>
                        <?php } ?>
                        <div class="subdomain-subtext">
                            <span class="subdomain-label">Subdomain:</span>
                            <span class="pill-code"><?php echo htmlspecialchars((string) $m['code'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="pill-db">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                            <?php echo htmlspecialchars((string) $m['database_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <div style="font-size: 11px; color: var(--text-subtle); margin-top: 2px;">@<?php echo htmlspecialchars((string) ($m['hostname'] ?? 'localhost'), ENT_QUOTES, 'UTF-8'); ?></div>
                    </td>
                    <td>
                        <?php if (!empty($m['android_code'])) { ?>
                            <span class="pill-android"><?php echo htmlspecialchars((string) $m['android_code'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php } else { ?>
                            <span style="color: var(--text-subtle); font-size: 12px;">&mdash;</span>
                        <?php } ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $expiryClass; ?>" style="font-size: 11px; padding: 3px 8px;">
                            <?php echo $expiryText; ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:inline-flex; align-items:center; gap:7px;">
                            <label class="switch-toggle" title="Click to <?php echo $isSuspended ? 'Activate' : 'Suspend'; ?> merchant store">
                                <input type="checkbox" class="merchant-status-toggle"
                                       data-merchant-id="<?php echo (int) $m['id']; ?>"
                                       data-merchant-name="<?php echo htmlspecialchars((string) $m['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                       <?php echo !$isSuspended ? 'checked' : ''; ?>>
                                <span class="switch-slider"></span>
                            </label>
                            <span class="status-badge <?php echo $isSuspended ? 'status-suspended' : 'status-active'; ?>" id="merchant-status-badge-<?php echo (int) $m['id']; ?>">
                                <?php echo $isSuspended ? 'Suspended' : 'Active'; ?>
                            </span>
                        </div>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <div class="action-icon-group">
                            <a href="index.php?action=backup_db&id=<?php echo (int) $m['id']; ?>" class="action-btn action-btn-backup btn-action-backup" title="Download MySQL Backup Dump (.sql)" aria-label="Backup Database">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            </a>
                            <?php $clientM = $m; unset($clientM['db_password']); ?>
                            <button type="button" class="action-btn action-btn-edit btn-action-edit" data-merchant='<?php echo json_encode($clientM, JSON_HEX_APOS | JSON_HEX_QUOT); ?>' title="Edit Merchant Configuration" aria-label="Edit Merchant">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            </button>
                            <button type="button" class="action-btn action-btn-clone btn-action-clone" data-merchant='<?php echo json_encode($clientM, JSON_HEX_APOS | JSON_HEX_QUOT); ?>' title="Clone Merchant Store Instance" aria-label="Clone Instance">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php } } ?>
            </tbody>
        </table>
    </div>
</div>
