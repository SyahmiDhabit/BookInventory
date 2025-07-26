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
    $table = $state === 'melaka' ? 'orderbookmelaka' : 'orderbookn9';
    $books = [];

$sql = "SELECT ob.*, ab.titleBook, sc." . ($state === 'melaka' ? 'schoolNameM' : 'schoolNameN') . " AS schoolName
        FROM $table ob
        LEFT JOIN allbooklist ab ON ob.codeBook = ab.codeBook
        LEFT JOIN school" . $state . " sc ON ob.schoolCode" . strtoupper($state[0]) . " = sc.schoolCode" . strtoupper($state[0]) . "
        ORDER BY ob.picID, ob.schoolCode" . strtoupper($state[0]);

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
        <a href="#" class="btn">📄 Customer Report</a>
        <a href="#" class="btn">🏫 List School Sort</a>
        <a href="adminstockbook.php" class="btn">📚 List Book</a>
        <a href="adminlogin.php" class="btn">🚪 Logout</a>
    </div>
</div>

<div class="container">
    <h2>📍 Negeri Sembilan - PIC Book List</h2>
    <?php if (!empty($n9Books)) : ?>
        <?php foreach ($n9Books as $picID => $schools): ?>
    <div class="pic-section">
        <h3>👤 <?= $pics[$picID] ?? "Unknown PIC (ID: $picID)" ?></h3>
        <?php foreach ($schools as $schoolCode => $schoolData): ?>
            <details>
                <summary>🏫 <?= $schoolData['schoolName'] ?></summary>
                <table>
                    <tr>
                        <th>Book Code</th>
                        <th>Title</th>
                        <th>Real Quantity</th>
                        <th>Short Quantity</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($schoolData['books'] as $book): ?>
                        <tr>
                            <td><?= $book['codeBook'] ?></td>
                            <td><?= $book['titleBook'] ?? '-' ?></td>
                            <td><?= $book['realQtyN'] ?? '-' ?></td>
<td><?= $book['sortQtyN'] ?? '-' ?></td>
<td><?= $book['statusN'] ?? '-' ?></td>

                            <td>
                                <form action="delete_orderbook.php" method="POST" onsubmit="return confirm('Delete this book entry?');">
                                    <input type="hidden" name="id" value="<?= $book['id'] ?>">
                                    <button type="submit" class="btn-delete">❌ Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </details>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

    <?php else: ?>
        <div class="no-data">No PIC Incharge</div>
    <?php endif; ?>
    <a href="newbookpicN9.php?state=n9" class="btn-add">➕ Add Book PIC</a>

    <h2>📍 Melaka - PIC Book List</h2>
    <?php if (!empty($melakaBooks)) : ?>
        <?php foreach ($melakaBooks as $picID => $schools): ?>
    <div class="pic-section">
        <h3>👤 <?= $pics[$picID] ?? "Unknown PIC (ID: $picID)" ?></h3>
        <?php foreach ($schools as $schoolCode => $schoolData): ?>
            <details>
                <summary>🏫 <?= $schoolData['schoolName'] ?></summary>
                <table>
                    <tr>
                        <th>Book Code</th>
                        <th>Title</th>
                        <th>Real Quantity</th>
                        <th>Short Quantity</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($schoolData['books'] as $book): ?>
                        <tr>
                            <td><?= $book['codeBook'] ?></td>
                            <td><?= $book['titleBook'] ?? '-' ?></td>
                            <td><?= $book['realQtyM'] ?></td>
                            <td><?= $book['sortQtyM'] ?></td>
                            <td><?= $book['statusM'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </details>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

    <?php else: ?>
        <div class="no-data">No PIC Incharge</div>
    <?php endif; ?>
    <a href="newbookpicMelaka.php?state=melaka" class="btn-add">➕ Add Book PIC</a>
</div>

</body>
</html>