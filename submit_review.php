<?php
session_start();

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection with error handling
$conn = new mysqli("localhost", "root", "", "aamm");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Validate session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: user_home.php");
    exit();
}

// Validate and sanitize inputs
$user_id = (int)$_SESSION['user_id'];
$restaurant_id = (int)$_POST['restaurant_id'];
$rating = (int)$_POST['rating'];
$review_text = $conn->real_escape_string(trim($_POST['review_text']));

// Validate rating range
if ($rating < 1 || $rating > 5) {
    header("Location: write_review.php?error=invalid_rating");
    exit();
}

// Validate review text
if (empty($review_text)) {
    header("Location: write_review.php?error=empty_review");
    exit();
}

// TEMPORARY SOLUTION: Disable trigger for this session
$conn->query("SET @DISABLE_TRIGGERS = TRUE");

// Insert review using parameterized query
$sql = "INSERT INTO reviewmetabase 
        (UserID, RestaurantID, ReviewDate, Rating, ReviewText, IsDeleted) 
        VALUES (?, ?, NOW(), ?, ?, 0)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    header("Location: write_review.php?error=database");
    exit();
}

$stmt->bind_param("iiis", $user_id, $restaurant_id, $rating, $review_text);

if ($stmt->execute()) {
    // Re-enable triggers
    $conn->query("SET @DISABLE_TRIGGERS = FALSE");
    header("Location: user_home.php?success=1");
} else {
    error_log("Execute failed: " . $stmt->error);
    header("Location: write_review.php?error=database");
}

$stmt->close();
$conn->close();
exit();
?>