<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_role = $_SESSION['role'] ?? '';

$secretary_nav = [
    [
        'href' => 'secretary_dashboard.php',
        'icon' => 'fa-table-cells-large',
        'label' => 'Dashboard',
        'pages' => ['secretary_dashboard.php'],
    ],
    [
        'href' => 'residents_list.php',
        'icon' => 'fa-users',
        'label' => 'Residents List',
        'pages' => ['residents_list.php', 'edit_member.php'],
    ],
    [
        'href' => 'residents.php',
        'icon' => 'fa-house-chimney',
        'label' => 'Household List',
        'pages' => ['residents.php', 'household_members.php', 'edit_household.php', 'add_household.php', 'add_members.php', 'view_households.php', 'save_household.php', 'save_household_session.php'],
    ],
    [
        'href' => 'activities.php',
        'icon' => 'fa-clipboard-list',
        'label' => 'Activity List',
        'pages' => ['activities.php', 'manage_beneficiaries.php', 'add_activity.php', 'edit_activity.php'],
    ],
    [
        'href' => 'settings.php',
        'icon' => 'fa-gear',
        'label' => 'Settings',
        'pages' => ['settings.php'],
    ],
];

$captain_nav = [
    [
        'href' => 'captain_dashboard.php',
        'icon' => 'fa-table-columns',
        'label' => 'Dashboard',
        'pages' => ['captain_dashboard.php'],
    ],
    [
        'href' => 'captain_household_list.php',
        'icon' => 'fa-house-chimney',
        'label' => 'Household List',
        'pages' => ['captain_household_list.php'],
    ],
    [
        'href' => 'captain_residents_list.php',
        'icon' => 'fa-users',
        'label' => 'Residents List',
        'pages' => ['captain_residents_list.php'],
    ],
    [
        'href' => 'captain_activity_list.php',
        'icon' => 'fa-clipboard-list',
        'label' => 'Activity List',
        'pages' => ['captain_activity_list.php'],
    ],
    [
        'href' => 'captain_reports.php',
        'icon' => 'fa-file-lines',
        'label' => 'Reports',
        'pages' => ['captain_reports.php'],
    ],
    [
        'href' => 'manage_users.php',
        'icon' => 'fa-user-gear',
        'label' => 'Manage Users',
        'pages' => ['manage_users.php'],
    ],
    [
        'href' => 'settings.php',
        'icon' => 'fa-gear',
        'label' => 'Settings',
        'pages' => ['settings.php'],
    ],
];

$nav_items = ($current_role === 'Barangay Captain') ? $captain_nav : $secretary_nav;
$show_archived_link = true;
$role_title = ($current_role === 'Barangay Captain') ? 'Captain' : 'Secretary';
$role_subtitle = ($current_role === 'Barangay Captain') ? 'Barangay Captain' : 'Barangay Secretary';

function left_nav_active($current, $pages) {
    return in_array($current, $pages, true) ? ' active' : '';
}
?>

