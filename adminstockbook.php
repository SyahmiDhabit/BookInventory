<!-- adminstockbook.php -->
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Pilih Negeri</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="adminstockbook.css" />
    
  
    <div class="header">Gramixx Book Inventory</div>

    <div class = "menu">
    <h1>Admin Panel</h1>
    <div class="button-grid">
        <a href="customerReport.php" class="btn">📄 Customer Report</a>
        <a href="admininterface.php" class="btn">🏫 List School Sort</a>
        <a href="adminstockbook.php" class="btn">📚 List Book</a>
        <a href="adminlogin.php" class="btn">🚪 Logout</a>
    </div>
    </div>
</head>
<body>
    <div class="container">
        <h2>Sila Pilih Negeri</h2>

        <form action="stockbook.php" method="GET" class="button-group">
            <button type="submit" name="negeri" value="Melaka" class="state-btn">Melaka</button>
            <button type="submit" name="negeri" value="Negeri Sembilan" class="state-btn">Negeri Sembilan</button>
        </form>
    </div>
</body>
</html>
