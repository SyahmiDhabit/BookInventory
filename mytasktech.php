<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Technician') {
    header("Location: login.php");
    exit();
}

$technician_id = $_SESSION['user_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $report_id = mysqli_real_escape_string($conn, $_POST['report_id']);
        $new_status = mysqli_real_escape_string($conn, $_POST['status']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        
        // VALIDATION: If status is 'Completed', MUST have image
        if ($new_status === 'Completed') {
            $image_uploaded = false;
            $target_file = '';
            
            // Check if image is uploaded
            if (isset($_FILES['completed_image']) && $_FILES['completed_image']['error'] === 0) {
                // Handle completed image upload
                $target_dir = "uploads/completed/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['completed_image']['name']);
                $target_file = $target_dir . $file_name;
                
                // Check file type
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($imageFileType, $allowed_types)) {
                    if (move_uploaded_file($_FILES['completed_image']['tmp_name'], $target_file)) {
                        $image_uploaded = true;
                    } else {
                        $error_message = "Failed to upload image. Please try again.";
                    }
                } else {
                    $error_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
                }
            }
            
            if (!$image_uploaded) {
                $error_message = "You must upload a photo of completed work to mark as 'Completed'!";
                $status_update_allowed = false;
            } else {
                $status_update_allowed = true;
            }
        } else {
            // For other statuses, no image required
            $status_update_allowed = true;
            $target_file = ''; // No image for other statuses
        }
        
        if ($status_update_allowed) {
            // Update the report
            $update_query = "UPDATE reports SET status = '$new_status', updated_at = NOW()";
            
            if ($new_status === 'Completed' && !empty($target_file)) {
                $update_query .= ", completed_image = '$target_file'";
            }
            
            $update_query .= " WHERE report_id = '$report_id'";
            
            if (mysqli_query($conn, $update_query)) {
                // Add status update record
                $insert_update = "INSERT INTO status_updates (report_id, technician_id, new_status, notes) 
                                 VALUES ('$report_id', '$technician_id', '$new_status', '$notes')";
                mysqli_query($conn, $insert_update);
                
                $success_message = "Status updated successfully!";
                
                // Redirect to prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error_message = "Error updating status: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch assigned reports
$reports_query = "SELECT 
    r.*,
    u.full_name as reporter_name,
    u.email as reporter_email,
    u.hostel_block as reporter_hostel_block,
    u.room_no as reporter_room_no,
    f.facility_name
FROM reports r
LEFT JOIN users u ON r.user_id = u.user_id
LEFT JOIN facilities f ON r.facility_id = f.facility_id
WHERE r.assigned_to = '$technician_id' 
AND r.status IN ('Ongoing', 'On Hold')
ORDER BY 
    CASE r.priority
        WHEN 'Urgent' THEN 1
        WHEN 'High' THEN 2
        WHEN 'Medium' THEN 3
        WHEN 'Low' THEN 4
        ELSE 5
    END, 
    r.report_date ASC";

$reports_result = mysqli_query($conn, $reports_query);
$assigned_reports = [];
if ($reports_result) {
    $assigned_reports = mysqli_fetch_all($reports_result, MYSQLI_ASSOC);
}

// Fetch completed reports
$completed_query = "SELECT 
    r.*,
    u.full_name as reporter_name,
    u.hostel_block as reporter_hostel_block,
    u.room_no as reporter_room_no,
    f.facility_name
FROM reports r
LEFT JOIN users u ON r.user_id = u.user_id
LEFT JOIN facilities f ON r.facility_id = f.facility_id
WHERE r.assigned_to = '$technician_id' 
AND r.status = 'Completed'
ORDER BY r.updated_at DESC 
LIMIT 10";

$completed_result = mysqli_query($conn, $completed_query);
$completed_reports = [];
if ($completed_result) {
    $completed_reports = mysqli_fetch_all($completed_result, MYSQLI_ASSOC);
}

// Get technician info
$tech_query = "SELECT full_name, email, phone FROM users WHERE user_id = '$technician_id'";
$tech_result = mysqli_query($conn, $tech_query);
$technician = mysqli_fetch_assoc($tech_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - HFRS Technician</title>
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
        .technician-profile-sidebar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 15px;
            border-radius: 12px;
            margin-top: 15px;
        }
        .technician-avatar-sidebar {
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
        .technician-text-sidebar h4 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .technician-text-sidebar p {
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
        .technician-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .technician-avatar {
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
        /* IMAGE REQUIREMENT STYLES */
        .image-requirement {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #c2410c;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .image-requirement i {
            color: #f59e0b;
            font-size: 16px;
        }
        .image-upload.required {
            border: 2px solid #fbbf24;
            background: #fffbeb;
            padding: 15px;
            border-radius: 8px;
        }
        .image-upload.required label {
            color: #b45309;
            font-weight: 700;
        }
        .file-input.required {
            border: 2px dashed #f59e0b;
            background: #fef3c7;
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .btn:disabled:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .stats-icon.ongoing { background: var(--primary); }
        .stats-icon.completed { background: var(--secondary); }
        .stats-icon.total { background: #8b5cf6; }
        .stats-content h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .stats-content .value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        .stats-content .description {
            font-size: 14px;
            color: var(--gray);
        }
        /* CONTENT TABS */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .tab-button {
            padding: 12px 30px;
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 8px 8px 0 0;
            position: relative;
        }
        .tab-button:hover {
            color: var(--primary);
            background: #f3f4f6;
        }
        .tab-button.active {
            color: var(--primary);
        }
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -7px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        /* REPORTS CONTAINER - CARD DESIGN */
        .reports-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .report-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
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
            cursor: pointer;
            transition: transform 0.3s;
        }
        .report-image img:hover {
            transform: scale(1.02);
        }
        /* Report Actions */
        .report-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f3f4f6;
        }
        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .status-select {
            flex: 1;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
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
        .btn-success {
            background: var(--secondary);
            color: white;
        }
        .btn-success:hover {
            background: #0d9488;
        }
        /* Notes Textarea */
        .notes-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
            margin-bottom: 10px;
        }
        .notes-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        /* Image Upload */
        .image-upload {
            margin-top: 15px;
        }
        .image-upload label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        .file-input {
            width: 100%;
            padding: 10px;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
        }
        /* Completed Image Display */
        .completed-image-container {
            margin-top: 15px;
            text-align: center;
        }
        .completed-image-container h4 {
            margin-bottom: 10px;
            color: var(--dark);
            font-size: 14px;
        }
        /* Modal for Image View */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            overflow-y: auto;
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            margin-top: 5%;
        }
        .modal-close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-close:hover {
            color: #bbb;
        }
        .modal-caption {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
            text-align: center;
            color: #ccc;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>HFRS Technician</h2>
            <p>Task Management</p>
            <div class="technician-profile-sidebar">
                <div class="technician-avatar-sidebar">
                    <?php echo strtoupper(substr($technician['full_name'], 0, 1)); ?>
                </div>
                <div class="technician-text-sidebar">
                    <h4><?php echo htmlspecialchars($technician['full_name']); ?></h4>
                    <p>Technician</p>
                </div>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="technician_profiles.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="mytasktech.php" class="active"><i class="fas fa-tasks"></i>My Tasks</a></li>
                <li><a href="complete_tech.php"><i class="fas fa-check-circle"></i>Completed</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- HEADER -->
        <div class="header">
            <div>
                <h1 class="page-title">My Tasks</h1>
                <p class="page-subtitle">Manage your assigned facility reports</p>
            </div>
            <div class="technician-info">
                <div class="technician-avatar">
                    <?php echo strtoupper(substr($technician['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <h4><?php echo htmlspecialchars($technician['full_name']); ?></h4>
                    <p>Technician</p>
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

        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-header">
                    <div class="stats-icon ongoing">
                        <i class="fas fa-tasks"></i>
                    </div>
                </div>
                <div class="stats-content">
                    <h3>Active Tasks</h3>
                    <div class="value"><?php echo count($assigned_reports); ?></div>
                    <p class="description">Currently assigned to you</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-header">
                    <div class="stats-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stats-content">
                    <h3>Completed</h3>
                    <div class="value"><?php echo count($completed_reports); ?></div>
                    <p class="description">Recently completed tasks</p>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('assigned')">Assigned Tasks</button>
            <button class="tab-button" onclick="switchTab('completed')">Completed Tasks</button>
        </div>

        <!-- ASSIGNED TASKS TAB -->
        <div id="assigned-tab" class="tab-content active">
            <div class="reports-container">
                <?php if (empty($assigned_reports)): ?>
                    <div class="report-card" style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #9ca3af; margin-bottom: 20px;"></i>
                        <h3 style="color: var(--gray); margin-bottom: 10px;">No active tasks assigned</h3>
                        <p>You don't have any active tasks at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assigned_reports as $report): ?>
                    <div class="report-card">
                        <!-- Report Header -->
                        <div class="report-card-header">
                            <div>
                                <h3 class="report-title">Report #<?php echo $report['report_id']; ?></h3>
                                <p class="report-id">Reported by: <?php echo htmlspecialchars($report['reporter_name'] ?? 'Unknown'); ?></p>
                            </div>
                            <div>
                                <span class="priority-badge priority-<?php echo strtolower($report['priority']); ?>">
                                    <?php echo htmlspecialchars($report['priority']); ?>
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
                        
                        <!-- Original Image Display -->
                        <?php if (!empty($report['image_path'])): ?>
                            <div class="report-image">
                                <h4 style="margin-bottom: 10px; font-size: 14px; color: var(--dark);">
                                    <i class="fas fa-image"></i> Original Report Image
                                </h4>
                                <img src="<?php echo htmlspecialchars($report['image_path']); ?>" 
                                     alt="Report Image" 
                                     onclick="openImageModal(this.src, 'Report Image')"
                                     onerror="this.style.display='none';this.parentElement.innerHTML='<div class=\'report-image-placeholder\'><i class=\'fas fa-image\'></i><p>Image not available</p></div>';">
                            </div>
                        <?php else: ?>
                            <div class="report-image-placeholder">
                                <i class="fas fa-image"></i>
                                <p>No image uploaded with report</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Status Update Form -->
                        <form method="POST" action="mytasktech.php" enctype="multipart/form-data" class="report-actions" id="form-<?php echo $report['report_id']; ?>">
                            <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                            
                            <textarea name="notes" class="notes-textarea" 
                                      placeholder="Add progress notes or comments..."></textarea>
                            
                            <div class="status-form">
                                <select name="status" class="status-select" required 
                                        onchange="toggleImageUpload(<?php echo $report['report_id']; ?>, this.value)">
                                    <option value="Ongoing" <?php echo $report['status'] == 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                    <option value="On Hold" <?php echo $report['status'] == 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                                    <option value="Completed">Completed</option>
                                </select>
                                
                                <!-- Image Upload for Completed Status - REQUIRED -->
                                <div class="image-upload" id="upload-<?php echo $report['report_id']; ?>" style="display: none;">
                                    <div class="image-requirement">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span><strong>REQUIRED:</strong> You must upload a photo of the completed work</span>
                                    </div>
                                    <label for="completed_image_<?php echo $report['report_id']; ?>">
                                        <i class="fas fa-camera"></i> Upload Completed Photo *
                                    </label>
                                    <input type="file" name="completed_image" 
                                           id="completed_image_<?php echo $report['report_id']; ?>"
                                           class="file-input required" 
                                           accept="image/*"
                                           required
                                           onchange="validateForm(<?php echo $report['report_id']; ?>)">
                                    <small style="display: block; margin-top: 5px; color: #6b7280;">
                                        <i class="fas fa-info-circle"></i> Upload JPG, PNG, or GIF image (Max 5MB)
                                    </small>
                                </div>
                                
                                <button type="submit" name="update_status" class="btn btn-primary" 
                                        id="submit-btn-<?php echo $report['report_id']; ?>">
                                    <i class="fas fa-save"></i> Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- COMPLETED TASKS TAB -->
        <div id="completed-tab" class="tab-content">
            <div class="reports-container">
                <?php if (empty($completed_reports)): ?>
                    <div class="report-card" style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                        <i class="fas fa-check-circle" style="font-size: 48px; color: #9ca3af; margin-bottom: 20px;"></i>
                        <h3 style="color: var(--gray); margin-bottom: 10px;">No completed tasks yet</h3>
                        <p>Your completed tasks will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($completed_reports as $report): ?>
                    <div class="report-card">
                        <!-- Report Header -->
                        <div class="report-card-header">
                            <div>
                                <h3 class="report-title">Report #<?php echo $report['report_id']; ?></h3>
                                <p class="report-id">Completed on: <?php echo date('d M Y', strtotime($report['updated_at'])); ?></p>
                            </div>
                            <span style="background: var(--secondary); color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px;">
                                Completed
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
                                    <p><?php echo date('d M Y', strtotime($report['report_date'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="detail-content">
                                    <h4>Reporter</h4>
                                    <p><?php echo htmlspecialchars($report['reporter_name'] ?? 'Unknown'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Issue Description -->
                        <div class="report-description">
                            <strong>Issue Description:</strong><br>
                            <?php echo nl2br(htmlspecialchars(substr($report['issue_description'], 0, 200))); ?>
                            <?php if (strlen($report['issue_description']) > 200): ?>...<?php endif; ?>
                        </div>
                        
                        <!-- Original Image -->
                        <?php if (!empty($report['image_path'])): ?>
                            <div class="report-image">
                                <h4 style="margin-bottom: 10px; font-size: 14px; color: var(--dark);">
                                    <i class="fas fa-image"></i> Original Report Image
                                </h4>
                                <img src="<?php echo htmlspecialchars($report['image_path']); ?>" 
                                     alt="Report Image" 
                                     onclick="openImageModal(this.src, 'Original Report Image')">
                            </div>
                        <?php endif; ?>
                        
                        <!-- Completed Image -->
                        <?php if (!empty($report['completed_image'])): ?>
                            <div class="completed-image-container">
                                <h4><i class="fas fa-check-circle"></i> Completed Work Photo</h4>
                                <img src="<?php echo htmlspecialchars($report['completed_image']); ?>" 
                                     alt="Completed Work" 
                                     style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 2px solid var(--secondary); cursor: pointer;"
                                     onclick="openImageModal(this.src, 'Completed Work Photo')">
                                <p style="font-size: 12px; color: var(--gray); margin-top: 5px;">
                                    <i class="fas fa-camera"></i> Photo uploaded by technician
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- IMAGE MODAL -->
    <div id="imageModal" class="modal">
        <span class="modal-close" onclick="closeImageModal()">&times;</span>
        <img class="modal-content" id="modalImage">
        <div id="modalCaption" class="modal-caption"></div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabName + '-tab').classList.add('active');
        }
        
        // Show/hide image upload based on status selection
        function toggleImageUpload(reportId, status) {
            const uploadDiv = document.getElementById('upload-' + reportId);
            const submitBtn = document.getElementById('submit-btn-' + reportId);
            const fileInput = document.getElementById('completed_image_' + reportId);
            
            if (status === 'Completed') {
                uploadDiv.style.display = 'block';
                uploadDiv.classList.add('required');
                // Disable submit button initially until image is selected
                submitBtn.disabled = true;
                submitBtn.title = "Please select an image first";
                fileInput.required = true;
            } else {
                uploadDiv.style.display = 'none';
                uploadDiv.classList.remove('required');
                submitBtn.disabled = false;
                submitBtn.title = "";
                fileInput.required = false;
            }
        }
        
        // Validate form before submission
        function validateForm(reportId) {
            const form = document.getElementById('form-' + reportId);
            const statusSelect = form.querySelector('select[name="status"]');
            const fileInput = document.getElementById('completed_image_' + reportId);
            const submitBtn = document.getElementById('submit-btn-' + reportId);
            
            if (statusSelect.value === 'Completed') {
                if (fileInput.files.length > 0) {
                    // Check file type
                    const file = fileInput.files[0];
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    
                    if (validTypes.includes(file.type)) {
                        // Check file size (max 5MB)
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        
                        if (file.size > maxSize) {
                            alert('Image size too large! Maximum size is 5MB.');
                            fileInput.value = '';
                            submitBtn.disabled = true;
                            submitBtn.title = "File too large (max 5MB)";
                            return false;
                        }
                        
                        submitBtn.disabled = false;
                        submitBtn.title = "";
                        return true;
                    } else {
                        alert('Please select a valid image file (JPG, PNG, or GIF)');
                        fileInput.value = '';
                        submitBtn.disabled = true;
                        submitBtn.title = "Invalid file type";
                        return false;
                    }
                } else {
                    submitBtn.disabled = true;
                    submitBtn.title = "Please select an image first";
                    return false;
                }
            }
            return true;
        }
        
        // Initialize image upload visibility based on current status
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.status-select').forEach(select => {
                const reportId = select.form.report_id.value;
                toggleImageUpload(reportId, select.value);
            });
        });
        
        // Form submission validation
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('report-actions')) {
                const statusSelect = e.target.querySelector('select[name="status"]');
                const fileInput = e.target.querySelector('input[name="completed_image"]');
                
                if (statusSelect.value === 'Completed') {
                    if (!fileInput || fileInput.files.length === 0) {
                        e.preventDefault();
                        alert('You must upload a photo of the completed work before marking as Completed!');
                        return false;
                    }
                    
                    // Check file size (max 5MB)
                    const file = fileInput.files[0];
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    
                    if (file.size > maxSize) {
                        e.preventDefault();
                        alert('Image size too large! Maximum size is 5MB.');
                        return false;
                    }
                }
            }
        });
        
        // Image modal functions
        function openImageModal(src, caption) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const captionText = document.getElementById('modalCaption');
            
            modal.style.display = 'block';
            modalImg.src = src;
            captionText.innerHTML = caption;
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                closeImageModal();
            }
        }
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        });
        
        // Add confirmation for status change to Completed
        document.addEventListener('change', function(e) {
            if (e.target.name === 'status' && e.target.value === 'Completed') {
                const reportId = e.target.form.report_id.value;
                const reportTitle = document.querySelector('#form-' + reportId + ' .report-title').textContent;
                
                if (confirm(`You are about to mark "${reportTitle}" as Completed.\n\nIMPORTANT: You MUST upload a photo of the completed work.\n\nContinue?`)) {
                    // Continue with the change
                } else {
                    // Revert to previous value
                    e.target.value = 'Ongoing';
                    toggleImageUpload(reportId, 'Ongoing');
                }
            }
        });
    </script>
</body>
</html>