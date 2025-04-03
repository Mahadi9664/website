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

// Get restaurants for dropdown
$restaurants = $conn->query("
    SELECT RestaurantID, Name 
    FROM restaurant 
    WHERE IsDeleted = 0
    ORDER BY Name
");

// Verify we got results
if (!$restaurants) {
    die("Error fetching restaurants: " . $conn->error);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Write Review</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        select, textarea, input {
            padding: 8px;
            font-size: 16px;
        }
        button {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Write a Review</h1>
    <form action="submit_review.php" method="POST">
        <select name="restaurant_id" required>
            <option value="">Select Restaurant</option>
            <?php 
            if ($restaurants->num_rows > 0) {
                while($row = $restaurants->fetch_assoc()) {
                    echo '<option value="' . $row['RestaurantID'] . '">' 
                        . htmlspecialchars($row['Name']) . '</option>';
                }
            } else {
                echo '<option value="" disabled>No restaurants available</option>';
            }
            ?>
        </select>
        
        <label>Rating (1-5):</label>
        <input type="number" name="rating" min="1" max="5" required>
        
        <textarea name="review_text" placeholder="Your review..." rows="5" required></textarea>
        
        <button type="submit">Submit Review</button>
    </form>
</body>
</html>
<?php
$conn->close();
?>