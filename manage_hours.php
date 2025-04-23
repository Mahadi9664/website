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
        }

        .form-container {
            background: #fff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            margin: 2rem auto;
        }

        h1 {
            color: #007bff;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #343a40;
        }

        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            min-height: 100px;
            font-family: inherit;
            resize: vertical;
        }

        button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.15s ease-in-out;
        }

        button:hover {
            background-color: #218838;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.25rem;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            display: inline-block;
            margin-top: 1rem;
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
            margin-top: 3rem;
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
    </style>
</head>
<body>
    <header class="bg-white shadow-sm py-3 fixed-top">
        <nav class="navbar navbar-expand-lg container">
            <a class="navbar-brand fw-bold text-primary" href="./owner_dashboard.php">Koi Khabo</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-dark"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-danger ms-3" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container mt-5 pt-5">
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
</body>
</html>
<?php
$conn->close();
?>