<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $schoolCodeM = $_POST['schoolCodeM'] ?? '';
    $picID = $_POST['picID'] ?? '';

    if (!empty($schoolCodeM) && !empty($picID)) {
        // Padam semua buku untuk sekolah ini yang bawah PIC ini
        $stmt = $conn->prepare("DELETE FROM orderbookmelaka WHERE schoolCodeM = ? AND picID = ?");
        $stmt->bind_param("si", $schoolCodeM, $picID);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect semula ke admin interface
    header("Location: admininterface.php");
    exit();
}
?>
