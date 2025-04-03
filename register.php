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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role_id = (int)$_POST['role'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    
    // Restaurant owner specific fields
    $restaurant_name = trim($_POST['restaurant_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $opening_hours = trim($_POST['opening_hours'] ?? '');
    $cuisine_type = trim($_POST['cuisine_type'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');

    // Validate common fields
    if (empty($username) || empty($password) || empty($confirm_password) || 
        empty($role_id) || empty($fullname) || empty($email)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: register.php");
        exit();
    }

    // Validate password
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords don't match";
        header("Location: register.php");
        exit();
    }

    // Validate restaurant owner specific fields if role is owner
    if ($role_id == 3 && (empty($restaurant_name) || empty($location) || 
        empty($opening_hours) || empty($cuisine_type) || empty($phone_number))) {
        $_SESSION['error'] = "All restaurant information is required";
        header("Location: register.php");
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Create user
        $stmt = $conn->prepare("INSERT INTO usercredentials (Username, PasswordHash) VALUES (?, ?)");
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bind_param("ss", $username, $password_hash);
        $stmt->execute();
        $user_id = $conn->insert_id;

        // 2. Assign role
        $stmt = $conn->prepare("INSERT INTO userroles (UserID, RoleID) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $role_id);
        $stmt->execute();

        // 3. Create profile
        $stmt = $conn->prepare("INSERT INTO userprofile (UserID, FullName, Email) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $fullname, $email);
        $stmt->execute();

        // 4. If restaurant owner, create restaurant
        if ($role_id == 3) {
            // Create restaurant
            $stmt = $conn->prepare("INSERT INTO restaurant (Name, Location, OpeningHours, CuisineType, AdminID) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $restaurant_name, $location, $opening_hours, $cuisine_type, $user_id);
            $stmt->execute();
            $restaurant_id = $conn->insert_id;

            // Add phone number
            $stmt = $conn->prepare("INSERT INTO restaurantnumber (RestaurantID, PhoneNumber) VALUES (?, ?)");
            $stmt->bind_param("is", $restaurant_id, $phone_number);
            $stmt->execute();
        }

        $conn->commit();
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: login.php");
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        header("Location: register.php");
    }
    exit();
}

// Get roles for dropdown
$roles = $conn->query("SELECT * FROM roles WHERE RoleName != 'Admin'"); // Don't show admin in registration
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .register-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #218838;
        }
        .error, .success {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .restaurant-fields {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2 style="text-align: center;">Register</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="register.php">
            <!-- Basic Information -->
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="role">Account Type</label>
                <select id="role" name="role" required onchange="toggleRestaurantFields()">
                    <option value="">-- Select Role --</option>
                    <?php while($role = $roles->fetch_assoc()): ?>
                        <option value="<?= $role['RoleID'] ?>"><?= htmlspecialchars($role['RoleName']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <!-- Restaurant Owner Fields (hidden by default) -->
            <div id="restaurant-fields" class="restaurant-fields">
                <h3>Restaurant Information</h3>
                
                <div class="form-group">
                    <label for="restaurant_name">Restaurant Name</label>
                    <input type="text" id="restaurant_name" name="restaurant_name">
                </div>
                
                <div class="form-group">
                    <label for="location">Location/Address</label>
                    <input type="text" id="location" name="location">
                </div>
                
                <div class="form-group">
                    <label for="opening_hours">Opening Hours</label>
                    <input type="text" id="opening_hours" name="opening_hours" placeholder="e.g., 9AM-10PM">
                </div>
                
                <div class="form-group">
                    <label for="cuisine_type">Cuisine Type</label>
                    <input type="text" id="cuisine_type" name="cuisine_type">
                </div>
                
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number">
                </div>
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <p style="text-align: center; margin-top: 15px;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>

    <script>
        function toggleRestaurantFields() {
            const roleSelect = document.getElementById('role');
            const restaurantFields = document.getElementById('restaurant-fields');
            
            // Show restaurant fields only if Restaurant Owner (role ID 3) is selected
            if (roleSelect.value === '3') {
                restaurantFields.style.display = 'block';
                
                // Make restaurant fields required
                document.querySelectorAll('#restaurant-fields input').forEach(input => {
                    input.required = true;
                });
            } else {
                restaurantFields.style.display = 'none';
                
                // Remove required from restaurant fields
                document.querySelectorAll('#restaurant-fields input').forEach(input => {
                    input.required = false;
                });
            }
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>