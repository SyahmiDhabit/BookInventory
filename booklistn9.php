<?php
include 'connection.php';

$sql = "SELECT codeBook, titleBook, category, totalReceive, comment, dateReceive FROM bookn9";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Senarai Buku - Negeri Sembilan</title> 
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background-color: #f9f9f9;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            border-collapse: collapse;
            margin: 30px auto;
            width: 90%;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ccc;
            padding: 12px 15px;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .back-btn {
            display: block;
            margin: 20px auto;
            text-align: center;
        }
        .back-btn a {
            padding: 10px 20px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-btn a:hover {
            background-color: #333;
        }
    </style>
</head>
<body>
   <div class="container">
    <h1>📚 Senarai Buku - Negeri Sembilan</h1>
    <a href="export_pdf_n9.php" target="_blank">
    <button>Export to PDF</button>
</a>

    
    <table border="1" cellpadding="10">
        <tr>
            <th>Code</th>
            <th>Title</th>
            <th>Category</th>
            <th>Total Receive</th>
            <th>Comment</th>
            <th>Date Received</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['codeBook']) ?></td>
                <td><?= htmlspecialchars($row['titleBook']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><?= htmlspecialchars($row['totalReceive']) ?></td>
                <td><?= htmlspecialchars($row['comment']) ?></td>
                <td><?= htmlspecialchars($row['dateReceive']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
    <div class="back-btn">
        <a href="mainbook.php">← Kembali ke Pilihan Negeri</a>
    </div>
</body>
</html>
