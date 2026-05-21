<?php
include('db.php');
session_start();

// Strict Role Access
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'Barangay Captain') {
    header("Location: login.php");
    exit();
}

$where = ["COALESCE(is_archived, 0) = 0"];

// --- SEARCH LOGIC ---
if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where[] = "(first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR middle_name LIKE '%$search%' OR CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE '%$search%' OR household_no LIKE '%$search%')";
}

// Category Filtering
if (!empty($_GET['category']) && $_GET['category'] !== 'All') {
    $cat = mysqli_real_escape_string($conn, $_GET['category']);
    if ($cat == 'Senior Citizen') $where[] = "is_senior = 1";
    elseif ($cat == 'Minor')      $where[] = "is_minor = 1";
    elseif ($cat == 'Voters')     $where[] = "is_voter = 1";
    elseif ($cat == 'Solo Parent')$where[] = "is_solo = 1";
    elseif ($cat == '4ps')        $where[] = "is_4ps = 1";
    elseif ($cat == 'PWD')        $where[] = "is_pwd = 1";
}

$where_sql = implode(" AND ", $where);

$query = "SELECT *
          FROM residents
          WHERE $where_sql
          ORDER BY id DESC";

$result = mysqli_query($conn, $query);

// Fetch all activity participants and group by resident_id
$act_query = mysqli_query($conn, "SELECT ap.resident_id, a.activity_name FROM activity_participants ap JOIN activities a ON ap.activity_id = a.id");
$resident_activities = [];
if ($act_query) {
    while ($row_act = mysqli_fetch_assoc($act_query)) {
        $resident_activities[$row_act['resident_id']][] = $row_act['activity_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents List - Profiling System</title>
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

        .main-container { flex: 1; overflow-y: auto; display: flex; flex-direction: column; box-sizing: border-box; width: 100%; }

        .panel { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); overflow-x: auto; width: 100%; box-sizing: border-box; }
        .controls { display: flex; justify-content: space-between; margin-bottom: 25px; align-items: center; gap: 16px; flex-wrap: wrap; }
        .filter-form { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .search-input, .category-select { padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; max-width: 100%; box-sizing: border-box; }
        .search-input:focus { border-color: var(--accent-blue); }

        table { width: 100%; border-collapse: collapse; min-width: 650px; }
        th { text-align: left; padding: 14px 12px; border-bottom: 2px solid #e2e8f0; font-size: 12px; color: var(--text-gray); letter-spacing: 0.5px; }
        td { padding: 14px 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #334155; }

        .status-pill { padding: 4px 10px; border-radius: 6px; background: #e8f5e9; color: #2e7d32; font-size: 12px; font-weight: 600; }
        .status-archived { background: #fee2e2; color: #991b1b; }

        .action-link { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; text-decoration: none; color: var(--accent-blue); background: #eff6ff; }

        .activity-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        @media (max-width: 1024px) {
            .controls { flex-direction: column; align-items: stretch; }
            .filter-form { flex-direction: column; align-items: stretch; width: 100%; }
            .search-input, .category-select { width: 100% !important; box-sizing: border-box; }
        }
    </style>
</head>
<body>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <header class="header">
        <div>
            <h2 style="margin:0;">Residents List</h2>
            <p style="color:#64748b; margin:0; font-size:14px;">View resident records</p>
        </div>
    </header>

    <div class="content-body">
        <div class="panel">
            <div class="controls">
                <form method="GET" class="filter-form">
                    <input type="text" id="residentSearch" name="search" class="search-input" placeholder="Search resident name or household no..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">

                    <select name="category" class="category-select" onchange="this.form.submit()">
                        <option value="All">All Categories</option>
                        <option value="Senior Citizen" <?php echo ($_GET['category'] ?? '') == 'Senior Citizen' ? 'selected' : ''; ?>>Senior Citizen</option>
                        <option value="Minor" <?php echo ($_GET['category'] ?? '') == 'Minor' ? 'selected' : ''; ?>>Minor</option>
                        <option value="Voters" <?php echo ($_GET['category'] ?? '') == 'Voters' ? 'selected' : ''; ?>>Voters</option>
                        <option value="Solo Parent" <?php echo ($_GET['category'] ?? '') == 'Solo Parent' ? 'selected' : ''; ?>>Solo Parent</option>
                        <option value="4ps" <?php echo ($_GET['category'] ?? '') == '4ps' ? 'selected' : ''; ?>>4ps</option>
                        <option value="PWD" <?php echo ($_GET['category'] ?? '') == 'PWD' ? 'selected' : ''; ?>>PWD</option>
                    </select>
                </form>
            </div>

            <table id="residentsTable">
                <thead>
                    <tr>
                        <th>Resident Name</th>
                        <th>Household No.</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Relationship</th>
                        <th>Activities</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $has_rows = $result && mysqli_num_rows($result) > 0; ?>
                    <?php if($has_rows): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <?php 
                            $activities = $resident_activities[$row['id']] ?? []; 
                            $row['included_activities'] = $activities;
                        ?>
                        <tr class="data-row" style="cursor: pointer;" onclick='openResidentModal(<?php echo json_encode($row); ?>)'>
                            <td><strong><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name'] . ($row['middle_name'] ? ' ' . $row['middle_name'] : '')); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['household_no'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['age'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['gender'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['relationship'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if (!empty($activities)): ?>
                                    <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                        <?php foreach ($activities as $act): ?>
                                            <span class="activity-badge"><?php echo htmlspecialchars($act); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-gray); font-size: 13px;">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill <?php echo ($row['status'] ?? 'Active') !== 'Active' ? 'status-archived' : ''; ?>">
                                    <?php echo htmlspecialchars($row['status'] ?? 'Active'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    <tr id="residentNoResults" style="<?php echo $has_rows ? 'display: none;' : ''; ?>">
                        <td colspan="7" style="text-align:center; padding: 40px; color: var(--text-gray);">No residents found matching your criteria.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // CONSISTENT SIDEBAR LOGIC
    document.addEventListener("DOMContentLoaded", function() {
        setupResidentSearch();
    });

    // CONSISTENT LOGOUT DROPDOWN
    function toggleLogout() {
        document.getElementById('logoutDropdown').classList.toggle('show');
    }

    window.onclick = function(e) {
        if (!e.target.closest('.user-profile-container')) {
            const dropdown = document.getElementById('logoutDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    }

    function setupResidentSearch() {
        const searchInput = document.getElementById('residentSearch');
        if (!searchInput) return;

        const rows = Array.from(document.querySelectorAll('#residentsTable tbody tr.data-row'));
        const emptyRow = document.getElementById('residentNoResults');

        const filterRows = () => {
            const term = searchInput.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach((row) => {
                const hay = row.textContent.toLowerCase();
                const match = hay.includes(term);
                row.style.display = match ? '' : 'none';
                if (match) visible += 1;
            });

            if (emptyRow) {
                emptyRow.style.display = visible === 0 ? '' : 'none';
            }
        };

        searchInput.addEventListener('input', filterRows);
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });

        filterRows();
    }

    function openResidentModal(data) {
        document.getElementById('modalResPhoto').src = data.photo_path || 'uploads/default.png';
        document.getElementById('modalResName').innerText = `${data.first_name} ${data.middle_name ? data.middle_name + ' ' : ''}${data.last_name}`;
        document.getElementById('modalResHH').innerText = `Household No: #${data.household_no || 'N/A'}`;
        document.getElementById('modalResAgeSex').innerText = `${data.age} yrs old • ${data.gender}`;
        document.getElementById('modalResDob').innerText = data.dob || 'N/A';
        document.getElementById('modalResCivil').innerText = data.civil_status || 'N/A';
        document.getElementById('modalResRel').innerText = data.relationship || 'N/A';
        document.getElementById('modalResEdu').innerText = data.education || 'N/A';
        document.getElementById('modalResEmp').innerText = data.employment_status || 'N/A';

        let badges = [];
        if(parseInt(data.is_voter)) badges.push('<span class="status-pill" style="background:#e0e7ff; color:#4f46e5;">Voter</span>');
        if(parseInt(data.is_senior)) badges.push('<span class="status-pill" style="background:#fef3c7; color:#d97706;">Senior Citizen</span>');
        if(parseInt(data.is_minor)) badges.push('<span class="status-pill" style="background:#ecfdf5; color:#059669;">Minor</span>');
        if(parseInt(data.is_pwd)) badges.push('<span class="status-pill" style="background:#fee2e2; color:#dc2626;">PWD</span>');
        if(parseInt(data.is_solo)) badges.push('<span class="status-pill" style="background:#f3e8ff; color:#9333ea;">Solo Parent</span>');
        if(parseInt(data.is_4ps)) badges.push('<span class="status-pill" style="background:#ffedd5; color:#ea580c;">4Ps</span>');

        document.getElementById('modalResBadges').innerHTML = badges.length ? badges.join('') : '<span style="color:#64748b; font-size:14px;">None</span>';

        let actBadges = (data.included_activities || []).map(act => `<span class="activity-badge" style="background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:600;">${act}</span>`);
        document.getElementById('modalResActivities').innerHTML = actBadges.length ? actBadges.join(' ') : '<span style="color:#64748b; font-size:14px;">None</span>';

        document.getElementById('residentDetailModal').style.display = 'flex';
    }
    function closeResidentModal() {
        document.getElementById('residentDetailModal').style.display = 'none';
    }
</script>

<div id="residentDetailModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:3000; justify-content:center; align-items:center; backdrop-filter:blur(4px);">
    <div class="panel modal-container" style="background:white; width:650px; max-width:90%; border-radius:24px; padding:32px; max-height:85vh; overflow-y:auto; position:relative;">
        <button onclick="closeResidentModal()" style="position:absolute; top:24px; right:24px; background:none; border:none; font-size:24px; cursor:pointer; color:#64748b;"><i class="fa-solid fa-xmark"></i></button>
        <div style="display:flex; align-items:center; gap:20px; margin-bottom:24px; border-bottom:1px solid #e2e8f0; padding-bottom:20px;">
            <img id="modalResPhoto" src="uploads/default.png" style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:2px solid var(--accent-blue);">
            <div>
                <h2 id="modalResName" style="margin:0 0 4px 0; font-size:22px; color:inherit;"></h2>
                <p id="modalResHH" style="margin:0; color:#64748b; font-size:14px;"></p>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
            <div><label style="font-size:12px; color:#64748b;">Age & Sex</label><div id="modalResAgeSex" style="font-weight:600; font-size:15px; color:inherit;"></div></div>
            <div><label style="font-size:12px; color:#64748b;">Birth Date</label><div id="modalResDob" style="font-weight:600; font-size:15px; color:inherit;"></div></div>
            <div><label style="font-size:12px; color:#64748b;">Civil Status</label><div id="modalResCivil" style="font-weight:600; font-size:15px; color:inherit;"></div></div>
            <div><label style="font-size:12px; color:#64748b;">Relationship</label><div id="modalResRel" style="font-weight:600; font-size:15px; color:inherit;"></div></div>
            <div><label style="font-size:12px; color:#64748b;">Education</label><div id="modalResEdu" style="font-weight:600; font-size:15px; color:inherit;"></div></div>
            <div><label style="font-size:12px; color:#64748b;">Employment</label><div id="modalResEmp" style="font-weight:600; font-size:15px; color:inherit;"></div></div>
        </div>
        <div>
            <label style="font-size:12px; color:#64748b; margin-bottom:8px; display:block;">Categories & Sectors</label>
            <div id="modalResBadges" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px;"></div>
        </div>
        <div>
            <label style="font-size:12px; color:#64748b; margin-bottom:8px; display:block;">Included Activities</label>
            <div id="modalResActivities" style="display:flex; gap:8px; flex-wrap:wrap;"></div>
        </div>
    </div>
</div>

</body>
</html>
















