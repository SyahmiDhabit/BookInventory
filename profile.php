<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('db_connect.php');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];

// Fetch user data
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);

// Fetch user statistics
$stats_query = "SELECT 
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_reports,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_reports,
    SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing_reports
    FROM reports WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$user_stats = mysqli_fetch_assoc($stats_result);

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        
        // Check file type and size
        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/profile_pictures/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Delete old profile picture if exists
                if (!empty($user_data['profile_image']) && file_exists($user_data['profile_image'])) {
                    unlink($user_data['profile_image']);
                }
                
                // Update database
                $update_pic_query = "UPDATE users SET profile_image = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $update_pic_query);
                mysqli_stmt_bind_param($stmt, "si", $upload_path, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $user_data['profile_image'] = $upload_path;
                    $message = "Profile picture updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Failed to update profile picture. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "Failed to upload file. Please try again.";
                $message_type = "error";
            }
        } else {
            if (!in_array($file_type, $allowed_types)) {
                $message = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
            } else {
                $message = "File size too large. Maximum size is 2MB.";
            }
            $message_type = "error";
        }
    }
    
    // Handle profile information update
    if (isset($_POST['update_profile'])) {
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $hostel_block = mysqli_real_escape_string($conn, $_POST['hostel_block']);
        $room_no = mysqli_real_escape_string($conn, $_POST['room_no']);
        
        $update_query = "UPDATE users SET 
            full_name = ?,
            email = ?,
            phone = ?,
            hostel_block = ?,
            room_no = ?
            WHERE user_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "sssssi", $full_name, $email, $phone, $hostel_block, $room_no, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['user_name'] = $full_name;
            $user_name = $full_name;
            $user_data['full_name'] = $full_name;
            $user_data['email'] = $email;
            $user_data['phone'] = $phone;
            $user_data['hostel_block'] = $hostel_block;
            $user_data['room_no'] = $room_no;
            
            if (empty($message)) { // Only set message if no picture upload message exists
                $message = "Profile updated successfully!";
                $message_type = "success";
            }
        } else {
            if (empty($message)) {
                $message = "Failed to update profile. Please try again.";
                $message_type = "error";
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (password_verify($current_password, $user_data['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_query = "UPDATE users SET password = ? WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $password_query);
                    mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        if (empty($message)) {
                            $message = "Password changed successfully!";
                            $message_type = "success";
                        }
                    } else {
                        if (empty($message)) {
                            $message = "Failed to change password. Please try again.";
                            $message_type = "error";
                        }
                    }
                } else {
                    if (empty($message)) {
                        $message = "Password must be at least 6 characters long.";
                        $message_type = "error";
                    }
                }
            } else {
                if (empty($message)) {
                    $message = "New passwords do not match.";
                    $message_type = "error";
                }
            }
        } else {
            if (empty($message)) {
                $message = "Current password is incorrect.";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HFRS</title>
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

        /* Profile Container */
        .profile-container {
            padding: 30px 60px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .profile-header h1 {
            font-size: 32px;
            color: var(--primary);
        }

        .profile-header .role-badge {
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
        }

        /* Message Alert */
        .message-alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        .message-alert.success {
            background: rgba(46, 213, 115, 0.1);
            border: 1px solid rgba(46, 213, 115, 0.3);
            color: #155724;
        }

        .message-alert.error {
            background: rgba(255, 71, 87, 0.1);
            border: 1px solid rgba(255, 71, 87, 0.3);
            color: #721c24;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Profile Grid */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 1024px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-header i {
            font-size: 24px;
            color: var(--secondary);
            width: 50px;
            height: 50px;
            background: rgba(0, 168, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-header h3 {
            font-size: 20px;
            color: var(--primary);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 14px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(0,168,255,0.1);
            outline: none;
        }

        .form-group input:disabled {
            background: #f8f9fa;
            color: var(--gray);
            cursor: not-allowed;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background: #0170b5;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f1f2f6;
            color: var(--primary);
            border: 2px solid #e1e5e9;
        }

        .btn-secondary:hover {
            background: #dfe4ea;
            border-color: var(--secondary);
        }

        /* User Stats */
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-item .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            display: block;
            margin-bottom: 5px;
        }

        .stat-item .label {
            font-size: 14px;
            color: var(--gray);
        }

        /* Profile Picture Section */
        .profile-picture-section {
            text-align: center;
            padding: 30px 0;
            position: relative;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, var(--secondary), var(--primary));
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .profile-picture .initials {
            font-size: 48px;
            font-weight: 700;
            color: white;
        }

        .upload-btn {
            display: inline-block;
            padding: 8px 20px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.3s;
            cursor: pointer;
        }

        .upload-btn:hover {
            background: var(--secondary);
        }

        .upload-btn input[type="file"] {
            display: none;
        }

        .profile-picture-upload {
            margin-top: 20px;
        }

        .profile-picture-upload form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .file-input-container {
            position: relative;
            margin-bottom: 10px;
        }

        .file-input-label {
            background: var(--secondary);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .file-input-label:hover {
            background: #0170b5;
        }

        .file-name {
            font-size: 14px;
            color: var(--gray);
            margin-top: 5px;
            display: none;
        }

        /* Profile Picture Preview */
        .profile-picture-preview {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
            display: none;
        }

        /* Footer */
        footer {
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 60px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .profile-container {
                padding: 20px;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .user-stats {
                grid-template-columns: 1fr;
            }
            
            .profile-picture {
                width: 120px;
                height: 120px;
            }
            
            .profile-picture .initials {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <header class="navbar">
        <div class="logo">
            <img src="images/utemlogo3.png" alt="UTeM Logo">
            <h1>Hostel Facilities Report System</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="report.php"><i class="fas fa-plus-circle"></i> Report</a></li>
                <li><a href="myreport.php"><i class="fas fa-list-alt"></i> My Reports</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user-circle"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Profile Container -->
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <h1>My Profile</h1>
            <div class="role-badge">
                <?php echo htmlspecialchars($role); ?>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="message-alert <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Profile Picture Section -->
        <div class="profile-card profile-picture-section">
            <div class="profile-picture" id="profilePicture">
                <?php if (!empty($user_data['profile_image']) && file_exists($user_data['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user_data['profile_image']); ?>" 
                         alt="Profile Picture" id="profileImage">
                    <div class="initials" id="profileInitials" style="display: none;">
                        <?php 
                        $initials = '';
                        $name_parts = explode(' ', $user_data['full_name']);
                        foreach ($name_parts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                            if (strlen($initials) >= 2) break;
                        }
                        echo $initials;
                        ?>
                    </div>
                <?php else: ?>
                    <div class="initials" id="profileInitials">
                        <?php 
                        $initials = '';
                        $name_parts = explode(' ', $user_data['full_name']);
                        foreach ($name_parts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                            if (strlen($initials) >= 2) break;
                        }
                        echo $initials;
                        ?>
                    </div>
                    <img src="" alt="Profile Picture" id="profileImage" style="display: none;">
                <?php endif; ?>
            </div>
            
            <h3><?php echo htmlspecialchars($user_data['full_name']); ?></h3>
            <p style="color: var(--gray); margin: 10px 0 20px;">Member since <?php echo date('F Y', strtotime($user_data['created_at'])); ?></p>
            
            <!-- Profile Picture Upload Form -->
            <div class="profile-picture-upload">
                <form method="POST" action="profile.php" enctype="multipart/form-data">
                    <div class="file-input-container">
                        <input type="file" id="profile_picture" name="profile_picture" 
                               accept="image/jpeg, image/jpg, image/png, image/gif"
                               onchange="previewImage(this)">
                        <div class="file-name" id="fileName"></div>
                    </div>
                    <button type="submit" class="upload-btn">
                        <i class="fas fa-upload"></i> Upload Photo
                    </button>
                    <p style="font-size: 12px; color: var(--gray); margin-top: 10px;">
                        Maximum file size: 2MB<br>
                        Allowed formats: JPG, PNG, GIF
                    </p>
                </form>
            </div>
        </div>

        <!-- Profile Grid -->
        <div class="profile-grid">
            <!-- Personal Information -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-user"></i>
                    <h3>Personal Information</h3>
                </div>
                <form method="POST" action="profile.php">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                               pattern="[0-9]{10,11}" placeholder="e.g., 0123456789">
                    </div>
                    
                    <div class="form-group">
                        <label for="hostel_block">Hostel Block</label>
                        <select id="hostel_block" name="hostel_block">
                            <option value="">Select Block</option>
                            <option value="Tuah" <?php echo (isset($user_data['hostel_block']) && $user_data['hostel_block'] == 'Tuah') ? 'selected' : ''; ?>>Tuah</option>
                            <option value="Jebat" <?php echo (isset($user_data['hostel_block']) && $user_data['hostel_block'] == 'Jebat') ? 'selected' : ''; ?>>Jebat</option>
                            <option value="Laksamana" <?php echo (isset($user_data['hostel_block']) && $user_data['hostel_block'] == 'Laksamana') ? 'selected' : ''; ?>>Laksamana</option>
                            <option value="Lekiu" <?php echo (isset($user_data['hostel_block']) && $user_data['hostel_block'] == 'Lekiu') ? 'selected' : ''; ?>>Lekiu</option>
                            <option value="Kasturi" <?php echo (isset($user_data['hostel_block']) && $user_data['hostel_block'] == 'Kasturi') ? 'selected' : ''; ?>>Kasturi</option>
                            <option value="Lestari" <?php echo (isset($user_data['hostel_block']) && $user_data['hostel_block'] == 'Lestari') ? 'selected' : ''; ?>>Lestari</option>
                            <option value="Al Jazari" <?php echo (isset($user_data['hostel_block']) && $user_data['hostel_block'] == 'Al Jazari') ? 'selected' : ''; ?>>Al Jazari</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="room_no">Room Number</label>
                        <input type="text" id="room_no" name="room_no" 
                               value="<?php echo htmlspecialchars($user_data['room_no'] ?? ''); ?>" 
                               placeholder="e.g., A-201">
                    </div>
                    
                    <div class="form-group">
                        <label for="user_id">User ID</label>
                        <input type="text" id="user_id" value="<?php echo htmlspecialchars($user_data['user_id']); ?>" disabled>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>

            <!-- Account Settings -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-lock"></i>
                    <h3>Account Security</h3>
                </div>
                <form method="POST" action="profile.php">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <div id="password-strength" style="font-size: 12px; margin-top: 5px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <div id="password-match" style="font-size: 12px; margin-top: 5px;"></div>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>

                <!-- User Statistics -->
                <div style="margin-top: 40px;">
                    <div class="card-header">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Your Activity</h3>
                    </div>
                    <div class="user-stats">
                        <div class="stat-item">
                            <span class="number"><?php echo $user_stats['total_reports'] ?? 0; ?></span>
                            <span class="label">Total Reports</span>
                        </div>
                        <div class="stat-item">
                            <span class="number"><?php echo $user_stats['completed_reports'] ?? 0; ?></span>
                            <span class="label">Completed</span>
                        </div>
                        <div class="stat-item">
                            <span class="number"><?php echo $user_stats['pending_reports'] ?? 0; ?></span>
                            <span class="label">Pending</span>
                        </div>
                        <div class="stat-item">
                            <span class="number"><?php echo $user_stats['ongoing_reports'] ?? 0; ?></span>
                            <span class="label">In Progress</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 Hostel Facilities Report System | Developed for UTeM</p>
    </footer>

    <script>
        // Image preview function
        function previewImage(input) {
            const file = input.files[0];
            const fileNameElement = document.getElementById('fileName');
            const profileImage = document.getElementById('profileImage');
            const profileInitials = document.getElementById('profileInitials');
            
            if (file) {
                // Show file name
                fileNameElement.textContent = file.name;
                fileNameElement.style.display = 'block';
                
                // Check file size (2MB = 2 * 1024 * 1024 bytes)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    input.value = '';
                    fileNameElement.style.display = 'none';
                    return;
                }
                
                // Check file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF)');
                    input.value = '';
                    fileNameElement.style.display = 'none';
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImage.src = e.target.result;
                    profileImage.style.display = 'block';
                    profileInitials.style.display = 'none';
                }
                reader.readAsDataURL(file);
            } else {
                fileNameElement.style.display = 'none';
                
                // If no profile image exists, show initials
                if (!profileImage.src) {
                    profileInitials.style.display = 'block';
                    profileImage.style.display = 'none';
                }
            }
        }

        // Password strength checker
        document.getElementById('new_password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthText = document.getElementById('password-strength');
            let strength = 'Weak';
            let color = '#ff4757';
            
            if (password.length >= 8) {
                strength = 'Medium';
                color = '#ffa502';
            }
            if (password.length >= 10 && /[A-Z]/.test(password) && /[0-9]/.test(password)) {
                strength = 'Strong';
                color = '#2ed573';
            }
            
            strengthText.textContent = `Password strength: ${strength}`;
            strengthText.style.color = color;
        });

        // Confirm password match
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = e.target.value;
            const matchText = document.getElementById('password-match');
            
            if (newPassword && confirmPassword) {
                if (newPassword === confirmPassword) {
                    matchText.textContent = '✓ Passwords match';
                    matchText.style.color = '#2ed573';
                } else {
                    matchText.textContent = '✗ Passwords do not match';
                    matchText.style.color = '#ff4757';
                }
            } else {
                matchText.textContent = '';
            }
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 3 && value.length <= 6) {
                value = value.slice(0, 3) + '-' + value.slice(3);
            } else if (value.length > 6) {
                value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
            }
            e.target.value = value;
        });

        // Initialize profile image display
        document.addEventListener('DOMContentLoaded', function() {
            const profileImage = document.getElementById('profileImage');
            const profileInitials = document.getElementById('profileInitials');
            
            // Show image if it has a source
            if (profileImage && profileImage.src) {
                profileImage.style.display = 'block';
                profileInitials.style.display = 'none';
            }
        });

        // Auto-hide message alerts after 5 seconds
        const messageAlert = document.querySelector('.message-alert');
        if (messageAlert) {
            setTimeout(() => {
                messageAlert.style.opacity = '0';
                messageAlert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    messageAlert.style.display = 'none';
                }, 500);
            }, 5000);
        }

        // Initialize tooltips
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                // Clear previous error messages
                const errorMessages = this.querySelectorAll('.error-message');
                errorMessages.forEach(msg => msg.remove());
                
                // Validate required fields
                const requiredFields = this.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message';
                        errorMsg.textContent = 'This field is required';
                        errorMsg.style.color = '#ff4757';
                        errorMsg.style.fontSize = '12px';
                        errorMsg.style.marginTop = '5px';
                        field.parentNode.appendChild(errorMsg);
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>