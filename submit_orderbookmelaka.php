<?php
include 'connection.php';

// Get raw JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['picID'], $data['schoolCode'], $data['status'], $data['books'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

$picID = $data['picID'];
$schoolCodeM = $data['schoolCode'];
$statusM = $data['status'];
$books = $data['books'];

foreach ($books as $book) {
    $codeBook = $book['code'];
    $realQtyM = (int) $book['realQty'];
    $sortQtyM = (int) $book['shortQty'];

    $stmt = $conn->prepare("
        INSERT INTO orderbookmelaka 
        (schoolCodeM, codeBook, realQtyM, sortQtyM, statusM, picID) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssiisi", $schoolCodeM, $codeBook, $realQtyM, $sortQtyM, $statusM, $picID);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true]);
?>
