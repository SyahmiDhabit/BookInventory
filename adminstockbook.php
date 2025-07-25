<!-- adminstockbook.php -->
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Pilih Negeri</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 60px;
            background-color: #f2f2f2;
        }

        h2 {
            margin-bottom: 30px;
            color: #333;
        }

        form button {
            padding: 15px 30px;
            font-size: 18px;
            margin: 10px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        form button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h2>Sila Pilih Negeri</h2>
    <form action="stockbook.php" method="GET">
        <button type="submit" name="negeri" value="Melaka">Melaka</button>
        <button type="submit" name="negeri" value="Negeri Sembilan">Negeri Sembilan</button>
    </form>
</body>
</html>
