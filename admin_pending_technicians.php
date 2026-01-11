<?php
session_start();
include('db_connect.php');

// Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Ensure approval_status column exists
$check_approval = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'approval_status'");
if (mysqli_num_rows($check_approval) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN approval_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending'");
}

// Handle approval/rejection
$success_message = '';
$error_message = '';

if (isset($_GET['approve'])) {
    $tech_id = mysqli_real_escape_string($conn, $_GET['approve']);
    $update_query = "UPDATE users SET approval_status = 'Approved', status = 'Active' WHERE user_id = '$tech_id' AND role = 'Technician'";
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Technician registration approved successfully!";
    } else {
        $error_message = "Failed to approve technician registration.";
    }
}

if (isset($_GET['reject'])) {
    $tech_id = mysqli_real_escape_string($conn, $_GET['reject']);
    $update_query = "UPDATE users SET approval_status = 'Rejected' WHERE user_id = '$tech_id' AND role = 'Technician'";
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Technician registration rejected.";
    } else {
        $error_message = "Failed to reject technician registration.";
    }
}

// Fetch pending technician registrations
$pending_query = mysqli_query($conn, "
    SELECT 
        u.user_id,
        u.full_name,
        u.email,
        u.phone,
        u.approval_status,
        u.certificate_path,
        u.created_at
    FROM users u
    WHERE u.role = 'Technician' 
    AND (u.approval_status = 'Pending' OR u.approval_status IS NULL)
    ORDER BY u.created_at DESC
");

// Fetch statistics
$pending_count = mysqli_num_rows($pending_query);
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM users WHERE role = 'Technician' AND (approval_status = 'Pending' OR approval_status IS NULL)"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Technician Registrations - HFRS Admin</title>
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
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* STATS OVERVIEW */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
        }

        .stat-icon.pending { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); }

        .stat-content h3 {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .stat-content .value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        /* PENDING CARDS */
        .pending-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }

        .pending-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid var(--primary);
        }

        .pending-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            border-color: var(--primary-dark);
        }

        .pending-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px 25px;
        }

        .pending-header h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .pending-header p {
            font-size: 13px;
            opacity: 0.9;
        }

        .pending-body {
            padding: 25px;
        }

        .pending-info {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .info-row i {
            width: 20px;
            color: var(--gray);
        }

        .certificate-section {
            background: #f9fafb;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .certificate-section h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .certificate-preview {
            margin-bottom: 10px;
        }

        .certificate-preview img,
        .certificate-preview iframe {
            max-width: 100%;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
        }

        .certificate-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 15px;
            background: white;
            border-radius: 8px;
            border: 2px solid var(--primary);
            transition: all 0.3s;
        }

        .certificate-link:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            text-decoration: none;
        }

        .btn-approve {
            background: var(--secondary);
            color: white;
        }

        .btn-approve:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-reject {
            background: var(--danger);
            color: white;
        }

        .btn-reject:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 25px;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 20px;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 25px;
            max-width: 500px;
            margin: 0 auto 25px;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray);
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: var(--danger);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-body img {
            max-width: 100%;
            border-radius: 8px;
        }

        .modal-body iframe {
            width: 100%;
            min-height: 600px;
            border: none;
            border-radius: 8px;
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header h2,
            .sidebar-header p,
            .admin-profile-sidebar,
            .sidebar nav a span {
                display: none;
            }
            
            .sidebar nav a {
                justify-content: center;
                padding: 15px;
            }
            
            .main-content {
                margin-left: 80px;
                padding: 25px;
            }

            .pending-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .pending-grid {
                grid-template-columns: 1fr;
            }
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
                <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
                <li><a href="admin_reports.php"><i class="fa-solid fa-file-alt"></i> <span>Manage Reports</span></a></li>
                <li><a href="admin_technicians.php"><i class="fa-solid fa-user-gear"></i> <span>Technicians</span></a></li>
                <li><a href="admin_pending_technicians.php" class="active"><i class="fa-solid fa-hourglass-half"></i> <span>Pending Registrations</span></a></li>
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
                <h1 class="page-title">Pending Technician Registrations</h1>
                <p class="page-subtitle">Review and approve technician registration requests</p>
            </div>
            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo substr($_SESSION['user_name'] ?? 'A', 0, 1); ?>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 16px;"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></div>
                    <div style="font-size: 13px; color: var(--gray);">Administrator</div>
                </div>
            </div>
        </div>

        <!-- MESSAGES -->
        <?php if ($success_message): ?>
            <div class="message success">
                <i class="fa-solid fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error">
                <i class="fa-solid fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- STATS OVERVIEW -->
        <div class="stats-overview">
            <div class="stat-box">
                <div class="stat-icon pending">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <h3>Pending Registrations</h3>
                    <div class="value"><?php echo $total_pending; ?></div>
                </div>
            </div>
        </div>

        <!-- PENDING REGISTRATIONS -->
        <div class="pending-grid">
            <?php if (mysqli_num_rows($pending_query) > 0): ?>
                <?php 
                // Reset pointer
                mysqli_data_seek($pending_query, 0);
                while ($pending = mysqli_fetch_assoc($pending_query)): 
                ?>
                    <div class="pending-card">
                        <div class="pending-header">
                            <h3><?php echo htmlspecialchars($pending['full_name']); ?></h3>
                            <p>Registered on <?php echo date('F j, Y', strtotime($pending['created_at'])); ?></p>
                        </div>
                        
                        <div class="pending-body">
                            <div class="pending-info">
                                <div class="info-row">
                                    <i class="fa-solid fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($pending['email']); ?></span>
                                </div>
                                <?php if (!empty($pending['phone'])): ?>
                                <div class="info-row">
                                    <i class="fa-solid fa-phone"></i>
                                    <span><?php echo htmlspecialchars($pending['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="info-row">
                                    <i class="fa-solid fa-calendar"></i>
                                    <span>Registered: <?php echo date('M d, Y g:i A', strtotime($pending['created_at'])); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($pending['certificate_path']) && file_exists($pending['certificate_path'])): ?>
                            <div class="certificate-section">
                                <h4><i class="fa-solid fa-certificate"></i> Certificate / Proof</h4>
                                <?php
                                $file_extension = strtolower(pathinfo($pending['certificate_path'], PATHINFO_EXTENSION));
                                if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): 
                                ?>
                                    <div class="certificate-preview">
                                        <img src="<?php echo htmlspecialchars($pending['certificate_path']); ?>" 
                                             alt="Certificate" 
                                             style="max-height: 200px; cursor: pointer;"
                                             onclick="openModal('<?php echo htmlspecialchars($pending['certificate_path']); ?>', 'image')">
                                    </div>
                                <?php elseif ($file_extension == 'pdf'): ?>
                                    <div class="certificate-preview">
                                        <iframe src="<?php echo htmlspecialchars($pending['certificate_path']); ?>" 
                                                style="width: 100%; height: 300px; border-radius: 8px;"></iframe>
                                    </div>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars($pending['certificate_path']); ?>" 
                                   target="_blank" 
                                   class="certificate-link">
                                    <i class="fa-solid fa-external-link-alt"></i>
                                    View Full Certificate
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="certificate-section">
                                <p style="color: var(--danger); font-size: 14px;">
                                    <i class="fa-solid fa-exclamation-triangle"></i> 
                                    Certificate not found or not uploaded.
                                </p>
                            </div>
                            <?php endif; ?>

                            <div class="action-buttons">
                                <a href="?approve=<?php echo $pending['user_id']; ?>" 
                                   class="btn btn-approve"
                                   onclick="return confirm('Are you sure you want to approve <?php echo htmlspecialchars($pending['full_name']); ?>?');">
                                    <i class="fa-solid fa-check"></i>
                                    Approve
                                </a>
                                <a href="?reject=<?php echo $pending['user_id']; ?>" 
                                   class="btn btn-reject"
                                   onclick="return confirm('Are you sure you want to reject <?php echo htmlspecialchars($pending['full_name']); ?>?');">
                                    <i class="fa-solid fa-times"></i>
                                    Reject
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-check-circle"></i>
                    <h4>No Pending Registrations</h4>
                    <p>All technician registrations have been reviewed. New registrations will appear here when submitted.</p>
                    <a href="admin_technicians.php" class="btn btn-approve">
                        <i class="fa-solid fa-arrow-left"></i>
                        View Approved Technicians
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL FOR CERTIFICATE VIEWING -->
    <div class="modal" id="certificateModal" onclick="closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3>Certificate / Proof</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be inserted here -->
            </div>
        </div>
    </div>

    <script>
        function openModal(filePath, type) {
            const modal = document.getElementById('certificateModal');
            const modalBody = document.getElementById('modalBody');
            
            if (type === 'image') {
                modalBody.innerHTML = `<img src="${filePath}" alt="Certificate" style="width: 100%;">`;
            } else if (type === 'pdf') {
                modalBody.innerHTML = `<iframe src="${filePath}" style="width: 100%; height: 80vh; border: none;"></iframe>`;
            }
            
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('certificateModal').classList.remove('active');
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
