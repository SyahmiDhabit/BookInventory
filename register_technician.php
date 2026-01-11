<?php
include('db_connect.php');
session_start();

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : '';

    // Validation
    if (empty($fullname) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!isset($_FILES['certificate']) || $_FILES['certificate']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload your certificate or proof of qualification.";
    } else {
        // Check if email exists
        $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered. Please use a different email.";
        } else {
            // Handle file upload
            $target_dir = "uploads/certificates/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $error = "Invalid file type. Please upload JPG, PNG, GIF, or PDF file.";
            } else {
                $file_name = time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $file_name;
                
                // Check file size (max 5MB)
                if ($_FILES['certificate']['size'] > 5000000) {
                    $error = "File size too large. Maximum size is 5MB.";
                } else {
                    if (move_uploaded_file($_FILES['certificate']['tmp_name'], $target_file)) {
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Generate user ID
                        $user_id = 'USER' . uniqid();
                        
                        // Check if approval_status column exists, if not add it
                        $check_approval = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'approval_status'");
                        if (mysqli_num_rows($check_approval) == 0) {
                            mysqli_query($conn, "ALTER TABLE users ADD COLUMN approval_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending'");
                        }
                        
                        // Check if certificate_path column exists, if not add it
                        $check_cert = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'certificate_path'");
                        if (mysqli_num_rows($check_cert) == 0) {
                            mysqli_query($conn, "ALTER TABLE users ADD COLUMN certificate_path VARCHAR(255) DEFAULT NULL");
                        }
                        
                        // Insert technician with pending approval
                        $sql = "INSERT INTO users (user_id, full_name, email, password, role, phone, approval_status, certificate_path, created_at) 
                                VALUES ('$user_id', '$fullname', '$email', '$hashed_password', 'Technician', '$phone', 'Pending', '$target_file', NOW())";

                        if (mysqli_query($conn, $sql)) {
                            $success = "Registration submitted successfully! Your application is pending admin approval. You will be notified once approved.";
                            header("refresh:3;url=login.php");
                        } else {
                            // Delete uploaded file if database insert fails
                            if (file_exists($target_file)) {
                                unlink($target_file);
                            }
                            $error = "Registration failed. Please try again.";
                        }
                    } else {
                        $error = "Failed to upload certificate. Please try again.";
                    }
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
    <title>Technician Registration - Hostel Facilities Report System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
           background: 
        linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)),
        url('images/mainPicDewan.jpg') center/cover no-repeat;
    min-height: 100vh;
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--dark);

        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 1200px;
            width: 100%;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.15);
            min-height: 700px;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.9), rgba(29, 78, 216, 0.95));
            color: white;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            opacity: 0.1;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }

        .logo-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
        }

        .logo-text p {
            font-size: 14px;
            opacity: 0.9;
        }

        .hero-content h2 {
            font-size: 36px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
            max-width: 400px;
        }

        .hero-content p {
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 40px;
            max-width: 400px;
        }

        .features {
            display: grid;
            gap: 20px;
            margin-top: 40px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .feature-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature-text h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .feature-text p {
            font-size: 14px;
            opacity: 0.8;
            margin: 0;
        }

        /* Form Section */
        .form-section {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--gray);
            font-size: 16px;
        }

        .form-container {
            max-width: 400px;
            width: 100%;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .input-group {
            position: relative;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
            color: var(--dark);
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
        }

        .input-icon.fa-user,
        .input-icon.fa-envelope,
        .input-icon.fa-phone {
            cursor: default;
        }

        .password-hint {
            font-size: 12px;
            color: var(--gray);
            margin-top: 5px;
            padding-left: 5px;
        }

        .file-upload-wrapper {
            position: relative;
            margin-bottom: 10px;
        }

        .file-upload-label {
            display: block;
            padding: 16px;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f9fafb;
        }

        .file-upload-label:hover {
            border-color: var(--primary);
            background: #f0f9ff;
        }

        .file-upload-label.has-file {
            border-color: var(--primary);
            background: #dbeafe;
        }

        .file-upload-label i {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 10px;
            display: block;
        }

        .file-upload-label input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-name {
            font-size: 14px;
            color: var(--dark);
            margin-top: 8px;
            font-weight: 500;
        }

        .file-hint {
            font-size: 12px;
            color: var(--gray);
            margin-top: 5px;
            padding-left: 5px;
        }

        .approval-notice {
            background: #fef3c7;
            border: 2px solid var(--warning);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .approval-notice i {
            color: var(--warning);
            font-size: 20px;
            margin-top: 2px;
        }

        .approval-notice p {
            font-size: 13px;
            color: #92400e;
            margin: 0;
            line-height: 1.5;
        }

        .btn-register {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 25px;
        }

        .btn-register:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .btn-register:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .error-message {
            background: #fee2e2;
            color: var(--danger);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            display: <?php echo $error ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 18px;
        }

        .success-message {
            background: #d1fae5;
            color: var(--success);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            display: <?php echo $success ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 10px;
        }

        .success-message i {
            font-size: 18px;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: var(--gray);
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            padding: 0 15px;
        }

        .login-prompt {
            text-align: center;
            margin-top: 20px;
            color: var(--gray);
            font-size: 15px;
        }

        .login-prompt a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .login-prompt a:hover {
            text-decoration: underline;
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .back-home:hover {
            background: #f0f9ff;
        }

        /* Password strength indicator */
        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
            display: none;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            
            .hero-section {
                padding: 40px 30px;
                text-align: center;
            }
            
            .form-section {
                padding: 40px 30px;
            }
        }

        @media (max-width: 480px) {
            .container {
                border-radius: 16px;
            }
            
            .hero-content h2 {
                font-size: 28px;
            }
            
            .form-header h2 {
                font-size: 28px;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="logo-text">
                    <h1>HFRS</h1>
                    <p>Technician Registration</p>
                </div>
            </div>

            <div class="hero-content">
                <h2>Join Our Technician Team</h2>
                <p>Register as a technician to help maintain hostel facilities. Upload your certificate or proof of qualification for admin approval.</p>
                
                <div class="features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Certificate Required</h4>
                            <p>Upload proof of qualification</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Admin Approval</h4>
                            <p>Your registration needs approval</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Start Working</h4>
                            <p>Fix issues and help students</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration Form Section -->
        <div class="form-section">
            <div class="form-container">
                <div class="form-header">
                    <h2>Technician Registration</h2>
                    <p>Fill in your details and upload your certificate</p>
                </div>

                <div class="approval-notice">
                    <i class="fas fa-info-circle"></i>
                    <p><strong>Important:</strong> Your registration requires admin approval. Please upload a valid certificate or proof of qualification. You will be able to login once your registration is approved.</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" id="registerForm">
                    <div class="form-group">
                        <label class="form-label" for="fullname">Full Name</label>
                        <div class="input-group">
                            <input type="text" id="fullname" name="fullname" 
                                   placeholder="Enter your full name" 
                                   value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" 
                                   required>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="input-group">
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number (Optional)</label>
                        <div class="input-group">
                            <input type="tel" id="phone" name="phone" 
                                   placeholder="Enter your phone number" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            <i class="fas fa-phone input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" 
                                   placeholder="Create a password (min. 6 characters)" 
                                   required
                                   oninput="checkPasswordStrength(this.value)">
                            <i id="togglePassword" class="fas fa-eye input-icon" onclick="togglePassword('password')"></i>
                        </div>
                        <div class="password-hint">Must be at least 6 characters long</div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm your password" 
                                   required
                                   oninput="checkPasswordMatch()">
                            <i id="toggleConfirmPassword" class="fas fa-eye input-icon" onclick="togglePassword('confirm_password')"></i>
                        </div>
                        <div class="password-hint" id="passwordMatchHint"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Certificate / Proof of Qualification *</label>
                        <div class="file-upload-wrapper">
                            <label class="file-upload-label" id="fileUploadLabel" for="certificate">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span id="fileUploadText">Click to upload certificate or proof</span>
                                <span class="file-name" id="fileName"></span>
                                <input type="file" id="certificate" name="certificate" 
                                       accept=".jpg,.jpeg,.png,.gif,.pdf" 
                                       required
                                       onchange="handleFileSelect(this)">
                            </label>
                        </div>
                        <div class="file-hint">
                            <i class="fas fa-info-circle"></i> Accepted formats: JPG, PNG, GIF, PDF (Max 5MB)
                        </div>
                    </div>

                    <button type="submit" class="btn-register" id="submitBtn">Submit Registration</button>

                    <div class="divider">
                        <span>Already have an account?</span>
                    </div>

                    <div class="login-prompt">
                        <p>Already registered? <a href="login.php">Sign In here</a></p>
                        <p style="margin-top: 10px;">Registering as a regular user? <a href="register.php">Register as User</a></p>
                        <a href="index.php" class="back-home">
                            <i class="fas fa-arrow-left"></i>
                            Back to Home
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById('toggle' + fieldId.charAt(0).toUpperCase() + fieldId.slice(1));
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            const label = document.getElementById('fileUploadLabel');
            const fileName = document.getElementById('fileName');
            const uploadText = document.getElementById('fileUploadText');
            
            if (file) {
                // Validate file size (5MB)
                if (file.size > 5000000) {
                    alert('File size too large. Maximum size is 5MB.');
                    input.value = '';
                    label.classList.remove('has-file');
                    fileName.textContent = '';
                    uploadText.textContent = 'Click to upload certificate or proof';
                    validateForm();
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please upload JPG, PNG, GIF, or PDF file.');
                    input.value = '';
                    label.classList.remove('has-file');
                    fileName.textContent = '';
                    uploadText.textContent = 'Click to upload certificate or proof';
                    validateForm();
                    return;
                }
                
                label.classList.add('has-file');
                fileName.textContent = file.name;
                uploadText.textContent = 'File selected:';
            } else {
                label.classList.remove('has-file');
                fileName.textContent = '';
                uploadText.textContent = 'Click to upload certificate or proof';
            }
            
            validateForm();
        }

        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            const strengthContainer = document.getElementById('passwordStrength');
            
            let strength = 0;
            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            // Color coding
            if (strength < 50) {
                strengthBar.style.background = '#ef4444';
            } else if (strength < 75) {
                strengthBar.style.background = '#f59e0b';
            } else {
                strengthBar.style.background = var(--success);
            }
            
            strengthContainer.style.display = 'block';
            
            validateForm();
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const hint = document.getElementById('passwordMatchHint');
            
            if (!confirmPassword) {
                hint.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                hint.textContent = '✓ Passwords match';
                hint.style.color = var(--success);
            } else {
                hint.textContent = '✗ Passwords do not match';
                hint.style.color = var(--danger);
            }
            
            validateForm();
        }

        function validateForm() {
            const fullname = document.getElementById('fullname').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const certificate = document.getElementById('certificate').files.length;
            
            const submitBtn = document.getElementById('submitBtn');
            
            // Basic validation
            if (fullname && email && password.length >= 6 && password === confirmPassword && certificate > 0) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Real-time form validation
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', validateForm);
            });
            
            // Initialize form validation
            validateForm();
        });

        // Focus effects
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
