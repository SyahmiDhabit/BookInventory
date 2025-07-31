<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $schoolCodeM = $_POST['schoolCodeM'] ?? '';
    $picID = $_POST['picID'] ?? '';

    if (!empty($schoolCodeM) && !empty($picID)) {
        // Ambil semua orderIDM yang berkaitan
        $stmt = $conn->prepare("SELECT orderIDM FROM orderbookmelaka WHERE schoolCodeM = ? AND picID = ?");
        $stmt->bind_param("si", $schoolCodeM, $picID);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderIDs = [];
        while ($row = $result->fetch_assoc()) {
            $orderIDs[] = $row['orderIDM'];
        }
        $stmt->close();

        // Padam semua laporan yang berkaitan dari reportmelaka
        if (!empty($orderIDs)) {
            $in = str_repeat('?,', count($orderIDs) - 1) . '?';
            $types = str_repeat('i', count($orderIDs));
            $stmt = $conn->prepare("DELETE FROM reportmelaka WHERE orderIDM IN ($in)");
            $stmt->bind_param($types, ...$orderIDs);
            $stmt->execute();
            $stmt->close();
        }

        // Akhir sekali padam order dari orderbookmelaka
        $stmt = $conn->prepare("DELETE FROM orderbookmelaka WHERE schoolCodeM = ? AND picID = ?");
        $stmt->bind_param("si", $schoolCodeM, $picID);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admininterface.php");
    exit();
}
?>
