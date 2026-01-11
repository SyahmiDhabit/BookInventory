<?php
session_start();
include("db_connect.php");
// Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle search + filter + pagination
$search = $_GET['search'] ?? "";
$status = $_GET['status'] ?? "";
$priority = $_GET['priority'] ?? "";
$technician = $_GET['technician'] ?? "";
$date_from = $_GET['date_from'] ?? "";
$date_to = $_GET['date_to'] ?? "";
$sort_by = $_GET['sort_by'] ?? "report_date";
$sort_order = $_GET['sort_order'] ?? "DESC";

// Bulk action handling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bulk_action'])) {
    if (!empty($_POST['selected_reports'])) {
        $report_ids = implode(',', array_map('intval', $_POST['selected_reports']));
        switch ($_POST['bulk_action']) {
            case 'assign':
                header("Location: admin_assign_bulk.php?ids=" . $report_ids);
                exit();
            case 'delete':
                // Delete selected reports
                $delete_query = "DELETE FROM reports WHERE report_id IN ($report_ids)";
                if (mysqli_query($conn, $delete_query)) {
                    $success_message = count($_POST['selected_reports']) . " report(s) deleted successfully.";
                } else {
                    $error_message = "Error deleting reports: " . mysqli_error($conn);
                }
                break;
            case 'pending':
                $update_query = "UPDATE reports SET status='Pending' WHERE report_id IN ($report_ids)";
                mysqli_query($conn, $update_query);
                $success_message = count($_POST['selected_reports']) . " report(s) updated to Pending.";
                break;
            case 'ongoing':
                $update_query = "UPDATE reports SET status='Ongoing' WHERE report_id IN ($report_ids)";
                mysqli_query($conn, $update_query);
                $success_message = count($_POST['selected_reports']) . " report(s) updated to Ongoing.";
                break;
            case 'completed':
                $update_query = "UPDATE reports SET status='Completed' WHERE report_id IN ($report_ids)";
                mysqli_query($conn, $update_query);
                $success_message = count($_POST['selected_reports']) . " report(s) updated to Completed.";
                break;
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; // Reduced for better card display
$offset = ($page - 1) * $per_page;

// Build query conditions
$conditions = [];
$params = [];
if (!empty($search)) {
    $conditions[] = "(r.facility_name LIKE ? OR r.issue_description LIKE ? OR u.full_name LIKE ? OR u2.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}
if (!empty($status) && $status !== 'all') {
    $conditions[] = "r.status = ?";
    $params[] = $status;
}
if (!empty($priority) && $priority !== 'all') {
    $conditions[] = "r.priority = ?";
    $params[] = $priority;
}
if (!empty($technician) && $technician !== 'all') {
    $conditions[] = "r.assigned_to = ?";
    $params[] = $technician;
}
if (!empty($date_from)) {
    $conditions[] = "DATE(r.report_date) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $conditions[] = "DATE(r.report_date) <= ?";
    $params[] = $date_to;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM reports r 
                LEFT JOIN users u ON r.assigned_to = u.user_id
                LEFT JOIN users u2 ON r.user_id = u2.user_id";
if (!empty($conditions)) {
    $count_query .= " WHERE " . implode(" AND ", $conditions);
}
$stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result_count = mysqli_stmt_get_result($stmt);
$total_rows = mysqli_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_rows / $per_page);

// Build main query - UPDATED TO GET REPORTER LOCATION
$query = "SELECT r.*,
    u.full_name as technician_name,
    u.email as technician_email,
    u2.full_name as reporter_name,
    u2.hostel_block as reporter_hostel_block,
    u2.room_no as reporter_room_no,
    f.facility_name as facility_name
FROM reports r
LEFT JOIN users u ON r.assigned_to = u.user_id
LEFT JOIN users u2 ON r.user_id = u2.user_id
LEFT JOIN facilities f ON r.facility_id = f.facility_id";
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Add sorting
$valid_sort_columns = ['report_date', 'priority', 'status', 'facility_name', 'updated_at'];
$sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'report_date';
$sort_order = $sort_order === 'ASC' ? 'ASC' : 'DESC';
$query .= " ORDER BY $sort_by $sort_order";

// Add pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Execute query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    $types = str_repeat('s', count($params) - 2) . 'ii';
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get technicians for filter dropdown
$technicians_result = mysqli_query($conn, "SELECT user_id, full_name FROM users WHERE role = 'Technician'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - HFRS Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        .message.error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* FILTER SECTION */
        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .filter-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
        }
        .filter-header .results-count {
            color: var(--gray);
            font-size: 14px;
            background: #f3f4f6;
            padding: 6px 12px;
            border-radius: 20px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-group label {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
        }
        .filter-input, .filter-select {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
            color: var(--dark);
            width: 100%;
        }
        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        /* BUTTONS */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .btn-secondary {
            background: #f3f4f6;
            color: var(--dark);
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        /* REPORTS CONTAINER - NEW CARD DESIGN */
        .reports-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .report-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
            position: relative;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }
        .report-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
        }
        .report-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        .report-id {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }
        /* Priority Badges */
        .priority-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-urgent {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .priority-high {
            background: #fed7aa;
            color: #c2410c;
            border: 1px solid #fdba74;
        }
        .priority-medium {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fcd34d;
        }
        .priority-low {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        /* Status Badges */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        .status-ongoing {
            background: #dbeafe;
            color: #2563eb;
        }
        .status-completed {
            background: #d1fae5;
            color: #059669;
        }
        /* Report Details Grid */
        .report-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .detail-icon {
            width: 36px;
            height: 36px;
            background: #f3f4f6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }
        .detail-content h4 {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 3px;
        }
        .detail-content p {
            font-size: 15px;
            font-weight: 600;
            color: var(--dark);
        }
        .report-description {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
            color: var(--dark);
            max-height: 100px;
            overflow-y: auto;
        }
        /* Image Display */
        .report-image-placeholder {
            width: 100%;
            height: 150px;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: var(--gray);
            border: 2px dashed #d1d5db;
        }
        .report-image-placeholder i {
            font-size: 36px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        .report-image {
            width: 100%;
            margin-bottom: 20px;
        }
        .report-image img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        /* Report Actions */
        .report-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f3f4f6;
        }
        .report-date {
            font-size: 13px;
            color: var(--gray);
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
        .btn-view {
            background: #dbeafe;
            color: var(--primary);
        }
        .btn-view:hover {
            background: #bfdbfe;
        }
        .btn-edit {
            background: #fef3c7;
            color: #d97706;
        }
        .btn-edit:hover {
            background: #fde68a;
        }
        .btn-assign {
            background: #d1fae5;
            color: #059669;
        }
        .btn-assign:hover {
            background: #a7f3d0;
        }
        /* Bulk Actions */
        .bulk-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .bulk-select-all {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 40px;
        }
        .page-link {
            padding: 8px 16px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s;
        }
        .page-link:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        /* Checkbox */
        .report-checkbox {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 20px;
            height: 20px;
            z-index: 1;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--gray);
            cursor: pointer;
            z-index: 2;
        }
        .modal-close:hover {
            color: var(--dark);
        }
        /* Modal Report Card */
        .modal-report-card {
            background: white;
        }
        .modal-report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
        }
        .modal-report-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 5px;
        }
        .modal-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .modal-detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
        }
        .modal-detail-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .modal-detail-content h4 {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 5px;
        }
        .modal-detail-content p {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }
        .modal-description {
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 15px;
            line-height: 1.6;
            color: var(--dark);
        }
        .modal-description strong {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
            color: var(--dark);
        }
        .modal-image-container {
            text-align: center;
            margin-bottom: 25px;
        }
        .modal-image-container img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f3f4f6;
        }
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
                <li><a href="admin_dashboard.php" ><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
                <li><a href="admin_reports.php" class="active"><i class="fa-solid fa-file-alt"></i> <span>Manage Reports</span></a></li>
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
        <!-- HEADER -->
        <div class="header">
            <div>
                <h1 class="page-title">Manage Reports</h1>
                <p class="page-subtitle">View and manage all facility reports</p>
            </div>
            <div class="admin-info">
                <div class="admin-avatar">A</div>
                <div>
                    <h4>Admin</h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <!-- FILTER SECTION -->
        <div class="filter-card">
            <div class="filter-header">
                <h3>Filter Reports</h3>
                <span class="results-count"><?php echo $total_rows; ?> reports found</span>
            </div>
            <form method="GET" action="admin_reports.php">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" class="filter-input" 
                               placeholder="Search reports..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="filter-select">
                            <option value="all" <?php echo $status === 'all' || $status === '' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Ongoing" <?php echo $status === 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority" class="filter-select">
                            <option value="all" <?php echo $priority === 'all' || $priority === '' ? 'selected' : ''; ?>>All Priority</option>
                            <option value="Urgent" <?php echo $priority === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                            <option value="High" <?php echo $priority === 'High' ? 'selected' : ''; ?>>High</option>
                            <option value="Medium" <?php echo $priority === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="Low" <?php echo $priority === 'Low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="technician">Assigned To</label>
                        <select id="technician" name="technician" class="filter-select">
                            <option value="all" <?php echo $technician === 'all' || $technician === '' ? 'selected' : ''; ?>>All Technicians</option>
                            <?php 
                            // Reset pointer for technicians
                            mysqli_data_seek($technicians_result, 0);
                            while($tech = mysqli_fetch_assoc($technicians_result)): 
                            ?>
                                <option value="<?php echo $tech['user_id']; ?>" 
                                        <?php echo $technician == $tech['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tech['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from" class="filter-input" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to" class="filter-input" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="sort_by">Sort By</label>
                        <select id="sort_by" name="sort_by" class="filter-select">
                            <option value="report_date" <?php echo $sort_by === 'report_date' ? 'selected' : ''; ?>>Report Date</option>
                            <option value="priority" <?php echo $sort_by === 'priority' ? 'selected' : ''; ?>>Priority</option>
                            <option value="status" <?php echo $sort_by === 'status' ? 'selected' : ''; ?>>Status</option>
                            <option value="updated_at" <?php echo $sort_by === 'updated_at' ? 'selected' : ''; ?>>Last Updated</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="sort_order">Sort Order</label>
                        <select id="sort_order" name="sort_order" class="filter-select">
                            <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="admin_reports.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        

            <!-- REPORTS CONTAINER - NEW CARD DESIGN -->
            <div class="reports-container">
                <?php if (mysqli_num_rows($result) == 0): ?>
                    <div class="report-card" style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #9ca3af; margin-bottom: 20px;"></i>
                        <h3 style="color: var(--gray); margin-bottom: 10px;">No reports found</h3>
                        <p>Try adjusting your filters or create a new report.</p>
                    </div>
                <?php else: ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="report-card">
                       
                        
                        <!-- Report Header -->
                        <div class="report-card-header">
                            <div>
                                <h3 class="report-title">Report #<?php echo $row['report_id']; ?></h3>
                                <p class="report-id">Reported by: <?php echo htmlspecialchars($row['reporter_name'] ?? 'Unknown'); ?></p>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 5px; align-items: flex-end;">
                                <span class="priority-badge priority-<?php echo strtolower($row['priority']); ?>">
                                    <?php echo htmlspecialchars($row['priority']); ?>
                                </span>
                                <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Report Details Grid -->
                        <div class="report-details-grid">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="detail-content">
                                    <h4>Location</h4>
                                    <?php if (!empty($row['reporter_hostel_block']) && !empty($row['reporter_room_no'])): ?>
                                        <p><?php echo htmlspecialchars($row['reporter_hostel_block'] . ' - Room ' . $row['reporter_room_no']); ?></p>
                                    <?php elseif (!empty($row['location'])): ?>
                                        <p><?php echo htmlspecialchars($row['location']); ?></p>
                                    <?php else: ?>
                                        <p>Not specified</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="detail-content">
                                    <h4>Reported On</h4>
                                    <p><?php echo date('d M Y, h:i A', strtotime($row['report_date'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <div class="detail-content">
                                    <h4>Assigned To</h4>
                                    <p><?php echo !empty($row['technician_name']) ? htmlspecialchars($row['technician_name']) : 'Not assigned'; ?></p>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div class="detail-content">
                                    <h4>Category</h4>
                                    <p><?php echo htmlspecialchars($row['facility_name'] ?: 'General'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Issue Description -->
                        <div class="report-description">
                            <strong>Issue Description:</strong><br>
                            <?php echo nl2br(htmlspecialchars(substr($row['issue_description'], 0, 200))); ?>
                            <?php if (strlen($row['issue_description']) > 200): ?>...<?php endif; ?>
                        </div>
                        
                        <!-- Image Display -->
                        <?php if (!empty($row['image_path'])): ?>
                            <div class="report-image">
                                <a href="<?php echo htmlspecialchars($row['image_path']); ?>" target="_blank">
                                    <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                                         alt="Report Image" 
                                         onerror="this.style.display='none';this.parentElement.innerHTML='<div class=\'report-image-placeholder\'><i class=\'fas fa-image\'></i><p>Image not available</p></div>';">
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="report-image-placeholder">
                                <i class="fas fa-image"></i>
                                <p>No image uploaded</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Report Actions -->
                        <div class="report-actions">
                            <div class="report-date">
                                <?php
                                $days_pending = floor((time() - strtotime($row['report_date'])) / (60 * 60 * 24));
                                $last_updated = !empty($row['updated_at']) ? 
                                    'Updated: ' . date('d M Y', strtotime($row['updated_at'])) : 
                                    'Pending for ' . $days_pending . ' days';
                                echo htmlspecialchars($last_updated);
                                ?>
                            </div>
                            <div class="action-buttons">
                                <!-- UBAH: Guna button untuk modal, bukan link -->
                                <button type="button" onclick="showReportModal(<?php echo $row['report_id']; ?>)" 
                                        class="btn btn-sm btn-view">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                
                                <?php if ($row['status'] == 'Pending'): ?>
                                    <a href="assign_task.php?report=<?php echo $row['report_id']; ?>" 
                                       class="btn btn-sm btn-assign">
                                        <i class="fas fa-user-check"></i> Assign
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php 
                        $params = $_GET;
                        $params['page'] = $page - 1;
                        echo http_build_query($params);
                    ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i> Prev
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?<?php 
                            $params = $_GET;
                            $params['page'] = $i;
                            echo http_build_query($params);
                        ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span class="page-link disabled">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?<?php 
                        $params = $_GET;
                        $params['page'] = $page + 1;
                        echo http_build_query($params);
                    ?>" class="page-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- REPORT DETAIL MODAL -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <button type="button" class="modal-close" onclick="closeReportModal()">
                <i class="fas fa-times"></i>
            </button>
            <div id="modalContent">
                <!-- Modal content akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Function untuk show report modal
        function showReportModal(reportId) {
            // Show loading spinner
            document.getElementById('modalContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 36px; color: var(--primary); margin-bottom: 20px;"></i>
                    <p>Loading report details...</p>
                </div>
            `;
            
            // Show modal
            document.getElementById('reportModal').style.display = 'block';
            
            // Load report details using AJAX
            fetch('get_report_details.php?id=' + reportId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modalContent').innerHTML = `
                        <div class="message error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Error loading report details. Please try again.</span>
                        </div>
                    `;
                });
        }
        
        // Function untuk close modal
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
        }
        
        // Close modal bila click luar modal content
        window.onclick = function(event) {
            var modal = document.getElementById('reportModal');
            if (event.target == modal) {
                closeReportModal();
            }
        }
        
        // Bulk selection functions
        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="selected_reports[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }
        
        function updateSelectAll() {
            const checkboxes = document.querySelectorAll('input[name="selected_reports[]"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const someChecked = Array.from(checkboxes).some(cb => cb.checked);
            const selectAll = document.getElementById('selectAll');
            
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;
        }
        
        // Close modal dengan ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeReportModal();
            }
        });
    </script>
</body>
</html>