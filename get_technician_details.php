<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['tech_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Technician ID required']);
    exit();
}

$tech_id = mysqli_real_escape_string($conn, $_GET['tech_id']);

$query = "SELECT 
    u.user_id,
    u.full_name,
    u.email,
    u.work_status,
    tp.skills,
    tp.experience_years,
    tp.notes,
    tp.updated_at,
    COUNT(DISTINCT r.report_id) as completed_tasks
    FROM users u
    LEFT JOIN technician_profiles tp ON u.user_id = tp.tech_id
    LEFT JOIN reports r ON u.user_id = r.assigned_to AND r.status = 'Completed'
    WHERE u.user_id = '$tech_id'
    GROUP BY u.user_id";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $technician = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($technician);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Technician not found']);
}

mysqli_close($conn);
?>