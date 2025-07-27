<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schoolCodeN'], $_POST['picID'])) {
    $schoolCode = $conn->real_escape_string($_POST['schoolCodeN']);
    $picID = intval($_POST['picID']);

    // Delete all book orders from orderbookn9 for this school and PIC
    $conn->query("DELETE FROM orderbookn9 WHERE schoolCodeN = '$schoolCode' AND picID = $picID");
}

header("Location: admininterface.php");
exit();
?>
