<?php
session_start();
require_once 'connection.php';

// Check owner role and restaurant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Restaurant Owner') {
    header("Location: login.php");
    exit();
}

$restaurant = $conn->query("
    SELECT * FROM restaurant 
    WHERE AdminID = {$_SESSION['user_id']} AND IsDeleted = 0
")->fetch_assoc();

if (!$restaurant) {
    die("No restaurant found for this owner");
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $review_id = (int)$_POST['review_id'];
    $comment_text = trim($_POST['comment_text']);
    
    $stmt = $conn->prepare("INSERT INTO commentmetadata (ReviewID, UserID, CommentDate, CommentText) VALUES (?, ?, NOW(), ?)");
    $stmt->bind_param("iis", $review_id, $_SESSION['user_id'], $comment_text);
    $stmt->execute();
}

// Get reviews for this restaurant
$reviews = $conn->query("
    SELECT r.*, u.Username 
    FROM reviewmetabase r
    JOIN usercredentials u ON r.UserID = u.UserID
    WHERE r.RestaurantID = {$restaurant['RestaurantID']} AND r.IsDeleted = 0
    ORDER BY r.ReviewDate DESC
");

// Get comments for these reviews
$comments = [];
if ($reviews->num_rows > 0) {
    $review_ids = [];
    while ($row = $reviews->fetch_assoc()) {
        $review_ids[] = $row['ReviewID'];
    }
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
    <title>View Reviews</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .review {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .comment {
            background-color: #f5f5f5;
            padding: 10px;
            margin: 10px 0 10px 20px;
            border-radius: 5px;
        }
        .comment-form {
            margin-top: 15px;
        }
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 5px;
        }
        .rating {
            color: gold;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Customer Reviews for <?= htmlspecialchars($restaurant['Name']) ?></h1>
    
    <?php if ($reviews->num_rows > 0): ?>
        <?php while($review = $reviews->fetch_assoc()): ?>
            <div class="review">
                <div class="review-header">
                    <h3><?= htmlspecialchars($review['Username']) ?></h3>
                    <div class="rating">Rating: <?= $review['Rating'] ?>/5</div>
                </div>
                <p><em>Posted on <?= $review['ReviewDate'] ?></em></p>
                <p><?= htmlspecialchars($review['ReviewText']) ?></p>
                
                <!-- Comments Section -->
                <h4>Comments:</h4>
                <?php if (!empty($comments[$review['ReviewID']])): ?>
                    <?php foreach($comments[$review['ReviewID']] as $comment): ?>
                        <div class="comment">
                            <strong><?= htmlspecialchars($comment['Username']) ?></strong>
                            <small>(<?= $comment['CommentDate'] ?>)</small>
                            <p><?= htmlspecialchars($comment['CommentText']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No comments yet.</p>
                <?php endif; ?>
                
                <!-- Add Comment Form -->
                <form class="comment-form" method="POST">
                    <input type="hidden" name="review_id" value="<?= $review['ReviewID'] ?>">
                    <textarea name="comment_text" placeholder="Write a response..." required></textarea>
                    <button type="submit" name="add_comment">Post Comment</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No reviews yet for your restaurant.</p>
    <?php endif; ?>
    
    <p><a href="owner_dashboard.php">← Back to Dashboard</a></p>
</body>
</html>
<?php $conn->close(); ?>