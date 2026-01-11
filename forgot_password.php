<?php
session_start();
include('db_connect.php');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        // Check if email exists
        $sql = "SELECT user_id, full_name, email FROM users WHERE email = ? AND status = 'Active'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $update_sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ssi", $reset_token, $token_expiry, $user['user_id']);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Create reset link
                $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                            "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . 
                            "/reset_password.php?token=" . $reset_token;
                
                // In a real application, you would send an email here
                // For now, we'll show the link on the page
                $message = "<div class='success-message'>
                    <h3><i class='fas fa-envelope'></i> Reset Link Generated</h3>
                    <p>A password reset link has been created for: <strong>" . htmlspecialchars($email) . "</strong></p>
                    <div class='reset-link-box'>
                        <p><strong>Reset Link:</strong></p>
                        <code>" . htmlspecialchars($reset_link) . "</code>
                        <p class='small-text'>Copy this link and open it in your browser. This link will expire in 1 hour.</p>
                    </div>
                    <p>For testing purposes, you can click this link directly: 
                    <a href='" . htmlspecialchars($reset_link) . "' class='btn-test-link'>Reset Password Now</a></p>
                </div>";
            } else {
                $error = "Error generating reset token. Please try again.";
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $error = "No account found with that email address.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - HFRS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #8b5cf6;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .forgot-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 24px;
            padding: 50px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
        
        .header h1 {
            font-size: 32px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .header p {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
        }
        
        .input-with-icon input {
            width: 100%;
            padding: 16px 16px 16px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .input-with-icon input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-message {
            background: #d1fae5;
            border: 2px solid #a7f3d0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .success-message h3 {
            color: #065f46;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-message p {
            color: #065f46;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .reset-link-box {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #a7f3d0;
        }
        
        .reset-link-box code {
            display: block;
            background: #f8fafc;
            padding: 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
            margin: 10px 0;
            border: 1px solid #e2e8f0;
        }
        
        .small-text {
            font-size: 13px;
            color: #64748b;
            margin-top: 10px;
        }
        
        .btn-test-link {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .forgot-container {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="header">
            <div class="header-icon">
                <i class="fas fa-key"></i>
            </div>
            <h1>Forgot Password</h1>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($message)): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i>
                    Send Reset Link
                </button>
            </form>
        <?php else: ?>
            <?php echo $message; ?>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>