<?php
include('db.php');
session_start();

// Ensure the user is logged in and has a role assigned
if(!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_email = '';
if ($user_id) {
    $q = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
    if ($q && mysqli_num_rows($q) > 0) {
        $u = mysqli_fetch_assoc($q);
        $user_email = $u['email'] ?? '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --sidebar-navy: #1e293b; 
            --accent-blue: #2563eb; 
            --logo-orange: #ff9800;
            --text-gray: #64748b;
        }

        body { font-family: 'Inter', sans-serif; margin: 0; display: flex; height: 100vh; background: #f1f5f9; overflow: hidden; }
        .sidebar { width: 280px; background: var(--sidebar-navy); color: white; display: flex; flex-direction: column; position: relative; flex-shrink: 0; transition: width 0.3s ease; overflow: hidden; }
        .sidebar.collapsed { width: 80px; }
        .sidebar-header { padding: 15px 15px; display: flex; align-items: center; height: 70px; }
        .brand-group { display: flex; align-items: center;  }
        .brand-logo-container { border: 3px solid var(--logo-orange); border-radius: 14px; width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .brand-logo-container i { color: var(--logo-orange); font-size: 30px; }
        .brand-text { margin-left: 15px; white-space: nowrap; }
        .brand-text b { display: block; font-size: 20px; color: white; }
        .brand-text span { color: #94a3b8; font-size: 14px; }     
        .toggle-icon { cursor: pointer; color: #64748b; font-size: 28px;  margin-left: auto; }
        .sidebar.collapsed .brand-group { display: none; }
        .sidebar.collapsed .sidebar-header { justify-content: center; padding: 25px 0; }
        .sidebar.collapsed .toggle-icon { margin-left: 0; color: white; font-size: 32px; }
        .nav-menu { padding: 10px 12px; flex-grow: 1; }
        .nav-item { display: flex; align-items: center; padding: 8px 12px; color: #cbd5e1; text-decoration: none; border-radius: 12px; margin-bottom: 4px; font-weight: 600;  }
        .nav-item.active { background: var(--accent-blue); color: white; }
        .nav-item i { font-size: 15px; min-width: 28px; text-align: center; }
        .nav-text { font-size: 14px; margin-left: 10px; }
        .sidebar.collapsed .nav-text { display: none; }
        .sidebar.collapsed .nav-item { justify-content: center; padding: 18px 0; }
        .main-container { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .top-header { background: #ffffff; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; position: relative; }
        .user-profile-container { position: relative; }
        .user-pill { display: flex; align-items: center; background: #f8fafc; padding: 8px 15px; border-radius: 50px; border: 1px solid #e2e8f0; cursor: pointer; }
        .avatar { background: var(--accent-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; } 
        .logout-dropdown { position: absolute; top: 110%; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 12px; width: 220px;  display: none; z-index: 100; overflow: hidden; }
        .logout-dropdown.show { display: block; }
        .dropdown-header { padding: 15px; text-align: center; border-bottom: 1px solid #e5e7eb; color: #64748b; font-size: 14px; }
        .dropdown-header b { display: block; color: #1e293b; margin-top: 4px; font-size: 16px; }
        .logout-btn { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 20px; color: #ef4444; text-decoration: none; font-weight: 600; font-size: 16px; }
        .content-body { padding: 16px 20px 20px; }
        .settings-card { background: white; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        h2 { margin: 0 0 5px 0; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 4px; }
        label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 14px; }
        input, select { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 16px; box-sizing: border-box; }
        .btn { background: var(--accent-blue); color: white; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; cursor: pointer; font-size: 16px; }
    </style>
</head>
<body>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <header class="top-header">
        <div>
            <h2>Settings</h2>
            <p style="margin:0; color: var(--text-gray);">Manage your account and system preferences</p>
        </div>

        <div class="user-profile-container">
            <div class="user-pill" onclick="toggleLogout()">
                <div class="avatar"><i class="fa-solid fa-user"></i></div>
                <div style="line-height: 1.2; margin-left: 15px; margin-right: 15px;">
                    <div style="font-weight: 600; font-size: 15px;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Secretary'); ?></div>
                    <div style="color:#64748b; font-size: 13px;"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Barangay Secretary'); ?></div>
                </div>
                <i class="fa-solid fa-chevron-down" style="font-size: 12px; color: #94a3b8;"></i>
            </div>
            
            <div class="logout-dropdown" id="logoutDropdown">
                <div class="dropdown-header">Signed in as<br><b><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Secretary'); ?></b></div>
                <a href="logout.php" class="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="content-body">
        <div class="settings-card">
            <h3 style="margin-top:0;">Profile Information</h3>
            <div class="form-grid">
                <div class="form-group"><label>Full Name</label><input type="text" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" disabled></div>
                <div class="form-group"><label>Username</label><input type="text" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" disabled></div>
                <div class="form-group"><label>System Role</label><input type="text" value="<?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?>" disabled></div>
                <div class="form-group"><label>Email Address</label><input type="email" value="<?php echo htmlspecialchars($user_email); ?>" placeholder="No email provided" disabled></div>
            </div>
            <button class="btn">Save Changes</button>
        </div>

        <div class="settings-card">
            <h3 style="margin-top:0;">Security</h3>
            <div class="form-grid">
                <div class="form-group"><label>Current Password</label><input type="password"></div>
                <div class="form-group"><label>New Password</label><input type="password"></div>
            </div>
            <button class="btn">Update Password</button>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const icon = document.getElementById('toggleBtn');
        sidebar.classList.toggle('collapsed');
        document.body.classList.toggle('sidebar-is-collapsed');
        
        if (sidebar.classList.contains('collapsed')) {
            localStorage.setItem('sidebar-collapsed', 'true');
            icon.classList.replace('fa-xmark', 'fa-bars');
        } else {
            localStorage.setItem('sidebar-collapsed', 'false');
            icon.classList.replace('fa-bars', 'fa-xmark');
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.body.classList.add('sidebar-is-collapsed');
            document.getElementById('sidebar').classList.add('collapsed');
            document.getElementById('toggleBtn').classList.replace('fa-xmark', 'fa-bars');
        }
    });

    function toggleLogout() {
        document.getElementById('logoutDropdown').classList.toggle('show');
    }

    window.onclick = function(event) {
        if (!event.target.closest('.user-profile-container')) {
            const dropdown = document.getElementById('logoutDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    }
</script>
</body>
</html>














