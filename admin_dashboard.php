<?php
session_start();
include('db_connect.php');

// Check admin login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['user_name'];

// Get statistics
$totalReports = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM reports"))['total'];
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM reports WHERE status='Pending'"))['total'];
$ongoing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM reports WHERE status='Ongoing'"))['total'];
$completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM reports WHERE status='Completed'"))['total'];

$techs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='Technician'"))['total'];
$students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='User'"))['total'];
$admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='Admin'"))['total'];

// Get recent reports (last 7 days)
$recentReports = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM reports WHERE report_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
))['total'];

// Get unresolved reports
$unresolved = $pending + $ongoing;

// Get monthly stats for chart
$monthly_stats = [];
$monthly_query = mysqli_query($conn, 
    "SELECT 
        DATE_FORMAT(report_date, '%Y-%m') as month,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
     FROM reports 
     WHERE report_date >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(report_date, '%Y-%m')
     ORDER BY month"
);

while($row = mysqli_fetch_assoc($monthly_query)) {
    $monthly_stats[] = $row;
}

// Get latest reports for table
$latest_query = mysqli_query($conn, 
    "SELECT r.*, u.full_name as reporter_name 
     FROM reports r
     LEFT JOIN users u ON r.user_id = u.user_id
     ORDER BY r.report_date DESC LIMIT 8"
);

// Get top technicians by completed tasks
$top_techs_query = mysqli_query($conn, 
    "SELECT u.full_name, COUNT(r.report_id) as completed_tasks
     FROM reports r
     JOIN users u ON r.assigned_to = u.user_id
     WHERE r.status = 'Completed' AND u.role = 'Technician'
     GROUP BY r.assigned_to
     ORDER BY completed_tasks DESC
     LIMIT 5"
);

// Calculate resolution rate
$resolution_rate = $totalReports > 0 ? round(($completed / $totalReports) * 100) : 0;

// Get urgent reports (if priority column exists)
$check_priority = mysqli_query($conn, "SHOW COLUMNS FROM reports LIKE 'priority'");
if (mysqli_num_rows($check_priority) > 0) {
    $urgent_reports = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as total FROM reports WHERE priority IN ('High', 'Urgent') AND status != 'Completed'"
    ))['total'];
} else {
    $urgent_reports = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - HFRS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --urgent: #dc2626;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --sidebar-bg: #0a1930;
            --sidebar-hover: #1e3a5c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

 /* ====== SIDEBAR ====== */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, #0d2342 100%);
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }

        .sidebar-header {
            padding: 0 30px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 25px;
        }

        .sidebar-header h2 {
            font-size: 28px;
            font-weight: 800;
            color: white;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .sidebar-header p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 20px;
        }

        .admin-profile-sidebar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 15px;
            border-radius: 12px;
            margin-top: 15px;
        }

        .admin-avatar-sidebar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .admin-text-sidebar h4 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .admin-text-sidebar p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
        }

        .sidebar nav ul {
            list-style: none;
            padding: 0 20px;
        }

        .sidebar nav li {
            margin-bottom: 8px;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 15px;
        }

        .sidebar nav a:hover {
            background: var(--sidebar-hover);
            color: white;
            transform: translateX(5px);
        }

        .sidebar nav a.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .sidebar nav a i {
            width: 22px;
            text-align: center;
            font-size: 18px;
        }
/* MAIN CONTENT */
.main-content {
    flex: 1;
    margin-left: 260px;
    padding: 30px;
    min-height: 100vh;
}

/* TOP BAR */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: white;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.page-title h1 {
    font-size: 28px;
    color: var(--primary);
    font-weight: 700;
}

.page-title p {
    color: var(--gray);
    font-size: 14px;
}

.top-actions {
    display: flex;
    gap: 15px;
    align-items: center;
}

.notification-bell {
    position: relative;
    cursor: pointer;
}

.notification-bell i {
    font-size: 20px;
    color: var(--primary);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--danger);
    color: white;
    font-size: 11px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* DASHBOARD GRID */
