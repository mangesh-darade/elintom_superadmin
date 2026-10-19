<?php defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or exit('Direct script access denied.'); ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            <span>Tenant Uploads &amp; Media Storage (<code>assets/mdata/</code>)</span>
        </h3>
        <form action="index.php?action=sync_storage" method="POST" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn-primary-action" style="border:0; cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path></svg>
                <span>Verify &amp; Sync Storage Folders</span>
            </button>
        </form>
    </div>
    <div class="card-body">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
            <p style="margin: 0; color: var(--text-muted); font-size: 11.5px;">
                Tenants store their invoices, product photos, receipts, and avatars in completely isolated storage roots under <code>assets/mdata/{code}/uploads/</code>. Zero crossover between merchants.
            </p>
            <?php 
                $storageInfo = MerchantRegistry::getTenantStorageMap($merchants);
                $storageMap = $storageInfo['map'] ?? array();
                $storageSource = $storageInfo['source'] ?? 'local';
            ?>
            <div>
                <?php if ($storageSource === 'api') { ?>
                    <span class="status-badge status-active" title="Connected via REST API to ElintOm ERP">REST API Live</span>
                <?php } else { ?>
                    <span class="status-badge" style="background:rgba(217,119,6,0.15); color:#d97706; border:1px solid rgba(217,119,6,0.3);">Local Sync</span>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Subdomain Code</th>
                    <th>Merchant Name</th>
                    <th>Physical Storage Path</th>
                    <th>Permissions</th>
                    <th>Folder Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($merchants)) {
                foreach ($merchants as $m) {
                    $mCode = (string) $m['code'];
                    $tStatus = $storageMap[$mCode] ?? array('mounted' => false, 'writable' => false);
                    $exists = !empty($tStatus['mounted']);
                    $writable = !empty($tStatus['writable']);
                    $subdirsInfo = isset($tStatus['subdirs_present']) ? " (" . $tStatus['subdirs_present'] . "/" . ($tStatus['subdirs_total'] ?? 23) . " subdirs)" : "";
            ?>
                <tr>
                    <td><span class="pill-code"><?php echo htmlspecialchars($mCode, ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><strong><?php echo htmlspecialchars((string) $m['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                    <td><code>assets/mdata/<?php echo htmlspecialchars($mCode, ENT_QUOTES, 'UTF-8'); ?>/</code></td>
                    <td>
                        <?php if ($writable) { ?>
                            <span style="font-size: 12px; color: #059669; font-weight: 600;">Read / Write</span>
                        <?php } elseif ($exists) { ?>
                            <span style="font-size: 12px; color: #d97706; font-weight: 600;">Read Only</span>
                        <?php } else { ?>
                            <span style="font-size: 12px; color: #dc2626; font-weight: 600;">Missing</span>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if ($exists) { ?>
                            <span class="status-badge status-active">Mounted<?php echo htmlspecialchars($subdirsInfo, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php } else { ?>
                            <span class="status-badge status-suspended">Needs Sync</span>
                        <?php } ?>
                    </td>
                </tr>
            <?php } } ?>
            </tbody>
        </table>
    </div>
</div>
