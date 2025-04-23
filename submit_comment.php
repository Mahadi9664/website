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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
    $review_id = (int)$_POST['review_id'];
    $user_id = $_SESSION['user_id'];
    $comment_text = trim($conn->real_escape_string($_POST['comment_text']));
    
    if (!empty($comment_text)) {
        $stmt = $conn->prepare("INSERT INTO commentmetadata 
                               (ReviewID, UserID, CommentText, CommentDate) 
                               VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $review_id, $user_id, $comment_text);
        $stmt->execute();
        $stmt->close();
    }
}

// Redirect back
$redirect = $_POST['redirect'] ?? 'user_home.php';
header("Location: $redirect");
exit();
?>