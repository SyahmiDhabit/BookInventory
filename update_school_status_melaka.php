<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $schoolCode = $_POST['schoolCodeM'] ?? '';
    $picID = $_POST['picID'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($schoolCode && $picID && $status) {
        // Update all book status for this school
        $stmt = $conn->prepare("UPDATE orderbookmelaka SET statusM = ? WHERE schoolCodeM = ? AND picID = ?");
        $stmt->bind_param("ssi", $status, $schoolCode, $picID);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();
header("Location: admininterface.php");
exit;
