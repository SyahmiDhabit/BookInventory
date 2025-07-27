<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schoolCodeN = $_POST['schoolCodeN'];
    $picID = $_POST['picID'];
    $status = $_POST['status'];

    // Update all matching books under that school for the PIC
    $stmt = $conn->prepare("UPDATE orderbookn9 SET statusN = ? WHERE schoolCodeN = ? AND picID = ?");
    $stmt->bind_param("ssi", $status, $schoolCodeN, $picID);
    $stmt->execute();
    $stmt->close();
}

header("Location: admininterface.php");
exit();
?>
