<?php
session_start();
include 'connection.php';

// Ambil data dari POST
$negeri = $_POST['negeri'] ?? '';
$codeBook = trim($_POST['codeBook'] ?? '');
$commentSelect = trim($_POST['commentSelect'] ?? '');
$jumlahKomen = trim($_POST['jumlahKomen'] ?? '');
$lainComment = trim($_POST['lainComment'] ?? '');
$dateReceive = $_POST['dateReceive'] ?? '';

// Validasi asas
if (empty($negeri) || empty($codeBook) || empty($commentSelect) || empty($dateReceive)) {
    die("Maklumat tidak lengkap.");
}

// Gabungkan komen berdasarkan pilihan
if (in_array($commentSelect, ['Kurang', 'Lebih', 'Tak Terima tapi DO ada'])) {
    $comment = $commentSelect . " - " . $jumlahKomen;
} elseif ($commentSelect === 'Lain-lain') {
    $comment = $commentSelect . " - " . $lainComment;
} else {
    $comment = $commentSelect;
}

// Tentukan jadual mengikut negeri
if ($negeri === 'Melaka') {
    $query = "UPDATE bookmelaka SET comment = ?, dateReceive = ? WHERE codeBook = ?";
} elseif ($negeri === 'Negeri Sembilan') {
    $query = "UPDATE bookn9 SET comment = ?, dateReceive = ? WHERE codeBook = ?";
} else {
    die("Negeri tidak sah.");
}

// Jalankan query
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL error: " . $conn->error);
}
$stmt->bind_param("sss", $comment, $dateReceive, $codeBook);
$stmt->execute();
$stmt->close();

// Simpan codeBook dalam sesi untuk senarai simpanan
if (!isset($_SESSION['savedCodes']) || !is_array($_SESSION['savedCodes'])) {
    $_SESSION['savedCodes'] = [];
}
if (!in_array($codeBook, $_SESSION['savedCodes'])) {
    $_SESSION['savedCodes'][] = $codeBook;
}

// Redirect balik ke list dengan negeri
header("Location: stockbook.php?negeri=" . urlencode($negeri));
exit;
?>
