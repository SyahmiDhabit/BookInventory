
<?php
include 'connection.php';

$negeri = $_POST['negeri'] ?? '';
$schoolCode = $_POST['sekolah'] ?? '';
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
              <th>Nama Buku</th>
              <th>Jumlah Ditempah</th>
              <th>Jumlah Diterima</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Matematik Tahun 1</td>
              <td>50</td>
              <td>45</td>
            </tr>
            <tr>
              <td>Sains Tahun 2</td>
              <td>60</td>
              <td>60</td>
            </tr>
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
        <input type="text" id="book-name" placeholder="Contoh: Bahasa Melayu Tahun 3">

        <label for="book-quantity">Kuantiti:</label>
        <input type="number" id="book-quantity" placeholder="Contoh: 10">

        <label for="comment">Komen:</label>
        <textarea name="comment" id="comment" rows="3" placeholder="Contoh: Buku tak cukup..."></textarea>

        <button type="button" id="add-book">Tambah Buku</button>

        <!-- Buku Yang Ditambah -->
        <div id="book-list">
          <h4>Buku Ditambah</h4>
          <ul id="book-orders"></ul>
        </div>

        <!-- Data tersembunyi yang akan dihantar -->
        <input type="hidden" name="book_data" id="book_data">

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
    const bookList = document.getElementById('book-orders');
    const bookDataInput = document.getElementById('book_data');

    document.getElementById('add-book').addEventListener('click', function () {
      const bookName = document.getElementById('book-name').value.trim();
      const bookQty = document.getElementById('book-quantity').value.trim();

      if (bookName && bookQty) {
        const bookItem = `${bookName} (${bookQty})`;
        bookOrders.push({ name: bookName, quantity: bookQty });

        const li = document.createElement('li');
        li.textContent = bookItem;
        bookList.appendChild(li);

        // Reset input
        document.getElementById('book-name').value = "";
        document.getElementById('book-quantity').value = "";

        // Update hidden input with JSON
        bookDataInput.value = JSON.stringify(bookOrders);
      }
    });
  </script>
</body>
</html>
