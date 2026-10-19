<?php defined('SUPER_ADMIN_ACCESS') or defined('MERCHANTS_ADMIN_ACCESS') or exit('Direct script access denied.'); ?>
        </main>
    </div>

    <!-- ADD MERCHANT MODAL -->
    <div class="modal-backdrop" id="modal-add-merchant">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Provision New Merchant Tenant</h3>
                <button type="button" class="btn-modal-close" data-close-modal>&times;</button>
            </div>
            <form action="index.php?action=add" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Business / Shop Name <span class="req">*</span></label>
                            <input type="text" name="name" class="form-input" required placeholder="e.g. Apex Retail Store">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subdomain Code (Slug) <span class="req">*</span></label>
                            <input type="text" name="code" class="form-input form-input-code" required placeholder="e.g. apex" pattern="[a-z0-9_]{1,40}" title="Lowercase alphanumeric and underscores only">
                            <span class="form-hint">Matches subdomain: <code>{code}.yourdomain.com</code></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dedicated Database Name <span class="req">*</span></label>
                            <input type="text" name="database_name" class="form-input form-input-code" required placeholder="e.g. stadmin_apex">
                            <span class="form-hint">Must exist or check auto-create below</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number <span class="req">*</span></label>
                            <input type="text" name="phone" class="form-input" required placeholder="e.g. 9876543210 (10-15 digits)">
                            <span class="form-hint">Must be unique per merchant</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address <span class="req">*</span></label>
                            <input type="email" name="email" class="form-input" required placeholder="e.g. admin@apexstore.com">
                            <span class="form-hint">Must be unique per merchant</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Android Unique Code</label>
                            <input type="text" name="android_code" class="form-input form-input-code" placeholder="e.g. APEX01" style="text-transform: uppercase;">
                            <span class="form-hint">Unique hardware/kiosk code for Mobile POS</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tenant Full URL</label>
                            <input type="text" name="url" class="form-input" placeholder="e.g. http://apex.localhost/ElintOm_PHP_8.5/">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Plan Expiry Date</label>
                            <input type="date" name="plan_expiry" class="form-input">
                            <span class="form-hint">Leave blank for Lifetime / Unlimited</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Opening Float Balance</label>
                            <input type="number" step="0.01" name="balance" class="form-input" value="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">DB Hostname</label>
                            <input type="text" name="hostname" class="form-input form-input-code" value="localhost">
                        </div>
                        <div class="form-group">
                            <label class="form-label">DB Username</label>
                            <input type="text" name="db_user" class="form-input form-input-code" value="root">
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">DB Password</label>
                            <input type="password" name="db_password" class="form-input form-input-code" placeholder="Leave empty if none">
                        </div>
                        <div class="form-group form-full" style="margin-top: 8px;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; cursor:pointer;">
                                <input type="checkbox" name="auto_create_db" value="1" checked style="width:16px; height:16px;">
                                <span>Auto-create MySQL Database if it does not exist</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn-primary-action">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Save Merchant</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT MERCHANT MODAL -->
    <div class="modal-backdrop" id="modal-edit-merchant">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Edit Merchant Details</h3>
                <button type="button" class="btn-modal-close" data-close-modal>&times;</button>
            </div>
            <form action="index.php?action=update" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="merchant_id" id="edit-id">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Business / Shop Name <span class="req">*</span></label>
                            <input type="text" name="name" id="edit-name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subdomain Code (Slug) <span class="req">*</span></label>
                            <input type="text" name="code" id="edit-code" class="form-input form-input-code" required pattern="[a-z0-9_]{1,40}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Database Name <span class="req">*</span></label>
                            <input type="text" name="database_name" id="edit-database" class="form-input form-input-code" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number <span class="req">*</span></label>
                            <input type="text" name="phone" id="edit-phone" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address <span class="req">*</span></label>
                            <input type="email" name="email" id="edit-email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Android Unique Code</label>
                            <input type="text" name="android_code" id="edit-android" class="form-input form-input-code" style="text-transform: uppercase;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tenant Full URL</label>
                            <input type="text" name="url" id="edit-url" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Plan Expiry Date</label>
                            <input type="date" name="plan_expiry" id="edit-plan-expiry" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Float Balance</label>
                            <input type="number" step="0.01" name="balance" id="edit-balance" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">DB Hostname</label>
                            <input type="text" name="hostname" id="edit-hostname" class="form-input form-input-code">
                        </div>
                        <div class="form-group">
                            <label class="form-label">DB Username</label>
                            <input type="text" name="db_user" id="edit-db-user" class="form-input form-input-code">
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">DB Password</label>
                            <input type="password" name="db_password" class="form-input form-input-code" placeholder="Leave blank to keep existing password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn-primary-action">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Update Merchant</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CLONE MERCHANT INSTANCE MODAL -->
    <div class="modal-backdrop" id="modal-clone-merchant">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title" style="display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    <span>Duplicate Merchant Store Instance</span>
                </h3>
                <button type="button" class="btn-modal-close" data-close-modal>&times;</button>
            </div>
            <form action="index.php?action=clone" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="source_id" id="clone-source-id">
                <div class="modal-body">
                    <div style="background:#f5f3ff; border:1px solid #ddd6fe; border-radius:var(--radius-md); padding:12px 16px; margin-bottom:18px; font-size:13px; color:#5b21b6;">
                        Cloning from: <strong id="clone-source-name">...</strong> (Subdomain: <code id="clone-source-code">...</code>, DB: <code id="clone-source-db">...</code>)
                    </div>
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">New Store / Branch Name <span class="req">*</span></label>
                            <input type="text" name="name" id="clone-new-name" class="form-input" required placeholder="e.g. Apex Retail (Downtown Branch)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Subdomain Slug <span class="req">*</span></label>
                            <input type="text" name="code" id="clone-new-code" class="form-input form-input-code" required placeholder="e.g. apex2" pattern="[a-z0-9_]{1,40}">
                            <span class="form-hint">Subdomain: <code>{code}.yourdomain.com</code></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Database Target <span class="req">*</span></label>
                            <input type="text" name="database_name" id="clone-new-db" class="form-input form-input-code" required placeholder="e.g. stadmin_apex2">
                            <span class="form-hint">Script will auto-create this database in MySQL</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="phone" id="clone-phone" class="form-input" placeholder="Can use same phone as parent store">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" id="clone-email" class="form-input" placeholder="Can use same email as parent store">
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Android Unique Code <span class="req">*</span></label>
                            <input type="text" name="android_code" id="clone-android" class="form-input form-input-code" required placeholder="e.g. APEX02" style="text-transform: uppercase;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Target Full URL</label>
                            <input type="text" name="url" id="clone-url" class="form-input">
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Plan Expiry Date</label>
                            <input type="date" name="plan_expiry" id="clone-plan-expiry" class="form-input">
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Duplication Mode</label>
                            <select name="clone_mode" class="form-input">
                                <option value="clean_sales" selected>Clone Structure, Products &amp; Settings (Zero Sales / Clean Ledger - Recommended)</option>
                                <option value="full_copy">Full Exact Mirror (Copies all historical sales, receipts and customers)</option>
                                <option value="schema_only">Clean Blank Schema Only (No products, empty catalog)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn-primary-action" style="background:#6d28d9;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Start Cloning Instance</span>
                    </button>
                </div>
            </form>
        </div>
    </div>



    <!-- TOAST NOTIFICATION CONTAINER -->
    <div class="toast-container" id="toast-container"></div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        (function () {
            var csrfToken = "<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>";

            // Mobile menu toggle & backdrop handling
            var mobileBtn = document.getElementById('mobile-toggle-btn');
            var sidebar = document.getElementById('sidebar');
            var backdrop = document.getElementById('sidebar-backdrop');

            function toggleSidebar(forceState) {
                if (!sidebar) return;
                var isOpen = typeof forceState === 'boolean' ? forceState : !sidebar.classList.contains('mobile-open');
                sidebar.classList.toggle('mobile-open', isOpen);
                if (backdrop) backdrop.classList.toggle('active', isOpen);
            }

            if (mobileBtn) {
                mobileBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    toggleSidebar(false);
                });
            }

            // Toast helper
            function showToast(message, type) {
                var container = document.getElementById('toast-container');
                if (!container) return;
                var toast = document.createElement('div');
                toast.className = 'toast ' + (type === 'error' ? 'toast-error' : 'toast-success');
                toast.innerHTML = (type === 'error' ? '&#9888; ' : '&#10003; ') + '<span>' + message + '</span>';
                container.appendChild(toast);
                setTimeout(function () {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(10px)';
                    toast.style.transition = 'all 0.25s ease';
                    setTimeout(function () { toast.remove(); }, 250);
                }, 3500);
            }

            // Row-level Merchant Active/Inactive Switch Toggle via AJAX
            document.querySelectorAll('.merchant-status-toggle').forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    var merchantId = toggle.getAttribute('data-merchant-id');
                    var merchantName = toggle.getAttribute('data-merchant-name') || ('Store #' + merchantId);
                    var isActive = toggle.checked ? 1 : 0;
                    var badge = document.getElementById('merchant-status-badge-' + merchantId);
                    var row = toggle.closest('tr');

                    var confirmMsg = isActive
                        ? 'Activate store "' + merchantName + '"? Access will be restored immediately.'
                        : 'Suspend store "' + merchantName + '"? Store POS access will be blocked (403).';

                    if (!confirm(confirmMsg)) {
                        toggle.checked = !isActive;
                        return;
                    }

                    // Optimistic UI update
                    if (badge) {
                        badge.textContent = isActive ? 'Active' : 'Suspended';
                        badge.className = 'status-badge ' + (isActive ? 'status-active' : 'status-suspended');
                    }
                    if (row) {
                        if (isActive) {
                            row.classList.remove('suspended-row');
                        } else {
                            row.classList.add('suspended-row');
                        }
                    }

                    toggle.parentElement.setAttribute('title', 'Click to ' + (isActive ? 'Suspend' : 'Activate') + ' merchant store');

                    // Send AJAX POST
                    var formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('merchant_id', merchantId);
                    formData.append('active', isActive);
                    formData.append('is_ajax', '1');

                    fetch('index.php?action=suspend', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.status === 'success') {
                            showToast(data.message, 'success');
                            updateStatusFilterCounts();
                        } else {
                            showToast(data.message || 'Failed to update merchant status', 'error');
                            revertUI();
                        }
                    })
                    .catch(function () {
                        showToast('Connection error updating merchant status.', 'error');
                        revertUI();
                    });

                    function revertUI() {
                        toggle.checked = !isActive;
                        if (badge) {
                            badge.textContent = !isActive ? 'Active' : 'Suspended';
                            badge.className = 'status-badge ' + (!isActive ? 'status-active' : 'status-suspended');
                        }
                        if (row) {
                            if (!isActive) {
                                row.classList.remove('suspended-row');
                            } else {
                                row.classList.add('suspended-row');
                            }
                        }
                        toggle.parentElement.setAttribute('title', 'Click to ' + (!isActive ? 'Suspend' : 'Activate') + ' merchant store');
                    }
                });
            });

            function updateStatusFilterCounts() {
                var activeRows = document.querySelectorAll('#merchants-table tbody tr:not(.suspended-row)').length;
                var suspendedRows = document.querySelectorAll('#merchants-table tbody tr.suspended-row').length;
                var totalRows = activeRows + suspendedRows;
                var sel = document.getElementById('status-filter');
                if (sel && sel.options.length >= 3) {
                    sel.options[0].text = 'All Statuses (' + totalRows + ')';
                    sel.options[1].text = 'Active Only (' + activeRows + ')';
                    sel.options[2].text = 'Suspended Only (' + suspendedRows + ')';
                }
            }

            // Modal Handlers
            var modalAdd = document.getElementById('modal-add-merchant');
            var modalEdit = document.getElementById('modal-edit-merchant');
            var modalClone = document.getElementById('modal-clone-merchant');

            var openAddBtn = document.getElementById('open-add-merchant');
            if (openAddBtn && modalAdd) {
                openAddBtn.addEventListener('click', function () {
                    modalAdd.classList.add('open');
                });
            }

            document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (modalAdd) modalAdd.classList.remove('open');
                    if (modalEdit) modalEdit.classList.remove('open');
                    if (modalClone) modalClone.classList.remove('open');
                });
            });

            [modalAdd, modalEdit, modalClone].forEach(function (m) {
                if (m) {
                    m.addEventListener('click', function (e) {
                        if (e.target === m) { m.classList.remove('open'); }
                    });
                }
            });

            // Edit button binder
            document.querySelectorAll('.btn-action-edit').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var data = JSON.parse(btn.getAttribute('data-merchant') || '{}');
                    document.getElementById('edit-id').value = data.id || '';
                    document.getElementById('edit-name').value = data.name || '';
                    document.getElementById('edit-code').value = data.code || '';
                    document.getElementById('edit-database').value = data.database_name || '';
                    document.getElementById('edit-phone').value = data.phone || '';
                    document.getElementById('edit-email').value = data.email || '';
                    document.getElementById('edit-android').value = data.android_code || '';
                    document.getElementById('edit-url').value = data.url || '';
                    document.getElementById('edit-plan-expiry').value = data.plan_expiry || '';
                    document.getElementById('edit-balance').value = data.balance || '0.00';
                    document.getElementById('edit-hostname').value = data.hostname || 'localhost';
                    document.getElementById('edit-db-user').value = data.db_user || 'root';

                    if (modalEdit) modalEdit.classList.add('open');
                });
            });

            // Clone button binder
            document.querySelectorAll('.btn-action-clone').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var data = JSON.parse(btn.getAttribute('data-merchant') || '{}');
                    document.getElementById('clone-source-id').value = data.id || '';
                    document.getElementById('clone-source-name').textContent = data.name || '';
                    document.getElementById('clone-source-code').textContent = data.code || '';
                    document.getElementById('clone-source-db').textContent = data.database_name || '';

                    document.getElementById('clone-new-name').value = (data.name || '') + ' (Branch 2)';
                    document.getElementById('clone-new-code').value = (data.code || '') + '2';
                    document.getElementById('clone-new-db').value = (data.database_name || '') + '_clone';
                    document.getElementById('clone-phone').value = data.phone || '';
                    document.getElementById('clone-email').value = data.email || '';
                    document.getElementById('clone-android').value = (data.android_code || '') + 'B';
                    document.getElementById('clone-url').value = data.url || '';
                    document.getElementById('clone-plan-expiry').value = data.plan_expiry || '';

                    if (modalClone) modalClone.classList.add('open');
                });
            });

            // Auto-open add form on validation errors
            <?php if (!empty($open_form)) { ?>
            if (modalAdd) { modalAdd.classList.add('open'); }
            <?php } ?>

            // Live search in merchants table
            var searchInput = document.getElementById('live-search');
            var statusFilter = document.getElementById('status-filter');
            var tableBody = document.querySelector('#merchants-table tbody');

            function filterRows() {
                if (!tableBody) return;
                var q = (searchInput ? searchInput.value : '').toLowerCase().trim();
                var s = statusFilter ? statusFilter.value : 'all';
                var rows = tableBody.querySelectorAll('tr');

                rows.forEach(function (r) {
                    var text = r.textContent.toLowerCase();
                    var isSuspended = r.classList.contains('suspended-row');
                    var matchText = text.indexOf(q) !== -1;
                    var matchStatus = (s === 'all') ||
                        (s === 'active' && !isSuspended) ||
                        (s === 'suspended' && isSuspended);

                    r.style.display = (matchText && matchStatus) ? '' : 'none';
                });
            }

            if (searchInput) searchInput.addEventListener('input', filterRows);
            if (statusFilter) statusFilter.addEventListener('change', filterRows);

            // Android Connection Switch Toggle via AJAX (Only blocks Android, NOT Web POS)
            document.querySelectorAll('.android-status-toggle').forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    var merchantId = toggle.getAttribute('data-merchant-id');
                    var merchantName = toggle.getAttribute('data-merchant-name') || ('Store #' + merchantId);
                    var isActive = toggle.checked ? 1 : 0;
                    var badge = document.getElementById('android-status-badge-' + merchantId);
                    var row = document.getElementById('merchant-row-' + merchantId);

                    // Optimistic UI update
                    if (badge) {
                        badge.textContent = isActive ? 'Active' : 'Suspended';
                        badge.className = 'status-badge ' + (isActive ? 'status-active' : 'status-suspended');
                    }
                    if (row) {
                        if (isActive) {
                            row.classList.remove('suspended-row');
                        } else {
                            row.classList.add('suspended-row');
                        }
                    }

                    var formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('merchant_id', merchantId);
                    formData.append('app_type', 'master_android');
                    formData.append('is_active', isActive);
                    formData.append('is_ajax', '1');

                    fetch('index.php?action=toggle_app_status', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.status === 'success') {
                            showToast(isActive ? 'Android connection is now ACTIVE. (Web POS unaffected)' : 'Android connection SUSPENDED. (Web POS unaffected)', 'success');
                        } else {
                            showToast(data.message || 'Failed to update Android connection status', 'error');
                            revertUI();
                        }
                    })
                    .catch(function () {
                        showToast('Connection error updating Android status.', 'error');
                        revertUI();
                    });

                    function revertUI() {
                        toggle.checked = !isActive;
                        if (badge) {
                            badge.textContent = !isActive ? 'Active' : 'Suspended';
                            badge.className = 'status-badge ' + (!isActive ? 'status-active' : 'status-suspended');
                        }
                        if (row) {
                            if (!isActive) {
                                row.classList.remove('suspended-row');
                            } else {
                                row.classList.add('suspended-row');
                            }
                        }
                    }
                });
            });

            // Android setup code lookup tester (Old flow restored)
            var testInput = document.getElementById('android-test-input');
            var testBtn = document.getElementById('android-test-btn');
            var testResult = document.getElementById('android-test-result');

            if (testBtn && testInput && testResult) {
                testBtn.addEventListener('click', function () {
                    var val = testInput.value.trim().toUpperCase();
                    if (!val) {
                        testResult.innerHTML = '<span style="color:#dc2626;">Please enter an Android code to check.</span>';
                        return;
                    }
                    testResult.innerHTML = '<span style="color:#64748b;">Querying attendance_api/setup resolver...</span>';

                    var fd = new FormData();
                    fd.append('android_code', val);

                    fetch('../attendance_api/setup', {
                        method: 'POST',
                        body: fd
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.status === 'success') {
                            var resName = data.name || (data.data ? data.data.name : '') || '';
                            var resUrl = data.url || (data.data ? data.data.url : '') || '';
                            var resCode = data.code || (data.data ? data.data.code : '') || '';
                            testResult.innerHTML = '<span style="color:#059669;">&check; <strong>' +
                                resName + '</strong> &rarr; <a href="' + resUrl + '" target="_blank" style="color:#4f46e5;">' +
                                resUrl + '</a></span>';
                        } else {
                            testResult.innerHTML = '<span style="color:#dc2626; font-size:11px; font-weight:600;">&times; ' + (data.message || 'Unknown error') + '</span>';
                        }
                    })
                    .catch(function (err) {
                        testResult.innerHTML = '<span style="color:#dc2626;">&times; Connection Error calling /attendance_api/setup</span>';
                    });
                });
            }
        })();
    </script>
</body>
</html>
