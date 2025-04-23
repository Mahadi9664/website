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
$stmt = $conn->prepare("SELECT RestaurantID FROM restaurant WHERE AdminID = ? AND IsDeleted = 0");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$restaurant) {
    die("No restaurant found");
}

// Handle comment submission (this part is now handled by submit_comment.php)

// Get reviews
$reviews = $conn->query("
    SELECT r.*, u.Username FROM reviewmetabase r
    JOIN usercredentials u ON r.UserID = u.UserID
    WHERE r.RestaurantID = {$restaurant['RestaurantID']} AND r.IsDeleted = 0
    ORDER BY r.ReviewDate DESC
");

// Get all comments for these reviews
$comments = [];
if ($reviews->num_rows > 0) {
    $review_ids = array_column($reviews->fetch_all(MYSQLI_ASSOC), 'ReviewID');
    $reviews->data_seek(0); // Reset pointer

    $comment_result = $conn->query("
        SELECT c.*, u.Username FROM commentmetadata c
        JOIN usercredentials u ON c.UserID = u.UserID
        WHERE c.ReviewID IN (".implode(',', $review_ids).") AND c.IsDeleted = 0
    ");

    while ($comment = $comment_result->fetch_assoc()) {
        $comments[$comment['ReviewID']][] = $comment;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Restaurant Reviews</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .review { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .review-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .comment { background: #f5f5f5; padding: 10px; margin: 10px 0 10px 20px; border-radius: 5px; }
        .comment-form { margin-top: 15px; }
        textarea { width: 100%; padding: 8px; margin-bottom: 5px; }
        .rating { color: gold; font-weight: bold; }
        .review-actions {
            margin-top: 10px;
            display: flex;
            gap: 15px;
            align-items: center; /* Align items vertically in the center */
        }
        .like-count {
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Customer Reviews</h1>

    <?php if ($reviews->num_rows > 0): ?>
        <?php while($review = $reviews->fetch_assoc()): ?>
            <div class="review">
                <div class="review-header">
                    <h3><?= htmlspecialchars($review['Username']) ?></h3>
                    <div class="rating"><?= $review['Rating'] ?>/5</div>
                </div>
                <p><em><?= $review['ReviewDate'] ?></em></p>
                <p><?= htmlspecialchars($review['ReviewText']) ?></p>

                <div class="review-actions">
                    <?php
                    $like_count = $conn->query("SELECT COUNT(*) as count FROM likedby
                                                WHERE ReviewID = {$review['ReviewID']}
                                                AND IsDeleted = 0")->fetch_assoc()['count'];
                    ?>
                    <span class="like-count">♥ <?= $like_count ?> likes</span>
                </div>

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

                <form class="comment-form" method="POST" action="submit_comment.php">
                    <input type="hidden" name="review_id" value="<?= $review['ReviewID'] ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                    <textarea name="comment_text" placeholder="Write a response..." required></textarea>
                    <button type="submit">Post Comment</button>
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