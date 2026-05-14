<?php 
include('db.php');
session_start();

$allowed_roles = ['Captain', 'Admin', 'Secretary'];
if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit();
}

// Handle Mark Received (AJAX)
if (isset($_POST['ajax_mark_received'])) {
    $activity_id = (int)$_POST['activity_id'];
    $resident_id = (int)($_POST['resident_id'] ?? 0);
    $household_no = $_POST['household_no'] ?? '';

    if ($activity_id > 0) {
        if ($household_no !== '') {
            $safe_hh = mysqli_real_escape_string($conn, $household_no);
            mysqli_query($conn, "UPDATE activity_participants SET status = 'Received', received_at = NOW() WHERE activity_id = '$activity_id' AND household_no = '$safe_hh'");
            $action_desc = mysqli_real_escape_string($conn, "Marked household #$safe_hh as received for activity #$activity_id");
            mysqli_query($conn, "INSERT INTO logs (action) VALUES ('$action_desc')");
        } elseif ($resident_id > 0) {
            $check = mysqli_query($conn, "SELECT id, status FROM activity_participants WHERE activity_id = '$activity_id' AND resident_id = '$resident_id' LIMIT 1");
            $row = $check ? mysqli_fetch_assoc($check) : null;

            if ($row) {
                if ($row['status'] !== 'Received') {
                    $participant_id = (int)$row['id'];
                    mysqli_query($conn, "UPDATE activity_participants SET status = 'Received', received_at = NOW() WHERE id = '$participant_id'");
                }
            } else {
                $hh_res = mysqli_query($conn, "SELECT household_no FROM residents WHERE id = '$resident_id' LIMIT 1");
                $hh_row = $hh_res ? mysqli_fetch_assoc($hh_res) : null;
                $household_no_res = $hh_row['household_no'] ?? null;

                if ($household_no_res === null || $household_no_res === '') {
                    $insert_sql = "INSERT INTO activity_participants (activity_id, resident_id, status) VALUES ('$activity_id', '$resident_id', 'Received')";
                } else {
                    $safe_hh_res = mysqli_real_escape_string($conn, $household_no_res);
                    $insert_sql = "INSERT INTO activity_participants (activity_id, resident_id, household_no, status) VALUES ('$activity_id', '$resident_id', '$safe_hh_res', 'Received')";
                }
                mysqli_query($conn, $insert_sql);
            }

            $action_desc = mysqli_real_escape_string($conn, "Marked resident #$resident_id as received for activity #$activity_id");
            mysqli_query($conn, "INSERT INTO logs (action) VALUES ('$action_desc')");
        }
    }
    echo json_encode(['success' => true]);
    exit();
}

// Handle Delete Activity (AJAX)
if (isset($_POST['ajax_delete_activity'])) {
    $activity_id = (int)$_POST['activity_id'];
    if ($activity_id > 0) {
        // Delete participants first, then the activity
        mysqli_query($conn, "DELETE FROM activity_participants WHERE activity_id = '$activity_id'");
        mysqli_query($conn, "DELETE FROM activities WHERE id = '$activity_id'");
        
        $action_desc = mysqli_real_escape_string($conn, "Deleted activity #$activity_id");
        mysqli_query($conn, "INSERT INTO logs (action) VALUES ('$action_desc')");
    }
    echo json_encode(['success' => true]);
    exit();
}

// Handle Fetch Participants (AJAX)
if (isset($_GET['ajax_fetch_participants'])) {
    $activity_id = (int)$_GET['activity_id'];
    $act_query = mysqli_query($conn, "SELECT activity_name FROM activities WHERE id = '$activity_id' LIMIT 1");
    $act_title = $act_query ? mysqli_fetch_assoc($act_query)['activity_name'] : '';
    $is_4ps_act = (strtolower(trim($act_title)) === '4ps beneficiary');

    if ($is_4ps_act) {
        $sql = "SELECT ap.household_no, min(ap.status) as status, min(ap.id) as participant_id,
                       GROUP_CONCAT(CONCAT(r.last_name, ', ', r.first_name) SEPARATOR '; ') as members
                FROM activity_participants ap
                JOIN residents r ON ap.resident_id = r.id
                WHERE ap.activity_id = '$activity_id'
                GROUP BY ap.household_no
                ORDER BY ap.household_no ASC";
        $participants = mysqli_query($conn, $sql);
        $data = [];
        while($row = mysqli_fetch_assoc($participants)) {
            $data[] = [
                'is_hh_view' => true,
                'household_no' => $row['household_no'],
                'members' => $row['members'],
                'status' => $row['status'],
                'participant_id' => $row['participant_id'],
                'activity_id' => $activity_id
            ];
        }
        echo json_encode($data);
        exit();
    } else {
        $sql = "SELECT ap.id AS participant_id, ap.status,
                   r.id AS resident_id, r.last_name, r.first_name, r.middle_name,
                   r.household_no, r.purok
            FROM activity_participants ap
            JOIN residents r ON ap.resident_id = r.id
            WHERE ap.activity_id = '$activity_id'
            ORDER BY r.last_name ASC, r.first_name ASC";
        $participants = mysqli_query($conn, $sql);
        $data = [];
        while($row = mysqli_fetch_assoc($participants)) {
            $data[] = $row;
        }
        echo json_encode($data);
        exit();
    }
}

