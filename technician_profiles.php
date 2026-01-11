<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Technician') {
    header("Location: login.php");
    exit();
}

$technician_id = $_SESSION['user_id'];
$message = '';
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle personal information update
    if (isset($_POST['update_personal_info'])) {
        $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
        $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
        
        // Validate full name
        if (empty($full_name)) {
            $error_message = "Full name is required.";
        } else {
            // Build update query
            $update_personal_query = "UPDATE users 
                                     SET full_name = '$full_name',
                                         phone = " . ($phone ? "'$phone'" : "NULL") . "
                                     WHERE user_id = '$technician_id'";
            
            if (mysqli_query($conn, $update_personal_query)) {
                $success_message = "Personal information updated successfully!";
                // Update session name if it changed
                if (isset($_SESSION['user_name'])) {
                    $_SESSION['user_name'] = $full_name;
                }
            } else {
                $error_message = "Error updating personal information: " . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['update_expertise'])) {
        $skills = mysqli_real_escape_string($conn, $_POST['skills'] ?? '');
        $experience_years = mysqli_real_escape_string($conn, $_POST['experience_years'] ?? '0');
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        
        // Check if profile exists
        $check_query = "SELECT tech_id FROM technician_profiles WHERE tech_id = '$technician_id'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing profile
            $update_query = "UPDATE technician_profiles 
                            SET skills = '$skills', 
                                experience_years = '$experience_years', 
                                notes = '$notes',
                                updated_at = NOW()
                            WHERE tech_id = '$technician_id'";
        } else {
            // Insert new profile
            $update_query = "INSERT INTO technician_profiles (tech_id, skills, experience_years, notes, updated_at) 
                            VALUES ('$technician_id', '$skills', '$experience_years', '$notes', NOW())";
        }
        
        if (mysqli_query($conn, $update_query)) {
            $success_message = "Expertise updated successfully!";
        } else {
            $error_message = "Error updating expertise: " . mysqli_error($conn);
        }
    }
    
    // Handle skill addition
    if (isset($_POST['add_skill'])) {
        $skill_name = mysqli_real_escape_string($conn, $_POST['skill_name'] ?? '');
        $proficiency_level = mysqli_real_escape_string($conn, $_POST['proficiency_level'] ?? 'Intermediate');
        $certification = mysqli_real_escape_string($conn, $_POST['certification'] ?? '');
        $years_experience = mysqli_real_escape_string($conn, $_POST['years_experience'] ?? '1');
        
        if (!empty($skill_name)) {
            $skill_query = "INSERT INTO technician_skills (technician_id, skill_name, proficiency_level, certification, years_experience, created_at) 
                           VALUES ('$technician_id', '$skill_name', '$proficiency_level', '$certification', '$years_experience', NOW())";
            
            if (mysqli_query($conn, $skill_query)) {
                $success_message = "Skill added successfully!";
            } else {
                $error_message = "Error adding skill: " . mysqli_error($conn);
            }
        }
    }
    
    // Handle skill deletion
    if (isset($_POST['delete_skill'])) {
        $skill_id = mysqli_real_escape_string($conn, $_POST['skill_id'] ?? '');
        
        if (!empty($skill_id)) {
            $delete_query = "DELETE FROM technician_skills WHERE skill_id = '$skill_id' AND technician_id = '$technician_id'";
            
            if (mysqli_query($conn, $delete_query)) {
                $success_message = "Skill removed successfully!";
            } else {
                $error_message = "Error removing skill: " . mysqli_error($conn);
            }
        }
    }
}

// Get technician info with profile data
$tech_query = "SELECT 
                u.full_name, u.email, u.phone, u.hostel_block, u.room_no, u.created_at,
                tp.skills, tp.experience_years, tp.notes,
                GROUP_CONCAT(DISTINCT ts.skill_name SEPARATOR ', ') as technician_skills
              FROM users u 
              LEFT JOIN technician_profiles tp ON u.user_id = tp.tech_id 
              LEFT JOIN technician_skills ts ON u.user_id = ts.technician_id 
              WHERE u.user_id = '$technician_id' 
              GROUP BY u.user_id";

$tech_result = mysqli_query($conn, $tech_query);
$technician = mysqli_fetch_assoc($tech_result);

// Get detailed skills
$skills_query = "SELECT * FROM technician_skills 
                 WHERE technician_id = '$technician_id' 
                 ORDER BY created_at DESC";
