<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Delete from both tables just in case
    $conn->query("DELETE FROM orderbookn9 WHERE id = $id");
    $conn->query("DELETE FROM orderbookmelaka WHERE id = $id");
}

header("Location: admininterface.php");
exit();
?>
