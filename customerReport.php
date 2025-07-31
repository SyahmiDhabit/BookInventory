<?php
if (isset($_GET['delete']) && isset($_GET['state'])) {
    include 'connection.php';
    $id = intval($_GET['delete']);
    $state = $_GET['state'];

    if ($state === 'Melaka') {
        $stmt = $conn->prepare("DELETE FROM reportmelaka WHERE reportIDM = ?");
        $stmt->bind_param("i", $id);
    } elseif ($state === 'Negeri Sembilan') {
        $stmt = $conn->prepare("DELETE FROM reportn9 WHERE reportIDN = ?");
        $stmt->bind_param("i", $id);
    }

    if (isset($stmt)) {
        $stmt->execute();
        $stmt->close();
    }

    header("Location: customerReport.php");
    exit();
}
?>

<?php
include 'connection.php';

// Fetch reports from Melaka
$queryMelaka = "
    SELECT 
        r.reportIDM, r.nameM, r.phoneNumberM, r.emailM, r.commentM, r.qtyM,
        s.schoolNameM, b.titleBook, o.codeBook
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
        s.schoolnameN, b.titleBook, o.codeBook
    FROM reportn9 r
    JOIN orderbookn9 o ON r.orderIDN = o.orderIDN
    JOIN schooln9 s ON o.schoolCodeN = s.schoolCodeN
    JOIN allbooklist b ON o.codeBook = b.codeBook
    ORDER BY r.reportIDN DESC
";

$resultN9 = $conn->query($queryN9);

if (isset($_GET['deleteID']) && isset($_GET['state'])) {
    $deleteID = intval($_GET['deleteID']);
    $state = $_GET['state'];

    if ($state === 'Melaka') {
        $conn->query("DELETE FROM reportmelaka WHERE reportIDM = $deleteID");
    } elseif ($state === 'Negeri Sembilan') {
        $conn->query("DELETE FROM reportn9 WHERE reportIDN = $deleteID");
    }

    header("Location: customerReport.php");
    exit();
}

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
            overflow-x: auto;
             display: inline-block;
        }
        table {
             width: auto;
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
        .details {
    display: none;
    margin-top: 10px;
    padding: 10px;
    border: 1px dashed #aaa;
    background: #fff;
}

.action-btn {
    padding: 5px 10px;
    background: #0066cc;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 4px;
}
.action-btn.delete {
    background: #cc0000;
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
            <th>No.</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>School</th>
            <th>Action</th>
        </tr>
        <?php 
        $count = 1;
        while($row = $resultN9->fetch_assoc()): 
            $rowId = "row" . $count;
        ?>
            <tr>
                <td><?= $count ?></td>
                <td><?= htmlspecialchars($row['nameN']) ?></td>
                <td><?= htmlspecialchars($row['emailN']) ?></td>
                <td><?= htmlspecialchars($row['phoneNumberN']) ?></td>
                <td>
                    <button class="action-btn" onclick="toggleDetails('<?= $rowId ?>')">
                        <?= htmlspecialchars($row['schoolnameN']) ?>
                    </button>
                </td>
                <td>
<button class="action-btn delete" onclick="confirmDelete('<?= $row['reportIDN'] ?>', 'Negeri Sembilan')">Delete</button>
                </td>
            </tr>
            <tr id="<?= $rowId ?>" class="details">
                <td colspan="6">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="border:1px solid #ccc; padding:8px;">Book Code</th>
                                <th style="border:1px solid #ccc; padding:8px;">Title</th>
                                <th style="border:1px solid #ccc; padding:8px;">Quantity</th>
                                <th style="border:1px solid #ccc; padding:8px;">Comment</th>
                                <th style="border:1px solid #ccc; padding:8px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border:1px solid #ccc; padding:8px;"><?= htmlspecialchars($row['codeBook']) ?></td>
                                <td style="border:1px solid #ccc; padding:8px;"><?= htmlspecialchars($row['titleBook']) ?></td>
                                <td style="border:1px solid #ccc; padding:8px;"><?= (int)$row['qtyN'] ?></td>
                                <td style="border:1px solid #ccc; padding:8px;"><?= htmlspecialchars($row['commentN']) ?></td>
                                <td style="border:1px solid #ccc; padding:8px;">
                                    <button class="action-btn delete" onclick="confirmDelete('<?= $row['reportIDN'] ?>')">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        <?php 
        $count++;
        endwhile; 
        ?>
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
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>School</th>
                <th>Action</th>
            </tr>
            <?php 
            $countM = 1;
            while($row = $resultMelaka->fetch_assoc()): 
                $rowId = "melakaRow" . $countM;
            ?>
                <tr>
                    <td><?= $countM ?></td>
                    <td><?= htmlspecialchars($row['nameM']) ?></td>
                    <td><?= htmlspecialchars($row['emailM']) ?></td>
                    <td><?= htmlspecialchars($row['phoneNumberM']) ?></td>
                    <td>
                        <button class="action-btn" onclick="toggleDetails('<?= $rowId ?>')">
                            <?= htmlspecialchars($row['schoolNameM']) ?>
                        </button>
                    </td>
                    <td>
                        <button class="action-btn delete" onclick="confirmDelete('<?= $row['reportIDM'] ?>', 'Melaka')">Delete</button>
                    </td>
                </tr>
                <tr id="<?= $rowId ?>" class="details">
                    <td colspan="6">
                        <table style="width:100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="border:1px solid #ccc; padding:8px;">Book Code</th>
                                    <th style="border:1px solid #ccc; padding:8px;">Title</th>
                                    <th style="border:1px solid #ccc; padding:8px;">Quantity</th>
                                    <th style="border:1px solid #ccc; padding:8px;">Comment</th>
                                    <th style="border:1px solid #ccc; padding:8px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="border:1px solid #ccc; padding:8px;"><?= htmlspecialchars($row['codeBook']) ?></td>
                                    <td style="border:1px solid #ccc; padding:8px;"><?= htmlspecialchars($row['titleBook']) ?></td>
                                    <td style="border:1px solid #ccc; padding:8px;"><?= (int)$row['qtyM'] ?></td>
                                    <td style="border:1px solid #ccc; padding:8px;"><?= htmlspecialchars($row['commentM']) ?></td>
                                    <td style="border:1px solid #ccc; padding:8px;">
                                        <button class="action-btn delete" onclick="confirmDelete('<?= $row['reportIDM'] ?>', 'Melaka')">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            <?php 
            $countM++;
            endwhile; ?>
        </table>
    <?php else: ?>
        <p>No reports found for Melaka.</p>
    <?php endif; ?>
</div>



</div>
<script>
function toggleDetails(rowId) {
    const row = document.getElementById(rowId);
    if (row.style.display === "none" || row.style.display === "") {
        row.style.display = "table-row";
    } else {
        row.style.display = "none";
    }
}

function confirmDelete(reportId, state) {
    if (confirm("Are you sure you want to delete this report?")) {
        window.location.href = "customerReport.php?deleteID=" + reportId + "&state=" + encodeURIComponent(state);
    }
}

</script>

</body>
</html>
