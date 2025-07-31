<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Padam dari orderbookn9 ikut orderIDN
    $conn->query("DELETE FROM orderbookn9 WHERE orderIDN = $id");

    // Padam juga dari reportn9 jika wujud
    $conn->query("DELETE FROM reportn9 WHERE orderIDN = $id");
}

header("Location: admininterface.php");
exit();
?>
