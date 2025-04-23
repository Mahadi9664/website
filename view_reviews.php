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

// Get photos for the reviews
$review_photos = [];
if ($reviews->num_rows > 0) {
    $review_ids = [];
    while ($row = $reviews->fetch_assoc()) {
        $review_ids[] = $row['ReviewID'];
        $reviews_data[] = $row; // Store review data for later use
    }
    $reviews->data_seek(0); // Reset pointer (though we now have $reviews_data)

    if (!empty($review_ids)) {
        $placeholders = implode(',', array_fill(0, count($review_ids), '?'));
        $photo_stmt = $conn->prepare("SELECT ReviewID, FilePath FROM review_photos WHERE ReviewID IN ($placeholders) AND IsDeleted = 0");
        if ($photo_stmt) {
            $photo_stmt->bind_param(str_repeat('i', count($review_ids)), ...$review_ids);
            $photo_stmt->execute();
            $photo_result = $photo_stmt->get_result();
            while ($photo = $photo_result->fetch_assoc()) {
                $review_photos[$photo['ReviewID']][] = $photo['FilePath'];
            }
            $photo_stmt->close();
        } else {
            error_log("Error preparing photo statement: " . $conn->error);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Reviews</title>
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

        .review {
            border: 1px solid #e9ecef;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
        }

        .review-header h3 {
            color: #212529;
            margin-bottom: 0;
        }

        .rating {
            color: #ffc107;
            font-weight: bold;
        }

        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .review-text {
            margin-bottom: 10px;
        }

        .review-images-container {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            overflow-x: auto;
        }

        .review-image {
            max-width: 150px;
            height: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .review-actions {
            margin-top: 10px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .like-count {
            color: #6c757d;
        }

        .comment-section {
            margin-top: 15px;
            border-top: 1px solid #e9ecef;
            padding-top: 15px;
        }

        .comment {
            background-color: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .comment strong {
            color: #212529;
        }

        .comment small {
            color: #6c757d;
        }

        .comment p {
            margin-bottom: 0;
        }

        .comment-form {
            margin-top: 15px;
        }

        .comment-form textarea {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            min-height: 60px;
            font-family: inherit;
            resize: vertical;
        }

        .comment-form button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.15s ease-in-out;
        }

        .comment-form button:hover {
            background-color: #0056b3;
        }

        h1 {
            color: #007bff;
            margin-bottom: 1.5rem;
        }

        h4 {
            margin-top: 1rem;
            color: #343a40;
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
        <h1>Customer Reviews</h1>

        <?php if ($reviews->num_rows > 0): ?>
            <?php while($review = $reviews->fetch_assoc()): ?>
                <div class="review">
                    <div class="review-header">
                        <h3><?= htmlspecialchars($review['Username']) ?></h3>
                        <div class="rating"><?= $review['Rating'] ?>/5</div>
                    </div>
                    <p class="review-date"><em><?= $review['ReviewDate'] ?></em></p>
                    <p class="review-text"><?= htmlspecialchars($review['ReviewText']) ?></p>

                    <?php if (isset($review_photos[$review['ReviewID']]) && !empty($review_photos[$review['ReviewID']])): ?>
                        <h4>Photos:</h4>
                        <div class="review-images-container">
                            <?php foreach ($review_photos[$review['ReviewID']] as $photoPath): ?>
                                <img src="<?= htmlspecialchars($photoPath) ?>" alt="Review Photo" class="review-image">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="review-actions">
                        <?php
                        $like_count = $conn->query("SELECT COUNT(*) as count FROM likedby
                                                        WHERE ReviewID = {$review['ReviewID']}
                                                          AND IsDeleted = 0")->fetch_assoc()['count'];
                        ?>
                        <span class="like-count">♥ <?= $like_count ?> likes</span>
                    </div>

                    <div class="comment-section">
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
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet for your restaurant.</p>
        <?php endif; ?>

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