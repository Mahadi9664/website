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

// Get user profile data
$user_id = $_SESSION['user_id'];
$profile_query = "
    SELECT uc.Username, up.FullName, up.Email, up.ProfilePictureURL, r.RoleName
    FROM usercredentials uc
    JOIN userprofile up ON uc.UserID = up.UserID
    JOIN userroles ur ON uc.UserID = ur.UserID
    JOIN roles r ON ur.RoleID = r.RoleID
    WHERE uc.UserID = $user_id AND uc.IsDeleted = 0
";

$profile_result = $conn->query($profile_query);
$profile = $profile_result->fetch_assoc();

// Get user's recent reviews
$reviews_query = "
    SELECT r.ReviewID, r.RestaurantID, res.Name as RestaurantName,
           r.Rating, r.ReviewText, r.ReviewDate
    FROM reviewmetabase r
    JOIN restaurant res ON r.RestaurantID = res.RestaurantID
    WHERE r.UserID = $user_id AND r.IsDeleted = 0
    ORDER BY r.ReviewDate DESC
    LIMIT 5
";

$reviews_result = $conn->query($reviews_query);
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$reviews_result->free();

// Get photos for the user's recent reviews
$review_photos = [];
if (!empty($reviews)) {
    $review_ids = array_column($reviews, 'ReviewID');
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
            max-width: 1000px;
            margin: 0 auto;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 3px solid #007bff;
        }
        .profile-info h1 {
            margin: 0 0 10px 0;
        }
        .profile-meta {
            color: #666;
            margin-bottom: 5px;
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
        .review-card {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .review-rating {
            color: #ffc107;
            font-weight: bold;
        }
        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .default-pic {
            font-size: 70px;
            text-align: center;
            line-height: 150px;
            background-color: #f0f0f0;
            color: #999;
        }
        .review-images-container {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            overflow-x: auto; /* Allow horizontal scrolling for many images */
        }
        .review-image {
            max-width: 100px;
            height: auto;
            border: 1px solid #eee;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="profile-header">
        <?php if (!empty($profile['ProfilePictureURL'])): ?>
            <img src="<?= htmlspecialchars($profile['ProfilePictureURL']) ?>" alt="Profile Picture" class="profile-pic">
        <?php else: ?>
            <div class="profile-pic default-pic">👤</div>
        <?php endif; ?>

        <div class="profile-info">
            <h1><?= htmlspecialchars($profile['FullName']) ?></h1>
            <div class="profile-meta"><strong>Username:</strong> <?= htmlspecialchars($profile['Username']) ?></div>
            <div class="profile-meta"><strong>Email:</strong> <?= htmlspecialchars($profile['Email']) ?></div>
            <div class="profile-meta"><strong>Account Type:</strong> <?= htmlspecialchars($profile['RoleName']) ?></div>

            <div style="margin-top: 15px;">
                <a href="edit_profile.php" class="btn">Edit Profile</a>
                <a href="change_password.php" class="btn">Change Password</a>
            </div>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">My Recent Reviews</h2>

        <?php if (!empty($reviews)): ?>
            <?php foreach($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div>
                            <strong><?= htmlspecialchars($review['RestaurantName']) ?></strong>
                            <span class="review-rating"><?= $review['Rating'] ?> ★</span>
                        </div>
                        <div class="review-date"><?= $review['ReviewDate'] ?></div>
                    </div>
                    <p><?= htmlspecialchars($review['ReviewText']) ?></p>

                    <?php if (isset($review_photos[$review['ReviewID']]) && !empty($review_photos[$review['ReviewID']])): ?>
                        <div class="review-images-container">
                            <?php foreach ($review_photos[$review['ReviewID']] as $photoPath): ?>
                                <img src="<?= htmlspecialchars($photoPath) ?>" alt="Review Photo" class="review-image">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <a href="restaurant_details.php?id=<?= $review['RestaurantID'] ?>">View Restaurant</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You haven't written any reviews yet.</p>
            <a href="restaurants.php" class="btn">Browse Restaurants</a>
        <?php endif; ?>
    </div>

    <a href="user_home.php" class="btn">← Back to Home</a>
</body>
</html>
<?php
$conn->close();
?>