// Fetch Activities with a count of how many residents have already received assistance
$query = "SELECT a.*, 
          (SELECT COUNT(*) FROM activity_participants ap WHERE ap.activity_id = a.id) as beneficiary_count 
          FROM activities a ORDER BY a.id DESC";
$result = mysqli_query($conn, $query);

$user_role = $_SESSION['role'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activities - Profiling System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --sidebar-navy: #1e293b; 
            --accent-blue: #2563eb; 
            --accent-green: #10b981;
            --logo-orange: #ff9800; 
            --text-gray: #64748b; 
        }

        body { font-family: 'Inter', sans-serif; margin: 0; display: flex; height: 100vh; background: #f1f5f9; overflow: hidden; }
        
        /* --- SIDEBAR --- */
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
        .nav-text { margin-left: 10px; }
        .sidebar.collapsed .nav-text { display: none; }
        .sidebar.collapsed .nav-item { justify-content: center; padding: 18px 0; }

        /* --- MAIN CONTAINER & TOP HEADER --- */
        .main-container { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .top-header { background: #ffffff; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; position: relative; }
        
        .user-profile-container { position: relative; }
        .user-pill { display: flex; align-items: center; background: #f8fafc; padding: 8px 15px; border-radius: 50px; border: 1px solid #e2e8f0; cursor: pointer; }
        .avatar { background: var(--accent-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        /* --- LOGOUT DROPDOWN --- */
        .logout-dropdown { position: absolute; top: 110%; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 12px; width: 220px;  display: none; z-index: 100; overflow: hidden; }
        .logout-dropdown.show { display: block; }
        .dropdown-header { padding: 15px; text-align: center; border-bottom: 1px solid #e5e7eb; color: #64748b; font-size: 14px; }
        .dropdown-header b { display: block; color: #1e293b; margin-top: 4px; font-size: 16px; }
        .logout-btn { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 20px; color: #ef4444; text-decoration: none; font-weight: 600; font-size: 16px; }

        /* --- PAGE CONTENT --- */
        .content-body { padding: 16px 20px 20px; }
        .panel { background: white; border: 1px solid #e5e7eb; padding: 18px; border-radius: 20px; border: 1px solid #e2e8f0;  }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 18px 15px; border-bottom: 2px solid #e5e7eb; color: var(--text-gray); font-size: 12px; text- letter-spacing: 0.05em; }
        td { padding: 18px 15px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #334155; }
        
        .badge { padding: 4px 10px; border-radius: 16px; font-size: 12px; font-weight: 600; background: #f1f5f9; color: var(--text-gray); display: inline-flex; align-items: center; gap: 5px; }
        .badge-success { background: #dcfce7; color: #166534; }
        
        .btn-add { background: var(--accent-blue); color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 600;  display: inline-flex; align-items: center; gap: 8px; }

        
        .action-link { text-decoration: none; font-weight: 600; font-size: 13px; margin-right: 15px; display: inline-flex; align-items: center; gap: 4px; }
        .link-blue { color: var(--accent-blue); }
        .link-green { color: var(--accent-green); }
        .link-red { color: #ef4444; }

        /* --- MODAL --- */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 20px; width: 600px; max-width: 90%; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .modal-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .modal-header h3 { margin: 0; color: #1e293b; font-size: 18px; }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
        .modal-body { padding: 25px; overflow-y: auto; flex-grow: 1; }
        .modal-table { width: 100%; border-collapse: collapse; }
        .modal-table th { text-align: left; padding: 12px; border-bottom: 2px solid #e5e7eb; color: var(--text-gray); font-size: 12px; }
        .modal-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .status-badge { background: #e8f5e9; color: #2e7d32; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .pending { background: #fee2e2; color: #991b1b; }
        .btn-mark { background: var(--accent-blue); color: white; border: none; padding: 8px 15px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 12px; }
        .btn-mark:hover { background: #1d4ed8; }
        .modal-empty { text-align: center; color: var(--text-gray); padding: 16px; font-size: 14px; }
    </style>
</head>
<body>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <header class="top-header">
        <div>
            <h2 style="margin:0; color: #1e293b;">Barangay Activities</h2>
            <p style="margin:0; color: var(--text-gray);">Manage programs and track assistance distribution</p>
        </div>

        <div class="user-profile-container">
            <div class="user-pill" onclick="toggleLogout()">
                <div class="avatar"><i class="fa-solid fa-user"></i></div>
                <div style="line-height: 1.2; margin-left: 15px; margin-right: 15px;">
                    <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($user_role); ?></div>
                    <div style="color:#64748b; font-size: 13px;">Authorized Personnel</div>
                </div>
                <i class="fa-solid fa-chevron-down" style="font-size: 12px; color: #94a3b8;"></i>
            </div>
            
            <div class="logout-dropdown" id="logoutDropdown">
                <div class="dropdown-header">Signed in as<br><b><?php echo htmlspecialchars($user_role); ?></b></div>
                <a href="logout.php" class="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="content-body">
        <div class="panel">
            <div style="display:flex; justify-content: space-between; align-items:center;">
                <h3 style="margin:0; color: #1e293b;">Activity Monitoring</h3>
                <a href="add_activity.php" class="btn-add"><i class="fa-solid fa-plus"></i> New Activity</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Activity Details</th>
                        <th>Date</th>
                        <th>Beneficiaries</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr style="cursor: pointer;" onclick="openActivityModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['activity_name'] ?? $row['title'] ?? 'N/A')); ?>')">
                            <td>
                                <strong style="display:block; color: #1e293b;"><?php echo htmlspecialchars($row['activity_name'] ?? $row['title'] ?? 'N/A'); ?></strong>
                                <small style="color: var(--text-gray);"><?php echo htmlspecialchars($row['description'] ?? 'No description provided'); ?></small>
                            </td>
                            
                            <td>
                                <span style="display:inline-flex; align-items:center; gap:5px;">
                                    <i class="fa-regular fa-calendar" style="color: var(--accent-blue);"></i>
                                    <?php 
                                        $date_val = $row['activity_date'] ?? $row['date'] ?? null;
                                        echo ($date_val) ? date('M d, Y', strtotime($date_val)) : 'TBA';
                                    ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge <?php echo ($row['beneficiary_count'] > 0) ? 'badge-success' : ''; ?>">
                                    <i class="fa-solid fa-people-group"></i> <?php echo $row['beneficiary_count']; ?> Given
                                </span>
                            </td>
                            
                            <td>
                                <a href="edit_activity.php?id=<?php echo $row['id']; ?>" class="action-link link-blue" onclick="event.stopPropagation();">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                                <a href="#" class="action-link link-red" onclick="event.stopPropagation(); deleteActivity(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['activity_name'] ?? 'this activity')); ?>');">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 50px; color: var(--text-gray);">No activities found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="activityModal">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h3>Activity Beneficiaries</h3>
                <small style="color: var(--text-gray);" id="modalActivityTitle">Loading...</small>
            </div>
            <button class="modal-close" onclick="closeActivityModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="modal-empty">Loading participants...</div>
        </div>
    </div>
</div>

<script>
    let currentActivityId = 0;

    function openActivityModal(id, title) {
        currentActivityId = id;
        document.getElementById('activityModal').style.display = 'flex';
        document.getElementById('modalActivityTitle').textContent = 'Tracking for: ' + title;
        fetchParticipants(id);
    }

    function closeActivityModal() {
        document.getElementById('activityModal').style.display = 'none';
        currentActivityId = 0;
        // Optionally refresh the page to update counts if changes were made
        location.reload();
    }

    function fetchParticipants(id) {
        const modalBody = document.getElementById('modalBody');
        modalBody.innerHTML = '<div class="modal-empty">Loading participants...</div>';

        fetch('activities.php?ajax_fetch_participants=1&activity_id=' + id)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    modalBody.innerHTML = '<div class="modal-empty">No beneficiaries found for this activity.</div>';
                    return;
                }

                if (data[0] && data[0].is_hh_view) {
                    let html = '<table class="modal-table"><thead><tr><th>Household No.</th><th>Household Members</th><th>Status</th><th>Action</th></tr></thead><tbody>';
                    data.forEach(p => {
                        const statusClass = p.status === 'Received' ? 'status-badge' : 'status-badge pending';
                        const statusText = p.status === 'Received' ? 'RECEIVED' : 'PENDING';
                        
                        html += `<tr>
                            <td><strong>Household #${p.household_no || 'N/A'}</strong></td>
                            <td><small style="color: #64748b;">${p.members}</small></td>
                            <td><span class="${statusClass}">${statusText}</span></td>
                            <td>`;
                        
                        if (p.status !== 'Received') {
                            html += `<button class="btn-mark" onclick="markReceivedHH(${p.activity_id}, '${p.household_no}')">Mark Received</button>`;
                        } else {
                            html += `<span style="color: #166534; font-size: 12px; font-weight: 600;"><i class="fa-solid fa-check"></i> Done</span>`;
                        }

                        html += `</td></tr>`;
                    });
                    html += '</tbody></table>';
                    modalBody.innerHTML = html;
                } else {
                    let html = '<table class="modal-table"><thead><tr><th>Resident</th><th>Household No.</th><th>Status</th><th>Action</th></tr></thead><tbody>';
                    data.forEach(p => {
                        const fullName = `${p.last_name}, ${p.first_name} ${p.middle_name || ''}`.trim();
                        const statusClass = p.status === 'Received' ? 'status-badge' : 'status-badge pending';
                        const statusText = p.status === 'Received' ? 'RECEIVED' : 'PENDING';
                        
                        html += `<tr>
                            <td><strong>${fullName}</strong></td>
                            <td>${p.household_no || 'N/A'}</td>
                            <td><span class="${statusClass}">${statusText}</span></td>
                            <td>`;
                        
                        if (p.status !== 'Received') {
                            html += `<button class="btn-mark" onclick="markReceived(${p.resident_id})">Mark Received</button>`;
                        } else {
                            html += `<span style="color: #166534; font-size: 12px; font-weight: 600;"><i class="fa-solid fa-check"></i> Done</span>`;
                        }

                        html += `</td></tr>`;
                    });
                    html += '</tbody></table>';
                    modalBody.innerHTML = html;
                }
            })
            .catch(err => {
                modalBody.innerHTML = '<div class="modal-empty">Error loading participants.</div>';
                console.error(err);
            });
    }

    function markReceived(residentId) {
        if(!currentActivityId) return;

        const formData = new FormData();
        formData.append('ajax_mark_received', '1');
        formData.append('activity_id', currentActivityId);
        formData.append('resident_id', residentId);

        fetch('activities.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                fetchParticipants(currentActivityId);
            }
        })
        .catch(err => console.error(err));
    }

    function markReceivedHH(activityId, householdNo) {
        const formData = new FormData();
        formData.append('ajax_mark_received', '1');
        formData.append('activity_id', activityId);
        formData.append('household_no', householdNo);

        fetch('activities.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                fetchParticipants(activityId);
            }
        })
        .catch(err => console.error(err));
    }

    // SIDEBAR TOGGLE (Consistent with settings.php)
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

    // Initialize sidebar state on page load
    document.addEventListener("DOMContentLoaded", function() {
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.body.classList.add('sidebar-is-collapsed');
            document.getElementById('sidebar').classList.add('collapsed');
            document.getElementById('toggleBtn').classList.replace('fa-xmark', 'fa-bars');
        }
    });

    // LOGOUT DROPDOWN
    function toggleLogout() {
        document.getElementById('logoutDropdown').classList.toggle('show');
    }

    // Close dropdown when clicking outside
    window.onclick = function(event) {
        if (!event.target.closest('.user-profile-container')) {
            const dropdown = document.getElementById('logoutDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    }

    function deleteActivity(activityId, activityName) {
        if (!confirm('Are you sure you want to delete "' + activityName + '"? This will also remove all its beneficiaries. This action cannot be undone.')) {
            return;
        }

        const formData = new FormData();
        formData.append('ajax_delete_activity', '1');
        formData.append('activity_id', activityId);

        fetch('activities.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(err => console.error(err));
    }
</script>
</body>
</html>















