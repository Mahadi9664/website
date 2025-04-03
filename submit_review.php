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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO reviewmetabase 
                          (UserID, RestaurantID, ReviewDate, Rating, ReviewText) 
                          VALUES (?, ?, NOW(), ?, ?)");
    $stmt->bind_param("iiis", 
        $_SESSION['user_id'],
        $_POST['restaurant_id'],
        $_POST['rating'],
        $_POST['review_text']
    );
    
    if ($stmt->execute()) {
        header("Location: user_home.php?success=1");
    } else {
        header("Location: write_review.php?error=1");
    }
    $stmt->close();
} else {
    header("Location: user_home.php");
}

$conn->close();
exit();
?>