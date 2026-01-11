<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Technician') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$report_id = intval($_GET['id']);
$technician_id = $_SESSION['user_id'];

// Get all photos from multiple sources
$query = mysqli_query($conn,
    "SELECT 
        r.report_id,
        r.image_path,
        r.completed_image,
        u.full_name as reporter_name,
        r.issue_description,
        r.report_date,
        r.status,
        r.priority,
        (SELECT GROUP_CONCAT(photo_path SEPARATOR ',') 
         FROM task_photos 
         WHERE task_id = r.report_id) as task_photos
     FROM reports r
     JOIN users u ON r.user_id = u.user_id
     WHERE r.report_id = $report_id 
     AND r.assigned_to = $technician_id"
);

$report = mysqli_fetch_assoc($query);

if (!$report) {
    die("Report not found or unauthorized access");
}

// Collect all photos
$all_photos = [];

// Issue photos
if (!empty($report['image_path'])) {
    $issue_photos = explode(',', $report['image_path']);
    foreach ($issue_photos as $photo) {
        if (!empty(trim($photo))) {
            $all_photos[] = [
                'path' => trim($photo),
                'type' => 'Issue Photo',
                'icon' => 'fas fa-camera',
                'color' => '#f59e0b'
            ];
        }
    }
}

// Completed photos
if (!empty($report['completed_image'])) {
    $completed_photos = explode(',', $report['completed_image']);
    foreach ($completed_photos as $photo) {
        if (!empty(trim($photo))) {
            $all_photos[] = [
                'path' => trim($photo),
                'type' => 'Completed Work',
                'icon' => 'fas fa-check-circle',
                'color' => '#10b981'
            ];
        }
    }
}

// Task photos
if (!empty($report['task_photos'])) {
    $task_photos_list = explode(',', $report['task_photos']);
    foreach ($task_photos_list as $photo) {
        if (!empty(trim($photo))) {
            $all_photos[] = [
                'path' => trim($photo),
                'type' => 'Task Progress',
                'icon' => 'fas fa-tasks',
                'color' => '#3b82f6'
            ];
        }
    }
}

