<?php
include 'connection.php';

// Get list of PICs
$picResult = $conn->query("SELECT * FROM personincharge");
$picOptions = "";
$hasPic = false;
if ($picResult->num_rows > 0) {
    $hasPic = true;
    while ($row = $picResult->fetch_assoc()) {
        $picOptions .= "<option value='" . $row['picID'] . "'>" . $row['picName'] . "</option>";
    }
}

// Get schools in Negeri Sembilan
$schoolResult = $conn->query("SELECT * FROM schooln9");
$schoolOptions = "";
while ($row = $schoolResult->fetch_assoc()) {
    $schoolOptions .= "<option value='" . $row['schoolCodeN'] . "'>" . $row['schoolNameN'] . "</option>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="newbookpic.css" />

    <title>Assign Book PIC - Negeri Sembilan</title>
    <script>
        function validateForm() {
            const pic = document.getElementById('picID').value.trim();
            const school = document.getElementById('schoolCode').value.trim();

            if (pic === "") {
                alert("Please select a person in charge.");
                return false;
            }
            if (school === "") {
                alert("Please select a school.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
<div class="container" id="container">
    <h2>Assign Book PIC - Negeri Sembilan</h2>

    <form action="addlistbookpicN9.php" method="post" onsubmit="return validateForm();">
        <label for="picID">Select Person In Charge:</label>
        <?php if ($hasPic): ?>
            <select name="picID" id="picID" required>
    <option value="" disabled selected>-- Choose PIC --</option>
    <?= $picOptions ?>
</select>

        <?php else: ?>
            <div class="no-pic">No personal incharge found.</div>
        <?php endif; ?>

        <label for="schoolCode">Select School (Negeri Sembilan):</label>
        <select name="schoolCode" id="schoolCode" required>
    <option value="" disabled selected>-- Choose School --</option>
    <?= $schoolOptions ?>
</select>

        <button type="submit" <?= !$hasPic ? 'disabled' : '' ?>>Confirm</button>
    </form>

    <div class="add-pic">
        <a href="createpicNameN9.php">➕ Create New Person In Charge</a>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const form = document.querySelector("form");
        form.addEventListener("submit", function (e) {
            const pic = document.getElementById("picID").value.trim();
            const school = document.getElementById("schoolCode").value.trim();

            if (pic === "" || school === "") {
                e.preventDefault(); // Stop form submission
                alert("Please select both a person in charge and a school.");
            }
        });
    });
</script>


</body>
</html>
