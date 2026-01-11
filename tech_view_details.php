<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Technician') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid report ID");
}

$report_id = intval($_GET['id']);

$sql = "
SELECT r.*, 
       u.full_name AS reported_by,
       u.hostel_block,
       u.room_no,
       tech.full_name as assigned_technician,
       f.facility_name
FROM reports r
JOIN users u ON r.user_id = u.user_id
LEFT JOIN users tech ON r.assigned_to = tech.user_id
LEFT JOIN facilities f ON r.facility_id = f.facility_id
WHERE r.report_id = $report_id
";

$result = mysqli_query($conn, $sql);
$report = mysqli_fetch_assoc($result);

if (!$report) {
    die("Report not found");
}

// Format dates
$report_date = date('d M Y, h:i A', strtotime($report['report_date']));
$updated_date = !empty($report['updated_at']) ? date('d M Y, h:i A', strtotime($report['updated_at'])) : 'Not updated';
$completed_date = !empty($report['completed_date']) ? date('d M Y, h:i A', strtotime($report['completed_date'])) : 'Not completed';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Details - HFRS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --border: #e2e8f0;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --hover-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }
        
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: var(--dark);
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border-left: 6px solid var(--primary);
        }
        
        .header-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
        
        .header-content h1 {
            font-size: 36px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .header-content p {
            color: var(--gray);
            font-size: 18px;
        }
        
        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        /* Task Card */
        .task-details-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            position: relative;
        }
        
        .task-details-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        /* Task Header */
        .task-header {
            padding: 25px 30px;
            background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .task-id-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .task-id-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .task-id-text h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .task-id-text small {
            color: var(--gray);
            font-size: 14px;
        }
        
        /* Status Section */
        .status-section {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        /* Priority Badge */
        .priority-badge {
            padding: 10px 22px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .priority-low { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 2px solid #a7f3d0;
        }
        
        .priority-medium { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 2px solid #fde68a;
        }
        
        .priority-high { 
            background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
            color: #92400e;
            border: 2px solid #fdba74;
        }
        
        .priority-urgent { 
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #991b1b;
            border: 2px solid #fca5a5;
        }
        
        /* Status Badge */
        .status-badge {
            padding: 10px 22px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-pending { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 2px solid #fde68a;
        }
        
        .status-ongoing { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 2px solid #bfdbfe;
        }
        
        .status-completed { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 2px solid #a7f3d0;
        }
        
        /* Task Body */
        .task-body {
            padding: 30px;
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border);
            transition: all 0.3s;
        }
        
        .info-item:hover {
            background: white;
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .info-label {
            font-size: 13px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-label i {
            color: var(--primary);
            font-size: 16px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }
        
        /* Issue Description */
        .issue-section {
            background: #fefce8;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            border: 2px solid #fde68a;
            position: relative;
        }
        
        .issue-section::before {
            content: 'Issue Description';
            position: absolute;
            top: -12px;
            left: 25px;
            background: #fde68a;
            color: #92400e;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .issue-description {
            color: var(--dark);
            line-height: 1.8;
            font-size: 16px;
            white-space: pre-line;
        }
        
        /* Progress Notes */
        <?php if (!empty($report['progress_notes'])): ?>
        .progress-section {
            background: #eff6ff;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            border: 2px solid #bfdbfe;
            position: relative;
        }
        
        .progress-section::before {
            content: 'Progress Notes';
            position: absolute;
            top: -12px;
            left: 25px;
            background: #bfdbfe;
            color: #1e40af;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .progress-notes {
            color: var(--dark);
            line-height: 1.7;
            font-size: 15px;
            white-space: pre-line;
        }
        <?php endif; ?>
        
        /* Image Gallery */
        <?php if (!empty($report['image_path']) || !empty($report['completed_image'])): ?>
        .gallery-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            border: 2px solid var(--border);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .gallery-title {
            font-size: 18px;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .gallery-item {
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid var(--border);
            cursor: pointer;
            transition: all 0.3s;
            aspect-ratio: 1;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
            border-color: var(--primary);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        <?php endif; ?>
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid var(--border);
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(37, 99, 235, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 2px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: var(--primary);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }
            
            .task-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .status-section {
                width: 100%;
                justify-content: flex-start;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .task-details-card {
            animation: fadeIn 0.6s ease forwards;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="header-content">
                <h1>Task Details</h1>
                <p>Complete information about the maintenance task</p>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Task Details Card -->
            <div class="task-details-card">
                <!-- Task Header -->
                <div class="task-header">
                    <div class="task-id-section">
                        <div class="task-id-icon">
                            <i class="fas fa-hashtag"></i>
                        </div>
                        <div class="task-id-text">
                            <h3>Report #<?php echo str_pad($report['report_id'], 6, '0', STR_PAD_LEFT); ?></h3>
                            <small>Submitted: <?php echo $report_date; ?></small>
                        </div>
                    </div>
                    <div class="status-section">
                        <?php 
                        $priority_class = 'priority-' . strtolower($report['priority']);
                        $status_class = 'status-' . strtolower($report['status']);
                        ?>
                        <span class="priority-badge <?php echo $priority_class; ?>">
                            <i class="fas fa-flag"></i>
                            <?php echo $report['priority']; ?>
                        </span>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <i class="fas fa-circle"></i>
                            <?php echo $report['status']; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Task Body -->
                <div class="task-body">
                    <!-- Information Grid -->
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-building"></i>
                                Facility
                            </div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($report['facility_name'] ?: 'General Facility'); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-hotel"></i>
                                Hostel Block
                            </div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($report['hostel_block'] ?: 'Not specified'); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-door-closed"></i>
                                Room Number
                            </div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($report['room_no'] ?: 'Not specified'); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-user"></i>
                                Reported By
                            </div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($report['reported_by']); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-user-cog"></i>
                                Assigned Technician
                            </div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($report['assigned_technician'] ?: 'Not assigned'); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-clock"></i>
                                Last Updated
                            </div>
                            <div class="info-value">
                                <?php echo $updated_date; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Issue Description -->
                    <div class="issue-section">
                        <div class="issue-description">
                            <?php echo nl2br(htmlspecialchars($report['issue_description'])); ?>
                        </div>
                    </div>
                    
                    <!-- Progress Notes -->
                    <?php if (!empty($report['progress_notes'])): ?>
                    <div class="progress-section">
                        <div class="progress-notes">
                            <?php echo nl2br(htmlspecialchars($report['progress_notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Image Gallery -->
                    <?php if (!empty($report['image_path']) || !empty($report['completed_image'])): ?>
                    <div class="gallery-section">
                        <h3 class="gallery-title">
                            <i class="fas fa-images"></i> Task Photos
                        </h3>
                        <div class="gallery-grid">
                            <?php 
                            // Display issue photos
                            if (!empty($report['image_path'])) {
                                $issue_photos = explode(',', $report['image_path']);
                                foreach($issue_photos as $photo) {
                                    if(!empty(trim($photo))) {
                                        echo '<div class="gallery-item" onclick="openImageModal(\'' . htmlspecialchars(trim($photo)) . '\')">
                                                <img src="' . htmlspecialchars(trim($photo)) . '" alt="Issue Photo">
                                              </div>';
                                    }
                                }
                            }
                            
                            // Display completed photos
                            if (!empty($report['completed_image'])) {
                                $completed_photos = explode(',', $report['completed_image']);
                                foreach($completed_photos as $photo) {
                                    if(!empty(trim($photo))) {
                                        echo '<div class="gallery-item" onclick="openImageModal(\'' . htmlspecialchars(trim($photo)) . '\')">
                                                <img src="' . htmlspecialchars(trim($photo)) . '" alt="Completed Photo">
                                              </div>';
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="complete_tech.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Tasks
                        </a>
                        <?php if($report['status'] !== 'Completed'): ?>
                        <a href="complete_task.php?id=<?php echo $report_id; ?>" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Mark as Completed
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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