$skills_result = mysqli_query($conn, $skills_query);
$detailed_skills = mysqli_fetch_all($skills_result, MYSQLI_ASSOC);

// Fetch technician's task statistics
$stats_query = "SELECT 
                COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_tasks,
                COUNT(CASE WHEN status = 'Ongoing' THEN 1 END) as ongoing_tasks,
                COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_tasks,
                COUNT(*) as total_tasks
                FROM reports 
                WHERE assigned_to = '$technician_id'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Fetch recent tasks
$tasks_query = "SELECT r.report_id, f.facility_name, r.issue_description, 
                r.status, r.report_date, r.priority, r.hostel_block,
                u.full_name as reporter_name
                FROM reports r
                LEFT JOIN users u ON r.user_id = u.user_id
                LEFT JOIN facilities f ON r.facility_id = f.facility_id 
                WHERE r.assigned_to = '$technician_id'
                ORDER BY r.report_date DESC 
                LIMIT 5";
$tasks_result = mysqli_query($conn, $tasks_query);
$recent_tasks = mysqli_fetch_all($tasks_result, MYSQLI_ASSOC);

// Get initials for avatar
$initials = '';
if ($technician && isset($technician['full_name'])) {
    $name_parts = explode(' ', $technician['full_name']);
    $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HFRS Technician</title>
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

    /* PROFILE HEADER */
    .profile-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        border-radius: 16px;
        padding: 40px;
        color: white;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .profile-header-content {
        display: flex;
        align-items: center;
        gap: 30px;
        position: relative;
        z-index: 1;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: 700;
        border: 4px solid rgba(255, 255, 255, 0.3);
    }

    .profile-info h1 {
        font-size: 36px;
        margin-bottom: 8px;
        font-weight: 700;
    }

    .profile-info .role {
        font-size: 18px;
        opacity: 0.9;
        margin-bottom: 20px;
    }

    .profile-meta {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
    }

    .meta-item i {
        font-size: 20px;
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
    .stats-icon.pending { background: var(--warning); }
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

    /* PROFILE SECTIONS */
    .profile-sections {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 30px;
    }

    @media (max-width: 1200px) {
        .profile-sections {
            grid-template-columns: 1fr;
        }
    }

    .profile-section {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e5e7eb;
    }

    .section-header h2 {
        font-size: 22px;
        font-weight: 700;
        color: var(--dark);
    }

    .edit-button {
        background: var(--primary);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s;
    }

    .edit-button:hover {
        background: var(--primary-dark);
    }

    /* FORMS */

     .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .info-item {
        margin-bottom: 20px;
    }

    .info-item label {
        display: block;
        font-size: 14px;
        color: var(--gray);
        font-weight: 500;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-item .value {
        font-size: 16px;
        color: var(--dark);
        font-weight: 500;
        padding: 10px 0;
    }

    /* SKILLS */
    .skills-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }

    .skill-tag {
        background: #e0e7ff;
        color: var(--primary-dark);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
    }
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--dark);
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .select-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 16px;
        background: white;
        cursor: pointer;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
    }

    .btn-secondary {
        background: var(--gray);
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 2px solid #e5e7eb;
    }

    /* MODAL STYLES */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-container {
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease-out;
        position: relative;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        padding: 25px 30px;
        border-bottom: 2px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        border-radius: 16px 16px 0 0;
    }

    .modal-header h3 {
        font-size: 22px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }

    .modal-body {
        padding: 30px;
    }

    .modal-footer {
        padding: 20px 30px;
        border-top: 2px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        background: #f8fafc;
        border-radius: 0 0 16px 16px;
    }

    @media (max-width: 768px) {
        .modal-container {
            width: 95%;
            max-width: none;
            margin: 20px;
            border-radius: 12px;
        }

        .modal-header {
            padding: 20px;
            border-radius: 12px 12px 0 0;
        }

        .modal-header h3 {
            font-size: 18px;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            flex-direction: column-reverse;
        }

        .modal-footer button {
            width: 100%;
        }
    }

    /* SKILLS LIST */
    .skills-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }

    .skill-tag {
        background: #e0e7ff;
        color: var(--primary-dark);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .skill-tag .delete-skill {
        background: none;
        border: none;
        color: var(--danger);
        cursor: pointer;
        padding: 2px;
        font-size: 12px;
    }

    .proficiency-badge {
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 5px;
    }

    .proficiency-beginner {
        background: #dbeafe;
        color: #1e40af;
    }

    .proficiency-intermediate {
        background: #fef3c7;
        color: #92400e;
    }

    .proficiency-expert {
        background: #d1fae5;
        color: #065f46;
    }

    /* ADD SKILL FORM */
    .add-skill-form {
        background: #f8fafc;
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
        border: 2px dashed #e5e7eb;
    }

    .form-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 15px;
        align-items: end;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }

    /* TASKS TABLE */
    .tasks-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .tasks-table th {
        background: #f8fafc;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: var(--gray);
        border-bottom: 2px solid #e5e7eb;
    }

    .tasks-table td {
        padding: 15px;
        border-bottom: 1px solid #e5e7eb;
    }

    .tasks-table tr:hover {
        background: #f8fafc;
    }

    .priority-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .priority-low {
        background: #d1fae5;
        color: #065f46;
    }

    .priority-medium {
        background: #fef3c7;
        color: #92400e;
    }

    .priority-high {
        background: #fee2e2;
        color: #dc2626;
    }

    .priority-urgent {
        background: #fce7f3;
        color: #be185d;
    }

    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-ongoing {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-completed {
        background: #d1fae5;
        color: #065f46;
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

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
        }

        .sidebar-header h2,
        .sidebar-header p,
        .technician-text-sidebar,
        .sidebar nav a span {
            display: none;
        }

        .sidebar nav a {
            justify-content: center;
            padding: 15px;
        }

        .sidebar nav a i {
            font-size: 20px;
            width: auto;
        }

        .main-content {
            margin-left: 70px;
            padding: 20px;
        }

        .profile-header-content {
            flex-direction: column;
            text-align: center;
        }

        .profile-meta {
            justify-content: center;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .modal-container {
            max-height: 95vh;
        }
    }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>HFRS</h2>
            <p>Hostel Facility Report System</p>
            <div class="technician-profile-sidebar">
                <div class="technician-avatar-sidebar"><?php echo $initials; ?></div>
                <div class="technician-text-sidebar">
                    <h4><?php echo htmlspecialchars($technician['full_name'] ?? 'Technician'); ?></h4>
                    <p>Technician</p>
                </div>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="technician_profiles.php" class="active"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="mytasktech.php"><i class="fas fa-tasks"></i> <span>My Tasks</span></a></li>
                <li><a href="complete_tech.php"><i class="fas fa-check-circle"></i> <span>Completed</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="header">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">Manage your technician profile and expertise</p>
            </div>
        </div>

        <!-- Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-header-content">
                <div class="profile-avatar"><?php echo $initials; ?></div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($technician['full_name'] ?? 'Technician'); ?></h1>
                    <div class="role">Senior Technician</div>
                    <div class="profile-meta">
                        <div class="meta-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($technician['email'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($technician['phone'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Joined: <?php echo date('F Y', strtotime($technician['created_at'] ?? 'now')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-header">
                    <div class="stats-content">
                        <h3>Total Tasks</h3>
                        <div class="value"><?php echo $stats['total_tasks'] ?? 0; ?></div>
                        <div class="description">All assigned tasks</div>
                    </div>
                    <div class="stats-icon total">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-header">
                    <div class="stats-content">
                        <h3>Completed</h3>
                        <div class="value"><?php echo $stats['completed_tasks'] ?? 0; ?></div>
                        <div class="description">Successfully resolved</div>
                    </div>
                    <div class="stats-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-header">
                    <div class="stats-content">
                        <h3>Ongoing</h3>
                        <div class="value"><?php echo $stats['ongoing_tasks'] ?? 0; ?></div>
                        <div class="description">Currently in progress</div>
                    </div>
                    <div class="stats-icon ongoing">
                        <i class="fas fa-tools"></i>
                    </div>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-header">
                    <div class="stats-content">
                        <h3>Pending</h3>
                        <div class="value"><?php echo $stats['pending_tasks'] ?? 0; ?></div>
                        <div class="description">Awaiting action</div>
                    </div>
                    <div class="stats-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Sections -->
        <div class="profile-sections">
            <!-- Personal Information -->
            <div class="profile-section">
                <div class="section-header">
                    <h2><i class="fas fa-id-card" style="margin-right: 10px;"></i> Personal Information</h2>
                    <button type="button" class="edit-button" onclick="openPersonalInfoModal()">
                        <i class="fas fa-edit" style="margin-right: 5px;"></i> Edit Info
                    </button>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <div class="value"><?php echo htmlspecialchars($technician['full_name'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Email Address</label>
                        <div class="value"><?php echo htmlspecialchars($technician['email'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Phone Number</label>
                        <div class="value"><?php echo htmlspecialchars($technician['phone'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Member Since</label>
                        <div class="value"><?php echo date('F j, Y', strtotime($technician['created_at'] ?? 'now')); ?></div>
                    </div>
                </div>
            </div>

            <!-- Expertise & Skills -->
            <div class="profile-section">
                <div class="section-header">
                    <h2><i class="fas fa-star" style="margin-right: 10px;"></i> Expertise & Skills</h2>
                    <button type="button" class="edit-button" onclick="toggleEditMode()">
                        <i class="fas fa-edit" style="margin-right: 5px;"></i> Edit Expertise
                    </button>
                </div>

                <!-- Display Mode -->
                <div id="displayMode">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Experience</label>
                            <div class="value"><?php echo ($technician['experience_years'] ?? 0) . ' year(s)'; ?></div>
                        </div>
                        <div class="info-item">
                            <label>Specialization</label>
                            <div class="value"><?php echo htmlspecialchars($technician['skills'] ?? 'General Technician'); ?></div>
                        </div>
                    </div>
                    
                    <!-- Skills List -->
                    <div style="margin-top: 20px;">
                        <label style="display: block; font-size: 14px; color: var(--gray); margin-bottom: 10px;">Technical Skills</label>
                        <?php if (!empty($detailed_skills)): ?>
                            <div class="skills-list">
                                <?php foreach ($detailed_skills as $skill): ?>
                                    <div class="skill-tag">
                                        <?php echo htmlspecialchars($skill['skill_name']); ?>
                                        <span class="proficiency-badge proficiency-<?php echo strtolower($skill['proficiency_level']); ?>">
                                            <?php echo $skill['proficiency_level']; ?>
                                        </span>
                                        <?php if ($skill['certification']): ?>
                                            <small title="Certification: <?php echo htmlspecialchars($skill['certification']); ?>">
                                                <i class="fas fa-certificate"></i>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--gray); font-style: italic;">No skills added yet.</p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($technician['notes'])): ?>
                    <div style="margin-top: 20px;">
                        <label style="display: block; font-size: 14px; color: var(--gray); margin-bottom: 10px;">Additional Notes</label>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 4px solid var(--primary);">
                            <?php echo nl2br(htmlspecialchars($technician['notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Edit Mode (Hidden by default) -->
                <div id="editMode" style="display: none;">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="skills">Primary Skills/Specialization</label>
                            <input type="text" class="form-control" id="skills" name="skills" 
                                   value="<?php echo htmlspecialchars($technician['skills'] ?? ''); ?>"
                                   placeholder="e.g., Electrical, Plumbing, HVAC">
                        </div>

                        <div class="form-group">
                            <label for="experience_years">Years of Experience</label>
                            <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                   value="<?php echo htmlspecialchars($technician['experience_years'] ?? '0'); ?>"
                                   min="0" max="50">
                        </div>

                        <div class="form-group">
                            <label for="notes">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" 
                                      placeholder="Any additional information about your expertise..."><?php echo htmlspecialchars($technician['notes'] ?? ''); ?></textarea>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="submit" name="update_expertise" class="btn btn-primary">
                                <i class="fas fa-save" style="margin-right: 5px;"></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleEditMode()">
                                <i class="fas fa-times" style="margin-right: 5px;"></i> Cancel
                            </button>
                        </div>
                    </form>

                    <!-- Add Skill Form -->
                    <div class="add-skill-form">
                        <h4 style="margin-bottom: 15px; color: var(--dark);">Add New Skill</h4>
                        <form method="POST" action="">
                            <div class="form-row">
                                <div>
                                    <label for="skill_name">Skill Name</label>
                                    <input type="text" class="form-control" id="skill_name" name="skill_name" 
                                           placeholder="e.g., Electrical Wiring" required>
                                </div>
                                <div>
                                    <label for="proficiency_level">Proficiency</label>
                                    <select class="select-control" id="proficiency_level" name="proficiency_level" required>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Intermediate" selected>Intermediate</option>
                                        <option value="Expert">Expert</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="years_experience">Years</label>
                                    <input type="number" class="form-control" id="years_experience" name="years_experience" 
                                           value="1" min="0" max="50">
                                </div>
                                <div>
                                    <label for="certification">Certification (Optional)</label>
                                    <input type="text" class="form-control" id="certification" name="certification" 
                                           placeholder="Certification name">
                                </div>
                            </div>
                            <button type="submit" name="add_skill" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-plus" style="margin-right: 5px;"></i> Add Skill
                            </button>
                        </form>
                    </div>

                    <!-- List Skills with Delete Option -->
                    <?php if (!empty($detailed_skills)): ?>
                    <div style="margin-top: 30px;">
                        <h4 style="margin-bottom: 15px; color: var(--dark);">Manage Skills</h4>
                        <div class="skills-list">
                            <?php foreach ($detailed_skills as $skill): ?>
                                <div class="skill-tag">
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    <span class="proficiency-badge proficiency-<?php echo strtolower($skill['proficiency_level']); ?>">
                                        <?php echo $skill['proficiency_level']; ?>
                                    </span>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this skill?');">
                                        <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                                        <button type="submit" name="delete_skill" class="delete-skill" title="Remove skill">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        

        <!-- Recent Tasks -->
        <div class="profile-section" style="margin-top: 30px;">
            <div class="section-header">
                <h2><i class="fas fa-history" style="margin-right: 10px;"></i> Recent Tasks</h2>
                <a href="mytasktech.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                    <i class="fas fa-external-link-alt" style="margin-right: 5px;"></i> View All Tasks
                </a>
            </div>
            
            <?php if (!empty($recent_tasks)): ?>
                <table class="tasks-table">
                    <thead>
                        <tr>
                            <th>Task ID</th>
                            <th>Facility</th>
                            <th>Issue Description</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Report Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_tasks as $task): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($task['report_id']); ?></td>
                            <td><?php echo htmlspecialchars($task['facility_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($task['issue_description'], 0, 50)) . (strlen($task['issue_description']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <span class="priority-badge priority-<?php echo strtolower($task['priority']); ?>">
                                    <?php echo htmlspecialchars($task['priority']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($task['status']); ?>">
                                    <?php echo htmlspecialchars($task['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($task['report_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No tasks found. You haven't been assigned any tasks yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Personal Information Edit Modal -->
    <div class="modal-overlay" id="personalInfoModal" onclick="if(event.target === this) closePersonalInfoModal()">
        <div class="modal-container" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-id-card"></i>
                    Edit Personal Information
                </h3>
                <button type="button" class="modal-close" onclick="closePersonalInfoModal()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="personalInfoForm">
                    <div class="form-group">
                        <label for="modal_full_name">Full Name *</label>
                        <input type="text" class="form-control" id="modal_full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($technician['full_name'] ?? ''); ?>"
                               placeholder="Enter your full name"
                               required>
                        <small style="color: var(--gray); font-size: 12px; margin-top: 5px; display: block;">
                            Your full name as it appears in the system
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal_phone">Phone Number</label>
                        <input type="tel" class="form-control" id="modal_phone" name="phone" 
                               value="<?php echo htmlspecialchars($technician['phone'] ?? ''); ?>"
                               placeholder="Enter your phone number (e.g., 0123456789)"
                               pattern="[0-9]{10,15}"
                               title="Please enter a valid phone number (10-15 digits)">
                        <small style="color: var(--gray); font-size: 12px; margin-top: 5px; display: block;">
                            Enter your phone number without dashes or spaces (optional)
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closePersonalInfoModal()">
                    <i class="fas fa-times" style="margin-right: 5px;"></i> Cancel
                </button>
                <button type="submit" name="update_personal_info" form="personalInfoForm" class="btn-primary">
                    <i class="fas fa-save" style="margin-right: 5px;"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <script>
    // Toggle between display and edit modes for expertise
    function toggleEditMode() {
        const displayMode = document.getElementById('displayMode');
        const editMode = document.getElementById('editMode');
        
        if (displayMode.style.display === 'none') {
            displayMode.style.display = 'block';
            editMode.style.display = 'none';
        } else {
            displayMode.style.display = 'none';
            editMode.style.display = 'block';
        }
    }

    // Open personal information edit modal
    function openPersonalInfoModal() {
        const modal = document.getElementById('personalInfoModal');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    // Close personal information edit modal
    function closePersonalInfoModal() {
        const modal = document.getElementById('personalInfoModal');
        modal.classList.remove('active');
        document.body.style.overflow = ''; // Restore scrolling
    }

    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('personalInfoModal');
            if (modal && modal.classList.contains('active')) {
                closePersonalInfoModal();
            }
        }
    });

    // Add active class to current page link
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.sidebar nav a');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    });
    </script>
</body>
</html>