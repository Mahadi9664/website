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
$stmt = $conn->prepare("SELECT * FROM restaurant WHERE AdminID = ? AND IsDeleted = 0");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$restaurant) {
    die("No restaurant found");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hours = trim($_POST['opening_hours']);
    $update = $conn->prepare("UPDATE restaurant SET OpeningHours = ? WHERE RestaurantID = ?");
    $update->bind_param("si", $hours, $restaurant['RestaurantID']);
    if ($update->execute()) {
        $success = "Hours updated successfully!";
        $restaurant['OpeningHours'] = $hours; // Update local copy
    }
    $update->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Hours</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        textarea { width: 100%; padding: 10px; min-height: 100px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .success { color: green; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Update Operating Hours</h1>
    <?php if (isset($success)): ?>
        <div class="success"><?= $success ?></div>
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
    <p><a href="owner_dashboard.php">← Back to Dashboard</a></p>
</body>
</html>
<?php $conn->close(); ?>