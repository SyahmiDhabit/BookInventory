<?php
session_start();
include 'connection.php';

$negeri = $_GET['negeri'] ?? '';
$search = $_GET['search'] ?? '';
$savedCodes = $_SESSION['savedCodes'] ?? [];

if (!is_array($savedCodes)) $savedCodes = [];

$searchSql = '';
$search = mysqli_real_escape_string($conn, $search);
if ($search !== '') {
    $searchSql = " AND (titleBook LIKE '%$search%' OR codeBook LIKE '%$search%')";
}

if ($negeri === 'Melaka') {
    $query = "SELECT bil, category, codeBook, titleBook, MelakaTengah AS totalReceive, comment, dateReceive FROM bookmelaka WHERE 1 $searchSql";
}
 elseif ($negeri === 'Negeri Sembilan') {
    $query = "SELECT bil, category, codeBook, titleBook, totalReceive, comment, dateReceive FROM bookn9 WHERE 1 $searchSql";
} else {
    echo "<p style='text-align:center;'>Sila pilih negeri dahulu.</p>";
    exit;
}

$result = mysqli_query($conn, $query);
if (!$result) {
    die("SQL Error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Senarai Buku - <?= htmlspecialchars($negeri) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* (CSS tidak berubah, sama seperti yang anda berikan) */
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 20px;
            background-color: #f8f9fa;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .back-btn-top {
            text-align: left;
            margin-bottom: 15px;
        }

        .back-btn-top button {
            padding: 10px 18px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .back-btn-top button:hover {
            background-color: #495057;
        }

        .search-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .search-container input[type="text"] {
            padding: 10px;
            width: 60%;
            max-width: 400px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .search-container button {
            padding: 10px 16px;
            font-size: 14px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 6px;
            margin-left: 5px;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #0b5ed7;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px 10px;
            border: 1px solid #dee2e6;
            text-align: center;
            font-size: 14px;
        }

        th {
            background-color: #e9ecef;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f1f3f5;
        }

        .save-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            background-color: #198754;
            color: #fff;
            transition: background-color 0.3s;
        }

        .save-btn:hover {
            background-color: #157347;
        }

        .comment-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 5px;
        }

        .comment-box label {
            font-size: 13px;
            margin-bottom: 5px;
            color: #555;
            text-align: center;
            width: 100%;
        }

        .comment-box input {
            width: 100%;
            max-width: 160px;
            padding: 6px;
            border: 1px solid #bbb;
            border-radius: 6px;
            font-size: 14px;
        }

        select {
            padding: 6px;
            border-radius: 5px;
        }

        input[type="date"] {
            padding: 6px;
            border-radius: 5px;
            border: 1px solid #bbb;
        }
    </style>
    <script>
        function toggleAmountInput(selectElement, index) {
            var amountBox = document.getElementById('amount_' + index);
            var otherBox = document.getElementById('other_' + index);

            var selectedValue = selectElement.value;
            if (selectedValue === 'Kurang' || selectedValue === 'Lebih' || selectedValue === 'Tak Terima tapi DO ada') {
                amountBox.style.display = 'flex';
                otherBox.style.display = 'none';
            } else if (selectedValue === 'Lain-lain') {
                amountBox.style.display = 'none';
                otherBox.style.display = 'flex';
            } else {
                amountBox.style.display = 'none';
                otherBox.style.display = 'none';
            }
        }
    </script>
</head>
<body>

<div class="back-btn-top">
    <button onclick="window.location.href='adminstockbook.php'">← Kembali ke Pilihan Negeri</button>
</div>

<h2>Senarai Buku - <?= htmlspecialchars($negeri) ?></h2>

<div class="search-container">
    <form method="GET" action="">
        <input type="hidden" name="negeri" value="<?= htmlspecialchars($negeri) ?>">
        <input type="text" name="search" placeholder="Cari nama sekolah atau kod buku..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Cari</button>
    </form>
</div>

<div class="table-container">
<?php if ($negeri === 'Negeri Sembilan'): ?>
    <a href="export_pdf_n9.php" target="_blank">
        <button>Export to PDF</button>
    </a>
<?php elseif ($negeri === 'Melaka'): ?>
    <a href="export_pdf_melaka.php" target="_blank">
        <button>Export to PDF</button>
    </a>
<?php endif; ?>



    <table>
        <thead>
        <tr>
            <th>BIL</th>
            <th>Kategori</th>
            <th>Kod Buku</th>
            <th>Tajuk Buku</th>
            <th>Jumlah Diterima</th>
            <th>Komen</th>
            <th>Tarikh Terima</th>
            <th>Tindakan</th>
        </tr>
        </thead>
        <tbody>
        <?php 
        $index = 0;
        while ($row = mysqli_fetch_assoc($result)):
            $commentParts = explode(" - ", $row['comment']);
            $selectedComment = $commentParts[0] ?? '';
            $additionalComment = $commentParts[1] ?? '';
        ?>
        <tr>
            <form action="savestockbook.php" method="POST">
                <input type="hidden" name="negeri" value="<?= htmlspecialchars($negeri) ?>">
                <input type="hidden" name="codeBook" value="<?= $row['codeBook'] ?>">

                <td><?= $row['bil'] ?></td>
                <td><?= $row['category'] ?></td>
                <td><?= $row['codeBook'] ?></td>
                <td><?= $row['titleBook'] ?></td>

              
                    <td><?= $row['totalReceive'] ?></td>
          

                <td>
                    <div class="comment-wrapper">
                        <select name="commentSelect" onchange="toggleAmountInput(this, <?= $index ?>)" required>
                            <option value="">--Pilih--</option>
                            <?php
                            $options = ['Ok', 'Kurang', 'Lebih', 'Tak Terima tapi DO ada', 'Lain-lain'];
                            foreach ($options as $option) {
                                $selected = ($option == $selectedComment) ? 'selected' : '';
                                echo "<option value=\"$option\" $selected>$option</option>";
                            }
                            ?>
                        </select>

                        <div id="amount_<?= $index ?>" class="comment-box" style="display: <?= in_array($selectedComment, ['Kurang', 'Lebih', 'Tak Terima tapi DO ada']) ? 'flex' : 'none' ?>">
                            <label>Masukkan Jumlah:</label>
                            <input type="number" name="jumlahKomen" value="<?= in_array($selectedComment, ['Kurang', 'Lebih', 'Tak Terima tapi DO ada']) ? $additionalComment : '' ?>">
                        </div>

                        <div id="other_<?= $index ?>" class="comment-box" style="display: <?= $selectedComment === 'Lain-lain' ? 'flex' : 'none' ?>">
                            <label>Nyatakan Komen:</label>
                            <input type="text" name="lainComment" value="<?= $selectedComment === 'Lain-lain' ? $additionalComment : '' ?>">
                        </div>
                    </div>
                </td>

                <td>
                    <input type="date" name="dateReceive" value="<?= $row['dateReceive'] ?>" required>
                </td>

                <td>
                    <button type="submit" class="save-btn">Simpan</button>
                </td>
            </form>
        </tr>
        <?php 
            $index++;
        endwhile; 
        ?>
        </tbody>
    </table>
</div>

</body>
</html>