<style>
    body.preload-transitions * {
        transition: none !important;
    }

    .sidebar {
        width: 220px !important;
        min-width: 220px !important;
        height: calc(100vh - 32px) !important;
        background: #ffffff !important;
        color: #1e293b !important;
        display: flex !important;
        flex-direction: column !important;
        position: sticky !important;
        top: 16px !important;
        flex-shrink: 0 !important;
        margin: 12px 2px 12px 12px !important;
        border-radius: 20px !important;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.22) !important;
        z-index: 50 !important;
        transition: width 0.42s cubic-bezier(0.22, 1, 0.36, 1), min-width 0.42s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s ease !important;
        overflow: hidden !important;
        will-change: width, min-width !important;
    }

    .sidebar.collapsed {
        width: 68px !important;
        min-width: 68px !important;
    }

    .sidebar-header {
        height: 90px !important;
        padding: 12px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        position: relative !important;
        box-sizing: border-box !important;
        outline: none !important;
        margin-bottom: 8px !important;
    }

    .brand-group {
        display: flex !important;
        align-items: center !important;
        min-width: 0 !important;
        flex: 1 !important;
        overflow: hidden !important;
        opacity: 1 !important;
        transition: opacity 0.2s ease !important;
    }

    .brand-logo-container {
        border: 3px solid #ff9800 !important;
        border-radius: 14px !important;
        width: 55px !important;
        height: 55px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
        box-sizing: border-box !important;
    }

    .brand-logo-container i {
        color: #ff9800 !important;
        font-size: 30px !important;
    }

    img.logo {
        width: 50px !important;
        height: 50px !important;
        border-radius: 10px !important;
        object-fit: cover !important;
        flex-shrink: 0 !important;
    }

    .brand-text {
        margin-left: 12px !important;
        white-space: normal !important;
        line-height: 1.2 !important;
        width: 110px !important;
        min-width: 110px !important;
    }

    .brand-text b {
        display: block !important;
        font-size: 14px !important;
        color: #1e293b !important;
        line-height: 1.2 !important;
    }

    .brand-text span {
        display: block !important;
        color: #94a3b8 !important;
        font-size: 11px !important;
        margin-top: 2px !important;
    }

    .toggle-icon {
        cursor: pointer !important;
        color: #64748b !important;
        font-size: 22px !important;
        position: absolute !important;
        right: 12px !important;
        line-height: 1 !important;
        transition: right 0.42s cubic-bezier(0.22, 1, 0.36, 1), transform 0.42s cubic-bezier(0.22, 1, 0.36, 1), color 0.2s ease !important;
        z-index: 10 !important;
    }

    .toggle-icon:hover {
        color: white !important;
    }

    .nav-menu {
        padding: 10px 10px 8px !important;
        flex-grow: 1 !important;
        box-sizing: border-box !important;
    }

    .nav-item {
        display: flex !important;
        align-items: center !important;
        min-height: 38px !important;
        padding: 7px 10px !important;
        color: #64748b !important;
        text-decoration: none !important;
        border-radius: 12px !important;
        margin-bottom: 4px !important;
        font-weight: 500 !important;
        box-sizing: border-box !important;
        transition: all 0.2s ease !important;
        outline: none !important;
    }

    .nav-item:hover {
        background: #f1f5f9 !important;
        color: #1e293b !important;
    }

    .nav-item.active {
        background: #2563eb !important;
        color: white !important;
    }

    .nav-item i {
        font-size: 15px !important;
        width: 24px !important;
        min-width: 24px !important;
        text-align: center !important;
        color: inherit !important;
        transition: color 0.2s ease !important;
    }

    .nav-text {
        display: inline-block !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        margin-left: 8px !important;
        white-space: nowrap !important;
        opacity: 1 !important;
        transition: opacity 0.16s ease !important;
    }

    .sidebar-footer {
        margin-top: auto !important;
        padding: 10px 10px 12px !important;
        border-top: 1px solid rgba(148, 163, 184, 0.15) !important;
        position: relative !important;
    }

    .sidebar-profile-container {
        position: relative !important;
    }

    .sidebar-account {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 14px !important;
        padding: 8px 10px !important;
        cursor: pointer !important;
        color: #1e293b !important;
        transition: background 0.2s ease, border-color 0.2s ease !important;
    }

    .sidebar-account:hover {
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;
    }

    .sidebar-avatar {
        width: 32px !important;
        height: 32px !important;
        border-radius: 50% !important;
        background: #2563eb !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
        color: white !important;
        font-size: 14px !important;
    }

    .sidebar-account-meta {
        min-width: 0 !important;
        line-height: 1.15 !important;
    }

    .sidebar-account-name {
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #1e293b !important;
        white-space: nowrap !important;
    }

    .sidebar-account-role {
        font-size: 11px !important;
        color: #94a3b8 !important;
        margin-top: 1px !important;
        white-space: nowrap !important;
    }

    .sidebar-account-caret {
        margin-left: auto !important;
        color: #94a3b8 !important;
        font-size: 11px !important;
    }

    .sidebar-logout-dropdown {
        position: absolute !important;
        left: 0 !important;
        right: 0 !important;
        bottom: calc(100% + 8px) !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        display: none !important;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18) !important;
        z-index: 130 !important;
    }

    .sidebar-logout-dropdown.show {
        display: block !important;
    }

    .sidebar-dropdown-header {
        padding: 12px !important;
        text-align: center !important;
        border-bottom: 1px solid #e5e7eb !important;
        color: #64748b !important;
        font-size: 13px !important;
    }

    .sidebar-dropdown-header b {
        display: block !important;
        color: #1e293b !important;
        margin-top: 4px !important;
        font-size: 15px !important;
    }

    .sidebar-logout-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10px !important;
        padding: 14px !important;
        color: #ef4444 !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        font-size: 14px !important;
    }

    .top-header,
    .header {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 18px !important;
        margin: 12px 12px 0 !important;
        padding: 12px 18px !important;
        box-sizing: border-box !important;
    }

    .main-container > .content-body {
        padding-top: 12px !important;
    }

    .sidebar-dropdown-link {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 12px 14px !important;
        color: #334155 !important;
        text-decoration: none !important;
        border-bottom: 1px solid #e5e7eb !important;
        font-size: 13px !important;
        font-weight: 600 !important;
    }

    .sidebar-dropdown-link:hover {
        background: #f8fafc !important;
    }

    .top-header .user-profile-container,
    .header .user-profile-container {
        display: none !important;
    }

    .sidebar.collapsed .brand-group {
        opacity: 0 !important;
        pointer-events: none !important;
    }

    .sidebar.collapsed .sidebar-header {
        justify-content: center !important;
        padding: 16px 0 !important;
    }

    .sidebar.collapsed .toggle-icon {
        right: 50% !important;
        transform: translateX(50%) !important;
        margin-left: 0 !important;
        color: #64748b !important;
        font-size: 24px !important;
    }

    .sidebar.collapsed .nav-text {
        opacity: 0 !important;
        width: 0 !important;
        margin-left: 0 !important;
        overflow: hidden !important;
    }

    .sidebar.collapsed .nav-item {
        justify-content: center !important;
        padding: 0 !important;
        width: 40px !important;
        height: 40px !important;
        margin: 0 auto 6px auto !important;
        border-radius: 12px !important;
    }

    .sidebar.collapsed .nav-item i {
        margin: 0 !important;
        width: auto !important;
        min-width: unset !important;
    }

    .sidebar.collapsed .sidebar-account {
        justify-content: center !important;
        padding: 8px !important;
        border-radius: 12px !important;
    }

    .sidebar.collapsed .sidebar-account-meta,
    .sidebar.collapsed .sidebar-account-caret {
        display: none !important;
    }

    .sidebar.collapsed .sidebar-logout-dropdown {
        left: 100% !important;
        right: auto !important;
        bottom: 0 !important;
        width: 210px !important;
        margin-left: 8px !important;
    }

    /* --- DARK MODE GLOBAL STYLES --- */
    body.dark-mode {
        background: #0f172a !important;
        color: #f8fafc !important;
    }
    body.dark-mode .sidebar {
        background: #1e293b !important;
        color: white !important;
        border-right: 1px solid #334155 !important;
    }
    body.dark-mode .brand-text b { color: white !important; }
    body.dark-mode .brand-text span { color: #94a3b8 !important; }
    body.dark-mode .toggle-icon { color: #cbd5e1 !important; }
    body.dark-mode .sidebar.collapsed .toggle-icon { color: white !important; }
    body.dark-mode .nav-item { color: #cbd5e1 !important; }
    body.dark-mode .nav-item:hover { background: rgba(148, 163, 184, 0.14) !important; color: white !important; }
    body.dark-mode .nav-item.active { background: #2563eb !important; color: white !important; }
    body.dark-mode .sidebar-account { background: rgba(248, 250, 252, 0.09) !important; border-color: rgba(148, 163, 184, 0.24) !important; color: #e2e8f0 !important; }
    body.dark-mode .sidebar-account:hover { background: rgba(248, 250, 252, 0.14) !important; border-color: rgba(148, 163, 184, 0.36) !important; }
    body.dark-mode .sidebar-account-name { color: #f8fafc !important; }
    body.dark-mode .sidebar-account-role { color: #94a3b8 !important; }
    body.dark-mode .sidebar-footer { border-color: rgba(148, 163, 184, 0.15) !important; }

    body.dark-mode .top-header,
    body.dark-mode .header {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    body.dark-mode .top-header h2,
    body.dark-mode .header h2 { color: white !important; }
    body.dark-mode .top-header p,
    body.dark-mode .header p { color: #94a3b8 !important; }
    
    body.dark-mode .user-pill {
        background: rgba(248, 250, 252, 0.09) !important;
        border-color: rgba(148, 163, 184, 0.24) !important;
        color: white !important;
    }
    body.dark-mode .user-pill div { color: #f8fafc !important; }
    
    body.dark-mode .logout-dropdown { background: #1e293b !important; border-color: #334155 !important; }
    body.dark-mode .dropdown-header { border-color: #334155 !important; color: #94a3b8 !important; }
    body.dark-mode .dropdown-header b { color: white !important; }
    body.dark-mode .logout-btn { color: #ef4444 !important; }
    body.dark-mode .logout-btn:hover { background: rgba(248, 250, 252, 0.05) !important; }
    
    body.dark-mode .content-body .panel,
    body.dark-mode .panel,
    body.dark-mode .report-panel,
    body.dark-mode .settings-card,
    body.dark-mode .records-card,
    body.dark-mode .content-card { background: #1e293b !important; border-color: #334155 !important; }
    body.dark-mode .panel h3, body.dark-mode .panel h2,
    body.dark-mode .report-panel h3, body.dark-mode .report-panel h2,
    body.dark-mode .settings-card h3, body.dark-mode .records-card h3,
    body.dark-mode .content-card h3 { color: white !important; }
    
    body.dark-mode .stat-card { background: #1e293b !important; border-color: #334155 !important; }
    body.dark-mode .stat-card h2 { color: white !important; }
    body.dark-mode .stat-card p { color: #94a3b8 !important; }
    
    body.dark-mode .chart-tile { background: #0f172a !important; border-color: #334155 !important; }
    body.dark-mode .chart-tile h4 { color: white !important; }
    
    body.dark-mode .form-group label { color: #cbd5e1 !important; }
    
    body.dark-mode td strong { color: white !important; }
    
    body.dark-mode .action-link { background: rgba(37, 99, 235, 0.2) !important; color: #60a5fa !important; }
    body.dark-mode .action-link:hover { background: rgba(37, 99, 235, 0.4) !important; color: white !important; }
    
    body.dark-mode table th { border-bottom-color: #334155 !important; color: #94a3b8 !important; }
    body.dark-mode table td { border-bottom-color: #334155 !important; color: #e2e8f0 !important; }
    
    body.dark-mode .badge { background: rgba(248, 250, 252, 0.09) !important; color: #e2e8f0 !important; }
    body.dark-mode .badge-success { background: rgba(22, 101, 52, 0.3) !important; color: #4ade80 !important; }
    
    body.dark-mode .report-tabs { background: #0f172a !important; border-color: #334155 !important; }
    body.dark-mode .report-tab { color: #94a3b8 !important; }
    body.dark-mode .report-tab:hover { color: white !important; background: rgba(248, 250, 252, 0.05) !important; }
    body.dark-mode .report-tab.active { background: #2563eb !important; color: white !important; }
    body.dark-mode .hh-group-header { background: #0f172a !important; border-left-color: #2563eb !important; }
    body.dark-mode .hh-group-header h4 { color: white !important; }
    
    body.dark-mode .modal-content, body.dark-mode .modal-container, body.dark-mode .edit-card { background: #1e293b !important; border-color: #334155 !important; }
    body.dark-mode .modal-container h2, body.dark-mode .edit-card .header h2 { color: white !important; }
    body.dark-mode .modal-container label, body.dark-mode .edit-card label { color: #cbd5e1 !important; }
    
    body.dark-mode .checkbox-container { background: #0f172a !important; border-color: #334155 !important; }
    body.dark-mode .checkbox-container label { color: #e2e8f0 !important; }
    body.dark-mode .benefits-title { color: white !important; }

    body.dark-mode .suggestion-list { background: #1e293b !important; border-color: #334155 !important; }
    body.dark-mode .suggestion-option { background: #1e293b !important; color: white !important; }
    body.dark-mode .suggestion-option:hover, body.dark-mode .suggestion-option:focus { background: #334155 !important; }

    body.dark-mode input:-webkit-autofill,
    body.dark-mode input:-webkit-autofill:hover, 
    body.dark-mode input:-webkit-autofill:focus, 
    body.dark-mode input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px #0f172a inset !important;
        -webkit-text-fill-color: white !important;
    }
    body.dark-mode .modal-header, body.dark-mode tr.modal-header { background: rgba(248, 250, 252, 0.05) !important; border-color: #334155 !important; }
    body.dark-mode .modal-header h3 { color: white !important; }
    body.dark-mode .modal-close { color: #94a3b8 !important; }
    body.dark-mode .modal-table th { border-bottom-color: #334155 !important; color: #94a3b8 !important; }
    body.dark-mode .modal-table td { border-bottom-color: #334155 !important; color: #e2e8f0 !important; }
    body.dark-mode .status-badge { background: rgba(22, 101, 52, 0.3) !important; color: #4ade80 !important; }
    body.dark-mode .status-badge.pending { background: rgba(153, 27, 27, 0.3) !important; color: #f87171 !important; }
    
    body.dark-mode input, body.dark-mode select, body.dark-mode textarea {
        background: #0f172a !important; border-color: #334155 !important; color: white !important;
    }
    body.dark-mode input:disabled, body.dark-mode select:disabled {
        background: #1e293b !important; color: #94a3b8 !important; border-color: #334155 !important;
    }

    /* --- MOBILE VIEW RESPONSIVENESS --- */
    @media (max-width: 768px) {
        body {
            flex-direction: column !important;
            overflow: auto !important;
            height: auto !important;
            min-height: 100vh !important;
        }
        .sidebar {
            width: 100% !important;
            min-width: 100% !important;
            height: auto !important;
            position: static !important;
            margin: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }
        body.dark-mode .sidebar {
            border-bottom: 1px solid #334155 !important;
        }
        .sidebar-header {
            height: 70px !important;
            margin-bottom: 0 !important;
            padding: 12px 20px !important;
        }
        .sidebar.collapsed {
            width: 100% !important;
            min-width: 100% !important;
        }
        .sidebar.collapsed .brand-group {
            opacity: 1 !important;
            pointer-events: auto !important;
            display: flex !important;
        }
        .sidebar.collapsed .sidebar-header {
            justify-content: flex-start !important;
            padding: 12px 20px !important;
        }
        .toggle-icon {
            display: block !important;
            position: absolute !important;
            right: 20px !important;
            top: 24px !important;
            transform: none !important;
            font-size: 26px !important;
        }
        .sidebar.collapsed .toggle-icon {
            right: 20px !important;
            transform: none !important;
        }
        .sidebar.collapsed .nav-menu,
        .sidebar.collapsed .sidebar-footer {
            display: none !important;
        }
        .nav-menu {
            display: flex !important;
            flex-direction: column !important;
            padding: 10px 20px !important;
        }
        .sidebar-footer {
            padding: 10px 20px !important;
        }
        .main-container {
            width: 100% !important;
            overflow-y: visible !important;
            padding-bottom: 40px !important;
        }
    }

    @media print {
        .sidebar { display: none !important; }
        body { background: white !important; }
    }
</style>

<!-- Add preload-transitions class to body to prevent flash, script immediately executes -->
<script>
    document.body.classList.add('preload-transitions');
</script>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand-group" id="brandGroup">
            <img src="logo/logopulantubig.png" class="logo" alt="Barangay Logo"> 
            <div class="brand-text"><b>Barangay Pulantubig</b><span>Residents Profiling System</span></div>
        </div>
        <i class="fa-solid fa-angles-right toggle-icon" id="toggleBtn" onclick="toggleSidebar()"></i>
    </div>

    <nav class="nav-menu">
        <?php foreach ($nav_items as $item): ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>" class="nav-item<?php echo left_nav_active($current_page, $item['pages']); ?>">
                <i class="fa-solid <?php echo htmlspecialchars($item['icon']); ?>"></i>
                <span class="nav-text"><?php echo htmlspecialchars($item['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-profile-container" id="sidebarProfileContainer">
            <div class="sidebar-account" id="sidebarAccountBtn" onclick="toggleSidebarAccountDropdown()">
                <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
                <div class="sidebar-account-meta">
                    <div class="sidebar-account-name"><?php echo htmlspecialchars($role_title); ?></div>
                    <div class="sidebar-account-role"><?php echo htmlspecialchars($role_subtitle); ?></div>
                </div>
                <i class="fa-solid fa-chevron-down sidebar-account-caret"></i>
            </div>

            <div class="sidebar-logout-dropdown" id="sidebarLogoutDropdown">
                <div class="sidebar-dropdown-header">Signed in as<br><b><?php echo htmlspecialchars($role_title); ?></b></div>
                <?php if ($show_archived_link): ?>
                    <a href="archived_residents.php" class="sidebar-dropdown-link">
                        <i class="fa-solid fa-box-archive"></i> Archived Residents
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="sidebar-logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Immediately set collapsed state if stored to avoid layout shift before paint
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        document.getElementById('sidebar').classList.add('collapsed');
        document.body.classList.add('sidebar-is-collapsed');
        document.getElementById('toggleBtn').classList.replace('fa-angles-left', 'fa-angles-right');
    }

    function closeSidebarAccountDropdown() {
        const dropdown = document.getElementById('sidebarLogoutDropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    }

    function toggleSidebarAccountDropdown() {
        const dropdown = document.getElementById('sidebarLogoutDropdown');
        if (!dropdown) return;
        dropdown.classList.toggle('show');
    }

    window.toggleSidebar = function() {
        const sidebar = document.getElementById('sidebar');
        const icon = document.getElementById('toggleBtn');
        if (!sidebar || !icon) return;

        sidebar.classList.toggle('collapsed');
        document.body.classList.toggle('sidebar-is-collapsed');
        closeSidebarAccountDropdown();

        if (sidebar.classList.contains('collapsed')) {
            icon.classList.remove('fa-angles-left');
            icon.classList.add('fa-angles-right');
            localStorage.setItem('sidebar-collapsed', 'true');
        } else {
            icon.classList.remove('fa-angles-right');
            icon.classList.add('fa-angles-left');
            localStorage.setItem('sidebar-collapsed', 'false');
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const icon = document.getElementById('toggleBtn');
        if (!sidebar || !icon) return;

        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.body.classList.add('sidebar-is-collapsed');
            sidebar.classList.add('collapsed');
            icon.classList.remove('fa-angles-left');
            icon.classList.add('fa-angles-right');
        } else {
            icon.classList.remove('fa-angles-right');
            icon.classList.add('fa-angles-left');
        }

        // Restore transitions after initial load
        setTimeout(() => {
            document.body.classList.remove('preload-transitions');
        }, 100);
    });

    window.addEventListener('click', function(e) {
        if (!e.target.closest('#sidebarProfileContainer')) {
            closeSidebarAccountDropdown();
        }
    });

    // Dark Mode Initialization and Toggle Logic
    document.addEventListener('DOMContentLoaded', function() {
        const topHeaders = document.querySelectorAll('.top-header, .header');
        topHeaders.forEach(header => {
            if (header.querySelector('.theme-toggle-btn')) return;
            
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'theme-toggle-btn';
            toggleBtn.innerHTML = '<i class="fa-solid fa-moon"></i>';
            toggleBtn.style.cssText = 'background:none; border:none; color:#64748b; font-size:20px; cursor:pointer; outline:none; transition:color 0.2s; padding: 0 10px; margin-left: auto;';
            
            header.appendChild(toggleBtn);

            toggleBtn.addEventListener('click', () => {
                document.body.classList.toggle('dark-mode');
                const isDark = document.body.classList.contains('dark-mode');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                updateThemeIcons();
            });
        });

        function updateThemeIcons() {
            const isDark = document.body.classList.contains('dark-mode');
            document.querySelectorAll('.theme-toggle-btn i').forEach(icon => {
                icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
                icon.style.color = isDark ? '#fbbf24' : '#64748b';
            });
        }

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
        updateThemeIcons();
    });
</script>
