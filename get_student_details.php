<?php
session_start();
include('db_connect.php');

// Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die('Student ID not specified');
}

$student_id = mysqli_real_escape_string($conn, $_GET['id']);

// Query to get student details with their reports count
$query = "SELECT 
    u.*,
    COUNT(r.report_id) as total_reports,
    COUNT(CASE WHEN r.status = 'Completed' THEN r.report_id END) as completed_reports,
    COUNT(CASE WHEN r.status = 'Pending' THEN r.report_id END) as pending_reports
FROM users u
LEFT JOIN reports r ON u.user_id = r.user_id
WHERE u.user_id = '$student_id' AND u.role = 'User'
GROUP BY u.user_id";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die('Student not found');
}

// Get first letter for avatar
$first_letter = strtoupper(substr($row['full_name'], 0, 1));

// Format created date
$created_date = date('d M Y', strtotime($row['created_at']));
$created_time = date('h:i A', strtotime($row['created_at']));

// Format last login if available
$last_login = !empty($row['last_login']) ? 
    date('d M Y, h:i A', strtotime($row['last_login'])) : 'Never logged in';
?>

<!-- Modal Content -->
<div class="modal-student-card">
    <!-- Student Header -->
    <div class="modal-student-header">
        <div class="student-avatar-large">
            <?php echo $first_letter; ?>
        </div>
        <div class="modal-student-info">
            <h2><?php echo htmlspecialchars($row['full_name']); ?></h2>
            <p><?php echo htmlspecialchars($row['email']); ?></p>
            <?php if (!empty($row['phone'])): ?>
                <p><?php echo htmlspecialchars($row['phone']); ?></p>
            <?php endif; ?>
            <p class="student-id">Student ID: #<?php echo $row['user_id']; ?></p>
        </div>
    </div>
    
    <!-- Student Details Grid -->
    <div class="modal-details-grid">
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="modal-detail-content">
                <h4>Location</h4>
                <?php if (!empty($row['hostel_block']) && !empty($row['room_no'])): ?>
                    <p><?php echo htmlspecialchars($row['hostel_block'] . ' - Room ' . $row['room_no']); ?></p>
                <?php else: ?>
                    <p>Not specified</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="modal-detail-content">
                <h4>Account Status</h4>
                <p>
                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>" style="margin: 0; display: inline-block;">
                        <?php echo $row['status']; ?>
                    </span>
                </p>
            </div>
        </div>
        
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="modal-detail-content">
                <h4>Registered Since</h4>
                <p><?php echo $created_date; ?></p>
                <p style="font-size: 13px; color: var(--gray); margin-top: 3px;">
                    <?php echo $created_time; ?>
                </p>
            </div>
        </div>
        
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div class="modal-detail-content">
                <h4>Last Login</h4>
                <p><?php echo $last_login; ?></p>
            </div>
        </div>
        
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="modal-detail-content">
                <h4>Total Reports</h4>
                <p><?php echo $row['total_reports']; ?> reports</p>
                <p style="font-size: 13px; color: var(--gray); margin-top: 3px;">
                    <?php echo $row['completed_reports']; ?> completed, <?php echo $row['pending_reports']; ?> pending
                </p>
            </div>
        </div>
    </div>
    
    <!-- Additional Info -->
    <?php if (!empty($row['profile_image'])): ?>
    <div style="margin-bottom: 20px; text-align: center;">
        <h4 style="margin-bottom: 10px; color: var(--dark);">Profile Image</h4>
        <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" 
             alt="Profile Image" 
             style="max-width: 150px; border-radius: 50%; border: 3px solid var(--primary);"
             onerror="this.style.display='none'">
    </div>
    <?php endif; ?>
    
    <!-- Modal Actions -->
    <div class="modal-actions">
        <button type="button" onclick="closeStudentModal()" class="btn-secondary" style="padding: 10px 20px;">
            <i class="fas fa-times"></i> Close
        </button>
        <a href="edit_student.php?id=<?php echo $row['user_id']; ?>" class="btn-primary" style="padding: 10px 20px; text-decoration: none;">
            <i class="fas fa-edit"></i> Edit Student
        </a>
        <a href="admin_manage_reports.php?search=<?php echo urlencode($row['email']); ?>" 
           class="btn" style="background: var(--secondary); color: white; padding: 10px 20px; text-decoration: none;">
            <i class="fas fa-clipboard-list"></i> View Reports
        </a>
    </div>
</div>