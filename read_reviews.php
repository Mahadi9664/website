<?php
session_start();

// Database connection
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "aamm";
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Database connection failed");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all restaurants - Simplified query
$restaurants = $conn->query("
    SELECT RestaurantID, Name, Location, CuisineType 
    FROM restaurant 
    WHERE IsDeleted = 0
    ORDER BY Name
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Browse Restaurants</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .restaurant-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .restaurant-card {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }
        .restaurant-card:hover {
            background-color: #f5f5f5;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #f0f0f0;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Browse Restaurants</h1>
    
    <div class="restaurant-list">
        <?php if ($restaurants->num_rows > 0): ?>
            <?php while($restaurant = $restaurants->fetch_assoc()): ?>
                <div class="restaurant-card" onclick="window.location='restaurant_details.php?id=<?= $restaurant['RestaurantID'] ?>'">
                    <h2><?= htmlspecialchars($restaurant['Name']) ?></h2>
                    <p><strong>Location:</strong> <?= htmlspecialchars($restaurant['Location']) ?></p>
                    <p><strong>Cuisine:</strong> <?= htmlspecialchars($restaurant['CuisineType']) ?></p>
                    <p>Click to view details →</p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No restaurants found.</p>
        <?php endif; ?>
    </div>
    
    <a href="user_home.php" class="back-link">← Back to Home</a>
</body>
</html>
<?php
$conn->close();
?>