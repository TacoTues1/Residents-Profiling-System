<?php
include('db.php');
session_start();

$allowed_roles = ['Captain', 'Barangay Captain', 'Admin', 'Secretary'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
    header("Location: login.php");
    exit();
}

$query = "SELECT a.*, 
          (SELECT COUNT(*) FROM activity_participants ap WHERE ap.activity_id = a.id) as beneficiary_count 
          FROM activities a WHERE COALESCE(a.is_archived, 0) = 1 ORDER BY a.archived_at DESC, a.id DESC";
$result = mysqli_query($conn, $query);

$user_role = $_SESSION['role'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Activities - Profiling System</title>
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
        
        .main-container { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .top-header { background: #ffffff; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; position: relative; }
        
        .user-profile-container { position: relative; }
        .user-pill { display: flex; align-items: center; background: #f8fafc; padding: 8px 15px; border-radius: 50px; border: 1px solid #e2e8f0; cursor: pointer; }
        .avatar { background: var(--accent-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .logout-dropdown { position: absolute; top: 110%; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 12px; width: 220px; display: none; z-index: 100; overflow: hidden; }
        .logout-dropdown.show { display: block; }
        .dropdown-header { padding: 15px; text-align: center; border-bottom: 1px solid #e5e7eb; color: #64748b; font-size: 14px; }
        .dropdown-header b { display: block; color: #1e293b; margin-top: 4px; font-size: 16px; }
        .logout-btn { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 20px; color: #ef4444; text-decoration: none; font-weight: 600; font-size: 16px; }
        .content-body { padding: 16px 20px 20px; }
        .panel { background: white; border: 1px solid #e5e7eb; padding: 18px; border-radius: 20px; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 18px 15px; border-bottom: 2px solid #e5e7eb; color: var(--text-gray); font-size: 12px; text- letter-spacing: 0.05em; }
        td { padding: 18px 15px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #334155; }
        .badge { padding: 4px 10px; border-radius: 16px; font-size: 12px; font-weight: 600; background: #f1f5f9; color: var(--text-gray); display: inline-flex; align-items: center; gap: 5px; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-muted { background: #e2e8f0; color: #334155; }   
        .action-link { text-decoration: none; font-weight: 600; font-size: 13px; margin-right: 15px; display: inline-flex; align-items: center; gap: 4px; }
        .link-blue { color: var(--accent-blue); }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 24px; width: 600px; max-width: 90%; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.1); transform: scale(0.95); transition: transform 0.2s ease-out; }
        .modal-overlay.show .modal-content { transform: scale(1); }
        .modal-header { padding: 24px 32px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .modal-header h3 { margin: 0; color: #1e293b; font-size: 20px; font-weight: 700; }
        .modal-close { background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.2s; }
        .modal-close:hover { background: #e2e8f0; color: #1e293b; }
        .modal-body { padding: 32px; overflow-y: auto; flex-grow: 1; }
        
        .modal-table { width: 100%; border-collapse: collapse; }
        .modal-table th { text-align: left; padding: 12px; border-bottom: 2px solid #e5e7eb; color: var(--text-gray); font-size: 12px; }
        .modal-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .status-badge { background: #e8f5e9; color: #2e7d32; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .pending { background: #fee2e2; color: #991b1b; }
        .modal-empty { text-align: center; color: var(--text-gray); padding: 16px; font-size: 14px; }
    </style>
</head>
<body>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <header class="top-header">
        <div>
            <h2 style="margin:0; color: #1e293b;">Archived Activities</h2>
            <p style="margin:0; color: var(--text-gray);">Completed activities moved from the active list</p>
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
            <table>
                <thead>
                    <tr>
                        <th>Activity Details</th>
                        <th>Date</th>
                        <th>Archived On</th>
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
                                <span class="badge badge-muted">
                                    <i class="fa-solid fa-box-archive"></i>
                                    <?php echo !empty($row['archived_at']) ? date('M d, Y', strtotime($row['archived_at'])) : '---'; ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge <?php echo ($row['beneficiary_count'] > 0) ? 'badge-success' : ''; ?>">
                                    <i class="fa-solid fa-people-group"></i> <?php echo $row['beneficiary_count']; ?> Given
                                </span>
                            </td>
                            
                            <td>
                                <a href="#" class="action-link link-blue" onclick="event.stopPropagation(); openActivityModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['activity_name'] ?? $row['title'] ?? 'N/A')); ?>');">
                                    <i class="fa-solid fa-eye"></i> View Members
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 50px; color: var(--text-gray);">No archived activities found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

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
    function openActivityModal(id, title) {
        const modal = document.getElementById('activityModal');
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);
        document.getElementById('modalActivityTitle').textContent = 'Tracking for: ' + title;
        fetchParticipants(id);
    }

    function closeActivityModal() {
        const modal = document.getElementById('activityModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 200);
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
                    let html = '<table class="modal-table"><thead><tr><th>Household No.</th><th>Household Members</th><th>Status</th></tr></thead><tbody>';
                    data.forEach(p => {
                        const statusClass = p.status === 'Received' ? 'status-badge' : 'status-badge pending';
                        const statusText = p.status === 'Received' ? 'RECEIVED' : 'PENDING';
                        
                        html += `<tr>
                            <td><strong>Household #${p.household_no || 'N/A'}</strong></td>
                            <td><small style="color: #64748b;">${p.members}</small></td>
                            <td><span class="${statusClass}">${statusText}</span></td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    modalBody.innerHTML = html;
                } else {
                    let html = '<table class="modal-table"><thead><tr><th>Resident</th><th>Household No.</th><th>Status</th></tr></thead><tbody>';
                    data.forEach(p => {
                        const fullName = `${p.last_name}, ${p.first_name} ${p.middle_name || ''}`.trim();
                        const statusClass = p.status === 'Received' ? 'status-badge' : 'status-badge pending';
                        const statusText = p.status === 'Received' ? 'RECEIVED' : 'PENDING';
                        
                        html += `<tr>
                            <td><strong>${fullName}</strong></td>
                            <td>${p.household_no || 'N/A'}</td>
                            <td><span class="${statusClass}">${statusText}</span></td>
                        </tr>`;
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
