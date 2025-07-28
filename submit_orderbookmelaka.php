<?php
include 'connection.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['picID'], $data['schoolCode'], $data['books']) || !is_array($data['books'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$picID = $data['picID'];
$schoolCode = $data['schoolCode'];
$books = $data['books'];

// Prepare correct statement
$stmt = $conn->prepare("INSERT INTO orderbookmelaka (schoolCodeM, codeBook, realQtyM, sortQtyM, statusM, picID) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($books as $book) {
    $stmt->bind_param(
        "ssiiis",
        $schoolCode,
        $book['code'],
        $book['realQty'],
        $book['shortQty'],
        $book['status'],
        $picID
    );
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true]);
?>
