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

// Get restaurants for dropdown
$restaurants = $conn->query("
    SELECT RestaurantID, Name 
    FROM restaurant 
    WHERE IsDeleted = 0
    ORDER BY Name
");

// Check for errors in query execution
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
        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h1>Write a Review</h1>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="error">
            <?php 
            switch($_GET['error']) {
                case 'invalid_rating':
                    echo "Please select a rating between 1 and 5 stars";
                    break;
                case 'empty_review':
                    echo "Please write your review text";
                    break;
                case 'database':
                    echo "There was an error saving your review. Please try again.";
                    break;
                default:
                    echo "An error occurred. Please try again.";
            }
            ?>
        </div>
    <?php endif; ?>
    
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
        
        <label for="rating">Rating (1-5):</label>
        <input type="number" id="rating" name="rating" min="1" max="5" required>
        
        <textarea name="review_text" placeholder="Your review..." rows="5" required></textarea>
        
        <button type="submit">Submit Review</button>
    </form>
</body>
</html>
<?php
$conn->close();
?>