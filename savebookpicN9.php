<?php
include 'connection.php';

$picID = $_POST['picID'];
$schoolCode = $_POST['schoolCode'];
$bookCodes = $_POST['bookCode'];
$bookTitles = $_POST['bookTitle'];
$originalQtys = $_POST['originalQty'];
$shortQtys = $_POST['shortQty'];
$statuses = $_POST['status'];

for ($i = 0; $i < count($bookCodes); $i++) {
    $code = $bookCodes[$i];
    $title = $bookTitles[$i];
    $original = $originalQtys[$i];
    $short = $shortQtys[$i];
    $status = $statuses[$i];

    $stmt = $conn->prepare("INSERT INTO bookn9 (picID, schoolCode, bookCode, bookTitle, originalQty, shortQty, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssis", $picID, $schoolCode, $code, $title, $original, $short, $status);
    $stmt->execute();
    $stmt->close();
}

header("Location: admininterface.php");
exit();
