<?php defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or exit('Direct script access denied.'); ?>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Android Unique Code</th>
                    <th>Merchant Name</th>
                    <th>Resolved Tenant Subdomain</th>
                    <th>Target Server URL</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($merchants)) {
                foreach ($merchants as $m) {
                    $mId = (int) $m['id'];
                    $isSuspended = ((int)($m['android_active'] ?? 1) !== 1);
            ?>
                <tr class="<?php echo $isSuspended ? 'suspended-row' : ''; ?>" id="merchant-row-<?php echo $mId; ?>">
                    <td>
                        <?php if (!empty($m['android_code'])) { ?>
                            <span class="pill-android" style="cursor: pointer;" title="Click to test lookup in tester above" onclick="testAndroidCode('<?php echo htmlspecialchars((string) $m['android_code'], ENT_QUOTES, 'UTF-8'); ?>');">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:2px;"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                                <?php echo htmlspecialchars((string) $m['android_code'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php } else { ?>
                            <span style="color: var(--text-subtle); font-size: 11.5px;">&mdash; (Not Set)</span>
                        <?php } ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars((string) $m['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <div style="font-size: 10.5px; color: var(--text-subtle); margin-top: 1px;">ID #<?php echo $mId; ?></div>
                    </td>
                    <td><span class="pill-code"><?php echo htmlspecialchars((string) $m['code'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td>
                        <?php if (!empty($m['url'])) { ?>
                            <a class="link-url" href="<?php echo htmlspecialchars((string) $m['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Open Storefront in new tab">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                                <span><?php echo htmlspecialchars((string) $m['url'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        <?php } else { ?>
                            <span style="color: var(--text-subtle); font-size: 11.5px;">&mdash;</span>
                        <?php } ?>
                    </td>
                    <td>
                        <div style="display:inline-flex; align-items:center; gap:7px;">
                            <label class="switch-toggle" title="Click to <?php echo $isSuspended ? 'Enable Android Connection' : 'Disable Android Connection'; ?>">
                                <input type="checkbox" class="android-status-toggle"
                                       data-merchant-id="<?php echo $mId; ?>"
                                       data-merchant-name="<?php echo htmlspecialchars((string) $m['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                       <?php echo !$isSuspended ? 'checked' : ''; ?>>
                                <span class="switch-slider"></span>
                            </label>
                            <span class="status-badge <?php echo $isSuspended ? 'status-suspended' : 'status-active'; ?>" id="android-status-badge-<?php echo $mId; ?>">
                                <?php echo $isSuspended ? 'Suspended' : 'Active'; ?>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php } } else { ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:30px; color:var(--text-subtle);">No merchants found.</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function testAndroidCode(code) {
    var input = document.getElementById('android-test-input');
    var btn = document.getElementById('android-test-btn');
    if (input && btn && code && code !== 'N/A') {
        input.value = code;
        btn.click();
    }
}
</script>
