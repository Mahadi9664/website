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

if (!isset($_GET['id'])) {
    header("Location: read_reviews.php");
    exit();
}

$restaurant_id = (int)$_GET['id'];

// Get restaurant details
$restaurant = $conn->query("
    SELECT r.*, AVG(rm.Rating) as AvgRating 
    FROM restaurant r
    LEFT JOIN reviewmetabase rm ON r.RestaurantID = rm.RestaurantID AND rm.IsDeleted = 0
    WHERE r.RestaurantID = $restaurant_id AND r.IsDeleted = 0
")->fetch_assoc();

if (!$restaurant) {
    header("Location: read_reviews.php");
    exit();
}

// Get food menu - Fixed column name to CuisineID
$foods = $conn->query("
    SELECT f.*, c.Name as CuisineName 
    FROM food f
    JOIN cuisine c ON f.CuisineID = c.CuisineID
    WHERE f.RestaurantID = $restaurant_id AND f.IsDeleted = 0
    ORDER BY f.Name
");

// Get reviews
$reviews = $conn->query("
    SELECT rm.*, u.Username 
    FROM reviewmetabase rm
    JOIN usercredentials u ON rm.UserID = u.UserID
    WHERE rm.RestaurantID = $restaurant_id AND rm.IsDeleted = 0
    ORDER BY rm.ReviewDate DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($restaurant['Name']) ?> - Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .restaurant-header {
            margin-bottom: 30px;
        }
        .food-item {
            margin: 10px 0;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .review {
            margin: 15px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
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
    <div class="restaurant-header">
        <h1><?= htmlspecialchars($restaurant['Name']) ?></h1>
        <p><strong>Location:</strong> <?= htmlspecialchars($restaurant['Location']) ?></p>
        <p><strong>Average Rating:</strong> <?= number_format($restaurant['AvgRating'] ?? 0, 1) ?>/5</p>
        <p><strong>Cuisine Type:</strong> <?= htmlspecialchars($restaurant['CuisineType']) ?></p>
    </div>

    <h2>Menu</h2>
    <?php if ($foods->num_rows > 0): ?>
        <?php while($food = $foods->fetch_assoc()): ?>
            <div class="food-item">
                <h3><?= htmlspecialchars($food['Name']) ?></h3>
                <p><?= htmlspecialchars($food['Description']) ?></p>
                <p><strong>Price:</strong> $<?= number_format($food['Price'], 2) ?></p>
                <p><strong>Cuisine:</strong> <?= htmlspecialchars($food['CuisineName']) ?></p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No food items listed for this restaurant.</p>
    <?php endif; ?>

    <h2>Reviews</h2>
    <?php if ($reviews->num_rows > 0): ?>
        <?php while($review = $reviews->fetch_assoc()): ?>
            <div class="review">
                <p><strong><?= htmlspecialchars($review['Username']) ?></strong> 
                rated <?= $review['Rating'] ?>/5 on <?= $review['ReviewDate'] ?></p>
                <p><?= htmlspecialchars($review['ReviewText']) ?></p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No reviews yet for this restaurant.</p>
    <?php endif; ?>

    <a href="read_reviews.php" class="back-link">← Back to Restaurants</a>
</body>
</html>
<?php
$conn->close();
?>