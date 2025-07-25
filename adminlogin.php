<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "booksystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin'] = $row['username'];
            header("Location: admininterface.php");
            exit();
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Admin tidak dijumpai.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #7ca9ecff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .login-box {
      background-color: #ffffff;
      padding: 40px 30px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }

    .login-box h2 {
      font-size: 24px;
      color: #4a4a4a;
      margin-bottom: 25px;
    }

    .login-box form {
      display: flex;
      flex-direction: column;
    }

    .login-box input[type="text"],
    .login-box input[type="password"] {
      padding: 12px 15px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
      background-color: #fdfdfd;
    }

    .login-box input:focus {
      border-color: #5a99f1ff;
      outline: none;
      background-color: #f0f8ff;
    }

    .login-box button {
      padding: 12px;
      background-color: #4791f8ff;
      color: white;
      font-size: 16px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .login-box button:hover {
      background-color: #5a9bf5;
    }

    .error {
      color: #e74c3c;
      margin-bottom: 15px;
      font-weight: bold;
    }

    .back-button {
      position: absolute;
      top: 20px;
      left: 20px;
      background-color: #6c757d;
      color: white;
      text-decoration: none;
      padding: 10px 15px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }

    .back-button:hover {
      background-color: #5a6268;
    }
  </style>
</head>
<body>
  <!-- Back Button -->
  <a href="mainpage.php" class="back-button">← Kembali ke Halaman Utama</a>

  <div class="login-box">
    <h2>WELCOME TO<br>ADMIN PAGE</h2>

    <?php if (!empty($error)): ?>
      <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="username" placeholder="Enter Username" required>
      <input type="password" name="password" placeholder="Enter Password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>
