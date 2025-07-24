<?php
include 'connection.php';

$negeri = $_POST['negeri'] ?? '';
$daerah = $_POST['daerah'] ?? '';
$schools = [];

if ($negeri === 'Melaka') {
    $stmt = $conn->prepare("SELECT schoolNameM AS name FROM schoolmelaka WHERE schoolNameM LIKE ?");
    $like = "%$daerah%";
    $stmt->bind_param("s", $like);
} elseif ($negeri === 'Negeri Sembilan') {
    $stmt = $conn->prepare("SELECT schoolNameN AS name FROM schooln9 WHERE schoolNameN LIKE ?");
    $like = "%$daerah%";
    $stmt->bind_param("s", $like);
}

if (isset($stmt)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $schools[] = $row['name'];
    }
    echo json_encode($schools);
}
?>
