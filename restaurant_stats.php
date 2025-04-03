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
<html>
<head>
    <title>Restaurant Statistics</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat-card { border: 1px solid #ddd; padding: 20px; text-align: center; border-radius: 8px; }
        .stat-value { font-size: 2.5em; font-weight: bold; margin: 10px 0; }
        .stat-label { color: #666; }
    </style>
</head>
<body>
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
    
    <div style="margin-top: 30px;">
        <h3>Review Timeline</h3>
        <p>First Review: <?= $stats['first_review'] ?? 'N/A' ?></p>
        <p>Last Review: <?= $stats['last_review'] ?? 'N/A' ?></p>
    </div>
    
    <p><a href="owner_dashboard.php">← Back to Dashboard</a></p>
</body>
</html>
<?php $conn->close(); ?>