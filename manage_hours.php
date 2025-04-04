<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Restaurant Owner') {
    header("Location: login.php");
    exit();
}

// Database connection with error handling
$conn = new mysqli("localhost", "root", "", "aamm");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get restaurant info - using direct query for simplicity
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM restaurant WHERE AdminID = $user_id AND IsDeleted = 0");
$restaurant = $result->fetch_assoc();

if (!$restaurant) {
    die("No restaurant found for this owner");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hours = trim($_POST['opening_hours']);
    $restaurant_id = $restaurant['RestaurantID'];
    
    // TEMPORARY: Disable triggers to prevent spatial function errors
    $conn->query("SET @DISABLE_TRIGGERS = TRUE");
    
    // Use direct query with proper escaping
    $sql = "UPDATE restaurant SET OpeningHours = '" . $conn->real_escape_string($hours) . "' 
            WHERE RestaurantID = $restaurant_id";
    
    if ($conn->query($sql)) {
        $success = "Hours updated successfully!";
        $restaurant['OpeningHours'] = $hours;
    } else {
        $error = "Error updating hours: " . $conn->error;
    }
    
    // Re-enable triggers
    $conn->query("SET @DISABLE_TRIGGERS = FALSE");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Operating Hours</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .form-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
            font-family: inherit;
            resize: vertical;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        button:hover {
            background: #2980b9;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Update Operating Hours</h1>
        
        <?php if (isset($success)): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php elseif (isset($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Current Hours:</label>
                <p><?= htmlspecialchars($restaurant['OpeningHours']) ?></p>
            </div>
            
            <div class="form-group">
                <label for="opening_hours">New Hours:</label>
                <textarea id="opening_hours" name="opening_hours" required><?= 
                    htmlspecialchars($restaurant['OpeningHours']) ?></textarea>
            </div>
            
            <button type="submit">Update Hours</button>
        </form>
        
        <a href="owner_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
<?php
$conn->close();
?>