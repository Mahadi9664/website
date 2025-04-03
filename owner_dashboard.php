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
<html>
<head>
    <title>Owner Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .dashboard-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .option-card {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .option-card h3 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h1>
        <a href="logout.php">Logout</a>
    </div>
    
    <h2>Managing: <?= htmlspecialchars($restaurant['Name']) ?></h2>
    
    <div class="dashboard-options">
        <div class="option-card" onclick="location.href='manage_menu.php'">
            <h3>🍽️ Manage Menu</h3>
            <p>Add, edit, or remove menu items</p>
        </div>
        <div class="option-card" onclick="location.href='manage_hours.php'">
            <h3>⏰ Update Hours</h3>
            <p>Set your restaurant's operating hours</p>
        </div>
        <div class="option-card" onclick="location.href='view_reviews.php'">
            <h3>⭐ View Reviews</h3>
            <p>See and respond to customer feedback</p>
        </div>
        <div class="option-card" onclick="location.href='restaurant_stats.php'">
            <h3>📊 Restaurant Stats</h3>
            <p>View performance analytics</p>
        </div>
    </div>
</body>
</html>
<?php 
$conn->close();
?>