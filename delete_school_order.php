<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schoolCodeN'], $_POST['picID'])) {
    $schoolCode = $conn->real_escape_string($_POST['schoolCodeN']);
    $picID = intval($_POST['picID']);

    // 1. Dapatkan semua orderIDN yang padan
    $orderIDs = [];
    $result = $conn->query("SELECT orderIDN FROM orderbookn9 WHERE schoolCodeN = '$schoolCode' AND picID = $picID");
    while ($row = $result->fetch_assoc()) {
        $orderIDs[] = $row['orderIDN'];
    }

    // 2. Padam dari reportn9 dahulu
    if (!empty($orderIDs)) {
        $in = implode(',', array_map('intval', $orderIDs)); // selamat kerana sudah dari DB
        $conn->query("DELETE FROM reportn9 WHERE orderIDN IN ($in)");
    }

    // 3. Barulah padam dari orderbookn9
    $conn->query("DELETE FROM orderbookn9 WHERE schoolCodeN = '$schoolCode' AND picID = $picID");
}

header("Location: admininterface.php");
exit();
?>
