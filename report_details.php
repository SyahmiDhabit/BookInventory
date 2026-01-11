<?php
session_start();
include('db_connect.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get report ID from URL
if (!isset($_GET['id'])) {
    header("Location: myreport.php");
    exit();
}

$report_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Get report details
$report_query = "SELECT r.*, 
                u.hostel_block as user_hostel_block, 
                u.room_no as user_room_no,
                u.full_name as user_name,
                t.full_name as technician_name
                FROM reports r
                LEFT JOIN users u ON r.user_id = u.user_id
                LEFT JOIN users t ON r.assigned_to = t.user_id
                WHERE r.report_id = '$report_id' AND r.user_id = '$user_id'";

$report_result = mysqli_query($conn, $report_query);
$report = mysqli_fetch_assoc($report_result);

if (!$report) {
    header("Location: myreport.php");
    exit();
}

// Get status updates for this report
$updates_query = "SELECT * FROM status_updates WHERE report_id = '$report_id' ORDER BY update_date DESC";
$updates_result = mysqli_query($conn, $updates_query);

// Get task photos
$photos_query = "SELECT * FROM task_photos WHERE task_id = '$report_id' ORDER BY photo_type, uploaded_at";
$photos_result = mysqli_query($conn, $photos_query);

// Use report-specific hostel block and room number if available, otherwise use user's info
$hostel_block = $report['hostel_block'] ?? $report['user_hostel_block'] ?? '';
$room_number = $report['room_number'] ?? $report['user_room_no'] ?? '';

// Format dates
$report_date = date('F j, Y \a\t g:i A', strtotime($report['report_date']));
$updated_date = !empty($report['updated_at']) ? 
    date('F j, Y \a\t g:i A', strtotime($report['updated_at'])) : 
    $report_date;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details - HFRS</title>
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
        }
        
        body {
            background-color: #f4f6fa;
            color: #333;
        }
        
        /* Navbar */
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
        
        /* Details Container */
        .details-container {
            padding: 30px 60px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Back Button */
        .back-btn {
            margin-bottom: 20px;
        }
        
        .back-btn a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .back-btn a:hover {
            background: var(--primary);
            color: white;
            transform: translateX(-5px);
        }
        
        /* Report Header */
        .report-header-details {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 6px solid var(--secondary);
        }
        
        .report-title-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .report-id-big {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .report-status-section {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { 
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        
        .status-ongoing { 
            background: #cce5ff;
            color: #004085;
            border: 2px solid #a6d0ff;
        }
        
        .status-completed { 
            background: #d4edda;
            color: #155724;
            border: 2px solid #b8e6c3;
        }
        
        /* Priority Badge */
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-high { background: #f8d7da; color: #721c24; }
        .priority-urgent { 
            background: #ff4757; 
            color: white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 71, 87, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 71, 87, 0); }
        }
        
        /* Report Info Grid */
        .report-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .info-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid var(--secondary);
        }
        
        .info-card h3 {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-card p {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary);
        }
        
        /* Description Section */
        .description-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .section-title {
            font-size: 20px;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .issue-description {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            line-height: 1.8;
            color: var(--dark);
            font-size: 16px;
            border-left: 4px solid var(--secondary);
        }
        
        /* Progress Notes */
        .progress-notes-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .progress-notes-content {
            background: #f1f8ff;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid var(--secondary);
            margin-top: 15px;
        }
        
        /* Image Gallery */
        .image-gallery-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .gallery-item {
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s;
            aspect-ratio: 1;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: var(--secondary);
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gallery-category {
            margin-bottom: 30px;
        }
        
        .gallery-category h4 {
            font-size: 16px;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Status Timeline */
        .timeline-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
            margin-top: 20px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--secondary);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -33px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--secondary);
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--secondary);
        }
        
        .timeline-date {
            font-size: 14px;
            color: var(--gray);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .timeline-content {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--secondary);
        }
        
        .timeline-status {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .timeline-notes {
            font-size: 14px;
            color: var(--gray);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0170b5;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,168,255,0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid #e1e5e9;
        }
        
        .btn-secondary:hover {
            background: #f1f2f6;
            border-color: var(--secondary);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar, .details-container {
                padding: 15px 20px;
            }
            
            .report-title-section {
                flex-direction: column;
            }
            
            .report-info-grid {
                grid-template-columns: 1fr;
            }
            
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <h1>HFRS</h1>
        </div>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="report.php"><i class="fas fa-plus-circle"></i> New Report</a></li>
            <li><a href="myreport.php"><i class="fas fa-history"></i> My Reports</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Details Container -->
    <div class="details-container">
        <!-- Back Button -->
        <div class="back-btn">
            <a href="myreport.php"><i class="fas fa-arrow-left"></i> Back to My Reports</a>
        </div>
        
        <!-- Report Header -->
        <div class="report-header-details">
            <div class="report-title-section">
                <div>
                    <div class="report-id-big">
                        <i class="fas fa-file-alt"></i>
                        Report #<?php echo str_pad($report['report_id'], 6, '0', STR_PAD_LEFT); ?>
                    </div>
                    <p style="color: var(--gray); margin-top: 5px;">
                        <i class="far fa-calendar"></i> Submitted on <?php echo $report_date; ?>
                    </p>
                </div>
                <div class="report-status-section">
                    <?php 
                    $status_class = 'status-' . strtolower($report['status']);
                    $priority_class = isset($report['priority']) ? 'priority-' . strtolower($report['priority']) : 'priority-medium';
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <i class="fas fa-circle"></i>
                        <?php echo $report['status']; ?>
                    </span>
                    <span class="priority-badge <?php echo $priority_class; ?>">
                        <i class="fas fa-flag"></i>
                        <?php echo $report['priority'] ?? 'Medium'; ?>
                    </span>
                </div>
            </div>
            
            <!-- Report Info Grid -->
            <div class="report-info-grid">
                <div class="info-card">
                    <h3><i class="fas fa-user"></i> Reported By</h3>
                    <p><?php echo htmlspecialchars($report['user_name']); ?></p>
                </div>
                
                <?php if(!empty($hostel_block)): ?>
                <div class="info-card">
                    <h3><i class="fas fa-hotel"></i> Hostel Block</h3>
                    <p><?php echo htmlspecialchars($hostel_block); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($room_number)): ?>
                <div class="info-card">
                    <h3><i class="fas fa-door-closed"></i> Room Number</h3>
                    <p><?php echo htmlspecialchars($room_number); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($report['technician_name'])): ?>
                <div class="info-card">
                    <h3><i class="fas fa-user-cog"></i> Assigned Technician</h3>
                    <p><?php echo htmlspecialchars($report['technician_name']); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="info-card">
                    <h3><i class="fas fa-clock"></i> Last Updated</h3>
                    <p><?php echo $updated_date; ?></p>
                </div>
                
                <?php if(isset($report['facility_name']) && !empty($report['facility_name'])): ?>
                <div class="info-card">
                    <h3><i class="fas fa-building"></i> Facility Type</h3>
                    <p><?php echo htmlspecialchars($report['facility_name']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Description Section -->
        <div class="description-section">
            <h2 class="section-title"><i class="fas fa-align-left"></i> Issue Description</h2>
            <div class="issue-description">
                <?php echo nl2br(htmlspecialchars($report['issue_description'])); ?>
            </div>
        </div>
        
        <!-- Progress Notes -->
        <?php if(!empty($report['progress_notes'])): ?>
        <div class="progress-notes-section">
            <h2 class="section-title"><i class="fas fa-sticky-note"></i> Technician Progress Notes</h2>
            <div class="progress-notes-content">
                <?php echo nl2br(htmlspecialchars($report['progress_notes'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Image Gallery -->
        <?php if((!empty($report['image_path']) && $report['image_path'] != 'uploads/') || !empty($report['completed_image']) || mysqli_num_rows($photos_result) > 0): ?>
        <div class="image-gallery-section">
            <h2 class="section-title"><i class="fas fa-images"></i> Photos & Attachments</h2>
            
            <!-- Before/During Photos -->
            <?php if(!empty($report['image_path']) && $report['image_path'] != 'uploads/'): ?>
            <div class="gallery-category">
                <h4><i class="fas fa-camera"></i> Issue Photos (Before/During)</h4>
                <div class="gallery-grid">
                    <?php 
                    $images = explode(',', $report['image_path']);
                    foreach($images as $image):
                        if(!empty(trim($image)) && trim($image) != 'uploads/'):
                    ?>
                    <div class="gallery-item" onclick="openImageModal('<?php echo htmlspecialchars(trim($image)); ?>')">
                        <img src="<?php echo htmlspecialchars(trim($image)); ?>" alt="Issue Photo">
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- After/Completed Photos -->
            <?php if(!empty($report['completed_image'])): ?>
            <div class="gallery-category">
                <h4><i class="fas fa-check-circle"></i> Completed Photos (After)</h4>
                <div class="gallery-grid">
                    <?php 
                    $completed_images = explode(',', $report['completed_image']);
                    foreach($completed_images as $image):
                        if(!empty(trim($image))):
                    ?>
                    <div class="gallery-item" onclick="openImageModal('<?php echo htmlspecialchars(trim($image)); ?>')">
                        <img src="<?php echo htmlspecialchars(trim($image)); ?>" alt="Completed Photo">
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Task Photos from task_photos table -->
            <?php if(mysqli_num_rows($photos_result) > 0): 
                $before_photos = [];
                $after_photos = [];
                $progress_photos = [];
                
                while($photo = mysqli_fetch_assoc($photos_result)) {
                    if($photo['photo_type'] == 'before') $before_photos[] = $photo;
                    if($photo['photo_type'] == 'after') $after_photos[] = $photo;
                    if($photo['photo_type'] == 'progress') $progress_photos[] = $photo;
                }
                
                if(!empty($before_photos)): ?>
                <div class="gallery-category">
                    <h4><i class="fas fa-camera"></i> Task Photos (Before)</h4>
                    <div class="gallery-grid">
                        <?php foreach($before_photos as $photo): ?>
                        <div class="gallery-item" onclick="openImageModal('<?php echo htmlspecialchars($photo['photo_path']); ?>')">
                            <img src="<?php echo htmlspecialchars($photo['photo_path']); ?>" alt="Before Photo">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($after_photos)): ?>
                <div class="gallery-category">
                    <h4><i class="fas fa-check-circle"></i> Task Photos (After)</h4>
                    <div class="gallery-grid">
                        <?php foreach($after_photos as $photo): ?>
                        <div class="gallery-item" onclick="openImageModal('<?php echo htmlspecialchars($photo['photo_path']); ?>')">
                            <img src="<?php echo htmlspecialchars($photo['photo_path']); ?>" alt="After Photo">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($progress_photos)): ?>
                <div class="gallery-category">
                    <h4><i class="fas fa-tasks"></i> Task Progress Photos</h4>
                    <div class="gallery-grid">
                        <?php foreach($progress_photos as $photo): ?>
                        <div class="gallery-item" onclick="openImageModal('<?php echo htmlspecialchars($photo['photo_path']); ?>')">
                            <img src="<?php echo htmlspecialchars($photo['photo_path']); ?>" alt="Progress Photo">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Status Updates Timeline -->
        <?php if(mysqli_num_rows($updates_result) > 0): ?>
        <div class="timeline-section">
            <h2 class="section-title"><i class="fas fa-history"></i> Status History</h2>
            <div class="timeline">
                <?php while($update = mysqli_fetch_assoc($updates_result)): 
                    $update_date = date('F j, Y \a\t g:i A', strtotime($update['update_date']));
                ?>
                <div class="timeline-item">
                    <div class="timeline-date">
                        <i class="far fa-calendar"></i> <?php echo $update_date; ?>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-status">
                            <i class="fas fa-sync-alt"></i> 
                            <?php echo $update['new_status'] ?? 'Status Update'; ?>
                        </div>
                        <?php if(!empty($update['notes'])): ?>
                        <div class="timeline-notes">
                            <?php echo nl2br(htmlspecialchars($update['notes'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="myreport.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; align-items: center; justify-content: center;">
        <img id="modalImage" src="" style="max-width: 90%; max-height: 90%; object-fit: contain;">
        <button onclick="closeImageModal()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 30px; cursor: pointer;">×</button>
    </div>

    <script>
        // Image Modal Functions
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside the image
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>
<?php
// Close database connection
mysqli_close($conn);
?>