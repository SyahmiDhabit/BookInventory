<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Interface - Gramixx</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f1f2f6;
        }

        .header {
            width: 100%;
            padding: 1.5em 0;
            background-color: #2d3436;
            color: #ffffff;
            text-align: center;
            font-size: 2em;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2em 1em;
            text-align: center;
        }

        h1 {
            color: #2d3436;
            font-size: 1.6em;
            margin-bottom: 1.5em;
        }

        .button-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1em;
            justify-items: center;
        }

        .btn {
            width: 100%;
            max-width: 220px;
            padding: 1em;
            background-color: #0984e3;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 500;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease, transform 0.2s;
        }

        .btn:hover {
            background-color: #74b9ff;
            transform: translateY(-2px);
        }

        /* Responsive font adjustments */
        @media (max-width: 768px) {
            .header {
                font-size: 1.6em;
            }

            h1 {
                font-size: 1.3em;
            }

            .btn {
                font-size: 0.95em;
                padding: 0.9em;
            }
        }

        @media (max-width: 480px) {
            .btn {
                font-size: 0.9em;
                padding: 0.8em;
            }
        }
    </style>
</head>
<body>

    <div class="header">
        Gramixx Book Inventory
    </div>

    <div class="container">
        <h1>Admin Panel</h1>

        <div class="button-grid">
            <a href="" class="btn">📄 List Report </a>
            <a href="" class="btn">🏫 List School Sort</a>
            <a href="adminstockbook.php" class="btn">📚 List Book</a>
            <a href="adminlogin.php" class="btn">🚪 Logout</a>
        </div>
    </div>

</body>
</html>
