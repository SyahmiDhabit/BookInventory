<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jsonData = json_decode($_POST['data'], true);
    $schoolCodeN = $_POST['schoolCode'];
    $picID = $_POST['picID'];

    $statusN = $jsonData['status'];         // Get status only once
    $items = $jsonData['items'];            // Array of books

    foreach ($items as $entry) {
        $codeBook = $entry['code'];         // Book code
        $realQtyN = $entry['originalQty'];  // Original quantity
        $sortQtyN = $entry['shortQty'];     // Shortage quantity

        // Insert into orderbookn9
        $stmt = $conn->prepare("INSERT INTO orderbookn9 
            (schoolCodeN, codeBook, realQtyN, sortQtyN, statusN, picID) 
            VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssiisi", $schoolCodeN, $codeBook, $realQtyN, $sortQtyN, $statusN, $picID);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admininterface.php");
    exit();
}
?>
