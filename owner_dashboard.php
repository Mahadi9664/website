<?php
session_start();

// Check if user is logged in and is a restaurant owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Restaurant Owner') {
    header("Location: login.php");
    exit();
}

// Database connection
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "aamm";
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get owner's restaurant
$restaurant = $conn->query("
    SELECT * FROM restaurant
    WHERE AdminID = {$_SESSION['user_id']} AND IsDeleted = 0
")->fetch_assoc();

if (!$restaurant) {
    die("No restaurant found for this owner");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        header {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }

        main {
            padding: 2rem 0;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding-left: 20px;
            padding-right: 20px;
        }

        .dashboard-header h1 {
            color: #007bff;
            margin-bottom: 0;
        }

        .dashboard-header a {
            color: #dc3545;
            text-decoration: none;
            transition: color 0.15s ease-in-out;
        }

        .dashboard-header a:hover {
            color: #c82333;
            text-decoration: underline;
        }

        .dashboard-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .option-card {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .option-card h3 {
            margin-top: 0;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .option-card p {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        h2 {
            color: #343a40;
            margin-top: 2rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
        }

        footer {
            background-color: #343a40;
            color: #ffffff;
            padding: 1rem 0;
            text-align: center;
            margin-top: 3rem;
        }

        footer a {
            color: #f8f9fa;
            text-decoration: none;
            margin: 0 0.5rem;
            transition: color 0.15s ease-in-out;
        }

        footer a:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        .social-icon {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <header class="bg-white shadow-sm py-3 fixed-top">
        <nav class="navbar navbar-expand-lg container">
            <a class="navbar-brand fw-bold text-primary" href="./owner_dashboard.php">Koi Khabo</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-dark"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-danger ms-3" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container mt-5 pt-5">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt me-2 text-primary"></i> Owner Dashboard</h1>
        </div>

        <h2><i class="fas fa-store me-2 text-success"></i> Managing: <?= htmlspecialchars($restaurant['Name']) ?></h2>

        <div class="dashboard-options">
            <div class="option-card" onclick="location.href='manage_menu.php'">
                <h3><i class="fas fa-utensils me-2 text-warning"></i> Manage Menu</h3>
                <p>Add, edit, or remove menu items</p>
            </div>
            <div class="option-card" onclick="location.href='manage_hours.php'">
                <h3><i class="fas fa-clock me-2 text-info"></i> Update Hours</h3>
                <p>Set your restaurant's operating hours</p>
            </div>
            <div class="option-card" onclick="location.href='view_reviews.php'">
                <h3><i class="fas fa-star me-2 text-warning"></i> View Reviews</h3>
                <p>See and respond to customer feedback</p>
            </div>
            <div class="option-card" onclick="location.href='restaurant_stats.php'">
                <h3><i class="fas fa-chart-bar me-2 text-primary"></i> Restaurant Stats</h3>
                <p>View performance analytics</p>
            </div>
        </div>
    </main>

    <footer class="bg-dark py-3 mt-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 mb-md-0 ">
                    <a href="#" class="text-white text-decoration-none">About</a>
                    <a href="#" class="text-white text-decoration-none">Contact</a>
                    <a href="#" class="text-white text-decoration-none">Report</a>
                </div>
                <div class="w-100 d-flex justify-content-center justify-content-md-center order-md-2 mb-md-0 align-items-center">
                    <span class="small text-secondary">Koi Khabo Ltd. &copy; <?= date('Y') ?></span>
                </div>
                <div class="d-flex gap-3 order-md-3 align-items-center">
                    <a href="#" aria-label="Facebook" class="text-white"><i class="fab fa-facebook-f social-icon"></i></a>
                    <a href="#" aria-label="Twitter" class="text-white"><i class="fab fa-twitter social-icon"></i></a>
                    <a href="#" aria-label="Instagram" class="text-white"><i class="fab fa-instagram social-icon"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-DBjhlmWnKzOWskcqhxv5c5PDqnU2qzLwW1tZqK7ujn9t0o5jvwDan6O8KEdkM9Eeg"
            crossorigin="anonymous"></script>
</body>
</html>
<?php
$conn->close();
?>