<?php
include 'connection.php';

// Fetch reports from Melaka
$queryMelaka = "
    SELECT 
        r.reportIDM, r.nameM, r.phoneNumberM, r.emailM, r.commentM, r.qtyM,
        s.schoolNameM, b.titleBook
    FROM reportmelaka r
    JOIN orderbookmelaka o ON r.orderIDM = o.orderIDM
    JOIN schoolmelaka s ON o.schoolCodeM = s.schoolCodeM
    JOIN allbooklist b ON o.codeBook = b.codeBook
    ORDER BY r.reportIDM DESC
";

$resultMelaka = $conn->query($queryMelaka);

// Fetch reports from Negeri Sembilan
$queryN9 = "
    SELECT 
        r.reportIDN, r.nameN, r.phoneNumberN, r.emailN, r.commentN, r.qtyN,
        s.schoolnameN, b.titleBook
    FROM reportn9 r
    JOIN orderbookn9 o ON r.orderIDN = o.orderIDN
    JOIN schooln9 s ON o.schoolCodeN = s.schoolCodeN
    JOIN allbooklist b ON o.codeBook = b.codeBook
    ORDER BY r.reportIDN DESC
";

$resultN9 = $conn->query($queryN9);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Report - Gramixx</title>
    <link rel="stylesheet" href="admininterface.css">
    <style>
        .report-section {
            border: 2px solid #ccc;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
            background: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #bbb;
        }
        h2 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="header">Gramixx Book Inventory</div>
<div class="container">
    <h1>Admin Panel</h1>
    <div class="button-grid">
        <a href="customerReport.php" class="btn">📄 Customer Report</a>
        <a href="admininterface.php" class="btn">🏫 List School Sort</a>
        <a href="adminstockbook.php" class="btn">📚 List Book</a>
        <a href="adminlogin.php" class="btn">🚪 Logout</a>
    </div>
</div>
<div class="container">
    <h1>Customer Book Complaint Reports</h1>

    <div class="report-section">
        <h2>📍 Negeri Sembilan</h2>
        <?php if ($resultN9->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>School</th>
                    <th>Book</th>
                    <th>Qty</th>
                    <th>Comment</th>
                </tr>
                <?php while($row = $resultN9->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nameN']) ?></td>
                        <td><?= htmlspecialchars($row['phoneNumberN']) ?></td>
                        <td><?= htmlspecialchars($row['emailN']) ?></td>
                        <td><?= htmlspecialchars($row['schoolnameN']) ?></td>
                        <td><?= htmlspecialchars($row['titleBook']) ?></td>
                        <td><?= (int)$row['qtyN'] ?></td>
                        <td><?= htmlspecialchars($row['commentN']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No reports found for Negeri Sembilan.</p>
        <?php endif; ?>
    </div>

    <div class="report-section">
        <h2>📍 Melaka</h2>
        <?php if ($resultMelaka->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>School</th>
                    <th>Book</th>
                    <th>Qty</th>
                    <th>Comment</th>
                </tr>
                <?php while($row = $resultMelaka->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nameM']) ?></td>
                        <td><?= htmlspecialchars($row['phoneNumberM']) ?></td>
                        <td><?= htmlspecialchars($row['emailM']) ?></td>
                        <td><?= htmlspecialchars($row['schoolNameM']) ?></td>
                        <td><?= htmlspecialchars($row['titleBook']) ?></td>
                        <td><?= (int)$row['qtyM'] ?></td>
                        <td><?= htmlspecialchars($row['commentM']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No reports found for Melaka.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
