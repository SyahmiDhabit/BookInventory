<?php
include 'connection.php';
<<<<<<< Updated upstream
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
=======

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
>>>>>>> Stashed changes
?>
