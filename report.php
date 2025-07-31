
<?php
include 'connection.php';

$negeri = $_POST['negeri'] ?? '';
$schoolCode = $_POST['sekolah'] ?? '';
$sekolah = $schoolCode; // ✅ Fix for hidden input
$schoolName = '';

if ($negeri === 'Melaka') {
    $query = "SELECT schoolNameM AS name FROM schoolmelaka WHERE schoolCodeM = ?";
} elseif ($negeri === 'Negeri Sembilan') {
    $query = "SELECT schoolNameN AS name FROM schooln9 WHERE schoolCodeN = ?";
}

if (!empty($query)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $schoolCode);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $schoolName);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}
$orderedBooks = [];
if ($negeri === 'Melaka') {
    $query = "SELECT ab.titleBook FROM orderbookmelaka ob JOIN allbooklist ab ON ob.codeBook = ab.codeBook WHERE ob.schoolCodeM = ?";
} elseif ($negeri === 'Negeri Sembilan') {
    $query = "SELECT ab.titleBook FROM orderbookn9 ob JOIN allbooklist ab ON ob.codeBook = ab.codeBook WHERE ob.schoolCodeN = ?";
}

if (!empty($query)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $schoolCode);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $orderedBooks[] = $row['titleBook'];
    }

    $stmt->close();
}

?>


<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <title>School Report System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="report.css">
</head>
<body>
  <!-- Header with School Info -->
  <header class="header" style="background-color: #000; color: white; padding: 20px; text-align: center;">
  <h2>Sistem Laporan Buku</h2>
  <p>Negeri: <?php echo htmlspecialchars($negeri); ?></p>
<p>Nama Sekolah: <?php echo htmlspecialchars($schoolName); ?></p>
</header>

  <div class="container">
    <!-- Section 1: View Book Order List -->
    <div class="section">
      <h3>Senarai Tempahan Buku Sebelum Ini</h3>
      <div class="order-list-header">
        <button id="view-order-btn" class="view-btn">Lihat Senarai</button>
        <button id="close-view-btn" class="close-btn" style="display:none;">Tutup</button>
      </div>
      
      <div id="order-list" class="order-list" style="display:none;">
        <table>
          <thead>
            <tr>
              <th>Kod Buku</th>
              <th>Nama Buku</th>
              <th>Jumlah Ditempah</th>
              <th>Jumlah Diterima</th>
            </tr>
          </thead>
          <tbody>
<?php
if ($negeri === 'Melaka') {
    $query = "SELECT ob.codeBook, ab.titleBook, ob.realQtyM, ob.sortQtyM
              FROM orderbookmelaka ob
              JOIN allbooklist ab ON ob.codeBook = ab.codeBook
              WHERE ob.schoolCodeM = ?";
} elseif ($negeri === 'Negeri Sembilan') {
    $query = "SELECT ob.codeBook, ab.titleBook, ob.realQtyN, ob.sortQtyN
              FROM orderbookn9 ob
              JOIN allbooklist ab ON ob.codeBook = ab.codeBook
              WHERE ob.schoolCodeN = ?";
}

if (!empty($query)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $schoolCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['codeBook']) . "</td>";
        echo "<td>" . htmlspecialchars($row['titleBook']) . "</td>";
        echo "<td>" . htmlspecialchars($negeri === 'Melaka' ? $row['realQtyM'] : $row['realQtyN']) . "</td>";
        echo "<td>" . htmlspecialchars($negeri === 'Melaka' ? $row['sortQtyM'] : $row['sortQtyN']) . "</td>";
        echo "</tr>";
    }
} else {
    echo '<tr><td colspan="4" style="text-align:center; color: #777;">Tiada rekod tempahan buku</td></tr>';
}


    $stmt->close();
}
?>
</tbody>

        </table>
      </div>
    </div>

    <!-- Section 2: Contact Info -->
    <div class="section">
      <h3>Maklumat Cikgu</h3>
      <form id="orderForm" action="submit_aduan.php" method="POST">
        <input type="hidden" name="sekolah" value="<?php echo htmlspecialchars($sekolah); ?>">
        <input type="hidden" name="negeri" value="<?php echo htmlspecialchars($negeri); ?>">

        <label for="name">Nama:</label>
        <input type="text" name="name" id="name" required>

        <label for="phone">Nombor Telefon:</label>
        <input type="text" name="phone" id="phone" required>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>

        <!-- Section 3: Tambah Aduan Buku -->
        <h3>Tambah Aduan Buku</h3>
        <label for="book-name">Nama Buku:</label>
        <select id="book-name">
  <option value="">-- Pilih Buku --</option>
  <?php foreach ($orderedBooks as $title): ?>
    <option value="<?= htmlspecialchars($title) ?>"><?= htmlspecialchars($title) ?></option>
  <?php endforeach; ?>
