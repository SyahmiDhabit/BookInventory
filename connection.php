<?php
$host = "localhost"; // atau IP/database host anda
$user = "root";      // username anda (GoDaddy biasanya bukan 'root')
$password = "";      // password anda
$dbname = "booksystem"; // nama database anda

// Sambung ke pangkalan data
$conn = new mysqli($host, $user, $password, $dbname);

// Semak sambungan
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

 echo "Sambungan berjaya";
?>
