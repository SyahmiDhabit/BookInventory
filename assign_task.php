<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle task assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_task'])) {
    $report_id = mysqli_real_escape_string($conn, $_POST['report_id']);
    $technician_id = mysqli_real_escape_string($conn, $_POST['technician_id']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $estimated_hours = mysqli_real_escape_string($conn, $_POST['estimated_hours'] ?? null);
    $assignment_notes = mysqli_real_escape_string($conn, $_POST['assignment_notes'] ?? '');
    
    $update_report = "UPDATE reports SET assigned_to = '$technician_id', status = 'Ongoing', priority = '$priority', updated_at = NOW() WHERE report_id = '$report_id'";
    
    if (mysqli_query($conn, $update_report)) {
        // Create assignment record
        $insert_assignment = "INSERT INTO assignments (report_id, technician_id, assigned_by, assignment_notes, estimated_hours, assign_date) 
                            VALUES ('$report_id', '$technician_id', '{$_SESSION['user_id']}', '$assignment_notes', '$estimated_hours', NOW())";
        mysqli_query($conn, $insert_assignment);
        
        // Update technician work status to Busy
        mysqli_query($conn, "UPDATE users SET work_status = 'Busy' WHERE user_id = '$technician_id'");
        
        echo "<script>window.location.href = 'assign_task.php?success=1';</script>";
        exit();
    } else {
        $error_message = "Error assigning task: " . mysqli_error($conn);
    }
}

if (isset($_GET['success'])) {
    $success_message = "Task assigned successfully!";
}

// FIXED QUERY: Remove PHP comments from SQL string
$reports_query = "SELECT 
    r.*,
    u.full_name as reporter_name,
    u.hostel_block as reporter_hostel_block,
    u.room_no as reporter_room_no,
    r.image_path
FROM reports r
LEFT JOIN users u ON r.user_id = u.user_id
WHERE r.status = 'Pending' 
ORDER BY
    CASE priority
        WHEN 'Urgent' THEN 1
        WHEN 'High' THEN 2
        WHEN 'Medium' THEN 3
        WHEN 'Low' THEN 4
        ELSE 5
    END, 
    r.report_date ASC";

$reports_result = mysqli_query($conn, $reports_query);
$pending_reports = [];
if ($reports_result) {
    $pending_reports = mysqli_fetch_all($reports_result, MYSQLI_ASSOC);
}

// Fetch ALL technicians with their expertise (not filtering by availability)
$tech_query = "SELECT 
    u.user_id, u.full_name, u.email, u.work_status,
    tp.skills, tp.experience_years,
    GROUP_CONCAT(DISTINCT ts.skill_name SEPARATOR ', ') as technician_skills,
    COUNT(DISTINCT r.report_id) as current_tasks
FROM users u
LEFT JOIN technician_profiles tp ON u.user_id = tp.tech_id
LEFT JOIN technician_skills ts ON u.user_id = ts.technician_id
LEFT JOIN reports r ON u.user_id = r.assigned_to AND r.status IN ('Ongoing', 'Assigned')
WHERE u.role = 'Technician' 
    AND u.status = 'Active'
GROUP BY u.user_id
ORDER BY u.full_name";

