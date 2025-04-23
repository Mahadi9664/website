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

// Handle like action (this should only update the database and echo a response)
if (isset($_GET['like_review'])) {
    $review_id = (int)$_GET['like_review'];
    $user_id = $_SESSION['user_id'];

    // Check if already liked
    $check_like_stmt = $conn->prepare("SELECT LikeID FROM likedby WHERE ReviewID = ? AND UserID = ? AND IsDeleted = 0");
    $check_like_stmt->bind_param("ii", $review_id, $user_id);
    $check_like_stmt->execute();
    $check_like_result = $check_like_stmt->get_result();

    if ($check_like_result->num_rows > 0) {
        // Unlike
        $unlike_stmt = $conn->prepare("UPDATE likedby SET IsDeleted = 1 WHERE ReviewID = ? AND UserID = ?");
        $unlike_stmt->bind_param("ii", $review_id, $user_id);
        $unlike_stmt->execute();
        $unlike_stmt->close();
    } else {
        // Like
        $like_stmt = $conn->prepare("INSERT INTO likedby (ReviewID, UserID, LikeDate) VALUES (?, ?, NOW())");
        $like_stmt->bind_param("ii", $review_id, $user_id);
        $like_stmt->execute();
        $like_stmt->close();
    }
    $check_like_stmt->close();

    // Echo a success message or any data needed by the AJAX request
    echo 'success';
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $stmt = $conn->prepare("INSERT INTO commentmetadata (ReviewID, UserID, CommentText) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iis", $_POST['review_id'], $_SESSION['user_id'], $_POST['comment_text']);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect to prevent form resubmission
    header("Location: restaurant_details.php?id=" . $restaurant_id);
    exit();
}

// Get restaurant details
$stmt = $conn->prepare("
    SELECT r.*, AVG(rm.Rating) as AvgRating
    FROM restaurant r
    LEFT JOIN reviewmetabase rm ON r.RestaurantID = rm.RestaurantID AND rm.IsDeleted = 0
    WHERE r.RestaurantID = ? AND r.IsDeleted = 0
");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$restaurant) {
    header("Location: restaurants.php");
    exit();
}

