<?php
session_start();

// Database connection
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "aamm";
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if (!$conn) {
    $_SESSION['error'] = "Database connection failed";
    header("Location: login.php");
    exit();
}

// Get form data
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Validate input
if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Username and password are required";
    header("Location: login.php");
    exit();
}

// Prepare SQL to prevent SQL injection
$stmt = $conn->prepare("SELECT uc.UserID, uc.Username, uc.PasswordHash, r.RoleName 
                        FROM usercredentials uc
                        JOIN userroles ur ON uc.UserID = ur.UserID
                        JOIN roles r ON ur.RoleID = r.RoleID
                        WHERE uc.Username = ? AND uc.IsDeleted = 0");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['PasswordHash'])) {
        // Authentication successful
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['RoleName'];
        
        // Redirect based on role
        switch ($user['RoleName']) {
            case 'Admin':
                header("Location: admin_dashboard.php");
                break;
            case 'Restaurant Owner':
                header("Location: owner_dashboard.php");
                break;
            default:
                header("Location: user_dashboard.php");
        }
        exit();
    } else {
        $_SESSION['error'] = "Invalid username or password";
    }
} else {
    $_SESSION['error'] = "Invalid username or password";
}

$stmt->close();
$conn->close();
header("Location: login.php");
exit();
?>