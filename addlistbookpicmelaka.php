<?php
include 'connection.php';

$picID = $_POST['picID'] ?? '';
$schoolCode = $_POST['schoolCode'] ?? '';

// Get PIC Name
$picName = '';
if ($picID) {
    $stmt = $conn->prepare("SELECT picName FROM personincharge WHERE picID = ?");
    $stmt->bind_param("i", $picID);
    $stmt->execute();
    $stmt->bind_result($picName);
    $stmt->fetch();
    $stmt->close();
}

// Get School Name from schoolmelaka
// Get School Name from schoolmelaka
$schoolName = '';
if ($schoolCode) {
    $stmt = $conn->prepare("SELECT schoolNameM FROM schoolmelaka WHERE schoolCodeM = ?");
    $stmt->bind_param("s", $schoolCode);
    $stmt->execute();
    $stmt->bind_result($schoolName);
    $stmt->fetch();
    $stmt->close();
}


// Get book list from bookmelaka
$books = [];
$resultBooks = $conn->query("SELECT codeBook, titleBook FROM bookmelaka");
// Corrected loop
while ($row = $resultBooks->fetch_assoc()) {
    $books[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tambah Buku - Melaka</title>
    <link rel="stylesheet" href="addlistbookpic.css">
</head>
<body>
    <div class="container">
        <h2>Tambah Buku untuk Sekolah - Melaka</h2>
        <p><strong>Negeri:</strong> Melaka</p>
        <p><strong>Sekolah:</strong> <?= htmlspecialchars($schoolName) ?></p>
        <p><strong>PIC:</strong> <?= htmlspecialchars($picName) ?></p>

        <form id="bookForm">
            <input type="hidden" name="picID" value="<?= $picID ?>">
            <input type="hidden" name="schoolCode" value="<?= $schoolCode ?>">

            <label for="bookCode">Kod Buku</label>
            <select id="bookCode" required>
                <option value="">-- Pilih Kod Buku --</option>
                <?php foreach ($books as $book): ?>
    <option value="<?= $book['codeBook'] ?>" data-title="<?= htmlspecialchars($book['titleBook']) ?>">
        <?= $book['codeBook'] ?>
    </option>
<?php endforeach; ?>


            </select>

            <label for="bookTitle">Tajuk Buku</label>
            <input type="text" id="bookTitle" readonly>

            <label for="realQty">Kuantiti Asal</label>
            <input type="number" id="realQty" required>

            <label for="shortQty">Kuantiti Kurang</label>
            <input type="number" id="shortQty" required>

            <label for="status">Status</label>
            <select id="status" required>
                <option value="">-- Pilih Status --</option>
                <option value="Delivered">Delivered</option>
                <option value="Not Delivered">Not Delivered</option>
            </select>

            <div class="actions">
                <button type="button" class="confirm" onclick="addToTable()">Add</button>
                <button type="button" class="cancel" onclick="window.location.href='admininterface.php'">Cancel</button>
            </div>
        </form>

        <table id="bookTable">
            <thead>
                <tr>
                    <th>Kod Buku</th>
                    <th>Tajuk Buku</th>
                    <th>Qty Asal</th>
                    <th>Qty Kurang</th>
                    <th>Status</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <div class="actions">
            <button class="confirm" onclick="submitData()">Confirm</button>
            <button class="cancel" onclick="window.location.href='admininterface.php'">Cancel</button>
        </div>
    </div>

    <script>
        const bookCodeSelect = document.getElementById('bookCode');
        const bookTitleInput = document.getElementById('bookTitle');
        const tableBody = document.querySelector('#bookTable tbody');
        const bookData = [];

        bookCodeSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const title = selectedOption.getAttribute('data-title');
            bookTitleInput.value = title || '';
        });

        function addToTable() {
            const code = bookCodeSelect.value;
            const title = bookTitleInput.value;
            const realQty = document.getElementById('realQty').value;
            const shortQty = document.getElementById('shortQty').value;
            const status = document.getElementById('status').value;

            if (!code || !realQty || !shortQty || !status) {
                alert("Please fill in all fields");
                return;
            }

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${code}</td>
                <td>${title}</td>
                <td>${realQty}</td>
                <td>${shortQty}</td>
                <td>${status}</td>
                <td class="delete-btn" onclick="deleteRow(this)">❌</td>
            `;
            tableBody.appendChild(row);

            bookData.push({ code, title, realQty, shortQty, status });

            bookCodeSelect.value = '';
            bookTitleInput.value = '';
            document.getElementById('realQty').value = '';
            document.getElementById('shortQty').value = '';
            document.getElementById('status').value = '';
        }

        function deleteRow(btn) {
            const row = btn.closest('tr');
            const index = Array.from(row.parentNode.children).indexOf(row);
            row.remove();
            bookData.splice(index, 1);
        }

        function submitData() {
            const formData = {
                picID: document.querySelector('input[name="picID"]').value,
                schoolCode: document.querySelector('input[name="schoolCode"]').value,
                books: bookData
            };

            fetch('submit_orderbookmelaka.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (response.ok) {
                    alert("Order submitted successfully!");
                    window.location.href = 'admininterface.php';
                } else {
                    alert("Submission failed");
                }
            });
        }
    </script>
</body>
</html>
