<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode($_POST['data'], true);
    $schoolCodeN = $_POST['schoolCode'];
    $picID = $_POST['picID'];

    foreach ($data as $entry) {
        $codeBook = $entry['code'];          // Book code only
        $realQtyN = $entry['originalQty'];   // Original quantity
        $sortQtyN = $entry['shortQty'];      // Shortage quantity
        $statusN = $entry['status'];         // Delivery status (string like "Delivered" or "Not Delivered")

        // Insert into orderbookn9 without titleBook
        $stmt = $conn->prepare("INSERT INTO orderbookn9 
            (schoolCodeN, codeBook, realQtyN, sortQtyN, statusN, picID) 
            VALUES (?, ?, ?, ?, ?, ?)");

        // Types: s = string, i = integer (3 strings, 3 ints)
        $stmt->bind_param("ssiisi", $schoolCodeN, $codeBook, $realQtyN, $sortQtyN, $statusN, $picID);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admininterface.php");
    exit();
}
?>
