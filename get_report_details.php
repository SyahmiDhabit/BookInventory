<?php
session_start();
include("db_connect.php");

// Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die('Report ID not specified');
}

$report_id = mysqli_real_escape_string($conn, $_GET['id']);

// Query untuk dapatkan report details
$query = "SELECT r.*,
    u.full_name as technician_name,
    u.email as technician_email,
    u2.full_name as reporter_name,
    u2.hostel_block as reporter_hostel_block,
    u2.room_no as reporter_room_no,
    u2.email as reporter_email,
    f.facility_name as facility_name
FROM reports r
LEFT JOIN users u ON r.assigned_to = u.user_id
LEFT JOIN users u2 ON r.user_id = u2.user_id
LEFT JOIN facilities f ON r.facility_id = f.facility_id
WHERE r.report_id = '$report_id'";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die('Report not found');
}

// Calculate days pending
$days_pending = floor((time() - strtotime($row['report_date'])) / (60 * 60 * 24));
$last_updated = !empty($row['updated_at']) ? 
    'Last Updated: ' . date('d M Y, h:i A', strtotime($row['updated_at'])) : 
    'Pending for ' . $days_pending . ' days';
?>

<!-- Modal Content -->
<div class="modal-report-card">
    <!-- Report Header -->
    <div class="modal-report-header">
        <div>
            <h2 class="modal-report-title">Report #<?php echo $row['report_id']; ?></h2>
            <p style="color: var(--gray); font-size: 14px;">
                Reported by: <?php echo htmlspecialchars($row['reporter_name'] ?? 'Unknown'); ?>
            </p>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
            <span class="priority-badge priority-<?php echo strtolower($row['priority']); ?>">
                <?php echo htmlspecialchars($row['priority']); ?> Priority
            </span>
            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                <?php echo htmlspecialchars($row['status']); ?>
            </span>
        </div>
    </div>
    
    <!-- Report Details Grid -->
    <div class="modal-details-grid">
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="modal-detail-content">
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
        
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="modal-detail-content">
                <h4>Reported On</h4>
                <p><?php echo date('d M Y, h:i A', strtotime($row['report_date'])); ?></p>
            </div>
        </div>
        
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="modal-detail-content">
                <h4>Reporter</h4>
                <p><?php echo htmlspecialchars($row['reporter_name'] ?? 'Unknown'); ?></p>
                <?php if (!empty($row['reporter_email'])): ?>
                    <p style="font-size: 13px; color: var(--gray); margin-top: 3px;">
                        <?php echo htmlspecialchars($row['reporter_email']); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-user-cog"></i>
            </div>
            <div class="modal-detail-content">
                <h4>Assigned To</h4>
                <p><?php echo !empty($row['technician_name']) ? htmlspecialchars($row['technician_name']) : 'Not assigned'; ?></p>
                <?php if (!empty($row['technician_email'])): ?>
                    <p style="font-size: 13px; color: var(--gray); margin-top: 3px;">
                        <?php echo htmlspecialchars($row['technician_email']); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-tag"></i>
            </div>
            <div class="modal-detail-content">
                <h4>Category</h4>
                <p><?php echo htmlspecialchars($row['facility_name'] ?: 'General'); ?></p>
            </div>
        </div>
        
        <div class="modal-detail-item">
            <div class="modal-detail-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="modal-detail-content">
                <h4>Status</h4>
                <p><?php echo htmlspecialchars($last_updated); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Issue Description -->
    <div class="modal-description">
        <strong>Issue Description:</strong>
        <?php echo nl2br(htmlspecialchars($row['issue_description'])); ?>
        
        <?php if (!empty($row['progress_notes'])): ?>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                <strong>Progress Notes:</strong><br>
                <?php echo nl2br(htmlspecialchars($row['progress_notes'])); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Image Display -->
    <div class="modal-image-container">
        <?php if (!empty($row['image_path'])): ?>
            <a href="<?php echo htmlspecialchars($row['image_path']); ?>" target="_blank">
                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                     alt="Report Image" 
                     onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg width=\'400\' height=\'300\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Crect width=\'100%25\' height=\'100%25\' fill=\'%23f3f4f6\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' font-family=\'Arial\' font-size=\'16\' fill=\'%239ca3af\' text-anchor=\'middle\' dy=\'.3em\'%3EImage not available%3C/text%3E%3C/svg%3E'">
            </a>
            <p style="margin-top: 10px; font-size: 14px; color: var(--gray);">
                <i class="fas fa-external-link-alt"></i> Click image to view full size
            </p>
        <?php else: ?>
            <div style="background: #f3f4f6; padding: 40px; border-radius: 10px; text-align: center;">
                <i class="fas fa-image" style="font-size: 48px; color: #9ca3af; margin-bottom: 15px;"></i>
                <p style="color: var(--gray);">No image uploaded for this report</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal Actions -->
    <div class="modal-actions">
        <button type="button" onclick="closeReportModal()" class="btn btn-secondary">
            <i class="fas fa-times"></i> Close
        </button>
       
        <?php if ($row['status'] == 'Pending'): ?>
            <a href="assign_task.php?report=<?php echo $row['report_id']; ?>" class="btn" style="background: var(--secondary); color: white;">
                <i class="fas fa-user-check"></i> Assign Task
            </a>
        <?php endif; ?>
    </div>
</div>