$tech_result = mysqli_query($conn, $tech_query);
$all_technicians = [];
if ($tech_result) {
    $all_technicians = mysqli_fetch_all($tech_result, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Tasks - HFRS Admin</title>
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
        /* Message Styles */
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
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* ====== REKA BENTUK BARU: CONTENT LAYOUT ====== */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }
        .pending-count {
            background: var(--primary);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        /* ====== REKA BENTUK BARU: PENDING REPORTS CARDS ====== */
        .reports-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .report-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-color: var(--primary);
        }
        .report-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
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
        .report-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        }
        .report-image-placeholder {
            width: 100%;
            height: 180px;
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
            font-size: 48px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        .report-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .report-date {
            font-size: 13px;
            color: var(--gray);
        }
        .assign-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .assign-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        /* ====== ALL TECHNICIANS SECTION ====== */
        .technicians-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .technician-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
        }
        .technician-card:hover {
            background: white;
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        .technician-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .tech-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--secondary) 0%, #0d9488 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }
        /* ====== UPDATED MODAL STYLES FOR EXPERTISE ====== */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }
        /* Technician Expertise Cards */
        .expertise-container {
            margin-top: 15px;
        }
        .expertise-card {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .expertise-card:hover {
            border-color: var(--primary);
            background: white;
            transform: translateY(-2px);
        }
        .expertise-card.selected {
            border-color: var(--primary);
            background: #eff6ff;
        }
        .expertise-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .expertise-header h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        .expertise-details {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 5px;
        }
        .expertise-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }
        .skill-tag {
            background: #e0e7ff;
            color: var(--primary-dark);
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .experience-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin-left: 8px;
        }
        .current-tasks {
            background: #f3f4f6;
            color: #6b7280;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        /* Workload Indicator */
        .workload-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: 5px;
        }
        .workload-light {
            background: var(--secondary);
        }
        .workload-medium {
            background: var(--warning);
        }
        .workload-heavy {
            background: var(--danger);
        }
        /* Modal Buttons */
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .modal-btn.cancel {
            background: #f3f4f6;
            color: var(--dark);
        }
        .modal-btn.assign {
            background: var(--primary);
            color: white;
        }
        .modal-btn.assign:hover {
            background: var(--primary-dark);
        }
        .modal-btn:disabled {
            background: #d1d5db;
            cursor: not-allowed;
        }
        /* Task Info in Modal */
        .task-info {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .task-info h4 {
            margin: 0 0 10px 0;
            color: var(--dark);
        }
        .task-info p {
            margin: 5px 0;
            color: var(--gray);
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
                <div class="admin-avatar-sidebar">A</div>
                <div class="admin-text-sidebar">
                    <h4>admin</h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>
        <nav>
            <ul>
               <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
                <li><a href="admin_reports.php"><i class="fa-solid fa-file-alt"></i> <span>Manage Reports</span></a></li>
                <li><a href="admin_technicians.php" ><i class="fa-solid fa-user-gear"></i> <span>Technicians</span></a></li>
                <li><a href="admin_pending_technicians.php"><i class="fa-solid fa-hourglass-half"></i> <span>Pending Registrations</span></a></li>
                <li><a href="admin_students.php"><i class="fa-solid fa-users"></i> <span>Students</span></a></li>
                <li><a href="assign_task.php" class="active"><i class="fa-solid fa-share-nodes"></i> <span>Assign Tasks</span></a></li>
                <li><a href="logout.php" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
            </ul>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- HEADER -->
        <div class="header">
            <div>
                <h1 class="page-title">Assign Tasks</h1>
                <p class="page-subtitle">Assign pending reports to technicians</p>
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

        <!-- CONTENT GRID -->
        <div class="content-grid">
            <!-- LEFT: Pending Reports -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Pending Reports</h2>
                    <span class="pending-count"><?php echo count($pending_reports); ?> Pending</span>
                </div>
                
                <div class="reports-container">
                    <?php if (empty($pending_reports)): ?>
                        <div class="report-card">
                            <p>No pending reports at the moment.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_reports as $report): ?>
                        <div class="report-card">
                            <!-- Report Header -->
                            <div class="report-card-header">
                                <div>
                                    <h3 class="report-title">Report #<?php echo $report['report_id']; ?></h3>
                                    <p class="report-id">Reported by: <?php echo htmlspecialchars($report['reporter_name'] ?? 'Unknown'); ?></p>
                                </div>
                                <span class="priority-badge priority-<?php echo strtolower($report['priority']); ?>">
                                    <?php echo htmlspecialchars($report['priority']); ?>
                                </span>
                            </div>
                            
                            <!-- Report Details Grid -->
                            <div class="report-details-grid">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h4>Location</h4>
                                        <?php if (!empty($report['reporter_hostel_block']) && !empty($report['reporter_room_no'])): ?>
                                            <p><?php echo htmlspecialchars($report['reporter_hostel_block'] . ' - Room ' . $report['reporter_room_no']); ?></p>
                                        <?php elseif (!empty($report['location'])): ?>
                                            <p><?php echo htmlspecialchars($report['location']); ?></p>
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
                                        <p><?php echo date('d M Y, h:i A', strtotime($report['report_date'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h4>Reporter ID</h4>
                                        <p><?php echo htmlspecialchars('User #' . $report['user_id']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <div class="detail-content">
                                        <h4>Category</h4>
                                        <p><?php echo htmlspecialchars($report['facility_name'] ?: 'General'); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Issue Description -->
                            <div class="report-description">
                                <strong>Issue Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($report['issue_description'])); ?>
                            </div>
                            
                            <!-- Image Display -->
                            <?php if (!empty($report['image_path'])): ?>
                                <div class="report-image" style="margin-bottom: 20px;">
                                    <h4 style="margin-bottom: 10px; font-size: 14px; color: var(--gray);">
                                        <i class="fas fa-image"></i> Attached Image
                                    </h4>
                                    <a href="<?php echo htmlspecialchars($report['image_path']); ?>" target="_blank" style="display: block;">
                                        <img src="<?php echo htmlspecialchars($report['image_path']); ?>" 
                                             alt="Report Image" 
                                             style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb;">
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="report-image-placeholder">
                                    <i class="fas fa-image"></i>
                                    <p>No image uploaded for this report</p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Report Actions -->
                            <div class="report-actions">
                                <div class="report-date">
                                    <?php
                                    $days_pending = floor((time() - strtotime($report['report_date'])) / (60 * 60 * 24));
                                    echo "[Pending for " . $days_pending . " days]";
                                    ?>
                                </div>
                                
                                <!-- Assign Button -->
                                <button type="button" class="assign-btn" 
                                        onclick="showAssignModal(<?php echo $report['report_id']; ?>, '<?php echo $report['priority']; ?>', '<?php echo htmlspecialchars(addslashes($report['facility_name'] ?: 'General')); ?>', '<?php echo htmlspecialchars(addslashes($report['issue_description'])); ?>')">
                                    <i class="fas fa-user-check"></i> Assign This Task
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- RIGHT: All Technicians -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Technicians</h2>
                    <span class="pending-count"><?php echo count($all_technicians); ?> Total</span>
                </div>
                <div class="technicians-container">
                    <?php if (empty($all_technicians)): ?>
                        <p>No technicians registered in the system.</p>
                    <?php else: ?>
                        <?php foreach ($all_technicians as $tech): ?>
                            <div class="technician-card">
                                <div class="technician-info">
                                    <div class="tech-avatar">
                                        <?php echo strtoupper(substr($tech['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h3><?php echo htmlspecialchars($tech['full_name']); ?></h3>
                                        <p><?php echo htmlspecialchars($tech['email']); ?></p>
                                        <?php if ($tech['skills']): ?>
                                            <p style="font-size: 12px; color: var(--primary); margin-top: 5px;">
                                                <i class="fas fa-star"></i> <?php echo htmlspecialchars($tech['skills']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($tech['current_tasks'] > 0): ?>
                                            <p style="font-size: 12px; color: var(--warning); margin-top: 3px;">
                                                <i class="fas fa-tasks"></i> <?php echo $tech['current_tasks']; ?> ongoing task(s)
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for assigning to specific technician -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 20px;">Assign Task</h2>
            
            <!-- Task Information -->
            <div class="task-info">
                <h4>Task Details</h4>
                <p><strong>Report ID:</strong> <span id="modalReportId"></span></p>
                <p><strong>Facility:</strong> <span id="modalFacility"></span></p>
                <p><strong>Priority:</strong> <span id="modalPriorityBadge"></span></p>
                <p><strong>Description:</strong> <span id="modalDescription"></span></p>
            </div>
            
            <form id="assignForm" method="POST" action="assign_task.php">
                <input type="hidden" name="report_id" id="modal_report_id">
                <input type="hidden" name="priority" id="modal_priority">
                
                <div class="form-group">
                    <label for="technician_id">Select Technician:</label>
                    <p style="font-size: 13px; color: var(--gray); margin-top: -5px; margin-bottom: 10px;">
                        Click on a technician card to select
                    </p>
                    
                    <div class="expertise-container" id="technicianOptions">
                        <?php foreach ($all_technicians as $tech): 
                            // Determine workload indicator
                            $workload_class = 'workload-light';
                            if ($tech['current_tasks'] > 2) {
                                $workload_class = 'workload-heavy';
                            } elseif ($tech['current_tasks'] > 0) {
                                $workload_class = 'workload-medium';
                            }
                        ?>
                            <div class="expertise-card" onclick="selectTechnician(<?php echo $tech['user_id']; ?>)">
                                <div class="expertise-header">
                                    <h4>
                                        <?php echo htmlspecialchars($tech['full_name']); ?>
                                        <?php if ($tech['current_tasks'] > 0): ?>
                                            <span class="workload-indicator <?php echo $workload_class; ?>" 
                                                  title="<?php echo $tech['current_tasks']; ?> ongoing task(s)"></span>
                                        <?php endif; ?>
                                    </h4>
                                    <?php if ($tech['current_tasks'] > 0): ?>
                                        <span class="current-tasks">
                                            <i class="fas fa-tasks"></i> <?php echo $tech['current_tasks']; ?> ongoing
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="expertise-details">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($tech['email']); ?>
                                </div>
                                
                                <?php if ($tech['experience_years'] > 0): ?>
                                    <div class="expertise-details">
                                        <i class="fas fa-calendar-alt"></i> <?php echo $tech['experience_years']; ?> years experience
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($tech['skills'])): ?>
                                    <div class="expertise-details">
                                        <i class="fas fa-star"></i> <strong>Expertise:</strong> <?php echo htmlspecialchars($tech['skills']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($tech['technician_skills'])): ?>
                                    <div class="expertise-skills">
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
                                <?php endif; ?>
                                
                                <input type="radio" name="technician_id" value="<?php echo $tech['user_id']; ?>" 
                                       id="tech_<?php echo $tech['user_id']; ?>" style="display: none;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            
                
                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="assign_task" class="modal-btn assign" id="assignBtn" disabled>
                        <i class="fas fa-user-check"></i> Assign Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedTechnicianId = null;
        
        function showAssignModal(reportId, priority, facility, description) {
            event.preventDefault();
            
            // Update modal content
            document.getElementById('modal_report_id').value = reportId;
            document.getElementById('modal_priority').value = priority;
            document.getElementById('modalReportId').textContent = '#' + reportId;
            document.getElementById('modalFacility').textContent = facility;
            document.getElementById('modalDescription').textContent = description.substring(0, 100) + (description.length > 100 ? '...' : '');
            
            // Update priority badge
            const priorityBadge = document.getElementById('modalPriorityBadge');
            priorityBadge.innerHTML = `<span class="priority-badge priority-${priority.toLowerCase()}">${priority}</span>`;
            
            // Reset selection
            selectedTechnicianId = null;
            document.querySelectorAll('.expertise-card.selected').forEach(card => {
                card.classList.remove('selected');
            });
            document.getElementById('assignBtn').disabled = true;
            document.getElementById('assignForm').reset();
            
            // Show modal
            document.getElementById('assignModal').style.display = 'block';
        }
        
        function selectTechnician(techId) {
            selectedTechnicianId = techId;
            
            // Update UI
            document.querySelectorAll('.expertise-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            const selectedCard = document.querySelector(`input[value="${techId}"]`).parentElement;
            selectedCard.classList.add('selected');
            
            // Update radio button
            document.querySelector(`input[value="${techId}"]`).checked = true;
            
            // Enable assign button
            document.getElementById('assignBtn').disabled = false;
        }
        
        function closeModal() {
            document.getElementById('assignModal').style.display = 'none';
            selectedTechnicianId = null;
        }
        
        // Form validation
        document.getElementById('assignForm').addEventListener('submit', function(e) {
            if (!selectedTechnicianId) {
                e.preventDefault();
                alert('Please select a technician');
                return false;
            }
            
            if (!document.getElementById('estimated_hours').value) {
                e.preventDefault();
                alert('Please enter estimated time');
                return false;
            }
            
            return true;
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('assignModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>