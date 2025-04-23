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
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .options {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 50px;
        }
        .option-card {
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            width: 200px;
        }
        .option-card:hover {
            background-color: #f5f5f5;
        }
        .profile-link {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .profile-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        <div>
            <a href="my_profile.php" class="profile-link">My Profile</a> |
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="options">
        <div class="option-card" onclick="location.href='write_review.php'">
            <h2>✍️ Write Review</h2>
        </div>
        <div class="option-card" onclick="location.href='restaurants.php'">
            <h2>🍽️ Browse Restaurants</h2>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>