.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

/* STATS GRID */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
}

.stat-card.total-reports::before { background: var(--primary); }
.stat-card.pending::before { background: var(--warning); }
.stat-card.ongoing::before { background: var(--secondary); }
.stat-card.completed::before { background: var(--success); }
.stat-card.technicians::before { background: #9b59b6; }
.stat-card.students::before { background: #2ecc71; }
.stat-card.recent::before { background: #3498db; }
.stat-card.resolution::before { background: #e74c3c; }

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-card.total-reports .stat-icon { background: rgba(10, 25, 48, 0.1); color: var(--primary); }
.stat-card.pending .stat-icon { background: rgba(255, 165, 2, 0.1); color: var(--warning); }
.stat-card.ongoing .stat-icon { background: rgba(0, 168, 255, 0.1); color: var(--secondary); }
.stat-card.completed .stat-icon { background: rgba(46, 213, 115, 0.1); color: var(--success); }
.stat-card.technicians .stat-icon { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }
.stat-card.students .stat-icon { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
.stat-card.recent .stat-icon { background: rgba(52, 152, 219, 0.1); color: #3498db; }
.stat-card.resolution .stat-icon { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }

.stat-content h3 {
    font-size: 14px;
    color: var(--gray);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-content .number {
    color: black;
    font-size: 32px;
    font-weight: 700;
    color: var(--primary);
    line-height: 1;
}

.stat-content .trend {
    font-size: 12px;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.trend.up { color: var(--success); }
.trend.down { color: var(--danger); }

/* CHARTS CONTAINER */
.chart-container {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chart-header h3 {
    font-size: 18px;
    color: var(--primary);
}

#monthlyChart {
    height: 300px !important;
}

/* RECENT ACTIVITY */
.recent-activity {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.activity-header h3 {
    font-size: 18px;
    color: var(--primary);
}

.activity-list {
    list-style: none;
}

.activity-item {
    padding: 15px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 15px;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.activity-icon.pending { background: var(--warning); }
.activity-icon.ongoing { background: var(--secondary); }
.activity-icon.completed { background: var(--success); }
.activity-icon.urgent { background: var(--danger); }

.activity-details h4 {
    font-size: 15px;
    color: var(--primary);
    margin-bottom: 5px;
}

.activity-details p {
    font-size: 13px;
    color: var(--gray);
}

.activity-time {
    font-size: 12px;
    color: var(--gray);
    margin-left: auto;
    white-space: nowrap;
}

/* TOP TECHNICIANS */
.top-techs {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.techs-header {
    margin-bottom: 20px;
}

.techs-header h3 {
    font-size: 18px;
    color: var(--primary);
}

.tech-item {
    padding: 15px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 15px;
}

.tech-item:last-child {
    border-bottom: none;
}

.tech-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

.tech-info {
    flex: 1;
}

.tech-info h4 {
    font-size: 15px;
    color: var(--primary);
    margin-bottom: 5px;
}

.tech-stats {
    display: flex;
    align-items: center;
    gap: 15px;
}

.tech-tasks {
    font-size: 13px;
    color: var(--gray);
    display: flex;
    align-items: center;
    gap: 5px;
}

.tech-rating {
    font-size: 13px;
    color: var(--warning);
    display: flex;
    align-items: center;
    gap: 3px;
}

/* QUICK ACTIONS */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.action-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s;
    border: 2px solid transparent;
    cursor: pointer;
}

.action-card:hover {
    border-color: var(--secondary);
    transform: translateY(-5px);
}

.action-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--secondary), #0077cc);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    color: white;
    font-size: 24px;
}

.action-card h3 {
    color: var(--primary);
    margin-bottom: 10px;
    font-size: 16px;
}

.action-card p {
    color: var(--gray);
    font-size: 14px;
}

/* FOOTER */
footer {
    text-align: center;
    padding: 25px;
    margin-top: 40px;
    color: var(--gray);
    font-size: 14px;
    border-top: 1px solid #eee;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .sidebar {
        width: 70px;
        overflow: visible;
    }
    
    .sidebar-header h2,
    .sidebar-header p,
    .admin-info,
    .sidebar-menu a span,
    .sidebar-footer {
        display: none;
    }
    
    .sidebar-menu a {
        justify-content: center;
        padding: 15px;
    }
    
    .sidebar-menu a i {
        margin-right: 0;
        font-size: 18px;
    }
    
    .main-content {
        margin-left: 70px;
        padding: 15px;
    }
    
    .top-bar {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
}

/* STATUS BADGE */
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-ongoing { background: #cce5ff; color: #004085; }
.status-completed { background: #d4edda; color: #155724; }

/* TABLE */
.data-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.table-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.table-header h3 {
    font-size: 18px;
    color: var(--primary);
}

.table-container {
    overflow-x: auto;
}

.table-container table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.table-container th {
    background: #f8f9fa;
    padding: 15px 20px;
    text-align: left;
    color: var(--primary);
    font-weight: 600;
    font-size: 14px;
    border-bottom: 2px solid #eee;
}

.table-container td {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    color: var(--dark);
    font-size: 14px;
}

.table-container tr:hover {
    background: #f8f9fa;
}

.table-actions {
    display: flex;
    gap: 8px;
}

.btn-table {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s;
}

.btn-view { background: #e3f2fd; color: #1976d2; }
.btn-view:hover { background: #bbdefb; }

.btn-edit { background: #fff3e0; color: #f57c00; }
.btn-edit:hover { background: #ffe0b2; }

.btn-delete { background: #ffebee; color: #d32f2f; }
.btn-delete:hover { background: #ffcdd2; }
</style>
</head>

<body>
<!-- ====== SIDEBAR ====== -->
    <!-- This sidebar now matches your admin_dashboard.php -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>HFRS Admin</h2>
            <p>Control Panel</p>
            <div class="admin-profile-sidebar">
                <div class="admin-avatar-sidebar">
                    <?php echo substr($_SESSION['user_name'] ?? 'A', 0, 1); ?>
                </div>
                <div class="admin-text-sidebar">
                    <h4><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>
        
        <nav>
            <ul>
                <li><a href="admin_dashboard.php" class="active"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
                <li><a href="admin_reports.php" ><i class="fa-solid fa-file-alt"></i> <span>Manage Reports</span></a></li>
                <li><a href="admin_technicians.php"><i class="fa-solid fa-user-gear"></i> <span>Technicians</span></a></li>
                <li><a href="admin_pending_technicians.php"><i class="fa-solid fa-hourglass-half"></i> <span>Pending Registrations</span></a></li>
                <li><a href="admin_students.php"><i class="fa-solid fa-users"></i> <span>Students</span></a></li>
                <li><a href="assign_task.php"><i class="fa-solid fa-share-nodes"></i> <span>Assign Tasks</span></a></li>
                <li><a href="logout.php" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
            </ul>
        </nav>
    </div>


<!-- MAIN CONTENT -->
<div class="main-content">
    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="page-title">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?>. Here's what's happening with your system.</p>
        </div>
        <div class="top-actions">
            
            <div style="color: var(--primary); font-weight: 500;">
                <i class="fas fa-calendar-alt"></i>
                <?php echo date('F j, Y'); ?>
            </div>
        </div>
    </div>

    <!-- STATS GRID -->
    <div class="stats-grid">
        <div class="stat-card total-reports">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3>Total Reports</h3>
                <div class="number"><?php echo $totalReports; ?></div>
                <div class="trend up">
                    <i class="fas fa-arrow-up"></i>
                    <?php echo $recentReports; ?> this week
                </div>
            </div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3>Pending</h3>
                <div class="number"><?php echo $pending; ?></div>
                <div class="trend">
                    <?php echo $totalReports > 0 ? round(($pending / $totalReports) * 100) : 0; ?>% of total
                </div>
            </div>
        </div>
        
        <div class="stat-card ongoing">
            <div class="stat-icon">
                <i class="fas fa-tools"></i>
            </div>
            <div class="stat-content">
                <h3>In Progress</h3>
                <div class="number"><?php echo $ongoing; ?></div>
                <div class="trend">
                    Active repairs
                </div>
            </div>
        </div>
        
        <div class="stat-card completed">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Completed</h3>
                <div class="number"><?php echo $completed; ?></div>
                <div class="trend up">
                    <i class="fas fa-arrow-up"></i>
                    Solved
                </div>
            </div>
        </div>
        
        <div class="stat-card technicians">
            <div class="stat-icon">
                <i class="fas fa-user-gear"></i>
            </div>
            <div class="stat-content">
                <h3>Technicians</h3>
                <div class="number"><?php echo $techs; ?></div>
                <div class="trend">
                    Available for tasks
                </div>
            </div>
        </div>
        
        <div class="stat-card students">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>Students</h3>
                <div class="number"><?php echo $students; ?></div>
                <div class="trend up">
                    <i class="fas fa-arrow-up"></i>
                    Active users
                </div>
            </div>
        </div>
        
        <div class="stat-card recent">
            <div class="stat-icon">
                <i class="fas fa-history"></i>
            </div>
            <div class="stat-content">
                <h3>Recent (7 Days)</h3>
                <div class="number"><?php echo $recentReports; ?></div>
                <div class="trend">
                    New reports
                </div>
            </div>
        </div>
        
       
    </div>

    <!-- DASHBOARD GRID -->
    <div class="dashboard-grid">
        <!-- LEFT COLUMN -->
        <div>
            <!-- MONTHLY CHART -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Monthly Report Activity</h3>
                    <select id="chartPeriod" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd; font-size: 14px;">
                        <option value="6">Last 6 Months</option>
                        <option value="12">Last Year</option>
                        <option value="3">Last 3 Months</option>
                    </select>
                </div>
                <canvas id="monthlyChart"></canvas>
            </div>

            <!-- RECENT REPORTS TABLE -->
            <div class="data-table" style="margin-top: 30px;">
                <div class="table-header">
                    <h3>Recent Reports</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Reporter</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($latest_query) > 0): ?>
                                <?php while($report = mysqli_fetch_assoc($latest_query)): ?>
                                <tr>
                                    <td>#<?php echo $report['report_id']; ?></td>
                                    <td><?php echo htmlspecialchars($report['reporter_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($report['status']); ?>">
                                            <?php echo $report['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                                    
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: var(--gray);">
                                        No reports found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div>
        

            <!-- TOP TECHNICIANS -->
            <div class="top-techs" style="margin-top: 30px;">
                <div class="techs-header">
                    <h3>Top Technicians</h3>
                </div>
                <?php if (mysqli_num_rows($top_techs_query) > 0): ?>
                    <?php while($tech = mysqli_fetch_assoc($top_techs_query)): ?>
                    <div class="tech-item">
                        <div class="tech-avatar">
                            <?php 
                            $tech_initials = '';
                            $tech_name_parts = explode(' ', $tech['full_name']);
                            foreach ($tech_name_parts as $part) {
                                $tech_initials .= strtoupper(substr($part, 0, 1));
                                if (strlen($tech_initials) >= 2) break;
                            }
                            echo $tech_initials;
                            ?>
                        </div>
                        <div class="tech-info">
                            <h4><?php echo htmlspecialchars($tech['full_name']); ?></h4>
                            <div class="tech-stats">
                                <span class="tech-tasks">
                                    <i class="fas fa-tasks"></i>
                                    <?php echo $tech['completed_tasks']; ?> tasks
                                </span>
                                <span class="tech-rating">
                                    <i class="fas fa-star"></i>
                                    
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: var(--gray);">
                        No technician data available
                    </div>
                <?php endif; ?>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="quick-actions" style="margin-top: 30px;">
                <div class="action-card" onclick="window.location.href='assign_task.php'">
                    <div class="action-icon">
                        <i class="fas fa-share-nodes"></i>
                    </div>
                    <h3>Assign Tasks</h3>
                    <p>Assign reports to technicians</p>
                </div>
                
                <div class="action-card" onclick="window.location.href='admin_reports.php'">
                    <div class="action-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Manage Reports</h3>
                    <p>View and manage all reports</p>
                </div>
                
                <div class="action-card" onclick="window.location.href='admin_technicians.php'">
                    <div class="action-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Add Technician</h3>
                    <p>Add new technician to system</p>
                </div>
            </div>
        </div>
    </div>

    <footer>
        © 2025 Hostel Facilities Report System | Admin Panel v2.0
    </footer>
</div>

<script>
    // Initialize Monthly Chart
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    
    <?php if (!empty($monthly_stats)): ?>
        const months = <?php echo json_encode(array_column($monthly_stats, 'month')); ?>;
        const totals = <?php echo json_encode(array_column($monthly_stats, 'total')); ?>;
        const completed = <?php echo json_encode(array_column($monthly_stats, 'completed')); ?>;
        
        // Format month labels
        const monthLabels = months.map(m => {
            const date = new Date(m + '-01');
            return date.toLocaleString('default', { month: 'short' }) + ' ' + date.getFullYear().toString().slice(-2);
        });
        
        const monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Total Reports',
                    data: totals,
                    backgroundColor: 'rgba(10, 25, 48, 0.7)',
                    borderColor: 'rgba(10, 25, 48, 1)',
                    borderWidth: 1
                }, {
                    label: 'Completed',
                    data: completed,
                    backgroundColor: 'rgba(46, 213, 115, 0.7)',
                    borderColor: 'rgba(46, 213, 115, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5
                        }
                    }
                }
            }
        });
    <?php else: ?>
        // Default empty chart
        const emptyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['No Data Available'],
                datasets: [{
                    label: 'Reports',
                    data: [0],
                    backgroundColor: 'rgba(200, 200, 200, 0.5)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    <?php endif; ?>

    // Chart period change
    document.getElementById('chartPeriod').addEventListener('change', function(e) {
        alert('Chart period changed to ' + e.target.value + ' months. This would update the chart data.');
        // In real implementation, you would fetch new data via AJAX
    });

    // Report actions
    function viewReport(reportId) {
        alert('Viewing report #' + reportId);
        // window.location.href = 'admin_report_view.php?id=' + reportId;
    }

    function editReport(reportId) {
        alert('Editing report #' + reportId);
        // window.location.href = 'admin_report_edit.php?id=' + reportId;
    }

    // Notification bell click
    document.querySelector('.notification-bell').addEventListener('click', function() {
        alert('Showing notifications');
        // window.location.href = 'admin_notifications.php';
    });

    // Auto-refresh dashboard every 5 minutes
    setTimeout(() => {
        window.location.reload();
    }, 300000);

    // Sidebar toggle for mobile
    let sidebarVisible = true;
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (sidebarVisible) {
            sidebar.style.transform = 'translateX(-100%)';
            mainContent.style.marginLeft = '0';
        } else {
            sidebar.style.transform = 'translateX(0)';
            mainContent.style.marginLeft = '260px';
        }
        sidebarVisible = !sidebarVisible;
    }

    // Add mobile menu toggle button
    if (window.innerWidth <= 768) {
        const topBar = document.querySelector('.top-bar');
        const toggleBtn = document.createElement('button');
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.style.cssText = 'background: var(--secondary); color: white; border: none; width: 40px; height: 40px; border-radius: 8px; cursor: pointer; font-size: 18px;';
        toggleBtn.onclick = toggleSidebar;
        topBar.insertBefore(toggleBtn, topBar.firstChild);
    }
</script>
</body>
</html>