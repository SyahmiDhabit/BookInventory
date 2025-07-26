<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $_SESSION['picID'] = $_POST['picID'];
    $_SESSION['schoolCode'] = $_POST['schoolCode'];
    header("Location: addlistbookpicN9.php");
    exit();
} else {
    echo "Invalid access.";
}
?>
