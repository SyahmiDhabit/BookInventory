<?php
session_start();
include('db_connect.php');

// Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Check if users table has status column
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'status'");
if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN status ENUM('Active', 'Inactive') DEFAULT 'Active'");
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $tech_id = intval($_GET['toggle_status']);
    $new_status = $_GET['status'] === 'Active' ? 'Inactive' : 'Active';
    
    $update_query = "UPDATE users SET status = '$new_status' WHERE user_id = $tech_id";
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Technician status updated to $new_status";
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(6, min(30, (int)$_GET['per_page'])) : 9; // Default 9, min 6, max 30
$offset = ($page - 1) * $per_page;

// Fetch technician statistics
$total_techs = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM users WHERE role = 'Technician'"))['count'];
    
$active_techs_result = mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM users WHERE role = 'Technician' AND status = 'Active'");
$active_techs = mysqli_num_rows($active_techs_result) > 0 ? 
    mysqli_fetch_assoc($active_techs_result)['count'] : $total_techs;

// Get total count for pagination (before grouping)
$count_query = "SELECT COUNT(DISTINCT u.user_id) as total
    FROM users u
    WHERE u.role = 'Technician'";
$count_result = mysqli_query($conn, $count_query);
$total_technicians = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_technicians / $per_page);

// Fetch technicians with expertise and stats (with pagination)
$tech_query = mysqli_query($conn, "
    SELECT 
        u.user_id,
        u.full_name,
        u.email,
        u.phone,
        COALESCE(u.status, 'Active') as status,
        u.created_at,
        u.profile_image,
        tp.skills,
        tp.experience_years,
        GROUP_CONCAT(DISTINCT ts.skill_name SEPARATOR ', ') as technician_skills,
        COUNT(DISTINCT r.report_id) as total_tasks,
        SUM(CASE WHEN r.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN r.status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing_tasks,
        AVG(CASE WHEN r.status = 'Completed' THEN TIMESTAMPDIFF(HOUR, r.report_date, r.updated_at) END) as avg_completion_hours
    FROM users u
    LEFT JOIN technician_profiles tp ON u.user_id = tp.tech_id
    LEFT JOIN technician_skills ts ON u.user_id = ts.technician_id
    LEFT JOIN reports r ON u.user_id = r.assigned_to
    WHERE u.role = 'Technician'
    GROUP BY u.user_id
    ORDER BY 
        COALESCE(u.status, 'Active') DESC,
        completed_tasks DESC,
        u.full_name ASC
    LIMIT $per_page OFFSET $offset
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technicians - HFRS Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
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

        body {
            display: flex;
            background: #f8fafc;
            min-height: 100vh;
            color: var(--dark);
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
            padding: 40px;
            padding-bottom: 80px;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 2px solid #e5e7eb;
        }

        .page-title {
            font-size: 36px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: var(--gray);
            font-size: 16px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .admin-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        /* MESSAGES */
        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
        }

        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .message.info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ====== UPDATED LAYOUT ====== */
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        /* STATS OVERVIEW */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
        }

        .stat-icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.active { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.expert { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

        .stat-content h3 {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .stat-content .value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        /* ACTIONS BAR */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .search-box {
            flex: 1;
            max-width: 400px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            min-width: 150px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        /* TECHNICIANS GRID */
        .technicians-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        /* TECH CARD - UPDATED DESIGN */
        .tech-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .tech-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            border-color: var(--primary);
        }

        .tech-card.inactive {
            opacity: 0.7;
            position: relative;
        }

        .tech-card.inactive::before {
            content: 'INACTIVE';
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--gray);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 1;
        }

        /* CARD HEADER */
        .tech-header {
            padding: 25px 25px 20px;
            position: relative;
        }

        .tech-status {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #e5e7eb;
        }

        .status-active { background: var(--secondary); }
        .status-inactive { background: var(--gray); }

        .tech-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 20px;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .tech-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .tech-avatar .initials {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
        }

        .tech-name {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .tech-email {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .tech-expertise {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }

        .expertise-tag {
            background: #e0e7ff;
            color: var(--primary-dark);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        /* CARD BODY */
        .tech-body {
            padding: 0 25px 25px;
        }

        /* PERFORMANCE METRICS */
        .performance-metrics {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .metric-row:last-child {
            margin-bottom: 0;
        }

        .metric-label {
            font-size: 13px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .metric-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--dark);
        }

        /* SKILLS SECTION */
        .skills-section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .skill-tag {
            background: #f3f4f6;
            color: var(--gray);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        /* ACTION BUTTONS */
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .action-btn {
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-assign {
            background: var(--primary);
            color: white;
        }

        .btn-assign:hover {
            background: var(--primary-dark);
        }

        .btn-toggle {
            background: #f3f4f6;
            color: var(--dark);
        }

        .btn-toggle:hover {
            background: #e5e7eb;
        }

        .btn-toggle.active {
            background: var(--secondary);
            color: white;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 25px;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 20px;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 25px;
            max-width: 500px;
            margin: 0 auto 25px;
        }

        /* PAGINATION */
        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            flex-wrap: wrap;
            gap: 15px;
        }

        .pagination-info {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-link {
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            font-size: 14px;
            background: white;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 44px;
            justify-content: center;
        }

        .page-link:hover:not(.disabled):not(.active) {
            background: #f3f4f6;
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f9fafb;
            color: var(--gray);
        }

        .page-link.disabled:hover {
            transform: none;
            border-color: #e5e7eb;
        }

        @media (max-width: 768px) {
            .pagination-wrapper {
                flex-direction: column;
                align-items: stretch;
            }

            .pagination-info {
                text-align: center;
                width: 100%;
            }

            .pagination {
                justify-content: center;
                flex-wrap: wrap;
            }

            .page-link {
                padding: 8px 12px;
                font-size: 13px;
                min-width: 40px;
            }
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 1200px) {
            .technicians-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header h2,
            .sidebar-header p,
            .admin-profile-sidebar,
            .sidebar nav a span {
                display: none;
            }
            
            .sidebar nav a {
                justify-content: center;
                padding: 15px;
            }
            
            .sidebar nav a i {
                font-size: 20px;
            }
            
            .main-content {
                margin-left: 80px;
                padding: 25px;
            }
            
            .actions-bar {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .filter-actions {
                justify-content: space-between;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .admin-info {
                width: 100%;
                justify-content: center;
            }
            
            .technicians-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                flex-direction: column;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
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
                <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
                <li><a href="admin_reports.php"><i class="fa-solid fa-file-alt"></i> <span>Manage Reports</span></a></li>
                <li><a href="admin_technicians.php" class="active"><i class="fa-solid fa-user-gear"></i> <span>Technicians</span></a></li>
                <li><a href="admin_pending_technicians.php"><i class="fa-solid fa-hourglass-half"></i> <span>Pending Registrations</span></a></li>
                <li><a href="admin_students.php"><i class="fa-solid fa-users"></i> <span>Students</span></a></li>
                <li><a href="assign_task.php"><i class="fa-solid fa-share-nodes"></i> <span>Assign Tasks</span></a></li>
                <li><a href="logout.php" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
            </ul>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- HEADER -->
        <div class="header">
            <div>
                <h1 class="page-title">Technician Management</h1>
                <p class="page-subtitle">Manage technician profiles, expertise, and performance</p>
            </div>
            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo substr($_SESSION['user_name'] ?? 'A', 0, 1); ?>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 16px;"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></div>
                    <div style="font-size: 13px; color: var(--gray);">Administrator</div>
                </div>
            </div>
        </div>

        <!-- MESSAGES -->
        <?php if (isset($success_message)): ?>
            <div class="message success">
                <i class="fa-solid fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="content-wrapper">
            <!-- STATS OVERVIEW -->
            <div class="stats-overview">
                <div class="stat-box">
                    <div class="stat-icon total">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Technicians</h3>
                        <div class="value"><?php echo $total_techs; ?></div>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon active">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Now</h3>
                        <div class="value"><?php echo $active_techs; ?></div>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon expert">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Specialists</h3>
                        <div class="value"><?php 
                            // Count technicians with specific skills
                            $specialists_query = mysqli_query($conn, 
                                "SELECT COUNT(DISTINCT u.user_id) as count 
                                FROM users u 
                                LEFT JOIN technician_profiles tp ON u.user_id = tp.tech_id
                                WHERE u.role = 'Technician' 
                                AND (tp.skills IS NOT NULL OR u.user_id IN (SELECT technician_id FROM technician_skills))");
                            echo mysqli_fetch_assoc($specialists_query)['count'];
                        ?></div>
                    </div>
                </div>
            </div>

            <!-- ACTIONS BAR -->
            <div class="actions-bar">
                <div class="search-box">
                    <input type="text" id="searchTechnicians" placeholder="Search technicians by name or expertise...">
                    <i class="fa-solid fa-search"></i>
                </div>
                <div class="filter-actions">
                    <select class="filter-select" id="statusFilter" onchange="filterTechnicians()">
                        <option value="all">All Status</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                    <select class="filter-select" id="perPageSelect" onchange="changePerPage(this.value)" style="min-width: 120px;">
                        <option value="6" <?php echo $per_page == 6 ? 'selected' : ''; ?>>6 per page</option>
                        <option value="9" <?php echo $per_page == 9 ? 'selected' : ''; ?>>9 per page</option>
                        <option value="12" <?php echo $per_page == 12 ? 'selected' : ''; ?>>12 per page</option>
                        <option value="18" <?php echo $per_page == 18 ? 'selected' : ''; ?>>18 per page</option>
                        <option value="30" <?php echo $per_page == 30 ? 'selected' : ''; ?>>30 per page</option>
                    </select>
                    <a href="admin_add_technician.php" class="btn btn-primary">
                        <i class="fa-solid fa-user-plus"></i>
                        Add Technician
                    </a>
                </div>
            </div>

            <!-- TECHNICIANS GRID -->
            <div class="technicians-grid" id="techniciansGrid">
                <?php if (mysqli_num_rows($tech_query) > 0): ?>
                    <?php while ($tech = mysqli_fetch_assoc($tech_query)): 
                        $is_active = $tech['status'] === 'Active';
                        $completion_rate = $tech['total_tasks'] > 0 ? 
                            round(($tech['completed_tasks'] / $tech['total_tasks']) * 100) : 0;
                        $workload = $tech['ongoing_tasks'];
                        $avg_time = round($tech['avg_completion_hours'] ?? 0, 1);
                        
                        // Get initials for avatar
                        $initials = '';
                        $name_parts = explode(' ', $tech['full_name']);
                        foreach ($name_parts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                            if (strlen($initials) >= 2) break;
                        }
                    ?>
                        <div class="tech-card <?php echo !$is_active ? 'inactive' : ''; ?>" 
                             data-status="<?php echo strtolower($tech['status']); ?>"
                             data-name="<?php echo strtolower($tech['full_name']); ?>"
                             data-skills="<?php echo strtolower($tech['skills'] . ' ' . $tech['technician_skills']); ?>">
                            
                            <div class="tech-header">
                                <div class="tech-status <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>"></div>
                                
                                <div class="tech-avatar">
                                    <?php if (!empty($tech['profile_image']) && file_exists($tech['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($tech['profile_image']); ?>" alt="<?php echo htmlspecialchars($tech['full_name']); ?>">
                                    <?php else: ?>
                                        <div class="initials"><?php echo $initials; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="tech-name"><?php echo htmlspecialchars($tech['full_name']); ?></h3>
                                <div class="tech-email"><?php echo htmlspecialchars($tech['email']); ?></div>
                                
                                <?php if ($tech['phone']): ?>
                                    <div class="tech-email">
                                        <i class="fa-solid fa-phone" style="margin-right: 5px;"></i>
                                        <?php echo htmlspecialchars($tech['phone']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($tech['experience_years'] > 0): ?>
                                    <div class="tech-email">
                                        <i class="fa-solid fa-calendar-alt" style="margin-right: 5px;"></i>
                                        <?php echo $tech['experience_years']; ?> years experience
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($tech['skills'])): ?>
                                    <div class="tech-expertise">
                                        <span class="expertise-tag"><?php echo htmlspecialchars($tech['skills']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="tech-body">
                                <!-- PERFORMANCE METRICS -->
                                <div class="performance-metrics">
                                    <div class="metric-row">
                                        <div class="metric-label">
                                            <i class="fa-solid fa-tasks"></i>
                                            <span>Total Tasks</span>
                                        </div>
                                        <div class="metric-value"><?php echo $tech['total_tasks']; ?></div>
                                    </div>
                                    
                                    <div class="metric-row">
                                        <div class="metric-label">
                                            <i class="fa-solid fa-check-circle"></i>
                                            <span>Completed</span>
                                        </div>
                                        <div class="metric-value">
                                            <?php echo $tech['completed_tasks']; ?>
                                            <span style="font-size: 12px; color: var(--gray); margin-left: 5px;">
                                                (<?php echo $completion_rate; ?>%)
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="metric-row">
                                        <div class="metric-label">
                                            <i class="fa-solid fa-clock"></i>
                                            <span>Avg. Time</span>
                                        </div>
                                        <div class="metric-value"><?php echo $avg_time; ?>h</div>
                                    </div>
                                    
                                    <div class="metric-row">
                                        <div class="metric-label">
                                            <i class="fa-solid fa-hourglass-half"></i>
                                            <span>Current Workload</span>
                                        </div>
                                        <div class="metric-value">
                                            <?php echo $workload; ?> tasks
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- SKILLS SECTION -->
                                <?php if (!empty($tech['technician_skills'])): ?>
                                <div class="skills-section">
                                    <div class="section-title">
                                        <i class="fa-solid fa-tools"></i>
                                        <span>Technical Skills</span>
                                    </div>
                                    <div class="skills-list">
                                        <?php 
                                        $skills = explode(', ', $tech['technician_skills']);
                                        foreach ($skills as $skill):
                                            if (!empty(trim($skill))):
                                        ?>
                                            <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- ACTION BUTTONS -->
                                <div class="action-buttons">
                                    <a href="assign_task.php?tech_id=<?php echo $tech['user_id']; ?>" class="action-btn btn-assign">
                                        <i class="fa-solid fa-share-nodes"></i>
                                        Assign Task
                                    </a>
                                    
                                    <?php if ($is_active): ?>
                                        <a href="admin_technicians.php?toggle_status=<?php echo $tech['user_id']; ?>&status=Active" 
                                           class="action-btn btn-toggle">
                                            <i class="fa-solid fa-toggle-on"></i>
                                            Deactivate
                                        </a>
                                    <?php else: ?>
                                        <a href="admin_technicians.php?toggle_status=<?php echo $tech['user_id']; ?>&status=Inactive" 
                                           class="action-btn btn-toggle active">
                                            <i class="fa-solid fa-toggle-off"></i>
                                            Activate
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-users-gear"></i>
                        <h4>No Technicians Found</h4>
                        <p>There are no technicians registered in the system yet. Add your first technician to get started.</p>
                        <a href="admin_add_technician.php" class="btn btn-primary">
                            <i class="fa-solid fa-user-plus"></i>
                            Add First Technician
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1 || $total_technicians > 0): ?>
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Showing <?php echo $total_technicians > 0 ? ($offset + 1) : 0; ?> - <?php echo min($offset + $per_page, $total_technicians); ?> of <?php echo $total_technicians; ?> technicians
                </div>
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $per_page; ?>" class="page-link">
                            <i class="fa-solid fa-chevron-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <span class="page-link disabled">
                            <i class="fa-solid fa-chevron-left"></i> Previous
                        </span>
                    <?php endif; ?>
                    
                    <?php
                    // Calculate page range to show
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    // Show first page if not in range
                    if ($start_page > 1): ?>
                        <a href="?page=1&per_page=<?php echo $per_page; ?>" class="page-link <?php echo $page == 1 ? 'active' : ''; ?>">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="page-link disabled">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php
                    // Show last page if not in range
                    if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="page-link disabled">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>&per_page=<?php echo $per_page; ?>" class="page-link <?php echo $page == $total_pages ? 'active' : ''; ?>">
                            <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $per_page; ?>" class="page-link">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-link disabled">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filter technicians by search and status
        function filterTechnicians() {
            const searchTerm = document.getElementById('searchTechnicians').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const techCards = document.querySelectorAll('.tech-card');
            
            techCards.forEach(card => {
                const techName = card.dataset.name;
                const techSkills = card.dataset.skills;
                const techStatus = card.dataset.status;
                
                // Check if matches search
                const matchesSearch = !searchTerm || 
                    techName.includes(searchTerm) || 
                    techSkills.includes(searchTerm);
                
                // Check if matches status filter
                const matchesStatus = statusFilter === 'all' || 
                    (statusFilter === 'active' && techStatus === 'active') ||
                    (statusFilter === 'inactive' && techStatus === 'inactive');
                
                // Show/hide card
                if (matchesSearch && matchesStatus) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Initialize search functionality
        document.getElementById('searchTechnicians').addEventListener('input', filterTechnicians);
        document.getElementById('statusFilter').addEventListener('change', filterTechnicians);
        
        // Confirmation for status toggle
        document.querySelectorAll('.btn-toggle').forEach(button => {
            button.addEventListener('click', function(e) {
                const action = this.textContent.includes('Activate') ? 'activate' : 'deactivate';
                const techName = this.closest('.tech-card').querySelector('.tech-name').textContent;
                
                if (!confirm(`Are you sure you want to ${action} ${techName}?`)) {
                    e.preventDefault();
                }
            });
        });
        
        // Add smooth hover effects
        document.querySelectorAll('.tech-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 8px 30px rgba(0,0,0,0.12)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 20px rgba(0,0,0,0.08)';
            });
        });

        // Change items per page
        function changePerPage(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            url.searchParams.set('page', '1'); // Reset to first page
            window.location.href = url.toString();
        }
    </script>
</body>
</html>