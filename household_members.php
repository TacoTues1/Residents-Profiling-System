<?php
include('db.php');
session_start();

// Security check: Only Secretary role
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'Secretary') {
    header("Location: login.php");
    exit();
}

$household_no = $_GET['household_no'] ?? '';
$safe_hh_no = mysqli_real_escape_string($conn, $household_no);

// Fetch Household Information
$query_h = "SELECT * FROM households WHERE household_no = '$safe_hh_no'";
$result_h = mysqli_query($conn, $query_h);
$household = mysqli_fetch_assoc($result_h);

if (!$household) {
    echo "<script>alert('Household not found.'); window.location.href='residents.php';</script>";
    exit();
}

// Fetch Members (Only active members)
$query_m = "SELECT * FROM residents WHERE household_no = '$safe_hh_no' AND COALESCE(is_archived, 0) = 0 ORDER BY id ASC";
$result_m = mysqli_query($conn, $query_m);

// Fetch all activity participants and group by resident_id
$act_query = mysqli_query($conn, "SELECT ap.resident_id, a.activity_name FROM activity_participants ap JOIN activities a ON ap.activity_id = a.id");
$resident_activities = [];
if ($act_query) {
    while ($row_act = mysqli_fetch_assoc($act_query)) {
        if (!empty($row_act['resident_id'])) {
            $resident_activities[$row_act['resident_id']][] = $row_act['activity_name'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Household Details | Profiling System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --sidebar-navy: #1e293b; 
            --accent-blue: #2563eb; 
            --logo-orange: #ff9800;
            --text-gray: #64748b; 
            --border-color: #e2e8f0;
            --main-bg: #f1f5f9;
            --danger-red: #ef4444;
            --table-font-size: 13px;
            --primary-text: #1e293b;
        }

        body { font-family: 'Inter', sans-serif; margin: 0; display: flex; height: 100vh; background: var(--main-bg); overflow: hidden; }

        /* --- SIDEBAR --- */
        .sidebar { width: 280px; background: var(--sidebar-navy); color: white; display: flex; flex-direction: column; position: relative; flex-shrink: 0; transition: width 0.3s ease; overflow: hidden; }
        .sidebar.collapsed { width: 80px; }
        
        
        .sidebar-header { padding: 15px 15px; display: flex; align-items: center; position: relative; height: 70px; justify-content: flex-start; }
        .brand-group { display: flex; align-items: center;  }
        .brand-logo-container { border: 3px solid var(--logo-orange); border-radius: 14px; width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .brand-logo-container i { color: var(--logo-orange); font-size: 30px; }
        .brand-text { margin-left: 15px; white-space: nowrap; }
        .brand-text b { display: block; font-size: 20px; line-height: 1.1; color: white; }
        .brand-text span { color: #94a3b8; font-size: 14px; }
        .toggle-icon { cursor: pointer; color: #64748b; font-size: 28px;  margin-left: auto; }

        .sidebar.collapsed .brand-group { display: none; }
        .sidebar.collapsed .sidebar-header { justify-content: center; }
        .sidebar.collapsed .toggle-icon { margin-left: 0; color: white; }

        .nav-menu { padding: 10px 12px; flex-grow: 1; }
        .nav-item { display: flex; align-items: center; padding: 8px 12px; color: #cbd5e1; text-decoration: none; border-radius: 12px; margin-bottom: 4px; }
        .nav-item.active { background: var(--accent-blue); color: white; }
        .nav-item i { font-size: 15px; min-width: 28px; text-align: center; }
        .nav-text { font-size: 16px; font-weight: 600; margin-left: 15px; }
        
        /* Centering icons when collapsed */
        .sidebar.collapsed .nav-item { justify-content: center; padding: 18px 0; }
        .sidebar.collapsed .nav-item i { min-width: unset; }
        .sidebar.collapsed .nav-text { display: none; }

        /* --- MAIN LAYOUT --- */
        .main-container { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .top-header { background: #ffffff; padding: 20px 40px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        
        .user-pill { display: flex; align-items: center; background: #f8fafc; padding: 8px 15px; border-radius: 50px; border: 1px solid #e2e8f0; cursor: pointer; }
        .avatar { background: var(--accent-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .logout-dropdown { position: absolute; top: 110%; right: 40px; background: white; border: 1px solid #e2e8f0; border-radius: 12px; width: 220px;  display: none; z-index: 100; }
        .logout-dropdown.show { display: block; }

        .content-body { padding: 16px 20px 20px; }
        .hh-card, .panel { background: white; border: 1px solid #e5e7eb; padding: 18px; border-radius: 20px; border: 1px solid var(--border-color); margin-bottom: 25px; }
        
        .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px; }
        .info-item label { display: block; font-size: 11px; font-weight: 800; color: var(--text-gray); text- margin-bottom: 6px; }
        .info-item span { font-size: 15px; color: #1e293b; font-weight: 600; }

        /* --- DATA TABLE --- */
        .table-wrapper { overflow-x: auto; border: 1px solid var(--border-color); border-radius: 12px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; min-width: 1400px; }
        th { text-align: left; padding: 16px; color: var(--text-gray); font-size: 11px; text- background: #f8fafc; border-bottom: 2px solid var(--border-color); }
        
        td { padding: 16px; border-bottom: 1px solid #e5e7eb; font-size: var(--table-font-size); color: var(--primary-text); vertical-align: middle; }

        .res-photo { width: 45px; height: 45px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border-color); cursor: pointer; }

        /* --- ACTION BUTTONS --- */
        .action-btns { display: flex; gap: 10px; }
        .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 35px; height: 35px; border-radius: 10px; text-decoration: none; border: 1px solid var(--border-color); cursor: pointer;  }
        .btn-edit { color: var(--accent-blue); background-color: #eff6ff; }

        .btn-archive { color: var(--danger-red); background-color: #fef2f2; border-color: #fecaca; }
        .btn-archive-text { width: auto; padding: 0 12px; gap: 7px; font-weight: 700; font-size: 12px; font-family: inherit; }
        .status-pill { display: inline-flex; padding: 6px 12px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 800; }
        .status-muted { background: #fef3c7; color: #92400e; }

        .btn-add { background: var(--accent-blue); color: white; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .btn-secondary { background: #f1f5f9; color: #1e293b; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border-color); }

        .lightbox-modal { display: none; position: fixed; z-index: 2000; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); justify-content: center; align-items: center; }
        .lightbox-content { max-width: 90%; max-height: 90%; border-radius: 16px; }

        /* --- CLASSIFICATION UNIFORM STYLING --- */
        .class-container { display: flex; flex-direction: column; justify-content: flex-start; }
        .class-text { 
            display: block; 
            font-size: var(--table-font-size); 
            font-weight: 500; 
            color: var(--primary-text); 
            line-height: 1.5;
        }

        .activity-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        /* Dark Mode Overrides */
        body.dark-mode .hh-card, body.dark-mode .panel { background: #1e293b; border-color: #334155; }
        body.dark-mode .info-item span { color: white; }
        body.dark-mode .info-item label { color: #94a3b8; }
        body.dark-mode .table-wrapper { border-color: #334155; }
        body.dark-mode th { background: #0f172a; border-bottom-color: #334155; color: #94a3b8; }
        body.dark-mode td { border-bottom-color: #334155; color: #e2e8f0; }
        body.dark-mode .class-text { color: #e2e8f0; }
        body.dark-mode .activity-badge { background: #03294f; color: #38bdf8; }
        body.dark-mode .btn-secondary { background: #0f172a; color: white; border-color: #334155; }
        body.dark-mode h2, body.dark-mode h3 { color: white !important; }
        body.dark-mode p { color: #94a3b8 !important; }
    </style>
</head>
<body>

<div class="lightbox-modal" id="lightboxModal" onclick="closeLightbox()">
    <img class="lightbox-content" id="lightboxImg" src="">
</div>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <header class="top-header">
        <div>
            <h2 style="margin:0; font-weight:700;">Household Profile</h2>
            <p style="margin:0; color: var(--text-gray);">Manage members of Household #<?php echo htmlspecialchars($household['household_no']); ?></p>
        </div>

        <div class="user-profile-container">
            <div class="user-pill" onclick="toggleLogout()">
                <div class="avatar"><i class="fa-solid fa-user"></i></div>
                <div style="margin: 0 15px; line-height: 1.2;">
                    <div style="font-weight: 600; font-size: 15px;">Secretary</div>
                    <div style="color:var(--text-gray); font-size: 12px;">Barangay Secretary</div>
                </div>
                <i class="fa-solid fa-chevron-down" style="font-size: 12px; color: #94a3b8;"></i>
            </div>
            <div class="logout-dropdown" id="logoutDropdown">
                <div style="padding: 15px; text-align: center; border-bottom: 1px solid #e5e7eb; color: #64748b; font-size: 14px;">Signed in as<br><b>Secretary</b></div>
                <a href="logout.php" class="logout-btn" style="display:flex; align-items:center; justify-content:center; padding:15px; color:var(--danger-red); text-decoration:none; font-weight:600;">
                    <i class="fa-solid fa-right-from-bracket" style="margin-right:10px;"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="content-body">
        <div class="hh-card">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:16px;">
                <h3 style="margin:0;">Household Information</h3>
                <a href="edit_household.php?household_no=<?php echo urlencode($household['household_no']); ?>" class="btn-secondary">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Household
                </a>
            </div>
            <div class="info-grid">
                <div class="info-item"><label>Household No.</label><span>#<?php echo htmlspecialchars($household['household_no']); ?></span></div>
                <div class="info-item"><label>Purok</label><span><?php echo htmlspecialchars($household['purok'] ?? '---'); ?></span></div>
                <div class="info-item"><label>Survey Date</label><span><?php echo !empty($household['survey_date']) ? date("M d, Y", strtotime($household['survey_date'])) : '---'; ?></span></div>
                <div class="info-item"><label>Complete Address</label><span><?php echo htmlspecialchars($household['address'] ?? '---'); ?></span></div>
                <div class="info-item"><label>House Ownership</label><span><?php echo htmlspecialchars($household['house'] ?? '---'); ?></span></div>
                <div class="info-item"><label>House Type</label><span><?php echo htmlspecialchars($household['house_type'] ?? '---'); ?></span></div>
                <div class="info-item"><label>Electricity</label><span><?php echo htmlspecialchars($household['electricity'] ?? '---'); ?></span></div>
                <div class="info-item"><label>Water Source</label><span><?php echo htmlspecialchars($household['water'] ?? '---'); ?></span></div>
            </div>
        </div>

        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin:0;">Registered Family Members</h3>
                <a href="add_members.php?household_no=<?php echo urlencode($safe_hh_no); ?>" class="btn-add">
                    <i class="fa-solid fa-plus"></i> Add Member
                </a>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Relation</th>
                            <th>Sex</th>
                            <th>Age</th>
                            <th>Birth Date</th>
                            <th>Civil Status</th>
                            <th>Education</th>
                            <th>Classification</th> 
                            <th>Activities</th>
                            <th>Employed</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($m = mysqli_fetch_assoc($result_m)): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?php echo (!empty($m['photo_path'])) ? $m['photo_path'] : 'uploads/default.png'; ?>" class="res-photo" onclick="openLightbox(this.src)">
                                    <div style="font-weight:700;">
                                        <?php echo htmlspecialchars($m['last_name'] . ', ' . $m['first_name'] . ', ' . $m['middle_name']); ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($m['relationship']); ?></td>
                            <td><?php echo htmlspecialchars($m['gender']); ?></td>
                            <td><?php echo htmlspecialchars($m['age']); ?></td>
                            <td><?php echo !empty($m['dob']) ? date("M d, Y", strtotime($m['dob'])) : '---'; ?></td>
                            <td><?php echo htmlspecialchars($m['civil_status']); ?></td>
                            <td><?php echo htmlspecialchars($m['education'] ?? '---'); ?></td>
                            
                            <td>
                                <div class="class-container">
                                    <?php 
                                        $all_labels = [];
                                        if (isset($m['is_4ps']) && $m['is_4ps'] == 1) $all_labels[] = "4Ps";
                                        if (isset($m['is_pwd']) && $m['is_pwd'] == 1) $all_labels[] = "PWD";
                                        if (isset($m['is_senior']) && $m['is_senior'] == 1) $all_labels[] = "Senior Citizen";
                                        if (isset($m['is_solo']) && $m['is_solo'] == 1) $all_labels[] = "Solo Parent";
                                        if (isset($m['is_voter']) && $m['is_voter'] == 1) $all_labels[] = "Voter"; 
                                        if (isset($m['is_minor']) && $m['is_minor'] == 1) $all_labels[] = "Minor";

                                        if (!empty($all_labels)) {
                                            foreach($all_labels as $label) {
                                                echo "<span class='class-text'>" . htmlspecialchars($label) . "</span>";
                                            }
                                        } else {
                                            echo "<span style='color:#cbd5e1;'>None</span>";
                                        }
                                    ?>
                                </div>
                            </td>

                            <td>
                                <?php 
                                    $activities = $resident_activities[$m['id']] ?? [];
                                    if (!empty($activities)): 
                                ?>
                                    <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                        <?php foreach ($activities as $act): ?>
                                            <span class="activity-badge"><?php echo htmlspecialchars($act); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">None</span>
                                <?php endif; ?>
                            </td>

                            <td><?php echo (isset($m['employment_status']) && strtoupper($m['employment_status']) == 'YES') ? 'Yes' : 'No'; ?></td>
                            <td>
                                <span class="status-pill <?php echo ($m['status'] ?? 'Active') !== 'Active' ? 'status-muted' : ''; ?>">
                                    <?php echo htmlspecialchars($m['status'] ?? 'Active'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="edit_member.php?id=<?php echo $m['id']; ?>" class="action-btn btn-edit" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <button type="button" class="action-btn btn-archive btn-archive-text" title="Archive Member" 
                                            onclick="openArchivePage('<?php echo $m['id']; ?>')">
                                        <i class="fa-solid fa-box-archive"></i> Archive
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const icon = document.getElementById('toggleBtn');
        sidebar.classList.toggle('collapsed');
        document.body.classList.toggle('sidebar-is-collapsed');
        icon.classList.toggle('fa-xmark');
        icon.classList.toggle('fa-bars');
        localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.body.classList.add('sidebar-is-collapsed');
            document.getElementById('sidebar').classList.add('collapsed');
            document.getElementById('toggleBtn').classList.replace('fa-xmark', 'fa-bars');
        }
    });

    function toggleLogout() { document.getElementById('logoutDropdown').classList.toggle('show'); }
    function openLightbox(src) { document.getElementById('lightboxImg').src = src; document.getElementById('lightboxModal').style.display = "flex"; }
    function closeLightbox() { document.getElementById('lightboxModal').style.display = "none"; }

    function openArchivePage(id) {
        window.location.href = "archived.php?id=" + id + "&household_no=<?php echo urlencode($household_no); ?>";
    }

    window.onclick = function(e) {
        if (!e.target.closest('.user-profile-container')) {
            const dropdown = document.getElementById('logoutDropdown');
            if (dropdown && dropdown.classList.contains('show')) { dropdown.classList.remove('show'); }
        }
    }
</script>
</body>
</html>















