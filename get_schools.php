<?php
include 'connection.php';
header('Content-Type: application/json');

$negeri = $_POST['negeri'] ?? '';
$schools = [];

if ($negeri === 'Melaka') {
    $query = "SELECT schoolCodeM AS code, schoolNameM AS name FROM schoolmelaka ORDER BY schoolNameM ASC";
} elseif ($negeri === 'Negeri Sembilan') {
    $query = "SELECT schoolCodeN AS code, schoolnameN AS name FROM schooln9 ORDER BY schoolnameN ASC";
} else {
    echo json_encode([]);
    exit;
}

$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $schools[] = $row;
    }
}

echo json_encode($schools);
?>
