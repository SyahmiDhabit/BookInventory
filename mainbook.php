<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Senarai Buku</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding-top: 100px;
        }
        button {
            padding: 15px 30px;
            font-size: 18px;
            margin: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Pilih Negeri Untuk Lihat Senarai Buku</h1>
    <form action="booklistmelaka.php" method="get" style="display:inline-block;">
        <button type="submit">Melaka</button>
    </form>
    <form action="booklistn9.php" method="get" style="display:inline-block;">
        <button type="submit">Negeri Sembilan</button>
    </form>
</body>
</html>
