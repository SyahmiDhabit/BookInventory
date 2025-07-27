<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['picName']) && !empty(trim($_POST['picName']))) {
        $picName = trim($_POST['picName']);

        $stmt = $conn->prepare("INSERT INTO personincharge (picName) VALUES (?)");
        $stmt->bind_param("s", $picName);
        $stmt->execute();
        $stmt->close();

        header("Location: newbookpicMelaka.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Person In Charge</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f1f2f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-box {
            width: 100%;
            max-width: 500px;
            padding: 30px;
            background-color: white;
            border: 2px solid #2d3436;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border-radius: 10px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #2d3436;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1em;
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            font-size: 1em;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .confirm {
            background-color: #00b894;
            color: white;
        }
        .confirm:hover {
            background-color: #55efc4;
        }
        .cancel {
            background-color: #d63031;
            color: white;
        }
        .cancel:hover {
            background-color: #ff7675;
        }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Create New Person In Charge</h2>
        <form action="createpicNameMelaka.php" method="post">
            <input type="text" name="picName" placeholder="Enter PIC Name" required />

            <div>
                <button type="submit" class="confirm">Confirm</button>
                <button type="button" class="cancel" onclick="window.location.href='newbookpicMelaka.php'">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
