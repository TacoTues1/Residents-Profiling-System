<?php
include('db.php');
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'Barangay Captain') {
    header("Location: login.php");
    exit();
}

$display_name = trim($_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'User'));
$display_role = trim($_SESSION['role'] ?? 'Barangay Captain');

$filter = $_GET['category'] ?? 'All';
$household_purok_filter = $_GET['household_purok'] ?? 'All';
$where_clause = "COALESCE(is_archived, 0) = 0";

if ($filter !== 'All') {
    $cat = mysqli_real_escape_string($conn, $filter);
    if ($cat == 'Senior Citizen') $where_clause .= " AND age >= 60";
    elseif ($cat == 'Minor')      $where_clause .= " AND age <= 17";
    elseif ($cat == 'Voters')     $where_clause .= " AND is_voter = 1";
    elseif ($cat == 'Solo Parent')$where_clause .= " AND is_solo = 1";
    elseif ($cat == '4ps')         $where_clause .= " AND is_4ps = 1";
    elseif ($cat == 'PWD')         $where_clause .= " AND is_pwd = 1";
}


$total_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM residents WHERE $where_clause"))['count'] ?? 0;
$total_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM households"))['count'] ?? 0;
$child_c  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM residents WHERE age <= 12 AND $where_clause"))['count'] ?? 0;
$teen_c   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM residents WHERE age BETWEEN 13 AND 19 AND $where_clause"))['count'] ?? 0;
$adult_c  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM residents WHERE age BETWEEN 20 AND 59 AND $where_clause"))['count'] ?? 0;
$senior_c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM residents WHERE age >= 60 AND $where_clause"))['count'] ?? 0;

$purok_labels = [];
$purok_counts = [];
$purok_options = [];
$purok_filter_sql = '';

if ($household_purok_filter !== 'All') {
    $escaped_household_purok = mysqli_real_escape_string($conn, $household_purok_filter);
    if ($escaped_household_purok === 'Unspecified') {
        $purok_filter_sql = "WHERE COALESCE(NULLIF(TRIM(purok), ''), 'Unspecified') = 'Unspecified'";
    } else {
        $purok_filter_sql = "WHERE COALESCE(NULLIF(TRIM(purok), ''), 'Unspecified') = '$escaped_household_purok'";
    }
}

$purok_options_query = mysqli_query(
    $conn,
    "SELECT DISTINCT COALESCE(NULLIF(TRIM(purok), ''), 'Unspecified') AS purok_name
     FROM households
     ORDER BY purok_name"
);

if ($purok_options_query) {
    while ($option_row = mysqli_fetch_assoc($purok_options_query)) {
        $purok_options[] = $option_row['purok_name'];
    }
}

$purok_query = mysqli_query(
    $conn,
    "SELECT COALESCE(NULLIF(TRIM(purok), ''), 'Unspecified') AS purok_name, COUNT(*) AS total_households
     FROM households
     $purok_filter_sql
     GROUP BY purok_name
     ORDER BY purok_name"
);

if ($purok_query) {
    while ($row = mysqli_fetch_assoc($purok_query)) {
        $purok_labels[] = $row['purok_name'];
        $purok_counts[] = (int) $row['total_households'];
    }
}

