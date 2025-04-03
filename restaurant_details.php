<?php
session_start();

// Database connection
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "aamm";
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: restaurants.php");
    exit();
}

$restaurant_id = (int)$_GET['id'];

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $stmt = $conn->prepare("INSERT INTO commentmetadata (ReviewID, UserID, CommentText) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $_POST['review_id'], $_SESSION['user_id'], $_POST['comment_text']);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: restaurant_details.php?id=".$restaurant_id);
    exit();
}

// Get restaurant details
$restaurant = $conn->query("
    SELECT r.*, AVG(rm.Rating) as AvgRating 
    FROM restaurant r
    LEFT JOIN reviewmetabase rm ON r.RestaurantID = rm.RestaurantID AND rm.IsDeleted = 0
    WHERE r.RestaurantID = $restaurant_id AND r.IsDeleted = 0
")->fetch_assoc();

if (!$restaurant) {
    header("Location: restaurants.php");
    exit();
}

// Get menu items
$menu = $conn->query("
    SELECT f.*, c.Name as CuisineName 
    FROM food f
    JOIN cuisine c ON f.CuisineID = c.CuisineID
    WHERE f.RestaurantID = $restaurant_id AND f.IsDeleted = 0
    ORDER BY f.Name
");

// Get reviews with comments
$reviews = $conn->query("
    SELECT rm.*, u.Username 
    FROM reviewmetabase rm
    JOIN usercredentials u ON rm.UserID = u.UserID
    WHERE rm.RestaurantID = $restaurant_id AND rm.IsDeleted = 0
    ORDER BY rm.ReviewDate DESC
");

// Get all comments for these reviews
$comments = [];
if ($reviews->num_rows > 0) {
    $review_ids = array_column($reviews->fetch_all(MYSQLI_ASSOC), 'ReviewID');
    $reviews->data_seek(0); // Reset pointer
    
    $comment_result = $conn->query("
        SELECT c.*, u.Username 
        FROM commentmetadata c
        JOIN usercredentials u ON c.UserID = u.UserID
        WHERE c.ReviewID IN (".implode(',', $review_ids).") AND c.IsDeleted = 0
        ORDER BY c.CommentDate
    ");
    
    while ($comment = $comment_result->fetch_assoc()) {
        $comments[$comment['ReviewID']][] = $comment;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($restaurant['Name']) ?> - Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .restaurant-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .restaurant-name {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .restaurant-meta {
            color: #6c757d;
            margin-bottom: 10px;
        }
        .rating {
            color: #ffc107;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .menu-item {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .menu-item-name {
            font-weight: bold;
            font-size: 1.1rem;
        }
        .menu-item-price {
            color: #28a745;
            font-weight: bold;
        }
        .review {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .review-author {
            font-weight: bold;
        }
        .review-rating {
            color: #ffc107;
            font-weight: bold;
        }
        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .comment {
            background-color: #f8f9fa;
            padding: 10px;
            margin: 10px 0 10px 20px;
            border-radius: 5px;
        }
        .comment-form {
            margin-top: 15px;
        }
        .comment-form textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            min-height: 80px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 16px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="restaurant-header">
        <h1 class="restaurant-name"><?= htmlspecialchars($restaurant['Name']) ?></h1>
        <div class="restaurant-meta">
            <p><strong>Location:</strong> <?= htmlspecialchars($restaurant['Location']) ?></p>
            <p><strong>Cuisine Type:</strong> <?= htmlspecialchars($restaurant['CuisineType']) ?></p>
            <p><strong>Hours:</strong> <?= htmlspecialchars($restaurant['OpeningHours']) ?></p>
        </div>
        <div class="rating">
            <?= str_repeat('★', round($restaurant['AvgRating'])) ?>
            <?= str_repeat('☆', 5 - round($restaurant['AvgRating'])) ?>
            (<?= number_format($restaurant['AvgRating'], 1) ?> average rating)
        </div>
    </div>
    
    <div class="section">
        <h2 class="section-title">Menu</h2>
        <?php if ($menu->num_rows > 0): ?>
            <?php while($item = $menu->fetch_assoc()): ?>
                <div class="menu-item">
                    <div class="menu-item-name"><?= htmlspecialchars($item['Name']) ?></div>
                    <div class="menu-item-price">$<?= number_format($item['Price'], 2) ?></div>
                    <p><?= htmlspecialchars($item['Description']) ?></p>
                    <div><small>Cuisine: <?= htmlspecialchars($item['CuisineName']) ?></small></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No menu items listed for this restaurant.</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2 class="section-title">Reviews</h2>
        <?php if ($reviews->num_rows > 0): ?>
            <?php while($review = $reviews->fetch_assoc()): ?>
                <div class="review">
                    <div class="review-header">
                        <div class="review-author"><?= htmlspecialchars($review['Username']) ?></div>
                        <div>
                            <span class="review-rating"><?= $review['Rating'] ?>★</span>
                            <span class="review-date"><?= $review['ReviewDate'] ?></span>
                        </div>
                    </div>
                    <p><?= htmlspecialchars($review['ReviewText']) ?></p>
                    
                    <!-- Comments Section -->
                    <h4>Comments</h4>
                    <?php if (!empty($comments[$review['ReviewID']])): ?>
                        <?php foreach($comments[$review['ReviewID']] as $comment): ?>
                            <div class="comment">
                                <div class="review-header">
                                    <div class="review-author"><?= htmlspecialchars($comment['Username']) ?></div>
                                    <div class="review-date"><?= $comment['CommentDate'] ?></div>
                                </div>
                                <p><?= htmlspecialchars($comment['CommentText']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No comments yet.</p>
                    <?php endif; ?>
                    
                    <!-- Comment Form -->
                    <form class="comment-form" method="POST">
                        <input type="hidden" name="review_id" value="<?= $review['ReviewID'] ?>">
                        <textarea name="comment_text" placeholder="Write a comment..." required></textarea>
                        <button type="submit" name="add_comment">Post Comment</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet for this restaurant.</p>
        <?php endif; ?>
    </div>
    
    <a href="restaurants.php" class="back-link">← Back to Restaurants</a>
</body>
</html>
<?php
$conn->close();
?>