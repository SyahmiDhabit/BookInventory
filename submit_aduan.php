<?php
include 'connection.php';
require 'vendor/autoload.php'; // Autoload PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $state = $_POST['negeri'] ?? '';

    $schoolCode = $_POST['schoolCode'] ?? '';
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $bookData = json_decode($_POST['bookData'], true);
    echo "<pre>";
print_r($bookData);
echo "</pre>";


    if (empty($bookData)) {
        echo "Tiada data buku dihantar.";
        exit();
    }

    $success = false;
    foreach ($bookData as $book) {
    // ✅ Semak dahulu sama ada key wujud
    if (!isset($book['orderID']) || !isset($book['qty'])) {
        continue; // Langkau jika data tak lengkap
    }

    $orderID = $book['orderID'];
    $qty = $book['qty'];
    $comment = $book['comment'] ?? '';

    // ... sambung proses insert seperti biasa


        if ($state === 'Negeri Sembilan') {
            $check = $conn->prepare("SELECT * FROM orderbookn9 WHERE orderIDN = ?");
            $check->bind_param("i", $orderID);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $insert = $conn->prepare("INSERT INTO reportn9 (orderIDN, nameN, phoneNumberN, emailN, commentN, qtyN) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$insert) {
    echo "Prepare error: " . $conn->error;
    continue;
}
                $insert->bind_param("issssi", $orderID, $name, $phone, $email, $comment, $qty);
                $insert->execute();
if ($insert->affected_rows > 0) {
    $success = true;
} else {
    echo "INSERT gagal: " . $insert->error;
}

            }
        } elseif ($state === 'Melaka') {
            $check = $conn->prepare("SELECT * FROM orderbookmelaka WHERE orderIDM = ?");
            $check->bind_param("i", $orderID);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $insert = $conn->prepare("INSERT INTO reportmelaka (orderIDM, nameM, phoneNumberM, emailM, commentM, qtyM) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$insert) {
    echo "Prepare error: " . $conn->error;
    continue;
}
                $insert->bind_param("issssi", $orderID, $name, $phone, $email, $comment, $qty);
                $insert->execute();
                $success = true;
            }
        }
    }

    if ($success) {
        // === Hantar Emel ===
        $mail = new PHPMailer(true);

        try {
            // Setting Gmail SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'piratekucing3@gmail.com';      // Ganti dengan email anda
            $mail->Password   = 'cwiemnzsojrrxpfr';          // Ganti dengan App Password Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;    

            // Penerima
            $mail->setFrom('piratekucing3@gmail.com', 'Sistem Laporan Buku');

            $mail->addAddress('admin@example.com', 'Admin');
 // Ganti ke emel admin sebenar

            // Kandungan
            $mail->isHTML(true);
            $mail->Subject = '📚 Aduan Buku Baru Diterima';
            $mail->Body    = "Satu aduan buku telah dihantar oleh <strong>$name</strong> dari sekolah <strong>$schoolCode</strong> untuk negeri <strong>$state</strong>.<br><br>Sila semak sistem untuk butiran penuh.";

            $mail->SMTPDebug = 2; // Atau 3 untuk lebih detail
$mail->Debugoutput = 'html';
            $mail->send();
            echo "Aduan berjaya dihantar dan emel telah dihantar ke admin.";
        } catch (Exception $e) {
            echo "Aduan berjaya dihantar tetapi emel gagal dihantar. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Aduan gagal dihantar.";
    }
} else {
    echo "Akses tidak sah.";
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <title>Terima Kasih</title>
  <!-- <meta http-equiv="refresh" content="3;url=mainpage.php"> Auto redirect -->
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      padding: 50px;
      background-color: #f0f0f0;
    }
    .message-box {
      background-color: #ffffff;
      padding: 30px;
      border-radius: 10px;
      display: inline-block;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <div class="message-box">
    <h2>✅ Terima kasih kerana menggunakan sistem aduan kami!</h2>
    <p>Anda akan diarahkan semula ke halaman utama dalam beberapa saat...</p>
  </div>
</body>
</html>
