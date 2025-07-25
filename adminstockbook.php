<!-- adminstockbook.php -->
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Pilih Negeri</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #e0eafc, #cfdef3);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 30px 20px;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        h2 {
            font-size: 26px;
            margin-bottom: 30px;
            color: #333;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }

        button {
            padding: 14px 24px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .state-btn {
            background-color: #007bff;
            color: white;
        }

        .state-btn:hover {
            background-color: #0056b3;
        }

        .back-btn {
            background-color: #6c757d;
            color: white;
        }

        .back-btn:hover {
            background-color: #5a6268;
        }

        @media (min-width: 600px) {
            button {
                font-size: 18px;
                padding: 16px;
            }

            h2 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Sila Pilih Negeri</h2>

        <form action="stockbook.php" method="GET" class="button-group">
            <button type="submit" name="negeri" value="Melaka" class="state-btn">Melaka</button>
            <button type="submit" name="negeri" value="Negeri Sembilan" class="state-btn">Negeri Sembilan</button>
        </form>

        <form action="admininterface.php" method="GET">
            <button type="submit" class="back-btn">← Kembali ke Admin Interface</button>
        </form>
    </div>
</body>
</html>
