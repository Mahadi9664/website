<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Restaurant Owner') {
    header("Location: login.php");
    exit();
}

// Create database connection
$conn = new mysqli("localhost", "root", "", "aamm");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get restaurant info
$stmt = $conn->prepare("SELECT Name FROM restaurant WHERE AdminID = ? AND IsDeleted = 0");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$restaurant) {
    die("No restaurant found");
}

// Get statistics
$stats = $conn->query("
    SELECT
        COUNT(DISTINCT r.ReviewID) as total_reviews,
        AVG(r.Rating) as avg_rating,
        COUNT(DISTINCT f.FoodID) as menu_items,
        COUNT(DISTINCT c.CommentID) as total_comments,
        MIN(r.ReviewDate) as first_review,
        MAX(r.ReviewDate) as last_review
    FROM restaurant res
    LEFT JOIN reviewmetabase r ON res.RestaurantID = r.RestaurantID AND r.IsDeleted = 0
    LEFT JOIN food f ON res.RestaurantID = f.RestaurantID AND f.IsDeleted = 0
    LEFT JOIN commentmetadata c ON r.ReviewID = c.ReviewID AND c.IsDeleted = 0
    WHERE res.RestaurantID = (
        SELECT RestaurantID FROM restaurant WHERE AdminID = {$_SESSION['user_id']} AND IsDeleted = 0
    )
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Statistics</title>
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

        h1 {
            color: #007bff;
            margin-bottom: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #fff;
            padding: 20px;
            text-align: center;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
            color: #28a745;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        h3 {
            color: #343a40;
            margin-top: 2rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
        }

        p {
            color: #6c757d;
            margin-bottom: 0.75rem;
        }

        p a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.15s ease-in-out;
        }

        p a:hover {
            color: #0056b3;
            text-decoration: underline;
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
        <h1><?= htmlspecialchars($restaurant['Name']) ?> Statistics</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_reviews'] ?? 0 ?></div>
                <div class="stat-label">Total Reviews</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['menu_items'] ?? 0 ?></div>
                <div class="stat-label">Menu Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_comments'] ?? 0 ?></div>
                <div class="stat-label">Comments</div>
            </div>
        </div>

        <div>
            <h3>Review Timeline</h3>
            <p>First Review: <?= $stats['first_review'] ?? 'N/A' ?></p>
            <p>Last Review: <?= $stats['last_review'] ?? 'N/A' ?></p>
        </div>

        <p><a href="owner_dashboard.php">← Back to Dashboard</a></p>
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
<?php $conn->close(); ?>