// Get menu items
$stmt = $conn->prepare("
    SELECT f.*, c.Name as CuisineName
    FROM food f
    JOIN cuisine c ON f.CuisineID = c.CuisineID
    WHERE f.RestaurantID = ? AND f.IsDeleted = 0
    ORDER BY f.Name
");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$menu = $stmt->get_result();
$stmt->close();

// Get reviews with comments and like counts
$stmt = $conn->prepare("
    SELECT rm.*, u.Username,
           (SELECT COUNT(lb.LikeID) FROM likedby lb WHERE lb.ReviewID = rm.ReviewID AND lb.IsDeleted = 0) as LikeCount,
           (SELECT COUNT(lb.LikeID) FROM likedby lb WHERE lb.ReviewID = rm.ReviewID AND lb.UserID = ? AND lb.IsDeleted = 0) as UserLiked
    FROM reviewmetabase rm
    JOIN usercredentials u ON rm.UserID = u.UserID
    WHERE rm.RestaurantID = ? AND rm.IsDeleted = 0
    ORDER BY rm.ReviewDate DESC
");
$stmt->bind_param("ii", $_SESSION['user_id'], $restaurant_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$reviews_result->free();
$stmt->close();

// Get all comments for these reviews
$comments = [];
if (!empty($reviews)) {
    $review_ids = array_column($reviews, 'ReviewID');
    $placeholders = implode(',', array_fill(0, count($review_ids), '?'));
    $types = str_repeat('i', count($review_ids));
    $comment_stmt = $conn->prepare("
        SELECT c.*, u.Username
        FROM commentmetadata c
        JOIN usercredentials u ON c.UserID = u.UserID
        WHERE c.ReviewID IN ($placeholders) AND c.IsDeleted = 0
        ORDER BY c.CommentDate
    ");
    $comment_stmt->bind_param($types, ...$review_ids);
    $comment_stmt->execute();
    $comment_result = $comment_stmt->get_result();
    while ($comment = $comment_result->fetch_assoc()) {
        $comments[$comment['ReviewID']][] = $comment;
    }
    $comment_stmt->close();
    $comment_result->free();
}

// Get photos for the reviews
$review_photos = [];
if (!empty($reviews)) {
    $review_ids = array_column($reviews, 'ReviewID');
    $placeholders = implode(',', array_fill(0, count($review_ids), '?'));
    $types = str_repeat('i', count($review_ids));
    $photo_stmt = $conn->prepare("SELECT ReviewID, FilePath FROM review_photos WHERE ReviewID IN ($placeholders) AND IsDeleted = 0");
    if ($photo_stmt) {
        $photo_stmt->bind_param($types, ...$review_ids);
        $photo_stmt->execute();
        $photo_result = $photo_stmt->get_result();
        while ($photo = $photo_result->fetch_assoc()) {
            $review_photos[$photo['ReviewID']][] = $photo['FilePath'];
        }
        $photo_stmt->close();
        $photo_result->free();
    } else {
        error_log("Error preparing photo statement: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['Name']) ?> - Details</title>
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
            margin-top: 80px; /* Adjust based on header height */
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .restaurant-header {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .restaurant-name {
            font-size: 2.5rem;
            color: #007bff;
            margin-bottom: 0.5rem;
        }

        .restaurant-meta p {
            color: #6c757d;
            margin-bottom: 0.2rem;
        }

        .rating {
            color: #ffc107;
            font-size: 1.2rem;
        }

        .section-title {
            font-size: 2rem;
            color: #343a40;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .menu-item {
            background-color: #fff;
            padding: 15px;
            margin-bottom: 1rem;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }

        .menu-item-name {
            font-weight: bold;
            color: #212529;
            margin-bottom: 0.3rem;
        }

        .menu-item-price {
            color: #28a745;
            font-weight: bold;
        }

        .review {
            background-color: #fff;
            padding: 15px;
            margin-bottom: 1.5rem;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
        }

        .review-author {
            font-weight: bold;
            color: #17a2b8;
        }

        .review-rating {
            color: #ffc107;
        }

        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .comment {
            background-color: #f8f9fa;
            padding: 10px;
            margin: 0.5rem 0 0.5rem 20px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }

        .comment-form {
            margin-top: 1rem;
            padding: 15px;
            background-color: #fff;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }

        .comment-form textarea {
            width: 100%;
            padding: 0.7rem;
            margin-bottom: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            min-height: 80px;
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #007bff;
            text-decoration: none;
            transition: color 0.15s ease-in-out;
        }

        .back-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        footer {
            background-color: #343a40;
            color: #ffffff;
            padding: 1rem 0;
            text-align: center;
            position: fixed;
            bottom: 0;
            width: 100%;
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

        .review-actions {
            margin-top: 10px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .like-btn {
            color: #6c757d;
            text-decoration: none;
            transition: color 0.15s ease-in-out;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .like-btn:hover {
            color: #007bff;
            text-decoration: underline;
        }

        .like-btn.liked {
            color: #dc3545; /* Red color for liked */
        }

        .like-count {
            margin-left: 5px;
            font-size: 0.9rem;
        }

        .review-images-container {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            overflow-x: auto; /* Allow horizontal scrolling for many images */
        }

        .review-image {
            max-width: 150px;
            height: auto;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
<header class="bg-white shadow-sm py-3 fixed-top">
    <nav class="navbar navbar-expand-lg container">
        <a class="navbar-brand fw-bold text-primary" href="./user_home.php">Koi Khabo</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link btn btn-outline-info me-3" href="restaurants.php">Go Back</a>
                </li>
            </ul>
        </div>
    </nav>
</header>

<main class="container">
    <div class="restaurant-header">
        <h1 class="restaurant-name"><?= htmlspecialchars($restaurant['Name']) ?></h1>
        <div class="restaurant-meta">
            <p><i class="fas fa-map-marker-alt text-secondary me-2"></i><?= htmlspecialchars($restaurant['Location']) ?></p>
            <p><i class="fas fa-utensils text-secondary me-2"></i><?= htmlspecialchars($restaurant['CuisineType']) ?></p>
            <p><i class="fas fa-clock text-secondary me-2"></i><?= htmlspecialchars($restaurant['OpeningHours']) ?></p>
        </div>
        <div class="rating">
            <?= str_repeat('★', round($restaurant['AvgRating'])) ?>
            <?= str_repeat('☆', 5 - round($restaurant['AvgRating'])) ?>
            <span class="ms-2 text-muted">(<?= number_format($restaurant['AvgRating'], 1) ?> average rating)</span>
        </div>
    </div>

    <section class="section">
        <h2 class="section-title">Menu</h2>
        <?php if ($menu->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php while ($item = $menu->fetch_assoc()): ?>
                    <div class="col">
                        <div class="menu-item">
                            <h5 class="menu-item-name"><?= htmlspecialchars($item['Name']) ?></h5>
                            <p class="menu-item-price">$<?= number_format($item['Price'], 2) ?></p>
                            <p class="text-muted"><?= htmlspecialchars($item['Description']) ?></p>
                            <div><small class="text-info">Cuisine: <?= htmlspecialchars($item['CuisineName']) ?></small></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No menu items listed for this restaurant.</p>
        <?php endif; ?>
    </section>

    <section class="section">
        <h2 class="section-title">Reviews</h2>
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review">
                    <div class="review-header">
                        <div class="review-author"><i class="fas fa-user me-2 text-primary"></i><?= htmlspecialchars($review['Username']) ?></div>
                        <div>
                            <span class="review-rating text-warning"><?= str_repeat('★', $review['Rating']) ?><?= str_repeat('☆', 5 - $review['Rating']) ?></span>
                            <span class="review-date text-muted ms-2"><?= date("F j, Y", strtotime($review['ReviewDate'])) ?></span>
                        </div>
                    </div>
                    <p class="mt-2"><?= htmlspecialchars($review['ReviewText']) ?></p>

                    <?php if (!empty($review_photos[$review['ReviewID']])): ?>
                        <div class="review-images-container">
                            <?php foreach ($review_photos[$review['ReviewID']] as $photo): ?>
                                <img src="<?= htmlspecialchars($photo) ?>" alt="Review Image" class="review-image">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="review-actions mt-3">
                        <a href="?id=<?= $restaurant_id ?>&like_review=<?= $review['ReviewID'] ?>" class="like-btn <?= $review['UserLiked'] > 0 ? 'liked' : '' ?>">
                            <i class="fas fa-heart me-1"></i> <span class="like-count"><?= $review['LikeCount'] ?></span>
                        </a>
                    </div>

                    <h4 class="mt-3">Comments</h4>
                    <?php if (!empty($comments[$review['ReviewID']])): ?>
                        <?php foreach ($comments[$review['ReviewID']] as $comment): ?>
                            <div class="comment">
                                <div class="review-header">
                                    <div class="review-author"><i class="fas fa-comment-dots me-2 text-info"></i><?= htmlspecialchars($comment['Username']) ?></div>
                                    <div class="review-date text-muted"><?= date("F j, Y", strtotime($comment['CommentDate'])) ?></div>
                                </div>
                                <p><?= htmlspecialchars($comment['CommentText']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No comments yet.</p>
                    <?php endif; ?>

                    <form class="comment-form mt-3" method="POST">
                        <input type="hidden" name="review_id" value="<?= $review['ReviewID'] ?>">
                        <textarea name="comment_text" class="form-control" placeholder="Write a comment..." required></textarea>
                        <button type="submit" name="add_comment" class="btn btn-primary mt-2">Post Comment</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">No reviews yet for this restaurant.</p>
        <?php endif; ?>
    </section>

    <a href="restaurants.php" class="back-link"><i class="fas fa-arrow-left me-2"></i> Back to Restaurants</a>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const likeButtons = document.querySelectorAll('.like-btn');
        likeButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const href = this.getAttribute('href');
                const likeCountSpan = this.querySelector('.like-count');
                const heartIcon = this.querySelector('.fa-heart');

                fetch(href)
                    .then(response => response.text())
                    .then(data => {
                        if (data === 'success') {
                            const currentLikes = parseInt(likeCountSpan.textContent);
                            if (this.classList.contains('liked')) {
                                likeCountSpan.textContent = currentLikes - 1;
                                this.classList.remove('liked');
                            } else {
                                likeCountSpan.textContent = currentLikes + 1;
                                this.classList.add('liked');
                            }
                        } else {
                            alert('Error liking/unliking review.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred.');
                    });
            });
        });
    });
</script>

</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>