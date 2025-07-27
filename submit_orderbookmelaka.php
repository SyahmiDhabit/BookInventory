<?php
include 'connection.php';

// Get raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Basic validation
if (!isset($data['picID'], $data['schoolCode'], $data['books']) || !is_array($data['books'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$picID = $data['picID'];
$schoolCode = $data['schoolCode'];
$books = $data['books'];

$stmt = $conn->prepare("INSERT INTO orderbookmelaka (picID, schoolCode, bookCode, bookTitle, realQty, shortQty, status) VALUES (?, ?, ?, ?, ?, ?, ?)");

foreach ($books as $book) {
    $stmt->bind_param(
        "isssdis",
        $picID,
        $schoolCode,
        $book['code'],
        $book['title'],
        $book['realQty'],
        $book['shortQty'],
        $book['status']
    );
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true]);