$report_date = date('d M Y, h:i A', strtotime($report['report_date']));
$total_photos = count($all_photos);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Photos - HFRS</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 25px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border-left: 6px solid var(--primary);
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
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
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .header-content p {
            color: var(--gray);
            font-size: 16px;
        }
        
        /* Photo Counter */
        .photo-counter {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Report Info */
        .report-info {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .info-chip {
            background: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 500;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
        }
        
        /* Photo Gallery */
        .gallery-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        .gallery-title {
            font-size: 24px;
            color: var(--dark);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* Grid Layout */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        @media (max-width: 768px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        /* Photo Card */
        .photo-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 2px solid var(--border);
            transition: all 0.4s ease;
            position: relative;
        }
        
        .photo-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--hover-shadow);
            border-color: var(--primary);
        }
        
        .photo-header {
            padding: 15px 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .photo-type {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .photo-number {
            background: var(--light);
            color: var(--dark);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        
        .photo-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .photo-image:hover {
            transform: scale(1.05);
        }
        
        .photo-footer {
            padding: 15px 20px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
        }
        
        .photo-path {
            font-size: 12px;
            color: var(--gray);
            word-break: break-all;
            font-family: monospace;
        }
        
        /* No Photos */
        .no-photos {
            text-align: center;
            padding: 60px 40px;
            grid-column: 1 / -1;
        }
        
        .no-photos-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: var(--primary);
            margin: 0 auto 25px;
            border: 4px solid #bae6fd;
        }
        
        .no-photos h3 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .no-photos p {
            color: var(--gray);
            font-size: 16px;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }
        
        .btn {
            padding: 15px 35px;
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
            transform: translateY(-3px);
        }
        
        /* Image Modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-image {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 10px;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .modal-info {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            color: white;
            padding: 15px;
            background: rgba(0, 0, 0, 0.7);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-left {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .gallery-grid {
                grid-template-columns: 1fr;
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
        
        .photo-card {
            animation: fadeIn 0.6s ease forwards;
        }
        
        .photo-card:nth-child(1) { animation-delay: 0.1s; }
        .photo-card:nth-child(2) { animation-delay: 0.2s; }
        .photo-card:nth-child(3) { animation-delay: 0.3s; }
        .photo-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="header-content">
                    <h1>Report Photos</h1>
                    <p>Report #<?php echo str_pad($report['report_id'], 6, '0', STR_PAD_LEFT); ?> - <?php echo htmlspecialchars($report['reporter_name']); ?></p>
                </div>
            </div>
            <div class="photo-counter">
                <i class="fas fa-camera"></i>
                <?php echo $total_photos; ?> Photos
            </div>
        </div>
        
        <!-- Report Information -->
        <div class="report-info">
            <div class="info-chip">
                <i class="fas fa-calendar"></i>
                <?php echo $report_date; ?>
            </div>
            <div class="info-chip">
                <i class="fas fa-flag"></i>
                Priority: <?php echo htmlspecialchars($report['priority']); ?>
            </div>
            <div class="info-chip">
                <i class="fas fa-circle"></i>
                Status: <?php echo htmlspecialchars($report['status']); ?>
            </div>
            <div class="info-chip">
                <i class="fas fa-align-left"></i>
                Issue: <?php echo htmlspecialchars(substr($report['issue_description'], 0, 30)) . '...'; ?>
            </div>
        </div>
        
        <!-- Photo Gallery -->
        <div class="gallery-section">
            <h2 class="gallery-title">
                <i class="fas fa-th-large"></i>
                Photo Gallery
            </h2>
            
            <?php if ($total_photos > 0): ?>
                <div class="gallery-grid">
                    <?php foreach ($all_photos as $index => $photo): ?>
                    <div class="photo-card">
                        <div class="photo-header">
                            <div class="photo-type" style="color: <?php echo $photo['color']; ?>">
                                <i class="<?php echo $photo['icon']; ?>"></i>
                                <?php echo $photo['type']; ?>
                            </div>
                            <div class="photo-number">
                                <?php echo $index + 1; ?>
                            </div>
                        </div>
                        
                        <img src="<?php echo htmlspecialchars($photo['path']); ?>" 
                             alt="<?php echo $photo['type']; ?>" 
                             class="photo-image"
                             onclick="openImageModal('<?php echo htmlspecialchars($photo['path']); ?>', '<?php echo $photo['type']; ?>')"
                             onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg width=\"400\" height=\"300\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Crect width=\"100%25\" height=\"100%25\" fill=\"%23f3f4f6\"/%3E%3Ctext x=\"50%25\" y=\"50%25\" font-family=\"Arial\" font-size=\"16\" fill=\"%239ca3af\" text-anchor=\"middle\" dy=\".3em\"%3EImage not found%3C/text%3E%3C/svg%3E'">
                        
                        <div class="photo-footer">
                            <div class="photo-path" title="<?php echo htmlspecialchars($photo['path']); ?>">
                                <i class="fas fa-link"></i> 
                                <?php echo htmlspecialchars(basename($photo['path'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <p style="text-align: center; color: var(--gray); margin-top: 25px; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> Click on any photo to view it in full size
                </p>
            <?php else: ?>
                <div class="no-photos">
                    <div class="no-photos-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    <h3>No Photos Available</h3>
                    <p>This report doesn't have any photos attached yet. Photos may be added as issue photos, completed work photos, or task progress photos.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="complete_tech.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Tasks
            </a>
            <a href="tech_view_details.php?id=<?php echo $report_id; ?>" class="btn btn-primary">
                <i class="fas fa-file-alt"></i> View Report Details
            </a>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <button class="modal-close" onclick="closeImageModal()">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" class="modal-image" src="" alt="Full size image">
        <div class="modal-info">
            <div id="modalType"></div>
            <div id="modalPath" style="font-size: 12px; opacity: 0.8; margin-top: 5px;"></div>
        </div>
    </div>

    <script>
        // Image Modal Functions
        function openImageModal(imageSrc, imageType) {
            const modalImage = document.getElementById('modalImage');
            const modalType = document.getElementById('modalType');
            const modalPath = document.getElementById('modalPath');
            const modal = document.getElementById('imageModal');
            
            modalImage.src = imageSrc;
            modalType.textContent = imageType;
            modalPath.textContent = imageSrc;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
        
        // Close modal when clicking outside the image
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
        
        // Photo card hover animation
        document.querySelectorAll('.photo-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>