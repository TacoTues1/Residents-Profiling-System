<?php 
include('db.php');
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'Barangay Captain') {
    header("Location: login.php");
    exit();
}

// Analytics Queries
$hh_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM households"))['c'] ?? 0;
$res_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM residents WHERE COALESCE(is_archived, 0) = 0"))['c'] ?? 0;
$voters_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM residents WHERE COALESCE(is_archived, 0) = 0 AND COALESCE(is_voter, 0) = 1"))['c'] ?? 0;
$senior_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM residents WHERE COALESCE(is_archived, 0) = 0 AND age >= 60"))['c'] ?? 0;
$pwd_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM residents WHERE COALESCE(is_archived, 0) = 0 AND COALESCE(is_pwd, 0) = 1"))['c'] ?? 0;
$fps_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM residents WHERE COALESCE(is_archived, 0) = 0 AND COALESCE(is_4ps, 0) = 1"))['c'] ?? 0;
$solo_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM residents WHERE COALESCE(is_archived, 0) = 0 AND COALESCE(is_solo, 0) = 1"))['c'] ?? 0;
$minor_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM residents WHERE COALESCE(is_archived, 0) = 0 AND age <= 17"))['c'] ?? 0;

// Demographic breakdown by Purok
$purok_report_query = mysqli_query($conn, "
    SELECT 
        COALESCE(NULLIF(TRIM(h.purok), ''), 'Unspecified') AS purok_name,
        COUNT(DISTINCT h.id) as total_hh,
        COUNT(r.id) as total_res,
        SUM(CASE WHEN r.gender = 'Male' THEN 1 ELSE 0 END) as male_count,
        SUM(CASE WHEN r.gender = 'Female' THEN 1 ELSE 0 END) as female_count,
        SUM(CASE WHEN r.is_voter = 1 THEN 1 ELSE 0 END) as voter_count
    FROM households h
    LEFT JOIN residents r ON r.household_no = h.household_no AND COALESCE(r.is_archived, 0) = 0
    GROUP BY purok_name
    ORDER BY purok_name ASC
");

// Residents List Query
$residents_query = mysqli_query($conn, "
    SELECT r.*, COALESCE(NULLIF(TRIM(h.purok), ''), 'Unspecified') as purok_name 
    FROM residents r 
    LEFT JOIN households h ON r.household_no = h.household_no 
    WHERE COALESCE(r.is_archived, 0) = 0 
    ORDER BY r.last_name ASC, r.first_name ASC
");

// Households List Query
$households_query = mysqli_query($conn, "
    SELECT h.*, 
        (SELECT CONCAT(last_name, ', ', first_name) FROM residents r WHERE r.household_no = h.household_no AND relationship = 'head' AND COALESCE(r.is_archived, 0) = 0 LIMIT 1) as head_name,
        (SELECT COUNT(*) FROM residents r WHERE r.household_no = h.household_no AND COALESCE(r.is_archived, 0) = 0) as member_count
    FROM households h 
    ORDER BY CAST(h.household_no AS UNSIGNED) ASC, h.household_no ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Captain Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <style>
        :root { --sidebar-navy: #1e293b; --accent-blue: #2563eb; --logo-orange: #ff9800; }
        body { font-family: 'Inter', sans-serif; margin: 0; display: flex; background: #f1f5f9; height: 100vh; overflow: hidden; }
        
        .main-container { flex: 1; overflow-y: auto; display: flex; flex-direction: column; box-sizing: border-box; width: 100%; }
        .header { background: white; padding: 15px 25px; border-radius: 16px; display: flex; justify-content: space-between; align-items: center; margin: 25px 30px; box-sizing: border-box; }
        .content-body { padding: 0 30px 30px 30px; box-sizing: border-box; width: 100%; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 20px; border-radius: 20px; border: 1px solid #e2e8f0; position: relative; }
        .stat-card p { font-size: 13px; font-weight: 600; color: #64748b; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card h2 { font-size: 28px; margin: 8px 0 0 0; color: #1e293b; }
        .stat-card i { position: absolute; top: 24px; right: 24px; font-size: 24px; color: #cbd5e1; }
        
        .report-panel { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 28px; margin-bottom: 24px; overflow-x: auto; width: 100%; box-sizing: border-box; }
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .report-header h3 { margin: 0; font-size: 18px; color: #1e293b; }
        
        .btn-action { padding: 10px 20px; border-radius: 14px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: none; }
        .btn-print { background: var(--accent-blue); color: white; }
        .btn-print:hover { background: #1d4ed8; }
        
        .report-tabs { display: flex; gap: 8px; flex-wrap: wrap; background: #f8fafc; padding: 6px; border-radius: 16px; border: 1px solid #e2e8f0; }
        .report-tab {
            padding: 10px 18px; border-radius: 12px; border: none; background: transparent; color: #64748b;
            font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease;
        }
        .report-tab:hover { color: #1e293b; background: #f1f5f9; }
        .report-tab.active { background: var(--accent-blue); color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        
        .btn-print {
            background: #10b981; color: white; padding: 12px 24px; border-radius: 14px; border: none;
            font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 10px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); transition: all 0.2s ease;
        }
        .btn-print:hover { background: #059669; transform: translateY(-1px); }
        
        table { width: 100%; border-collapse: collapse; min-width: 650px; }
        th { text-align: left; padding: 16px; color: #64748b; font-size: 13px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 16px; border-bottom: 1px solid #e2e8f0; font-size: 15px; color: #334155; }
        
        .hh-group-header { background: #f8fafc; border-left: 4px solid var(--accent-blue); padding: 16px 20px; margin-top: 24px; margin-bottom: 12px; border-radius: 0 12px 12px 0; }
        .hh-group-header h4 { margin: 0; font-size: 16px; color: #1e293b; }
        
        /* Dynamic Report Visibility */
        .report-section { display: none; }
        .report-section.active { display: block; }
        
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 1024px) {
            .top-header { margin: 16px !important; padding: 14px 20px !important; }
            .content-body { padding: 0 16px 16px 16px !important; }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .top-header { margin: 12px !important; padding: 12px 16px !important; flex-direction: column; gap: 12px; align-items: flex-start; }
            .content-body { padding: 0 12px 12px 12px !important; }
            .report-tabs { flex-direction: column; width: 100%; }
            .report-tab { justify-content: center; width: 100%; }
            .btn-print { width: 100%; justify-content: center; }
            .report-panel { padding: 16px; }
        }

        .print-header { display: none; }
        
        @media print {
            .sidebar, .top-header, .theme-toggle-btn { display: none !important; }
            body { background: white !important; overflow: visible !important; height: auto !important; }
            .main-container { padding: 0 !important; overflow: visible !important; width: 100% !important; margin: 0 !important; }
            .content-body { padding: 0 !important; }
            
            .print-header { display: flex !important; align-items: center; margin-bottom: 24px; border-bottom: 2px solid #000; padding-bottom: 16px; min-height: 80px; gap: 16px; }
            .print-header-logo { flex-shrink: 0; width: 70px; height: 70px; }
            .print-header-logo img { width: 100%; height: 100%; object-fit: contain; }
            .print-header-text { flex: 1; text-align: center; padding-right: 86px; box-sizing: border-box; }
            .print-header-text h1 { font-size: 24px; margin: 0 0 4px 0; color: #000; font-weight: 700; }
            .print-header-text p { font-size: 13px; color: #000; margin: 0; }
            
            .stats-grid { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; margin-bottom: 20px !important; }
            .stat-card { border: 1px solid #cbd5e1 !important; padding: 12px !important; border-radius: 10px !important; box-shadow: none !important; page-break-inside: avoid; }
            .stat-card h2 { font-size: 18px !important; margin-top: 4px !important; }
            .stat-card p { font-size: 10px !important; }
            .stat-card i { display: none !important; }
            .report-panel { border: none !important; padding: 0 !important; margin: 0 !important; box-shadow: none !important; }
            
            table { border-collapse: collapse !important; width: 100% !important; }
            th, td { border: 1px solid #000 !important; padding: 6px 8px !important; font-size: 10px !important; color: #000 !important; }
            th { background-color: #000 !important; color: #fff !important; font-weight: 700 !important; }
            .report-section { display: none !important; }
            .report-section.active { display: block !important; }
            .hh-group-header { background: #e5e5e5 !important; border-left: 4px solid #000 !important; padding: 6px 10px !important; margin-top: 12px !important; margin-bottom: 6px !important; page-break-after: avoid; font-size: 12px !important; color: #000 !important; }
        }
    </style>
</head>
<body>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <header class="top-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap; flex: 1;">
            <div>
                <h2 style="margin:0;">Barangay Reports</h2>
                <p style="color:#64748b; margin:0; font-size:14px;">Comprehensive sector analytics & summary reports</p>
            </div>
            <div class="report-tabs">
                <button class="report-tab active" onclick="switchReport('all', this, 'Official Barangay Overview & Summary Analytics')"><i class="fa-solid fa-chart-pie"></i> Overview</button>
                <button class="report-tab" onclick="switchReport('residents', this, 'Official Residents List Report')"><i class="fa-solid fa-users"></i> Residents List</button>
                <button class="report-tab" onclick="switchReport('households', this, 'Official Household List Report')"><i class="fa-solid fa-house-chimney"></i> Household List</button>
                <button class="report-tab" onclick="switchReport('hh-members', this, 'Official Households & Resident Members Report')"><i class="fa-solid fa-people-roof"></i> Households & Members</button>
            </div>
        </div>
        <button onclick="downloadDirectPDF()" class="btn-print"><i class="fa-solid fa-file-pdf"></i> Download PDF</button>
    </header>

    <div class="content-body">
        <div class="print-header">
            <div class="print-header-logo">
                <img src="logo/logopulantubig.png" alt="Barangay Logo">
            </div>
            <div class="print-header-text">
                <h1>Barangay Pulantubig</h1>
                <p id="printSubtitle">Official Barangay Overview & Summary Analytics</p>
            </div>
        </div>

        <!-- 1. ALL REPORTS (OVERVIEW SUMMARY) -->
        <div id="report-all" class="report-section active">
            <div class="stats-grid">
                <div class="stat-card">
                    <p>Total Households</p>
                    <h2><?php echo number_format($hh_count); ?></h2>
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <div class="stat-card">
                    <p>Total Residents</p>
                    <h2><?php echo number_format($res_count); ?></h2>
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-card">
                    <p>Total Registered Voters</p>
                    <h2><?php echo number_format($voters_count); ?></h2>
                    <i class="fa-solid fa-check-to-slot"></i>
                </div>
                <div class="stat-card">
                    <p>Senior Citizens</p>
                    <h2><?php echo number_format($senior_count); ?></h2>
                    <i class="fa-solid fa-person-cane"></i>
                </div>
                <div class="stat-card">
                    <p>PWD Beneficiaries</p>
                    <h2><?php echo number_format($pwd_count); ?></h2>
                    <i class="fa-solid fa-wheelchair"></i>
                </div>
                <div class="stat-card">
                    <p>4Ps Beneficiaries</p>
                    <h2><?php echo number_format($fps_count); ?></h2>
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <div class="stat-card">
                    <p>Solo Parents</p>
                    <h2><?php echo number_format($solo_count); ?></h2>
                    <i class="fa-solid fa-person-breastfeeding"></i>
                </div>
                <div class="stat-card">
                    <p>Minor Residents</p>
                    <h2><?php echo number_format($minor_count); ?></h2>
                    <i class="fa-solid fa-child"></i>
                </div>
            </div>

            <div class="report-panel">
                <div class="report-header">
                    <h3>Purok Demographics Breakdown</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Purok Name</th>
                            <th>Households</th>
                            <th>Total Residents</th>
                            <th>Male</th>
                            <th>Female</th>
                            <th>Registered Voters</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($purok_report_query && mysqli_num_rows($purok_report_query) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($purok_report_query)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['purok_name']); ?></strong></td>
                                <td><?php echo number_format($row['total_hh']); ?></td>
                                <td><?php echo number_format($row['total_res']); ?></td>
                                <td><?php echo number_format($row['male_count']); ?></td>
                                <td><?php echo number_format($row['female_count']); ?></td>
                                <td><?php echo number_format($row['voter_count']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">No demographics data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. RESIDENTS LIST REPORT -->
        <div id="report-residents" class="report-section">
            <div class="report-panel">
                <div class="report-header">
                    <h3>Residents List Report</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Household No.</th>
                            <th>Purok</th>
                            <th>Sex</th>
                            <th>Age</th>
                            <th>Civil Status</th>
                            <th>Classification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($residents_query && mysqli_num_rows($residents_query) > 0): ?>
                            <?php 
                            mysqli_data_seek($residents_query, 0);
                            while ($r = mysqli_fetch_assoc($residents_query)): 
                                $class_labels = [];
                                if (isset($r['is_4ps']) && $r['is_4ps'] == 1) $class_labels[] = "4Ps";
                                if (isset($r['is_pwd']) && $r['is_pwd'] == 1) $class_labels[] = "PWD";
                                if (isset($r['is_senior']) && $r['is_senior'] == 1) $class_labels[] = "Senior";
                                if (isset($r['is_solo']) && $r['is_solo'] == 1) $class_labels[] = "Solo Parent";
                                $classification = !empty($class_labels) ? implode(', ', $class_labels) : 'None';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['last_name'] . ', ' . $r['first_name'] . ($r['middle_name'] ? ' ' . $r['middle_name'] : '')); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['household_no'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($r['purok_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['gender'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($r['age'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($r['civil_status'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($classification); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; padding:30px; color:#64748b;">No residents data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. HOUSEHOLD LIST REPORT -->
        <div id="report-households" class="report-section">
            <div class="report-panel">
                <div class="report-header">
                    <h3>Household List Report</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Household No.</th>
                            <th>Address</th>
                            <th>Purok</th>
                            <th>Head of Family</th>
                            <th>Members Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($households_query && mysqli_num_rows($households_query) > 0): ?>
                            <?php 
                            mysqli_data_seek($households_query, 0);
                            while ($h = mysqli_fetch_assoc($households_query)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($h['household_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($h['address']); ?></td>
                                <td><?php echo htmlspecialchars($h['purok']); ?></td>
                                <td><?php echo htmlspecialchars($h['head_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($h['member_count']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:#64748b;">No household data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. HOUSEHOLDS WITH RESIDENT MEMBERS REPORT -->
        <div id="report-hh-members" class="report-section">
            <div class="report-panel">
                <div class="report-header" style="margin-bottom:0;">
                    <h3>Households with Resident Members Report</h3>
                </div>
                <?php if ($households_query && mysqli_num_rows($households_query) > 0): ?>
                    <?php 
                    mysqli_data_seek($households_query, 0);
                    while ($h = mysqli_fetch_assoc($households_query)): 
                        $hh_no = mysqli_real_escape_string($conn, $h['household_no']);
                        $mem_query = mysqli_query($conn, "SELECT * FROM residents WHERE household_no = '$hh_no' AND COALESCE(is_archived, 0) = 0 ORDER BY CASE WHEN relationship = 'Head' THEN 1 ELSE 2 END, id ASC");
                    ?>
                    <div class="hh-group-header">
                        <h4>Household #<?php echo htmlspecialchars($h['household_no']); ?> - Address: <?php echo htmlspecialchars($h['address'] . ', ' . $h['purok']); ?></h4>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Sex</th>
                                <th>Age</th>
                                <th>Civil Status</th>
                                <th>Employed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($mem_query && mysqli_num_rows($mem_query) > 0): ?>
                                <?php while ($m = mysqli_fetch_assoc($mem_query)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($m['last_name'] . ', ' . $m['first_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($m['relationship']); ?></td>
                                    <td><?php echo htmlspecialchars($m['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($m['age']); ?></td>
                                    <td><?php echo htmlspecialchars($m['civil_status']); ?></td>
                                    <td><?php echo (isset($m['employment_status']) && strtoupper($m['employment_status']) == 'YES') ? 'Yes' : 'No'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="color:#64748b;">No active members recorded.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center; padding:30px; color:#64748b;">No household records available.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function switchReport(reportType, tabElement, subtitleText) {
    // Hide all report sections
    const sections = document.querySelectorAll('.report-section');
    sections.forEach(sec => sec.classList.remove('active'));
    
    // Remove active class from all tabs
    const tabs = document.querySelectorAll('.report-tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Show selected section
    const activeSection = document.getElementById('report-' + reportType);
    if (activeSection) {
        activeSection.classList.add('active');
    }
    
    // Set active tab
    if (tabElement) {
        tabElement.classList.add('active');
    }

    // Update print subtitle
    if (subtitleText) {
        const subtitleEl = document.getElementById('printSubtitle');
        if (subtitleEl) subtitleEl.innerText = subtitleText;
    }
}

async function downloadDirectPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('portrait', 'mm', 'letter');
    const pageWidth = doc.internal.pageSize.getWidth();
    const margin = 14;
    let y = 20;
    
    const subtitleText = document.getElementById('printSubtitle')?.innerText || 'Official Barangay Overview & Summary Analytics';
    
    // --- HEADER WITH LOGO ---
    const logoSize = 18;
    const logoX = margin;
    const logoY = 12;
    
    // Try to load and add the barangay logo (only colored element)
    try {
        const logoImg = new Image();
        logoImg.crossOrigin = 'anonymous';
        logoImg.src = 'logo/logopulantubig.png';
        await new Promise((resolve, reject) => {
            logoImg.onload = resolve;
            logoImg.onerror = reject;
            setTimeout(reject, 3000);
        });
        
        const canvas = document.createElement('canvas');
        canvas.width = logoImg.naturalWidth;
        canvas.height = logoImg.naturalHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(logoImg, 0, 0);
        const logoDataUrl = canvas.toDataURL('image/png');
        doc.addImage(logoDataUrl, 'PNG', logoX, logoY, logoSize, logoSize);
    } catch(e) {
        console.warn('Could not load logo for PDF:', e);
    }
    
    // Center the title text in the page (black text only)
    const textAreaStart = margin + logoSize + 4;
    const textAreaEnd = pageWidth - margin;
    const centerX = (textAreaStart + textAreaEnd) / 2;
    
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(20);
    doc.setFont('helvetica', 'bold');
    doc.text('Barangay Pulantubig', centerX, 19, { align: 'center' });
    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');
    doc.text(subtitleText, centerX, 26, { align: 'center' });
    y = 33;
    doc.setDrawColor(0, 0, 0);
    doc.setLineWidth(0.5);
    doc.line(margin, y, pageWidth - margin, y);
    y += 10;
    
    // === Black & white table styles ===
    const bwHeadStyles = { fillColor: [0, 0, 0], textColor: [255, 255, 255], fontStyle: 'bold' };
    const bwBodyStyles = { textColor: [0, 0, 0] };
    const bwAltRow = { fillColor: [255, 255, 255] };
    
    // Helper: extract table data from DOM
    function getTableData(table) {
        const headers = [];
        const body = [];
        table.querySelectorAll('thead th').forEach(th => headers.push(th.innerText.trim()));
        table.querySelectorAll('tbody tr').forEach(tr => {
            const row = [];
            tr.querySelectorAll('td').forEach(td => row.push(td.innerText.trim()));
            if (row.length > 0) body.push(row);
        });
        return { headers, body };
    }
    
    const activeSection = document.querySelector('.report-section.active');
    if (!activeSection) return;
    const sectionId = activeSection.id;
    
    // --- OVERVIEW SECTION ---
    if (sectionId === 'report-all') {
        // Stat cards as a summary table
        const statCards = activeSection.querySelectorAll('.stat-card');
        if (statCards.length > 0) {
            const statHeaders = [];
            const statValues = [];
            statCards.forEach(card => {
                const label = card.querySelector('p')?.innerText.trim() || '';
                const value = card.querySelector('h2')?.innerText.trim() || '0';
                statHeaders.push(label);
                statValues.push(value);
            });
            
            doc.autoTable({
                head: [statHeaders],
                body: [statValues],
                startY: y,
                margin: { left: margin, right: margin },
                styles: { fontSize: 8, cellPadding: 4, halign: 'center', textColor: [0, 0, 0] },
                headStyles: { ...bwHeadStyles, fontSize: 7 },
                bodyStyles: { fontStyle: 'bold', fontSize: 14, textColor: [0, 0, 0] },
                alternateRowStyles: bwAltRow,
                theme: 'grid'
            });
            y = doc.lastAutoTable.finalY + 10;
        }
        
        // Purok demographics table
        const tables = activeSection.querySelectorAll('table');
        tables.forEach(table => {
            const { headers, body } = getTableData(table);
            if (headers.length > 0 && body.length > 0) {
                const reportHeader = table.closest('.report-panel')?.querySelector('.report-header h3');
                if (reportHeader) {
                    doc.setTextColor(0, 0, 0);
                    doc.setFontSize(13);
                    doc.setFont('helvetica', 'bold');
                    doc.text(reportHeader.innerText.trim(), margin, y);
                    y += 6;
                }
                
                doc.autoTable({
                    head: [headers],
                    body: body,
                    startY: y,
                    margin: { left: margin, right: margin },
                    styles: { fontSize: 9, cellPadding: 3, textColor: [0, 0, 0] },
                    headStyles: { ...bwHeadStyles, fontSize: 8 },
                    alternateRowStyles: bwAltRow,
                    theme: 'grid'
                });
                y = doc.lastAutoTable.finalY + 10;
            }
        });
    }
    
    // --- RESIDENTS / HOUSEHOLDS LIST ---
    if (sectionId === 'report-residents' || sectionId === 'report-households') {
        const reportHeader = activeSection.querySelector('.report-header h3');
        if (reportHeader) {
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text(reportHeader.innerText.trim(), margin, y);
            y += 8;
        }
        
        const table = activeSection.querySelector('table');
        if (table) {
            const { headers, body } = getTableData(table);
            doc.autoTable({
                head: [headers],
                body: body,
                startY: y,
                margin: { left: margin, right: margin },
                styles: { fontSize: 8, cellPadding: 3, textColor: [0, 0, 0] },
                headStyles: { ...bwHeadStyles, fontSize: 8 },
                alternateRowStyles: bwAltRow,
                theme: 'grid'
            });
        }
    }
    
    // --- HOUSEHOLDS WITH MEMBERS ---
    if (sectionId === 'report-hh-members') {
        doc.setTextColor(0, 0, 0);
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text('Households with Resident Members', margin, y);
        y += 8;
        
        const hhGroups = activeSection.querySelectorAll('.hh-group-header');
        hhGroups.forEach((header, idx) => {
            const table = header.nextElementSibling;
            if (!table || table.tagName !== 'TABLE') return;
            
            // Check if we need a new page
            if (y > doc.internal.pageSize.getHeight() - 40) {
                doc.addPage();
                y = 20;
            }
            
            // Household group header (black & white)
            doc.setFillColor(230, 230, 230);
            doc.rect(margin, y - 4, pageWidth - margin * 2, 8, 'F');
            doc.setDrawColor(0, 0, 0);
            doc.setLineWidth(1);
            doc.line(margin, y - 4, margin, y + 4);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(0, 0, 0);
            doc.text(header.innerText.trim(), margin + 4, y + 1);
            y += 8;
            
            const { headers: th, body: tb } = getTableData(table);
            if (th.length > 0) {
                doc.autoTable({
                    head: [th],
                    body: tb.length > 0 ? tb : [Array(th.length).fill('No members')],
                    startY: y,
                    margin: { left: margin, right: margin },
                    styles: { fontSize: 8, cellPadding: 2.5, textColor: [0, 0, 0] },
                    headStyles: { ...bwHeadStyles, fontSize: 7 },
                    alternateRowStyles: bwAltRow,
                    theme: 'grid'
                });
                y = doc.lastAutoTable.finalY + 6;
            }
        });
    }
    
    // --- SAVE ---
    let filename = subtitleText.replace(/[^a-zA-Z0-9 ]/g, '').replace(/\s+/g, '_') + '.pdf';
    doc.save(filename);
}
</script>

</body>
</html>
