<?php 
include('db.php');
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'Barangay Captain') {
    header("Location: login.php");
    exit();
}

// Fetch all households entered by Secretary
$query = "SELECT h.household_no, h.address, h.purok,
          (SELECT CONCAT(first_name, ' ', last_name) FROM residents r2 WHERE r2.household_no = h.household_no AND r2.relationship = 'Head' AND COALESCE(r2.is_archived, 0) = 0 LIMIT 1) as head_name,
          COUNT(r1.id) as member_count
          FROM households h
          LEFT JOIN residents r1 ON r1.household_no = h.household_no AND COALESCE(r1.is_archived, 0) = 0
          GROUP BY h.household_no, h.address, h.purok
          ORDER BY h.id DESC";
$result = mysqli_query($conn, $query);

// Fetch members mapping for instant modal viewing
$all_members_query = mysqli_query($conn, "SELECT household_no, first_name, last_name, relationship, age, gender FROM residents WHERE COALESCE(is_archived, 0) = 0 ORDER BY relationship = 'Head' DESC, id ASC");
$hh_members_map = [];
if ($all_members_query) {
    while ($mem = mysqli_fetch_assoc($all_members_query)) {
        $hh_no = $mem['household_no'];
        if (!isset($hh_members_map[$hh_no])) {
            $hh_members_map[$hh_no] = [];
        }
        $hh_members_map[$hh_no][] = $mem;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Household Records | Captain</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --accent-blue: #2563eb; --light-bg: #f1f5f9; }
        body { font-family: 'Inter', sans-serif; margin: 0; display: flex; background: var(--light-bg); height: 100vh; overflow: hidden; }
        
        /* Main Content */
        .main-container { flex: 1; overflow-y: auto; display: flex; flex-direction: column; box-sizing: border-box; width: 100%; }
        .header { background: white; padding: 15px 25px; border-radius: 16px; display: flex; justify-content: space-between; align-items: center; margin: 25px 30px; box-sizing: border-box; }
        .content-body { padding: 0 30px 30px 30px; box-sizing: border-box; width: 100%; }

        .content-card { background: white; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; overflow-x: auto; width: 100%; box-sizing: border-box; }
        .table-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
        .filter-group { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .search-bar { background: #f1f5f9; border: none; padding: 10px 20px; border-radius: 16px; width: 300px; max-width: 100%; box-sizing: border-box; font-size: 14px; outline: none; }
        .category-select { padding: 10px 16px; border: 1px solid #e2e8f0; border-radius: 16px; font-size: 14px; outline: none; background: white; color: #334155; cursor: pointer; max-width: 100%; box-sizing: border-box; }
        
        table { width: 100%; border-collapse: collapse; min-width: 650px; }
        th { text-align: left; padding: 15px; color: #64748b; font-size: 14px; border-bottom: 1px solid #e5e7eb; }
        td { padding: 15px; border-bottom: 1px solid #e5e7eb; font-size: 15px; font-weight: 500; }
        
        .status-active { background: #22c55e; color: white; padding: 5px 15px; border-radius: 16px; font-size: 12px; }
        .view-btn { color: #64748b; cursor: pointer; font-size: 18px; }

        @media (max-width: 1024px) {
            .table-controls { flex-direction: column; align-items: stretch; }
            .filter-group { flex-direction: column; align-items: stretch; width: 100%; }
            .search-bar, .category-select { width: 100% !important; }
            .header { margin: 16px !important; padding: 14px 20px !important; }
            .content-body { padding: 0 16px 16px 16px !important; }
        }

        @media (max-width: 768px) {
            .header { margin: 12px !important; padding: 12px 16px !important; flex-direction: column; gap: 12px; align-items: flex-start; }
            .content-body { padding: 0 12px 12px 12px !important; }
            .content-card { padding: 16px; }
        }
    </style>
</head>
<body>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <header class="header">
        <div>
            <h2 style="margin:0;">Household List</h2>
            <p style="color:#64748b; margin:0; font-size:14px;">View and manage household records</p>
        </div>
    </header>

    <div class="content-body">
        <div class="content-card">
            <div class="table-controls">
                <h3>Household Records</h3>
                <div class="filter-group">
                    <input type="text" id="householdSearch" class="search-bar" placeholder="Search household no, address, purok, or head...">
                    <select id="householdPurokFilter" class="category-select">
                        <option value="All">All Puroks</option>
                        <?php 
                        $purok_query = mysqli_query($conn, "SELECT DISTINCT COALESCE(NULLIF(TRIM(purok), ''), 'Unspecified') AS purok_name FROM households ORDER BY purok_name");
                        if ($purok_query) {
                            while ($p_row = mysqli_fetch_assoc($purok_query)) {
                                echo '<option value="' . htmlspecialchars($p_row['purok_name']) . '">' . htmlspecialchars($p_row['purok_name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <table id="householdsTable">
                <thead>
                    <tr>
                        <th>Household No.</th>
                        <th>Address</th>
                        <th>Purok</th>
                        <th>Head of Family</th>
                        <th>Members</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $has_rows = $result && mysqli_num_rows($result) > 0; ?>
                    <?php if ($has_rows): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="data-row" style="cursor:pointer;" onclick='openHouseholdModal(<?php echo json_encode($row); ?>, <?php echo json_encode($hh_members_map[$row['household_no']] ?? []); ?>)'>
                            <td><strong><?php echo htmlspecialchars($row['household_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td><?php echo htmlspecialchars($row['purok']); ?></td>
                            <td><?php echo htmlspecialchars($row['head_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['member_count']); ?></td>
                            <td><span class="view-btn"><i class="fa-regular fa-eye"></i></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    <tr id="householdNoResults" style="<?php echo $has_rows ? 'display: none;' : ''; ?>">
                        <td colspan="6" style="text-align:center; padding: 40px; color: #64748b;">No households found matching your criteria.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('householdSearch');
        const purokFilter = document.getElementById('householdPurokFilter');
        if (!searchInput || !purokFilter) return;

        const rows = Array.from(document.querySelectorAll('#householdsTable tbody tr.data-row'));
        const emptyRow = document.getElementById('householdNoResults');

        const filterRows = () => {
            const term = searchInput.value.trim().toLowerCase();
            const selectedPurok = purokFilter.value.trim();
            let visible = 0;

            rows.forEach((row) => {
                const hay = row.textContent.toLowerCase();
                const matchSearch = hay.includes(term);
                
                const purokCell = row.cells[2].textContent.trim();
                const matchPurok = selectedPurok === 'All' || purokCell === selectedPurok || (selectedPurok === 'Unspecified' && purokCell === '');

                if (matchSearch && matchPurok) {
                    row.style.display = '';
                    visible += 1;
                } else {
                    row.style.display = 'none';
                }
            });

            if (emptyRow) {
                emptyRow.style.display = visible === 0 ? '' : 'none';
            }
        };

        searchInput.addEventListener('input', filterRows);
        purokFilter.addEventListener('change', filterRows);
    });

    function openHouseholdModal(hh, members) {
        document.getElementById('modalHHNo').innerText = hh.household_no;
        document.getElementById('modalHHAddress').innerText = hh.address;
        document.getElementById('modalHHPurok').innerText = hh.purok;

        let rows = members.map(m => `
            <tr style="border-bottom:1px solid #e2e8f0;">
                <td style="padding:12px; font-weight:600; font-size:14px;">${m.first_name} ${m.last_name}</td>
                <td style="padding:12px; font-size:14px;">
                    <span class="status-pill" style="background:${m.relationship === 'Head' ? '#dbeafe' : '#f1f5f9'}; color:${m.relationship === 'Head' ? '#1d4ed8' : '#475569'}; padding:4px 10px; border-radius:12px; font-weight:600; font-size:12px;">${m.relationship}</span>
                </td>
                <td style="padding:12px; font-size:14px;">${m.age}</td>
                <td style="padding:12px; font-size:14px;">${m.gender}</td>
            </tr>
        `);

        document.getElementById('modalHHMembersList').innerHTML = rows.length ? rows.join('') : '<tr><td colspan="4" style="text-align:center; padding:20px; color:#64748b;">No members registered yet.</td></tr>';
        document.getElementById('householdDetailModal').style.display = 'flex';
    }
    function closeHouseholdModal() {
        document.getElementById('householdDetailModal').style.display = 'none';
    }
</script>

<div id="householdDetailModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:3000; justify-content:center; align-items:center; backdrop-filter:blur(4px);">
    <div class="panel modal-container" style="background:white; width:700px; max-width:90%; border-radius:24px; padding:32px; max-height:85vh; overflow-y:auto; position:relative;">
        <button onclick="closeHouseholdModal()" style="position:absolute; top:24px; right:24px; background:none; border:none; font-size:24px; cursor:pointer; color:#64748b;"><i class="fa-solid fa-xmark"></i></button>
        <div style="margin-bottom:24px; border-bottom:1px solid #e2e8f0; padding-bottom:20px;">
            <h2 style="margin:0 0 4px 0; font-size:22px; color:inherit;">Household #<span id="modalHHNo"></span></h2>
            <p style="margin:0; color:#64748b; font-size:14px;"><i class="fa-solid fa-location-dot" style="color:#2563eb; margin-right:6px;"></i> <span id="modalHHAddress"></span> (Purok <span id="modalHHPurok"></span>)</p>
        </div>
        <h3 style="font-size:16px; margin:0 0 16px 0; color:inherit;">Household Members</h3>
        <table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
            <thead>
                <tr class="modal-header">
                    <th style="padding:12px; text-align:left; font-size:13px; color:#64748b; border:none;">Name</th>
                    <th style="padding:12px; text-align:left; font-size:13px; color:#64748b; border:none;">Relationship</th>
                    <th style="padding:12px; text-align:left; font-size:13px; color:#64748b; border:none;">Age</th>
                    <th style="padding:12px; text-align:left; font-size:13px; color:#64748b; border:none;">Gender</th>
                </tr>
            </thead>
            <tbody id="modalHHMembersList"></tbody>
        </table>
    </div>
</div>

</body>
</html>














