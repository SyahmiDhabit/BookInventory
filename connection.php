<?php
$host = "localhost"; // atau IP/database host anda
$user = "gramixx_gramixx";      // username anda (GoDaddy biasanya bukan 'root')
$password = "gramixx1234@";      // password anda
$dbname = "gramixx_booksystem"; // nama database anda

// Sambung ke pangkalan data
$conn = new mysqli($host, $user, $password, $dbname);

// Semak sambungan
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

?>
