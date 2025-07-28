<?php
include 'connection.php';

// Fetch all PICs
$picResult = $conn->query("SELECT * FROM personincharge");
$pics = [];
while ($row = $picResult->fetch_assoc()) {
    $pics[$row['picID']] = $row['picName'];
}

// Helper: Get books for a state
function getBooksByState($conn, $state, $pics) {
    $books = [];

    if ($state === 'melaka') {
        $sql = "SELECT ob.*, ab.titleBook, sc.schoolNameM AS schoolName
                FROM orderbookmelaka ob
                LEFT JOIN allbooklist ab ON ob.codeBook = ab.codeBook
                LEFT JOIN schoolmelaka sc ON ob.schoolCodeM = sc.schoolCodeM
                ORDER BY ob.picID, ob.schoolCodeM";
    } else {
        $sql = "SELECT ob.*, ab.titleBook, sc.schoolNameN AS schoolName
                FROM orderbookn9 ob
                LEFT JOIN allbooklist ab ON ob.codeBook = ab.codeBook
                LEFT JOIN schooln9 sc ON ob.schoolCodeN = sc.schoolCodeN
                ORDER BY ob.picID, ob.schoolCodeN";
    }

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $picID = $row['picID'];
        $schoolCode = $state === 'melaka' ? $row['schoolCodeM'] : $row['schoolCodeN'];
        $schoolName = $row['schoolName'];

        if (!isset($books[$picID])) $books[$picID] = [];
        if (!isset($books[$picID][$schoolCode])) {
            $books[$picID][$schoolCode] = [
                'schoolName' => $schoolName,
                'books' => []
            ];
        }

        $books[$picID][$schoolCode]['books'][] = $row;
    }

    return $books;
}


// Fetch books for both states
$melakaBooks = getBooksByState($conn, 'melaka', $pics);
$n9Books = getBooksByState($conn, 'n9', $pics);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Interface - Gramixx</title>
    <link rel="stylesheet" href="admininterface.css" />
</head>
<body>

<div class="header">Gramixx Book Inventory</div>

<div class="container">
    <h1>Admin Panel</h1>
    <div class="button-grid">
        <a href="customerReport.php" class="btn">📄 Customer Report</a>
        <a href="#" class="btn">🏫 List School Sort</a>
        <a href="adminstockbook.php" class="btn">📚 List Book</a>
        <a href="adminlogin.php" class="btn">🚪 Logout</a>
    </div>
</div>

<div class="container">
    <div class="state-wrapper">
    <h2>📍 Negeri Sembilan - PIC Book List</h2>
    <?php if (!empty($n9Books)) : ?>
        <?php foreach ($n9Books as $picID => $schools): ?>
    <div class="pic-section">
        <h3>👤 <?= $pics[$picID] ?? "Unknown PIC (ID: $picID)" ?></h3>
        <table>
    <tr>
        <th>No.</th>
        <th>School Name</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php $count = 1; foreach ($schools as $schoolCode => $schoolData): ?>
        <tr>
            <td><?= $count++ ?></td>
            <td>
                <details>
<summary><?= $schoolData['schoolName'] ?></summary>

                    <table>
                        <tr>
                            <th>Book Code</th>
                            <th>Title</th>
                            <th>Real Quantity</th>
                            <th>Sort Quantity</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach ($schoolData['books'] as $book): ?>
                            <tr>
                                <td><?= $book['codeBook'] ?></td>
                                <td><?= $book['titleBook'] ?? '-' ?></td>
                                <td><?= $book['realQtyN'] ?? '-' ?></td>
                                <td><?= $book['sortQtyN'] ?? '-' ?></td>
                                <td>
                                    <form action="delete_orderbook.php" method="POST" onsubmit="return confirm('Delete this book entry?');">
                                        <input type="hidden" name="id" value="<?= $book['orderIDN'] ?>">
                                        <button type="submit" class="btn-delete">❌ Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </details>
            </td>
            <?php
    // Check if all books are marked as Delivered
    $allDelivered = true;
    foreach ($schoolData['books'] as $book) {
        if ($book['statusN'] !== 'Delivered') {
            $allDelivered = false;
            break;
        }
    }
