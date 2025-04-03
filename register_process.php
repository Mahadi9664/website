<?php
session_start();

require 'connection.php';

// Get form data
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role_id = $_POST['role'] ?? '';
$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$profile_pic = $_FILES['profile_pic'] ?? null;

// Validate inputs
if (empty($username) || empty($password) || empty($confirm_password) || empty($role_id) || empty($fullname) || empty($email)) {
    $_SESSION['error'] = "All fields are required except profile picture";
    header("Location: register.php");
    exit();
}

if ($password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match";
    header("Location: register.php");
    exit();
}

if (strlen($password) < 8) {
    $_SESSION['error'] = "Password must be at least 8 characters";
    header("Location: register.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format";
    header("Location: register.php");
    exit();
}

// Check if username already exists
$stmt = $conn->prepare("SELECT UserID FROM usercredentials WHERE Username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Username already taken";
    header("Location: register.php");
    exit();
}

// Check if email already exists
$stmt = $conn->prepare("SELECT UserID FROM userprofile WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Email already registered";
    header("Location: register.php");
    exit();
}

// Hash the password securely
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Start transaction
$conn->begin_transaction();

try {
    // Insert into usercredentials
    $stmt = $conn->prepare("INSERT INTO usercredentials (Username, PasswordHash, CreatedAt, IsDeleted) VALUES (?, ?, NOW(), 0)");
    $stmt->bind_param("ss", $username, $password_hash);
    $stmt->execute();
    $user_id = $conn->insert_id;

    // Handle profile picture upload
    $profile_pic_url = null;
    if ($profile_pic && $profile_pic['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profile_pics/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($profile_pic['name'], PATHINFO_EXTENSION);
        $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($profile_pic['tmp_name'], $destination)) {
            $profile_pic_url = $destination;
        }
    }

    // Insert into userprofile
    $stmt = $conn->prepare("INSERT INTO userprofile (UserID, FullName, Email, ProfilePictureURL, IsDeleted) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("isss", $user_id, $fullname, $email, $profile_pic_url);
    $stmt->execute();

    // Insert into userroles
    $stmt = $conn->prepare("INSERT INTO userroles (UserID, RoleID) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $role_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = "Registration successful! You can now login.";
    header("Location: login.php");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Registration failed: " . $e->getMessage();
    header("Location: register.php");
    exit();
} finally {
    $conn->close();
}
?>