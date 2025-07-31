<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Padam dahulu dari reportmelaka (yang bergantung kepada orderIDM ini)
    $conn->query("DELETE FROM reportmelaka WHERE orderIDM = $id");

    // Kemudian padam dari orderbookmelaka
    $conn->query("DELETE FROM orderbookmelaka WHERE orderIDM = $id");
}

header("Location: admininterface.php");
exit();
?>
