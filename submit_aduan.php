<?php
include 'connection.php';

$negeri = $_POST['negeri'] ?? '';
$school = $_POST['school'] ?? '';
$orderID = $_POST['orderID'] ?? 0;
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$comment = $_POST['comment'] ?? '';
$books = json_decode($_POST['bookData'], true);

// Sanity check
if (!$orderID || empty($books)) {
    die("Order ID or books list is missing.");
}

// Prepare common variables
$tableReport = '';
$tableOrder = '';
$fieldOrderID = '';
$fieldName = '';
$fieldPhone = '';
$fieldEmail = '';
$fieldComment = '';
$fieldQty = '';

if ($negeri === 'Negeri Sembilan') {
    $tableReport = 'reportn9';
    $tableOrder = 'orderbookn9';
    $fieldOrderID = 'orderIDN';
    $fieldName = 'nameN';
    $fieldPhone = 'phoneNumberN';
    $fieldEmail = 'emailN';
    $fieldComment = 'commentN';
    $fieldQty = 'qtyN';
} elseif ($negeri === 'Melaka') {
    $tableReport = 'reportmelaka';
    $tableOrder = 'orderbookmelaka';
    $fieldOrderID = 'orderIDM';
    $fieldName = 'nameM';
    $fieldPhone = 'phoneNumberM';
    $fieldEmail = 'emailM';
    $fieldComment = 'commentM';
    $fieldQty = 'qtyM';
} else {
    die("Invalid state selected.");
}

// 🔍 Check if order ID exists in the correct table
$check = $conn->prepare("SELECT COUNT(*) FROM $tableOrder WHERE $fieldOrderID = ?");
$check->bind_param("i", $orderID);
$check->execute();
$check->bind_result($count);
$check->fetch();
$check->close();

if ($count == 0) {
    die("❌ Order ID does not exist in $tableOrder.");
}

// ✅ Insert into the corresponding report table
$query = "INSERT INTO $tableReport ($fieldOrderID, $fieldName, $fieldPhone, $fieldEmail, $fieldComment, $fieldQty)
          VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

foreach ($books as $book) {
    $qty = intval($book['quantity']);
    $stmt->bind_param("issssi", $orderID, $name, $phone, $email, $comment, $qty);
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo "Report submitted successfully.";

?>