$population_labels = ['Children (0-12)', 'Teenagers (13-19)', 'Adults (20-59)', 'Seniors (60+)'];
$population_counts = [(int) $child_c, (int) $teen_c, (int) $adult_c, (int) $senior_c];
$activities_query = mysqli_query($conn, "SELECT action, created_at FROM logs ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Captain Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sidebar-navy: #1e293b;
            --accent-blue: #2563eb;
            --logo-orange: #ff9800;
        }
        
        body { font-family: 'Inter', sans-serif; margin: 0; display: flex; background: #f1f5f9; height: 100vh; overflow: hidden; }

        .main-container { flex: 1; overflow-y: auto; display: flex; flex-direction: column; box-sizing: border-box; width: 100%; }
        .top-header {
            background: #ffffff;
            padding: 18px 28px;
            margin: 24px 30px 0;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-profile-container { position: relative; }
        .user-pill { display: flex; align-items: center; background: #f8fafc; padding: 8px 15px; border-radius: 50px; border: 1px solid #e2e8f0; cursor: pointer; }
        .avatar { background: var(--accent-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .logout-dropdown { position: absolute; top: 110%; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 12px; width: 220px;  display: none; z-index: 100; overflow: hidden; }
        .logout-dropdown.show { display: block; }
        .dropdown-header { padding: 15px; text-align: center; border-bottom: 1px solid #e5e7eb; color: #64748b; font-size: 14px; }
        .dropdown-header b { display: block; color: #1e293b; margin-top: 4px; font-size: 16px; }
        .logout-btn { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 20px; color: #ef4444; text-decoration: none; font-weight: 600; font-size: 16px; }

        .content-body { padding: 16px 20px 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 18px; }
        .stat-card { background: white; padding: 18px; border-radius: 20px; border: 1px solid #e2e8f0; position: relative; }
        .stat-card p { font-size: 14px; font-weight: 600; color: #64748b; margin: 0; }
        .stat-card h2 { font-size: 32px; margin: 10px 0 5px 0; color: #1e293b; }
        .stat-card .card-icon { position: absolute; top: 30px; right: 30px; font-size: 28px; color: #cbd5e1; }

        .dashboard-main-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 16px; }
        .panel { background: white; border: 1px solid #e5e7eb; padding: 18px; border-radius: 20px; border: 1px solid #e2e8f0; }
        .panel h3 { font-size: 18px; margin-bottom: 5px; }

        .population-panel-header { display: flex; justify-content: space-between; gap: 14px; align-items: flex-start; margin-bottom: 18px; }
        .population-filter-form { margin: 0; }
        .population-filter-group { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

        .population-charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .chart-tile {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px;
        }

        .chart-tile h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #334155;
        }

        .chart-wrap {
            height: 250px;
            position: relative;
        }

        .empty-chart-text {
            color: #94a3b8;
            font-size: 13px;
            margin: 0;
            text-align: center;
            padding-top: 95px;
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .activity-item { 
            border-left: 3px solid #e2e8f0; 
            padding-left: 20px; 
            padding-bottom: 18px; 
            position: relative; 
            opacity: 0;
            animation: slideInUp 0.5s ease-out forwards;
        }
        .activity-item:nth-child(3) { animation-delay: 0.1s; }
        .activity-item:nth-child(4) { animation-delay: 0.2s; }
        .activity-item:nth-child(5) { animation-delay: 0.3s; }
        .activity-item:nth-child(6) { animation-delay: 0.4s; }
        .activity-item:nth-child(7) { animation-delay: 0.5s; }
        
        .activity-item::before { content: ""; position: absolute; left: -9px; top: 0; width: 15px; height: 15px; background: var(--accent-blue); border-radius: 50%; }
        
        .filter-select { padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; font-family: inherit; font-weight: 500; color: #1e293b; outline: none; }

        @media (max-width: 1200px) {
            .population-charts-grid {
                grid-template-columns: 1fr;
            }
            .chart-wrap {
                height: 220px;
            }
        }

        @media (max-width: 1024px) {
            .dashboard-main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .top-header {
                padding: 14px 20px;
                margin: 16px 16px 0;
            }
            .content-body {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<?php include_once('left_navbar.php'); ?>

<div class="main-container">
    <header class="top-header">
        <div>
            <h2 style="margin:0;">Welcome, <?php echo htmlspecialchars($display_name); ?></h2>
            <p style="margin:0; color:#64748b; font-size: 15px;"><?php echo htmlspecialchars($display_role); ?></p>
        </div>
    </header>

    <div class="content-body">
        <div class="stats-grid">
            <div class="stat-card">
                <p>Total Residents (<?php echo htmlspecialchars($filter); ?>)</p>
                <h2 class="counter" data-target="<?php echo $total_res; ?>">0</h2>
                <i class="fa-solid fa-users card-icon" style="color: #cbd5e1;"></i>
            </div>
            <div class="stat-card">
                <p>Total Households</p>
                <h2 class="counter" data-target="<?php echo $total_house; ?>">0</h2>
                <i class="fa-solid fa-house card-icon" style="color: #cbd5e1;"></i>
            </div>
        </div>

        <div class="dashboard-main-grid">
            <div class="population-charts-grid">
                <!-- Residents Panel -->
                <div class="panel" style="display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; gap: 10px;">
                        <div>
                            <h3 style="margin:0 0 4px 0; font-size: 16px;">Residents by Age Group</h3>
                            <p style="color:#64748b; font-size: 13px; margin: 0;">Filter: <b><?php echo htmlspecialchars($filter); ?></b></p>
                        </div>
                        <form method="GET" id="filterForm" style="margin:0;">
                            <input type="hidden" name="household_purok" value="<?php echo htmlspecialchars($household_purok_filter); ?>">
                            <select name="category" class="filter-select" style="padding: 6px 10px; font-size: 13px;" onchange="document.getElementById('filterForm').submit()">
                                <option value="All" <?php if($filter == 'All') echo 'selected'; ?>>All Residents</option>
                                <option value="Senior Citizen" <?php if($filter == 'Senior Citizen') echo 'selected'; ?>>Seniors Only</option>
                                <option value="Minor" <?php if($filter == 'Minor') echo 'selected'; ?>>Minors Only</option>
                                <option value="PWD" <?php if($filter == 'PWD') echo 'selected'; ?>>PWD Only</option>
                                <option value="Voters" <?php if($filter == 'Voters') echo 'selected'; ?>>Voters Only</option>
                            </select>
                        </form>
                    </div>
                    <div class="chart-wrap" style="flex-grow: 1;">
                        <canvas id="populationPieChart"></canvas>
                    </div>
                </div>

                <!-- Households Panel -->
                <div class="panel" style="display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; gap: 10px;">
                        <div>
                            <h3 style="margin:0 0 4px 0; font-size: 16px;">Households per Purok</h3>
                            <p style="color:#64748b; font-size: 13px; margin: 0;">Distribution across puroks</p>
                        </div>
                        <form method="GET" style="margin:0;">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter); ?>">
                            <select name="household_purok" class="filter-select" style="padding: 6px 10px; font-size: 13px;" onchange="this.form.submit()">
                                <option value="All" <?php if($household_purok_filter === 'All') echo 'selected'; ?>>All Purok</option>
                                <?php foreach ($purok_options as $purok_option): ?>
                                    <option value="<?php echo htmlspecialchars($purok_option); ?>" <?php if($household_purok_filter === $purok_option) echo 'selected'; ?>><?php echo htmlspecialchars($purok_option); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <?php if (!empty($purok_labels)): ?>
                        <div class="chart-wrap" style="flex-grow: 1;">
                            <canvas id="purokPieChart"></canvas>
                        </div>
                    <?php else: ?>
                        <p class="empty-chart-text" style="padding-top: 50px;">No household purok data available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <h3>Recent Activities</h3>
                <p style="color:#64748b; font-size: 14px; margin-bottom: 25px;">Latest system updates</p>
                <?php if($activities_query && mysqli_num_rows($activities_query) > 0): ?>
                    <?php while($log = mysqli_fetch_assoc($activities_query)): ?>
                        <div class="activity-item">
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($log['action']); ?></div>
                            <small style="color:#64748b;"><?php echo date('h:i A', strtotime($log['created_at'])); ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:#64748b;">No recent activities found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const counters = document.querySelectorAll('.counter');
        const duration = 1500; // ms
        const frameDuration = 1000 / 60; // 60fps
        const totalFrames = Math.round(duration / frameDuration);
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'), 10) || 0;
            let frame = 0;
            const easeOutQuad = t => t * (2 - t);
            
            const counterInterval = setInterval(() => {
                frame++;
                const progress = easeOutQuad(frame / totalFrames);
                const currentCount = Math.round(target * progress);
                
                counter.innerText = currentCount.toLocaleString();
                
                if (frame >= totalFrames) {
                    clearInterval(counterInterval);
                    counter.innerText = target.toLocaleString();
                }
            }, frameDuration);
        });
    });

    const populationLabels = <?php echo json_encode($population_labels); ?>;
    const populationCounts = <?php echo json_encode($population_counts); ?>;
    const purokLabels = <?php echo json_encode($purok_labels); ?>;
    const purokCounts = <?php echo json_encode($purok_counts); ?>;

    const chartTooltip = {
        callbacks: {
            label: function(context) {
                const value = context.parsed || 0;
                return context.label + ': ' + value;
            }
        }
    };

    const populationChartCanvas = document.getElementById('populationPieChart');
    if (populationChartCanvas) {
        new Chart(populationChartCanvas, {
            type: 'pie',
            data: {
                labels: populationLabels,
                datasets: [{
                    data: populationCounts,
                    backgroundColor: ['#4f46e5', '#8b5cf6', '#10b981', '#f97316'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: chartTooltip
                }
            }
        });
    }

    const purokChartCanvas = document.getElementById('purokPieChart');
    if (purokChartCanvas && purokCounts.length > 0) {
        new Chart(purokChartCanvas, {
            type: 'pie',
            data: {
                labels: purokLabels,
                datasets: [{
                    data: purokCounts,
                    backgroundColor: ['#0ea5e9', '#22c55e', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6', '#ef4444', '#64748b'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: chartTooltip
                }
            }
        });
    }
</script>

</body>
</html>














