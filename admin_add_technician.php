<?php
session_start();
include('db_connect.php');

// Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Initialize variables
$success_message = '';
$error_message = '';
$form_data = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'username' => '',
    'password' => '',
    'skills' => '',
    'experience_years' => '',
    'notes' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_technician'])) {
    // Sanitize and validate input
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $password = mysqli_real_escape_string($conn, $_POST['password'] ?? '');
    $skills = mysqli_real_escape_string($conn, $_POST['skills'] ?? '');
    $experience_years = intval($_POST['experience_years'] ?? 0);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // Store form data for re-population
    $form_data = compact('full_name', 'email', 'phone', 'username', 'password', 'skills', 'experience_years', 'notes');
    
    // Validate required fields
    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Check if email already exists
        $check_email = mysqli_query($conn, "SELECT user_id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $error_message = "Email already exists. Please use a different email.";
        } else {
            // Check if username already exists
            $check_username = mysqli_query($conn, "SELECT user_id FROM users WHERE username = '$username'");
            if (mysqli_num_rows($check_username) > 0) {
                $error_message = "Username already exists. Please choose a different username.";
            } else {
                // Start transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert into users table
                    $user_query = "INSERT INTO users (full_name, email, phone, username, password, role, status, work_status, created_at) 
                                  VALUES ('$full_name', '$email', '$phone', '$username', '$hashed_password', 'Technician', 'Active', 'Available', NOW())";
                    
                    if (mysqli_query($conn, $user_query)) {
                        $new_technician_id = mysqli_insert_id($conn);
                        
                        // Insert into technician_profiles table
                        if (!empty($skills) || $experience_years > 0 || !empty($notes)) {
                            $profile_query = "INSERT INTO technician_profiles (tech_id, skills, experience_years, notes, updated_at) 
                                             VALUES ('$new_technician_id', '$skills', '$experience_years', '$notes', NOW())";
                            mysqli_query($conn, $profile_query);
                        }
                        
                        // Handle skills input
                        if (isset($_POST['technician_skills']) && is_array($_POST['technician_skills'])) {
                            foreach ($_POST['technician_skills'] as $skill) {
                                if (!empty(trim($skill))) {
                                    $skill_name = mysqli_real_escape_string($conn, trim($skill));
                                    $skill_query = "INSERT INTO technician_skills (technician_id, skill_name, proficiency_level, created_at) 
                                                   VALUES ('$new_technician_id', '$skill_name', 'Intermediate', NOW())";
                                    mysqli_query($conn, $skill_query);
                                }
                            }
                        }
                        
                        // Commit transaction
                        mysqli_commit($conn);
                        
                        $success_message = "Technician added successfully!";
                        
                        // Clear form data
                        $form_data = array_fill_keys(array_keys($form_data), '');
                    } else {
                        throw new Exception("Error adding technician: " . mysqli_error($conn));
                    }
                } catch (Exception $e) {
                    // Rollback transaction on error
                    mysqli_rollback($conn);
                    $error_message = $e->getMessage();
                }
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
    <title>Add New Technician - HFRS Admin</title>
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

        /* FORM CONTAINER */
        .form-container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-section {
            margin-bottom: 40px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .section-title i {
            color: var(--primary);
            width: 40px;
            height: 40px;
            background: #eff6ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* FORM GRID */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
            font-size: 14px;
        }

        .form-group label .required {
            color: var(--danger);
            margin-left: 3px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-control.error {
            border-color: var(--danger);
        }

        .form-help {
            font-size: 13px;
            color: var(--gray);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* SKILLS INPUT */
        .skills-container {
            margin-top: 10px;
        }

        .skill-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .skill-input-group input {
            flex: 1;
        }

        .add-skill-btn {
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 50px;
        }

        .add-skill-btn:hover {
            background: #e5e7eb;
            border-color: var(--primary);
            color: var(--primary);
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .skill-tag {
            background: #e0e7ff;
            color: var(--primary-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .remove-skill {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 14px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* PASSWORD STRENGTH */
        .password-strength {
            margin-top: 8px;
        }

        .strength-meter {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            background: var(--danger);
            border-radius: 3px;
            transition: width 0.3s, background 0.3s;
        }

        .strength-text {
            font-size: 12px;
            color: var(--gray);
        }

        /* FORM ACTIONS */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid #e5e7eb;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: var(--dark);
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        /* PREVIEW CARD */
        .preview-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            border: 2px solid #e5e7eb;
        }

        .preview-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .preview-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }

        .preview-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .preview-item label {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 5px;
            display: block;
        }

        .preview-item .value {
            font-weight: 500;
            color: var(--dark);
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
            
            .sidebar nav a i {
                font-size: 20px;
            }
            
            .main-content {
                margin-left: 80px;
                padding: 25px;
            }
            
            .form-container {
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .admin-info {
                width: 100%;
                justify-content: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
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
                <li><a href="admin_technicians.php" class="active"><i class="fa-solid fa-user-gear"></i> <span>Technicians</span></a></li>
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
                <h1 class="page-title">Add New Technician</h1>
                <p class="page-subtitle">Create a new technician account with expertise details</p>
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

        <!-- FORM CONTAINER -->
        <form method="POST" action="" class="form-container" onsubmit="return validateForm()">
            <!-- BASIC INFORMATION -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Basic Information</span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['full_name']); ?>" 
                               placeholder="Enter full name" required>
                        <div class="form-help">
                            <i class="fa-solid fa-info-circle"></i>
                            Technician's full name as it should appear in the system
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['email']); ?>" 
                               placeholder="Enter email address" required>
                        <div class="form-help">
                            <i class="fa-solid fa-info-circle"></i>
                            Will be used for login and notifications
                        </div>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['phone']); ?>" 
                               placeholder="Enter phone number">
                        <div class="form-help">
                            <i class="fa-solid fa-info-circle"></i>
                            Optional contact number
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['username']); ?>" 
                               placeholder="Choose a username" required>
                        <div class="form-help">
                            <i class="fa-solid fa-info-circle"></i>
                            Unique username for system login
                        </div>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <input type="password" name="password" id="password" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['password']); ?>" 
                               placeholder="Create a strong password" required
                               onkeyup="checkPasswordStrength()">
                        <div class="password-strength">
                            <div class="strength-meter">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Password strength: Weak</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                               placeholder="Confirm your password" required
                               onkeyup="checkPasswordMatch()">
                        <div class="form-help" id="passwordMatchText"></div>
                    </div>
                </div>
            </div>

            <!-- EXPERTISE & SKILLS -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fa-solid fa-star"></i>
                    <span>Expertise & Skills</span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Primary Expertise/Skills</label>
                        <input type="text" name="skills" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['skills']); ?>" 
                               placeholder="e.g., Electrical, Plumbing, HVAC">
                        <div class="form-help">
                            <i class="fa-solid fa-info-circle"></i>
                            Main areas of specialization
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Years of Experience</label>
                        <input type="number" name="experience_years" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['experience_years']); ?>" 
                               placeholder="0" min="0" max="50">
                        <div class="form-help">
                            <i class="fa-solid fa-info-circle"></i>
                            Total years of experience in the field
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Technical Skills</label>
                    <div class="skills-container">
                        <div class="skill-input-group">
                            <input type="text" id="skillInput" class="form-control" 
                                   placeholder="Add a specific skill (e.g., Electrical Wiring)">
                            <button type="button" class="add-skill-btn" onclick="addSkill()">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <div class="skills-list" id="skillsList">
                            <!-- Skills will be added here -->
                        </div>
                    </div>
                    <div class="form-help">
                        <i class="fa-solid fa-info-circle"></i>
                        Add specific technical skills. Click + to add each skill
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Additional Notes</label>
                    <textarea name="notes" class="form-control" 
                              placeholder="Any additional information about the technician..."><?php echo htmlspecialchars($form_data['notes']); ?></textarea>
                    <div class="form-help">
                        <i class="fa-solid fa-info-circle"></i>
                        Optional notes about the technician's background or special qualifications
                    </div>
                </div>
            </div>

            <!-- PREVIEW -->
            <div class="preview-section">
                <div class="preview-title">
                    <i class="fa-solid fa-eye"></i>
                    <span>Account Preview</span>
                </div>
                <div class="preview-card">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); 
                              border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 20px;" 
                              id="previewAvatar">
                            <?php echo $form_data['full_name'] ? strtoupper(substr($form_data['full_name'], 0, 1)) : 'T'; ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 18px;" id="previewName">
                                <?php echo $form_data['full_name'] ?: 'Technician Name'; ?>
                            </div>
                            <div style="font-size: 14px; color: var(--gray);">
                                <span class="badge" style="background: #10b981; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px;">
                                    Technician
                                </span>
                                <span style="margin-left: 10px; font-size: 13px;">Active Status</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="preview-info">
                        <div class="preview-item">
                            <label>Email</label>
                            <div class="value" id="previewEmail"><?php echo $form_data['email'] ?: 'email@example.com'; ?></div>
                        </div>
                        <div class="preview-item">
                            <label>Phone</label>
                            <div class="value" id="previewPhone"><?php echo $form_data['phone'] ?: 'Not provided'; ?></div>
                        </div>
                        <div class="preview-item">
                            <label>Experience</label>
                            <div class="value" id="previewExperience">
                                <?php echo $form_data['experience_years'] ? $form_data['experience_years'] . ' years' : 'Not specified'; ?>
                            </div>
                        </div>
                        <div class="preview-item">
                            <label>Expertise</label>
                            <div class="value" id="previewSkills"><?php echo $form_data['skills'] ?: 'General Technician'; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM ACTIONS -->
            <div class="form-actions">
                <a href="admin_technicians.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Technicians
                </a>
                <button type="submit" name="add_technician" class="btn btn-primary">
                    <i class="fa-solid fa-user-plus"></i>
                    Add Technician
                </button>
            </div>
        </form>
    </div>

    <script>
        // Skills Management
        let skills = [];
        
        function addSkill() {
            const skillInput = document.getElementById('skillInput');
            const skill = skillInput.value.trim();
            
            if (skill && !skills.includes(skill)) {
                skills.push(skill);
                updateSkillsList();
                skillInput.value = '';
                updatePreview();
            }
        }
        
        function removeSkill(index) {
            skills.splice(index, 1);
            updateSkillsList();
            updatePreview();
        }
        
        function updateSkillsList() {
            const skillsList = document.getElementById('skillsList');
            skillsList.innerHTML = '';
            
            skills.forEach((skill, index) => {
                const skillTag = document.createElement('div');
                skillTag.className = 'skill-tag';
                skillTag.innerHTML = `
                    ${skill}
                    <button type="button" class="remove-skill" onclick="removeSkill(${index})">
                        <i class="fa-solid fa-times"></i>
                    </button>
                `;
                skillsList.appendChild(skillTag);
            });
            
            // Update hidden input for form submission
            updateSkillsInput();
        }
        
        function updateSkillsInput() {
            // Create hidden inputs for each skill
            const existingInputs = document.querySelectorAll('input[name="technician_skills[]"]');
            existingInputs.forEach(input => input.remove());
            
            skills.forEach(skill => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'technician_skills[]';
                input.value = skill;
                document.querySelector('form').appendChild(input);
            });
        }
        
        // Password Strength Checker
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let color = '#ef4444';
            let text = 'Very Weak';
            
            if (password.length >= 6) {
                strength += 25;
                text = 'Weak';
            }
            if (password.length >= 8) {
                strength += 25;
                text = 'Fair';
                color = '#f59e0b';
            }
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) {
                strength += 25;
                text = 'Good';
                color = '#10b981';
            }
            if (/[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) {
                strength += 25;
                text = 'Strong';
                color = '#059669';
            }
            
            strengthFill.style.width = strength + '%';
            strengthFill.style.background = color;
            strengthText.textContent = `Password strength: ${text}`;
            strengthText.style.color = color;
        }
        
        // Password Match Checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('passwordMatchText');
            
            if (!password || !confirmPassword) {
                matchText.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchText.innerHTML = '<i class="fa-solid fa-check-circle" style="color: #10b981;"></i> Passwords match';
            } else {
                matchText.innerHTML = '<i class="fa-solid fa-exclamation-circle" style="color: #ef4444;"></i> Passwords do not match';
            }
        }
        
        // Live Preview Updates
        function updatePreview() {
            const previewName = document.getElementById('previewName');
            const previewEmail = document.getElementById('previewEmail');
            const previewPhone = document.getElementById('previewPhone');
            const previewExperience = document.getElementById('previewExperience');
            const previewSkills = document.getElementById('previewSkills');
            const previewAvatar = document.getElementById('previewAvatar');
            
            // Update from form inputs
            const nameInput = document.querySelector('input[name="full_name"]');
            const emailInput = document.querySelector('input[name="email"]');
            const phoneInput = document.querySelector('input[name="phone"]');
            const experienceInput = document.querySelector('input[name="experience_years"]');
            const skillsInput = document.querySelector('input[name="skills"]');
            
            previewName.textContent = nameInput.value || 'Technician Name';
            previewEmail.textContent = emailInput.value || 'email@example.com';
            previewPhone.textContent = phoneInput.value || 'Not provided';
            previewExperience.textContent = experienceInput.value ? experienceInput.value + ' years' : 'Not specified';
            previewSkills.textContent = skillsInput.value || 'General Technician';
            
            // Update avatar initials
            const initials = nameInput.value ? 
                nameInput.value.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : 'T';
            previewAvatar.textContent = initials;
            
            // Add skills to preview
            if (skills.length > 0) {
                const skillsText = document.getElementById('previewSkills');
                skillsText.textContent = skillsInput.value ? 
                    skillsInput.value + ' + ' + skills.join(', ') : 
                    skills.join(', ');
            }
        }
        
        // Form Validation
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Check password match
            if (password !== confirmPassword) {
                alert('Passwords do not match. Please check your password entries.');
                document.getElementById('confirm_password').focus();
                return false;
            }
            
            // Check password strength
            if (password.length < 6) {
                alert('Password must be at least 6 characters long.');
                document.getElementById('password').focus();
                return false;
            }
            
            // Check required fields
            const requiredFields = document.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields.');
                return false;
            }
            
            return true;
        }
        
        // Add event listeners for live preview
        document.addEventListener('DOMContentLoaded', function() {
            // Update preview on input
            document.querySelectorAll('input, textarea').forEach(input => {
                input.addEventListener('input', updatePreview);
            });
            
            // Add skill on Enter key
            document.getElementById('skillInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addSkill();
                }
            });
            
            // Initialize preview
            updatePreview();
        });
    </script>
</body>
</html>