?>

           <td>
    <form action="update_school_status.php" method="POST">
        <input type="hidden" name="schoolCodeN" value="<?= $schoolCode ?>">
        <input type="hidden" name="picID" value="<?= $picID ?>">

        <select name="status" onchange="this.form.submit()">
            <option value="Delivered" <?= $allDelivered ? 'selected' : '' ?>>Delivered</option>
            <option value="Not Delivered" <?= !$allDelivered ? 'selected' : '' ?>>Not Delivered</option>
        </select>
    </form>
</td>

            <td>
                <form action="delete_school_order.php" method="POST" onsubmit="return confirm('Delete all book orders for this school?');">
                    <input type="hidden" name="schoolCodeN" value="<?= $schoolCode ?>">
                    <input type="hidden" name="picID" value="<?= $picID ?>">
                    <button type="submit" class="btn-delete">❌ Delete School</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

    </div>
<?php endforeach; ?>

    <?php else: ?>
        <div class="no-data">No PIC Incharge</div>
    <?php endif; ?>
    <a href="newbookpicN9.php?state=n9" class="btn-add">➕ Add Book PIC</a>
    </div>


    <div class="state-wrapper">
    <h2>📍 Melaka - PIC Book List</h2>
    <?php if (!empty($melakaBooks)) : ?>
        <?php foreach ($melakaBooks as $picID => $schools): ?>
            <div class="pic-section">
                <h3>👤 <?= $pics[$picID] ?? "Unknown PIC (ID: $picID)" ?></h3>
                <table>
                    <tr>
                        <th>No.</th>
                        <th>School Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php $count = 1; foreach ($schools as $schoolCode => $schoolData): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td>
                                <details>
                                    <summary><?= $schoolData['schoolName'] ?></summary>

                                    <table>
                                        <tr>
                                            <th>Book Code</th>
                                            <th>Title</th>
                                            <th>Real Quantity</th>
                                            <th>Sort Quantity</th>
                                            <th>Action</th>
                                        </tr>
                                        <?php foreach ($schoolData['books'] as $book): ?>
                                            <tr>
                                                <td><?= $book['codeBook'] ?></td>
                                                <td><?= $book['titleBook'] ?? '-' ?></td>
                                                <td><?= $book['realQtyM'] ?? '-' ?></td>
                                                <td><?= $book['sortQtyM'] ?? '-' ?></td>
                                                <td>
                                                    <form action="delete_orderbook_melaka.php" method="POST" onsubmit="return confirm('Delete this book entry?');">
                                                        <input type="hidden" name="id" value="<?= $book['orderIDM'] ?>">
                                                        <button type="submit" class="btn-delete">❌ Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </details>
                            </td>
                            <?php
                                // Check if all books are marked as Delivered
                                $allDelivered = true;
                                foreach ($schoolData['books'] as $book) {
                                    if ($book['statusM'] !== 'Delivered') {
                                        $allDelivered = false;
                                        break;
                                    }
                                }
                            ?>
                            <td>
                                <form action="update_school_status_melaka.php" method="POST">
                                    <input type="hidden" name="schoolCodeM" value="<?= $schoolCode ?>">
                                    <input type="hidden" name="picID" value="<?= $picID ?>">

                                    <select name="status" onchange="this.form.submit()">
                                        <option value="Delivered" <?= $allDelivered ? 'selected' : '' ?>>Delivered</option>
                                        <option value="Not Delivered" <?= !$allDelivered ? 'selected' : '' ?>>Not Delivered</option>
                                    </select>
                                </form>
                            </td>

                            <td>
                                <form action="delete_school_order_melaka.php" method="POST" onsubmit="return confirm('Delete all book orders for this school?');">
                                    <input type="hidden" name="schoolCodeM" value="<?= $schoolCode ?>">
                                    <input type="hidden" name="picID" value="<?= $picID ?>">
                                    <button type="submit" class="btn-delete">❌ Delete School</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-data">No PIC Incharge</div>
    <?php endif; ?>

    <a href="newbookpicMelaka.php?state=melaka" class="btn-add">➕ Add Book PIC</a>
</div>

</div>

</body>
</html>