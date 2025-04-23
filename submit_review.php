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
    $review_id = $conn->insert_id; // Get the ID of the newly inserted review

    // Handle image uploads
    if (isset($_FILES['review_images']) && !empty($_FILES['review_images']['name'][0])) {
        $uploadDir = 'uploads/review_images/'; // Create this directory if it doesn't exist
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        foreach ($_FILES['review_images']['tmp_name'] as $key => $tmpName) {
            $fileName = $_FILES['review_images']['name'][$key];
            $fileSize = $_FILES['review_images']['size'][$key];
            $fileType = $_FILES['review_images']['type'][$key];
            $fileError = $_FILES['review_images']['error'][$key];

            error_log("Processing file: " . $fileName . ", Size: " . $fileSize . ", Type: " . $fileType . ", Error: " . $fileError);

            if ($fileError === UPLOAD_ERR_OK) {
                if (in_array($fileType, $allowedTypes)) {
                    if ($fileSize <= $maxFileSize) {
                        $newFileName = uniqid() . '_' . basename($fileName);
                        $destination = $uploadDir . $newFileName;

                        error_log("Attempting to move " . $tmpName . " to " . $destination);
                        if (move_uploaded_file($tmpName, $destination)) {
                            // Save the file path to the database
                            $photoSql = "INSERT INTO review_photos (ReviewID, FilePath) VALUES (?, ?)";
                            $photoStmt = $conn->prepare($photoSql);
                            if ($photoStmt) {
                                $photoStmt->bind_param("is", $review_id, $destination);
                                $photoStmt->execute();
                                $photoStmt->close();
                                error_log("File " . $fileName . " successfully uploaded and path saved.");
                            } else {
                                error_log("Prepare photo statement failed: " . $conn->error);
                                // Optionally handle this error, maybe delete the uploaded file
                            }
                        } else {
                            error_log("Failed to move uploaded file: " . $fileName);
                            header("Location: write_review.php?error=upload_error");
                            exit();
                        }
                    } else {
                        error_log("File size exceeded: " . $fileName);
                        header("Location: write_review.php?error=file_size_exceeded");
                        exit();
                    }
                } else {
                    error_log("Invalid file type: " . $fileName . " (" . $fileType . ")");
                    header("Location: write_review.php?error=invalid_file_type");
                    exit();
                }
            } elseif ($fileError !== UPLOAD_ERR_NO_FILE) {
                error_log("Upload error for " . $fileName . ": " . $fileError);
                header("Location: write_review.php?error=upload_error");
                exit();
            }
        }
    }

    // Re-enable triggers
    $conn->query("SET @DISABLE_TRIGGERS = FALSE");
    header("Location: user_home.php?success=1");
} else {
    error_log("Execute review statement failed: " . $stmt->error);
    header("Location: write_review.php?error=database");
}

$stmt->close();
$conn->close();
exit();
?>