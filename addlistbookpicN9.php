<?php
include 'connection.php';
$books = [];

$result = $conn->query("SELECT codeBook, titleBook FROM bookn9");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $picID = $_POST['picID'];
    $schoolCode = $_POST['schoolCode'];

    // Get PIC name
    $stmt = $conn->prepare("SELECT picName FROM personincharge WHERE picID = ?");
    $stmt->bind_param("i", $picID);
    $stmt->execute();
    $stmt->bind_result($picName);
    $stmt->fetch();
    $stmt->close();

    // Get School name
    $stmt = $conn->prepare("SELECT schoolNameN FROM schooln9 WHERE schoolCodeN = ?");
    $stmt->bind_param("s", $schoolCode);
    $stmt->execute();
    $stmt->bind_result($schoolName);
    $stmt->fetch();
    $stmt->close();
} else {
    // Fallback in case someone accessed the page directly
    $picName = "Unknown";
    $schoolName = "Unknown";
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <link rel="stylesheet" href="addlistbookpic.css" />

    <title>Add Book List - N9</title>
</head>
<body>
    
<div class="container">
    <h2>Add Book List - Negeri Sembilan</h2>

    <!-- Info Display -->
    <p><strong>Negeri:</strong> Negeri Sembilan</p>
  <?php
$schoolCode = $_POST['schoolCode'] ?? '';
$picID = $_POST['picID'] ?? '';

$schoolName = 'Unknown';
$picName = 'Unknown';

if ($schoolCode) {
$stmt = $conn->prepare("SELECT schoolNameN FROM schooln9 WHERE schoolCodeN = ?");
    $stmt->bind_param("s", $schoolCode);
    $stmt->execute();
    $stmt->bind_result($schoolName);
    $stmt->fetch();
    $stmt->close();
}

if ($picID) {
$stmt = $conn->prepare("SELECT picName FROM personincharge WHERE picID = ?");
    $stmt->bind_param("s", $picID);
    $stmt->execute();
    $stmt->bind_result($picName);
    $stmt->fetch();
    $stmt->close();
}
?>

<p><strong>Sekolah:</strong> <?= htmlspecialchars($schoolName) ?></p>
<p><strong>PIC:</strong> <?= htmlspecialchars($picName) ?></p>
<label for="status">Status</label>
        <select id="status">
            <option value="Delivered">Delivered</option>
            <option value="Not Delivered">Not Delivered</option>
        </select>

    <!-- Form -->
    <form id="bookForm">
        <label for="bookCode">Book Code</label>
        <select id="bookCode" name="bookCode" onchange="syncTitle()">
    <option value="">-- Choose Code --</option>
    <?php foreach ($books as $book): ?>
        <option value="<?= $book['codeBook'] ?>"><?= $book['codeBook'] ?></option>
    <?php endforeach; ?>
</select>

        <label for="bookTitle">Book Title</label>
        <select id="bookTitle" name="bookTitle" onchange="syncCode()">
    <option value="">-- Choose Title --</option>
    <?php foreach ($books as $book): ?>
        <option value="<?= $book['codeBook'] ?>"><?= $book['titleBook'] ?></option>
    <?php endforeach; ?>
</select>

        <label for="originalQty">Original Quantity</label>
        <input type="number" id="originalQty" min="0">

        <label for="shortQty">Sort Quantity</label>
        <input type="number" id="shortQty" min="0">

<div class="actions">
<button type="button" class="confirm" onclick="addRow()">Add</button>            </div>    </form>

    <!-- Temporary Table -->
    <table id="tempTable">
        <thead>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Original Qty</th>
                <th>Sort Quantity</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <div class="actions">
        <form action="admininterface.php" method="post">
            <button type="submit" class="cancel">Cancel</button>
        </form>
        <form action="submit_orderbookn9.php" method="post" onsubmit="return saveData()">
    <input type="hidden" name="data" id="dataInput">
    <input type="hidden" name="schoolCode" value="<?= htmlspecialchars($schoolCode) ?>">
    <input type="hidden" name="picID" value="<?= htmlspecialchars($picID) ?>">
    <button type="submit" class="confirm">Confirm</button>
</form>

    </div>
</div>

<script>
    const bookMap = <?= json_encode($books) ?>;

function syncTitle() {
    const selectedCode = document.getElementById('bookCode').value;
    const match = bookMap.find(b => b.codeBook === selectedCode);
    if (match) {
        document.getElementById('bookTitle').value = match.codeBook;
    }
}

function syncCode() {
    const selectedCode = document.getElementById('bookTitle').value;
    document.getElementById('bookCode').value = selectedCode;
}


    function addRow() {
    const code = document.getElementById('bookCode').value;
    const title = document.getElementById('bookTitle').options[document.getElementById('bookTitle').selectedIndex].text;
    const originalQty = document.getElementById('originalQty').value;
    const shortQty = document.getElementById('shortQty').value;

    if (!code || !title || originalQty === "" || shortQty === "") {
        alert("Please fill in all fields.");
        return;
    }

    const tbody = document.getElementById('tempTable').querySelector('tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${code}</td>
        <td>${title}</td>
        <td>${originalQty}</td>
        <td>${shortQty}</td>
        <td><span class="delete-btn" onclick="this.closest('tr').remove()">🗑️</span></td>
    `;
    tbody.appendChild(row);
}


    function saveData() {
    const rows = document.getElementById('tempTable').querySelectorAll('tbody tr');
    const data = [];
    const status = document.getElementById('status').value;

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        data.push({
            code: cells[0].innerText,
            title: cells[1].innerText,
            originalQty: cells[2].innerText,
            shortQty: cells[3].innerText
        });
    });

    if (data.length === 0) {
        alert("No data to submit.");
        return false;
    }

    const fullData = {
        status: status,
        items: data
    };

    document.getElementById('dataInput').value = JSON.stringify(fullData);
    return true;
}

</script>
</body>
</html>
