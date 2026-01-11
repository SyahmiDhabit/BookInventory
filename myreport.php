<?php
session_start();
include('db_connect.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];

// Check what columns exist in reports table
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM reports");
$reports_columns = [];
while($col = mysqli_fetch_assoc($check_columns)) {
    $reports_columns[] = $col['Field'];
}

// Build query based on available columns
$columns_to_select = ['report_id', 'user_id', 'issue_description', 'report_date', 'status', 'image_path'];
if (in_array('facility_id', $reports_columns)) {
    $columns_to_select[] = 'facility_id';
}
if (in_array('facility_name', $reports_columns)) {
    $columns_to_select[] = 'facility_name';
}
if (in_array('progress_notes', $reports_columns)) {
    $columns_to_select[] = 'progress_notes';
}
if (in_array('priority', $reports_columns)) {
    $columns_to_select[] = 'priority';
}
if (in_array('hostel_block', $reports_columns)) {
    $columns_to_select[] = 'hostel_block';
}
if (in_array('room_number', $reports_columns)) {
    $columns_to_select[] = 'room_number';
}
if (in_array('assigned_to', $reports_columns)) {
    $columns_to_select[] = 'assigned_to';
}
if (in_array('updated_at', $reports_columns)) {
    $columns_to_select[] = 'updated_at';
}
if (in_array('completed_image', $reports_columns)) {
    $columns_to_select[] = 'completed_image';
}

$columns_str = implode(', ', $columns_to_select);

// Get technician names for assigned reports
$technician_query = "SELECT u.user_id, u.full_name FROM users u WHERE u.role = 'Technician'";
$tech_result = mysqli_query($conn, $technician_query);
$technicians = [];
while($tech = mysqli_fetch_assoc($tech_result)) {
    $technicians[$tech['user_id']] = $tech['full_name'];
}

// Get user's hostel block and room number
$user_query = mysqli_query($conn, "SELECT hostel_block, room_no FROM users WHERE user_id = '$user_id'");
$user_info = mysqli_fetch_assoc($user_query);
$user_hostel_block = $user_info['hostel_block'] ?? '';
$user_room_no = $user_info['room_no'] ?? '';

// Handle filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Build base query
$sql = "SELECT $columns_str FROM reports WHERE user_id = '$user_id'";

// Apply status filter
if ($status_filter != 'all') {
    $sql .= " AND status = '$status_filter'";
}

// Apply search filter
if (!empty($search_query)) {
    $sql .= " AND (issue_description LIKE '%$search_query%'";
    if (in_array('facility_name', $reports_columns)) {
        $sql .= " OR facility_name LIKE '%$search_query%'";
    }
    if (in_array('progress_notes', $reports_columns)) {
        $sql .= " OR progress_notes LIKE '%$search_query%'";
    }
    $sql .= ")";
}

// Apply sorting
switch ($sort_by) {
    case 'date_asc':
        $sql .= " ORDER BY report_date ASC";
        break;
    case 'priority':
        if (in_array('priority', $reports_columns)) {
            $sql .= " ORDER BY FIELD(priority, 'Urgent', 'High', 'Medium', 'Low')";
        } else {
            $sql .= " ORDER BY report_date DESC";
        }
        break;
    case 'status':
        $sql .= " ORDER BY FIELD(status, 'Pending', 'Ongoing', 'Completed')";
        break;
    default:
        $sql .= " ORDER BY report_date DESC";
        break;
}

$result = mysqli_query($conn, $sql);

// Get statistics
$stats_sql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
    FROM reports WHERE user_id = '$user_id'";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - HFRS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        :root {
            --primary: #0a1930;
            --secondary: #00a8ff;
            --success: #2ed573;
            --warning: #ffa502;
            --danger: #ff4757;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --light-blue: #e6f7ff;
            --light-green: #e6ffe6;
            --light-orange: #fff7e6;
        }
        
        body {
            background-color: #f4f6fa;
            color: #333;
        }
        
        /* Navbar - UNCHANGED */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 60px;
            background-color: var(--primary);
            color: white;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo img {
            height: 45px;
        }
        
        .logo h1 {
            font-size: 22px;
            font-weight: 600;
        }
        
        .navbar ul {
            list-style: none;
            display: flex;
            gap: 30px;
        }
        
        .navbar a {
            text-decoration: none;
            color: white;
            font-weight: 500;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .navbar a:hover {
            color: var(--secondary);
        }
        
        /* Reports Container */
        .reports-container {
            padding: 30px 60px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: var(--gray);
            font-size: 16px;
        }
        
        /* Statistics Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
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
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-icon.total { background: rgba(10, 25, 48, 0.1); color: var(--primary); }
        .stat-icon.pending { background: rgba(255, 165, 2, 0.1); color: var(--warning); }
        .stat-icon.ongoing { background: rgba(0, 168, 255, 0.1); color: var(--secondary); }
        .stat-icon.completed { background: rgba(46, 213, 115, 0.1); color: var(--success); }
        
        .stat-content h3 {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-content .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }
        
        /* Filters Bar */
        .filters-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: var(--primary);
            font-size: 14px;
            white-space: nowrap;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }
        
        .filter-group input {
            min-width: 250px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0170b5;
        }
        
        .btn-secondary {
            background: #f1f2f6;
            color: var(--primary);
            border: 2px solid #e1e5e9;
        }
        
        .btn-secondary:hover {
            background: #dfe4ea;
            border-color: var(--secondary);
        }
        
        /* Reports Grid */
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* ======================================= */
        /* ENHANCED REPORT CARD DESIGN - OPTION 1  */
        /* ======================================= */
        
        .report-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .report-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(10, 25, 48, 0.12);
            border-color: #c2e0ff;
        }
        
        /* Card Header with Gradient */
        .report-header {
            background: linear-gradient(135deg, var(--primary) 0%, #1a3a5f 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .report-id-section {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .report-id-label {
            font-size: 12px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-id {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .report-id i {
            font-size: 16px;
            color: var(--secondary);
        }
        
        .report-header-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }
        
        .report-date {
            font-size: 13px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .report-date i {
            font-size: 12px;
        }
        
        /* Card Body */
        .report-body {
            padding: 24px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        
        .report-facility {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .report-facility i {
            color: var(--secondary);
            font-size: 16px;
        }
        
        .report-description {
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            background: #f8fafc;
            padding: 12px 15px;
            border-radius: 8px;
            border-left: 3px solid var(--secondary);
        }
        
        /* Progress Tabs */
        .progress-tabs {
            display: flex;
            background: #f8fafc;
            border-radius: 10px;
            padding: 4px;
            margin: 10px 0;
        }
        
        .progress-tab {
            flex: 1;
            text-align: center;
            padding: 10px 5px;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            border-radius: 8px;
            cursor: default;
            transition: all 0.3s;
            position: relative;
        }
        
        .progress-tab.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .progress-tab.completed::before {
            content: '✓';
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--success);
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Enhanced Details Grid */
        .report-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin: 10px 0;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: #f8fafc;
            border-radius: 8px;
            transition: background 0.2s;
        }
        
        .detail-item:hover {
            background: #f1f5f9;
        }
        
        .detail-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(0, 168, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
            font-size: 14px;
        }
        
        .detail-content {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
        }
        
        /* Image Gallery */
        .image-gallery {
            margin-top: 15px;
        }
        
        .image-section {
            margin-bottom: 15px;
        }
        
        .image-section h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .image-preview-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .image-preview {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .image-preview:hover {
            transform: scale(1.05);
            border-color: var(--secondary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Enhanced Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { 
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-ongoing { 
            background: linear-gradient(135deg, #cce5ff 0%, #a6d0ff 100%);
            color: #004085;
            border: 1px solid #a6d0ff;
        }
        
        .status-completed { 
            background: linear-gradient(135deg, #d4edda 0%, #b8e6c3 100%);
            color: #155724;
            border: 1px solid #b8e6c3;
        }
        
        /* Enhanced Priority Badge */
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .priority-low { 
            background: linear-gradient(135deg, #e6ffe6 0%, #d4fcd4 100%);
            color: #155724;
            border: 1px solid #d4fcd4;
        }
        
        .priority-medium { 
            background: linear-gradient(135deg, #fff7e6 0%, #ffedcc 100%);
            color: #856404;
            border: 1px solid #ffedcc;
        }
        
        .priority-high { 
            background: linear-gradient(135deg, #ffe6e6 0%, #ffd4d4 100%);
            color: #721c24;
            border: 1px solid #ffd4d4;
        }
        
        .priority-urgent { 
            background: linear-gradient(135deg, #ff4757 0%, #ff2e43 100%);
            color: white;
            border: 1px solid #ff2e43;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.4); }
            70% { box-shadow: 0 0 0 8px rgba(255, 71, 87, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 71, 87, 0); }
        }
        
        /* Card Footer - UPDATED: Removed share and edit buttons */
        .report-footer {
            padding: 18px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid #e2e8f0;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-icon:hover {
            background: var(--secondary);
            color: white;
            border-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        /* Progress Notes */
        .progress-notes {
            background: #f1f8ff;
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 10px;
            border-left: 3px solid var(--secondary);
        }
        
        .progress-notes h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .progress-notes p {
            font-size: 13px;
            color: var(--gray);
            line-height: 1.5;
        }
        
        /* Empty State */
        .no-reports {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1 / -1;
        }
        
        .no-reports i {
            font-size: 60px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }
        
        .no-reports h3 {
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .no-reports p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto 25px;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 500px) {
            .report-details-grid {
                grid-template-columns: 1fr;
            }
            
            .report-footer {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
  <header class="navbar">
    <div class="logo">
      <img src="images/utemlogo3.png" alt="UTeM Logo">
      <h1>Hostel Facilities Report System</h1>
    </div>
    <nav>
      <ul>
        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="report.php"><i class="fas fa-plus-circle"></i> Report Issue</a></li>
        <li><a href="myreport.php" class="active"><i class="fas fa-list-alt"></i> My Reports</a></li>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>
  </header>

    <!-- Reports Container -->
    <div class="reports-container">
        <div class="page-header">
            <h1><i class="fas fa-file-alt"></i> My Reports</h1>
            <p>View and manage all your submitted maintenance reports</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Reports</h3>
                    <div class="number"><?php echo $stats['total'] ?? 0; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>Pending</h3>
                    <div class="number"><?php echo $stats['pending'] ?? 0; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon ongoing">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <h3>Ongoing</h3>
                    <div class="number"><?php echo $stats['ongoing'] ?? 0; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>Completed</h3>
                    <div class="number"><?php echo $stats['completed'] ?? 0; ?></div>
                </div>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="filters-bar">
            <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                <div class="filter-group">
                    <label for="status"><i class="fas fa-filter"></i> Status:</label>
                    <select name="status" id="status">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Reports</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Ongoing" <?php echo $status_filter == 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort"><i class="fas fa-sort"></i> Sort by:</label>
                    <select name="sort" id="sort">
                        <option value="date_desc" <?php echo $sort_by == 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="date_asc" <?php echo $sort_by == 'date_asc' ? 'selected' : ''; ?>>Oldest First</option>
                        <?php if (in_array('priority', $reports_columns)): ?>
                        <option value="priority" <?php echo $sort_by == 'priority' ? 'selected' : ''; ?>>Priority</option>
                        <?php endif; ?>
                        <option value="status" <?php echo $sort_by == 'status' ? 'selected' : ''; ?>>Status</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search"><i class="fas fa-search"></i> Search:</label>
                    <input type="text" name="search" id="search" placeholder="Search reports..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                
                <a href="myreport.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Clear Filters
                </a>
            </form>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($report = mysqli_fetch_assoc($result)): ?>
                    <?php 
                    // Determine which progress tabs are active
                    $pending_active = $report['status'] == 'Pending' ? 'active' : '';
                    $ongoing_active = $report['status'] == 'Ongoing' ? 'active' : '';
                    $completed_active = $report['status'] == 'Completed' ? 'active' : '';
                    
                    $pending_completed = in_array($report['status'], ['Ongoing', 'Completed']) ? 'completed' : '';
                    $ongoing_completed = $report['status'] == 'Completed' ? 'completed' : '';
                    
                    // Get status badge class
                    $status_class = 'status-' . strtolower($report['status']);
                    
                    // Get priority badge class if exists
                    $priority_badge = '';
                    if (isset($report['priority']) && !empty($report['priority'])) {
                        $priority_class = 'priority-' . strtolower($report['priority']);
                        $priority_label = $report['priority'];
                        $priority_icon = '';
                        
                        switch(strtolower($report['priority'])) {
                            case 'urgent':
                                $priority_icon = 'fas fa-exclamation-triangle';
                                break;
                            case 'high':
                                $priority_icon = 'fas fa-exclamation-circle';
                                break;
                            case 'medium':
                                $priority_icon = 'fas fa-minus-circle';
                                break;
                            case 'low':
                                $priority_icon = 'fas fa-arrow-down-circle';
                                break;
                            default:
                                $priority_icon = 'fas fa-flag';
                        }
                        
                        $priority_badge = "<span class='priority-badge $priority_class'><i class='$priority_icon'></i> $priority_label</span>";
                    }
                    
                    // Format dates
                    $report_date = date('M d, Y', strtotime($report['report_date']));
                    $updated_date = isset($report['updated_at']) && !empty($report['updated_at']) ? 
                        date('M d, Y', strtotime($report['updated_at'])) : $report_date;
                    
                    // Use report-specific hostel block and room number if available, otherwise use user's info
                    $report_hostel_block = $report['hostel_block'] ?? $user_hostel_block;
                    $report_room_number = $report['room_number'] ?? $user_room_no;
                    ?>
                    
                    <div class="report-card">
                        <!-- Card Header -->
                        <div class="report-header">
                            <div class="report-id-section">
                                <div class="report-id-label">Report ID</div>
                                <div class="report-id">
                                    <i class="fas fa-hashtag"></i>
                                    <?php echo str_pad($report['report_id'], 3, '0', STR_PAD_LEFT); ?>
                                </div>
                            </div>
                            <div class="report-header-right">
                                <div class="report-date">
                                    <i class="far fa-calendar"></i>
                                    Submitted: <?php echo $report_date; ?>
                                </div>
                                <?php if($priority_badge): ?>
                                    <?php echo $priority_badge; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="report-body">
                            <!-- Facility/Location -->
                            <?php if(isset($report['facility_name']) && !empty($report['facility_name'])): ?>
                            <div class="report-facility">
                                <i class="fas fa-building"></i>
                                <?php echo htmlspecialchars($report['facility_name']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Issue Description -->
                            <div class="report-description">
                                <?php echo htmlspecialchars($report['issue_description']); ?>
                            </div>
                            
                            <!-- Progress Tabs -->
                            <div class="progress-tabs">
                                <div class="progress-tab <?php echo $pending_active . ' ' . $pending_completed; ?>">
                                    Submitted
                                </div>
                                <div class="progress-tab <?php echo $ongoing_active . ' ' . $ongoing_completed; ?>">
                                    In Progress
                                </div>
                                <div class="progress-tab <?php echo $completed_active; ?>">
                                    Completed
                                </div>
                            </div>
                            
                            <!-- Details Grid -->
                            <div class="report-details-grid">
                                <?php if(!empty($report_hostel_block)): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-hotel"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Hostel Block</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($report_hostel_block); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if(!empty($report_room_number)): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-door-closed"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Room Number</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($report_room_number); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if(isset($report['assigned_to']) && !empty($report['assigned_to']) && isset($technicians[$report['assigned_to']])): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-user-cog"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Assigned Technician</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($technicians[$report['assigned_to']]); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Last Updated</div>
                                        <div class="detail-value"><?php echo $updated_date; ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Progress Notes -->
                            <?php if(isset($report['progress_notes']) && !empty($report['progress_notes'])): ?>
                            <div class="progress-notes">
                                <h4><i class="fas fa-sticky-note"></i> Technician Notes</h4>
                                <p><?php echo htmlspecialchars($report['progress_notes']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Image Gallery -->
                            <?php if((isset($report['image_path']) && !empty($report['image_path'])) || (isset($report['completed_image']) && !empty($report['completed_image']))): ?>
                            <div class="image-gallery">
                                <?php if(isset($report['image_path']) && !empty($report['image_path'])): ?>
                                <div class="image-section">
                                    <h4><i class="fas fa-camera"></i> Issue Photos</h4>
                                    <div class="image-preview-container">
                                        <?php 
                                        // Handle single image or multiple images
                                        $images = explode(',', $report['image_path']);
                                        foreach($images as $image):
                                            if(!empty(trim($image))):
                                        ?>
                                        <div class="image-preview">
                                            <img src="<?php echo htmlspecialchars(trim($image)); ?>" alt="Issue Photo">
                                        </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if(isset($report['completed_image']) && !empty($report['completed_image'])): ?>
                                <div class="image-section">
                                    <h4><i class="fas fa-check-circle"></i> Completed Photos</h4>
                                    <div class="image-preview-container">
                                        <?php 
                                        $completed_images = explode(',', $report['completed_image']);
                                        foreach($completed_images as $image):
                                            if(!empty(trim($image))):
                                        ?>
                                        <div class="image-preview">
                                            <img src="<?php echo htmlspecialchars(trim($image)); ?>" alt="Completed Photo">
                                        </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Card Footer - UPDATED: Only View Details button -->
                        <div class="report-footer">
                            <div class="status-badge <?php echo $status_class; ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo $report['status']; ?>
                            </div>
                            <div class="action-buttons">
                                <button class="btn btn-primary" title="View Full Details" onclick="viewReportDetails(<?php echo $report['report_id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- No Reports State -->
                <div class="no-reports">
                    <i class="fas fa-inbox"></i>
                    <h3>No Reports Found</h3>
                    <p>You haven't submitted any maintenance reports yet, or no reports match your current filters.</p>
                    <a href="report.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Submit Your First Report
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Function to view report details
        function viewReportDetails(reportId) {
            // Redirect to a new page for detailed view
            window.location.href = 'report_details.php?id=' + reportId;
        }
        
        // Image preview modal
        document.addEventListener('DOMContentLoaded', function() {
            const imagePreviews = document.querySelectorAll('.image-preview');
            imagePreviews.forEach(preview => {
                preview.addEventListener('click', function() {
                    const imgSrc = this.querySelector('img').src;
                    
                    // Create a modal to view the image
                    const modal = document.createElement('div');
                    modal.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.9);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 10000;
                        cursor: zoom-out;
                    `;
                    modal.innerHTML = `
                        <img src="${imgSrc}" style="max-width: 90%; max-height: 90%; object-fit: contain;" />
                        <button style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 30px; cursor: pointer;">×</button>
                    `;
                    document.body.appendChild(modal);
                    
                    modal.addEventListener('click', function(e) {
                        if (e.target.tagName === 'BUTTON' || e.target === modal) {
                            document.body.removeChild(modal);
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
<?php
// Close database connection
mysqli_close($conn);
?>