</select>


        <label for="book-quantity">Kuantiti:</label>
        <input type="number" id="book-quantity" placeholder="Contoh: 10">

        <label for="comment">Komen:</label>
        <textarea name="comment" id="comment" rows="3" placeholder="Contoh: Buku tak cukup..."></textarea>

        <button type="button" id="add-book">Tambah Buku</button>

        <!-- Buku Yang Ditambah -->
        <div id="book-list">
          <h4>Buku Ditambah</h4>
          <table id="book-orders" border="1" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
  <thead>
    <tr>
      <th>Nama Buku</th>
      <th>Kuantiti</th>
      <th>Komen</th>
      <th>Tindakan</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

        </div>

        <!-- Data tersembunyi yang akan dihantar -->
      <?php
// Get order ID for this school
$orderID = 0;
if ($negeri === 'Melaka') {
    $stmt = $conn->prepare("SELECT orderIDM FROM orderbookmelaka WHERE schoolCodeM = ? LIMIT 1");
    $stmt->bind_param("s", $schoolCode);
    $stmt->execute();
    $stmt->bind_result($orderID);
    $stmt->fetch();
    $stmt->close();
} elseif ($negeri === 'Negeri Sembilan') {
    $stmt = $conn->prepare("SELECT orderIDN FROM orderbookn9 WHERE schoolCodeN = ? LIMIT 1");
    $stmt->bind_param("s", $schoolCode);
    $stmt->execute();
    $stmt->bind_result($orderID);
    $stmt->fetch();
    $stmt->close();
}

// ✅ Display warning if no orderID found
if ($orderID === 0) {
    echo "<p style='color:red;'>❌ No Order ID found for this school ($schoolCode) in $negeri.</p>";
}
?>

<!-- ✅ NOW place the hidden input AFTER orderID is correctly set -->
<input type="hidden" name="orderID" value="<?= htmlspecialchars($orderID) ?>">
<input type="hidden" name="bookData" id="bookData">

<!-- Butang -->
<div class="button-row">
  <button type="button" class="back" onclick="history.back()">Kembali</button>
  <button type="submit" class="submit">Hantar Aduan</button>
</div>

      </form>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    // Toggle View Order List
    document.getElementById('view-order-btn').addEventListener('click', function () {
      document.getElementById('order-list').style.display = 'block';
      this.style.display = 'none';
      document.getElementById('close-view-btn').style.display = 'inline-block';
    });

    document.getElementById('close-view-btn').addEventListener('click', function () {
      document.getElementById('order-list').style.display = 'none';
      this.style.display = 'none';
      document.getElementById('view-order-btn').style.display = 'inline-block';
    });

    // Tambah Buku
   const bookOrders = [];
const bookList = document.querySelector('#book-orders tbody');
const bookDataInput = document.getElementById('bookData');

document.getElementById('add-book').addEventListener('click', function () {
  const bookName = document.getElementById('book-name').value.trim();
  const bookQty = document.getElementById('book-quantity').value.trim();
  const comment = document.getElementById('comment').value.trim();

  if (bookName && bookQty && comment) {
    // Store in array
const orderID = document.querySelector('input[name="orderID"]').value;

const entry = {
  name: bookName,
  qty: bookQty,
  comment: comment,
  orderID: orderID
};
console.log(entry);
    bookOrders.push(entry);

    // Create row
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${bookName}</td>
      <td>${bookQty}</td>
      <td>${comment}</td>
      <td><button type="button" class="delete-btn">Padam</button></td>
    `;
    bookList.appendChild(row);

    // Reset input
        // Remove the selected book from dropdown
    const dropdown = document.getElementById('book-name');
    const selectedOption = dropdown.querySelector(`option[value="${bookName}"]`);
    if (selectedOption) {
      selectedOption.remove();
    }

    // Reset input
    dropdown.value = "";

    document.getElementById('book-quantity').value = "";
    document.getElementById('comment').value = "";

    // Update hidden field
    bookDataInput.value = JSON.stringify(bookOrders);

    // Delete handler
   row.querySelector('.delete-btn').addEventListener('click', function () {
  const rowIndex = Array.from(bookList.children).indexOf(row);
  const removed = bookOrders.splice(rowIndex, 1)[0];  // save the removed book

  // Remove the row from table
  row.remove();

  // Update the hidden input
  bookDataInput.value = JSON.stringify(bookOrders);

  // Re-add the book to the dropdown
  const dropdown = document.getElementById('book-name');
  const newOption = document.createElement('option');
  newOption.value = removed.name;
  newOption.textContent = removed.name;
  dropdown.appendChild(newOption);
});

  }
});

    
  </script>
</body>
</html>
