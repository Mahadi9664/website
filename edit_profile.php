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

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get current profile data
$profile_query = "
    SELECT uc.Username, up.FullName, up.Email, up.ProfilePictureURL
    FROM usercredentials uc
    JOIN userprofile up ON uc.UserID = up.UserID
    WHERE uc.UserID = $user_id AND uc.IsDeleted = 0
";
$profile_result = $conn->query($profile_query);
$profile = $profile_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    
    // Validate inputs
    if (empty($fullname) || empty($email)) {
        $error = "Full name and email are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Handle profile picture upload
        $profile_pic_url = $profile['ProfilePictureURL'];
        
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/profile_pics/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($file_ext), $allowed_ext)) {
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
                $destination = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                    // Delete old profile picture if it exists and isn't the default
                    if ($profile_pic_url && !str_contains($profile_pic_url, 'default_profile.png')) {
                        @unlink($profile_pic_url);
                    }
                    $profile_pic_url = $destination;
                } else {
                    $error = "Failed to upload profile picture";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed";
            }
        }
        
        if (empty($error)) {
            // Update profile in database
            $stmt = $conn->prepare("UPDATE userprofile SET FullName = ?, Email = ?, ProfilePictureURL = ? WHERE UserID = ?");
            $stmt->bind_param("sssi", $fullname, $email, $profile_pic_url, $user_id);
            
            if ($stmt->execute()) {
                $message = "Profile updated successfully!";
                // Refresh profile data
                $profile_result = $conn->query($profile_query);
                $profile = $profile_result->fetch_assoc();
            } else {
                $error = "Error updating profile: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .profile-pic-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 2px solid #007bff;
        }
        .default-pic {
            font-size: 50px;
            text-align: center;
            line-height: 100px;
            background-color: #f0f0f0;
            color: #999;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-right: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
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
    </style>
</head>
<body>
    <h1>Edit Profile</h1>
    
    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="profile-pic-container">
            <?php if (!empty($profile['ProfilePictureURL'])): ?>
                <img src="<?= htmlspecialchars($profile['ProfilePictureURL']) ?>" alt="Profile Picture" class="profile-pic">
            <?php else: ?>
                <div class="default-pic">👤</div>
            <?php endif; ?>
            <div>
                <label for="profile_pic">Change Profile Picture</label>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
            </div>
        </div>
        
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" value="<?= htmlspecialchars($profile['Username']) ?>" readonly>
            <small>Username cannot be changed</small>
        </div>
        
        <div class="form-group">
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($profile['FullName']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($profile['Email']) ?>" required>
        </div>
        
        <button type="submit" class="btn">Save Changes</button>
        <a href="my_profile.php" class="btn" style="background-color: #6c757d;">Cancel</a>
    </form>
</body>
</html>
<?php
$conn->close();
?>