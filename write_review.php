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

if (!$restaurants) {
    die("Error fetching restaurants: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex: 1;
            padding: 100px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 600px;
            margin: 0 auto;
        }

        select,
        textarea,
        input[type="number"],
        input[type="file"] {
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

        footer {
            margin-top: auto;
        }

        .justify-content-evenly {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
        }
    </style>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg bg-body-tertiary mb-2">
            <div class="container-fluid ">

                <div class="collapse navbar-collapse d-flex justify-content-between" id="navbarTogglerDemo01">
                    <a class="navbar-brand ms-3" href="./user_home.php">Koi Khabo</a>
                    <a class="btn btn-outline-info me-3" href="user_home.php">Go Back</a>
                </div>
            </div>
        </nav>
    </header>
    <main>
        <h1>Write a Review</h1>

        <?php if (isset($_GET['error'])): ?>
            <div class="error">
                <?php
                switch ($_GET['error']) {
                    case 'invalid_rating':
                        echo "Please select a rating between 1 and 5 stars";
                        break;
                    case 'empty_review':
                        echo "Please write your review text";
                        break;
                    case 'database':
                        echo "There was an error saving your review. Please try again.";
                        break;
                    case 'upload_error':
                        echo "Error uploading one or more images. Please try again.";
                        break;
                    case 'invalid_file_type':
                        echo "One or more selected files are not valid image types (JPEG, PNG, GIF).";
                        break;
                    case 'file_size_exceeded':
                        echo "One or more uploaded files exceeded the maximum allowed size.";
                        break;
                    default:
                        echo "An error occurred. Please try again.";
                }
                ?>
            </div>
        <?php endif; ?>

        <form action="submit_review.php" method="POST" enctype="multipart/form-data">
            <select class="form-select" name="restaurant_id" required>
                <option value="">Select Restaurant</option>
                <?php
                if ($restaurants->num_rows > 0) {
                    while ($row = $restaurants->fetch_assoc()) {
                        echo '<option value="' . $row['RestaurantID'] . '">'
                            . htmlspecialchars($row['Name']) . '</option>';
                    }
                } else {
                    echo '<option value="" disabled>No restaurants available</option>';
                }
                ?>
            </select>

            <label for="rating" class="form-label">Rating (1-5):</label>
            <input type="number" class="form-control" id="rating" name="rating" min="1" max="5" required>

            <label for="review_text" class="form-label">Your Review:</label>
            <textarea class="form-control" name="review_text" placeholder="Your review..." rows="5" required></textarea>

            <label for="review_images" class="form-label">Add Photos (JPEG, PNG, GIF - Max 5MB each):</label>
            <input type="file" class="form-control" id="review_images" name="review_images[]" accept="image/jpeg, image/png, image/gif" multiple>
            <small class="form-text text-muted">You can select multiple images.</small>

            <button type="submit" class="btn btn-success">Submit Review</button>
        </form>
    </main>
    <footer class="bg-black py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 mb-md-0 ">
                    <a href="#" class="text-white text-decoration-none">About</a>
                    <a href="#" class="text-white text-decoration-none">Contact </a>
                    <a href="#" class="text-white text-decoration-none">Report </a>
                </div>
                <div class="w-100 d-flex justify-content-center justify-content-md-center order-md-2 mb-md-0 align-items-center">
                    <span class="small text-secondary">Koi Khabo Ltd.</span>
                </div>
                <div class="d-flex gap-3 order-md-3 align-items-center">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f social-icon text-white"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter social-icon text-white"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram social-icon text-white"></i></a>
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