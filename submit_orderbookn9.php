<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode($_POST['data'], true);
    $schoolCodeN = $_POST['schoolCode'];
    $picID = $_POST['picID'];

    foreach ($data as $entry) {
        $codeBook = $entry['code'];
        $realQtyN = $entry['originalQty'];
        $sortQtyN = $entry['shortQty'];
        $statusN = $entry['status'];

        // ✅ FIX: Removed 'titleBook' from this line
        $stmt = $conn->prepare("INSERT INTO orderbookn9 (schoolCodeN, codeBook, realQtyN, sortQtyN, statusN, picID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiis", $schoolCodeN, $codeBook, $realQtyN, $sortQtyN, $statusN, $picID);
        $stmt->execute();
    }

    header("Location: admininterface.php");
    exit();
}
?>
