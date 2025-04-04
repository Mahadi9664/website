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
$stmt = $conn->prepare("SELECT RestaurantID, Name FROM restaurant WHERE AdminID = ? AND IsDeleted = 0");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$restaurant) {
    die("No restaurant found");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        $stmt = $conn->prepare("INSERT INTO food (RestaurantID, Name, Description, Price, CuisineID) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issdi", 
            $restaurant['RestaurantID'],
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['cuisine_id']
        );
        $stmt->execute();
        $stmt->close();
    } 
    elseif (isset($_POST['update_item'])) {
        $stmt = $conn->prepare("UPDATE food SET Name = ?, Description = ?, Price = ?, CuisineID = ? 
                               WHERE FoodID = ? AND RestaurantID = ?");
        $stmt->bind_param("ssdiii",
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['cuisine_id'],
            $_POST['food_id'],
            $restaurant['RestaurantID']
        );
        $stmt->execute();
        $stmt->close();
    }
    elseif (isset($_POST['delete_item'])) {
        $stmt = $conn->prepare("UPDATE food SET IsDeleted = 1 WHERE FoodID = ? AND RestaurantID = ?");
        $stmt->bind_param("ii", $_POST['food_id'], $restaurant['RestaurantID']);
        $stmt->execute();
        $stmt->close();
    }
    
    // Refresh the page after any form submission
    header("Location: manage_menu.php");
    exit();
}

// Get filter/sort parameters
$search = $_GET['search'] ?? '';
$cuisine_filter = $_GET['cuisine'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

// Build query
$query = "SELECT f.*, c.Name as CuisineName FROM food f
          JOIN cuisine c ON f.CuisineID = c.CuisineID
          WHERE f.RestaurantID = ? AND f.IsDeleted = 0";

$params = [$restaurant['RestaurantID']];
$types = "i";

// Add search filter
if (!empty($search)) {
    $query .= " AND (f.Name LIKE ? OR f.Description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// Add cuisine filter
if (!empty($cuisine_filter) && is_numeric($cuisine_filter)) {
    $query .= " AND f.CuisineID = ?";
    $params[] = $cuisine_filter;
    $types .= "i";
}

// Add sorting
switch ($sort) {
    case 'name_desc': $query .= " ORDER BY f.Name DESC"; break;
    case 'price_asc': $query .= " ORDER BY f.Price ASC"; break;
    case 'price_desc': $query .= " ORDER BY f.Price DESC"; break;
    case 'cuisine_asc': $query .= " ORDER BY c.Name ASC"; break;
    default: $query .= " ORDER BY f.Name ASC";
}

// Prepare and execute
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$menu_items = $stmt->get_result();

// Get cuisines for dropdown
$cuisines = $conn->query("SELECT * FROM cuisine");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Menu</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .filters { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-container { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group { margin-bottom: 10px; }
        .form-group label { display: inline-block; width: 100px; }
        .tab-buttons { margin-bottom: 10px; }
        .action-form { display: inline; }
        button { cursor: pointer; }
    </style>
</head>
<body>
    <h1>Manage Menu: <?= htmlspecialchars($restaurant['Name']) ?></h1>
    
    <!-- Search and Filter -->
    <div class="filters">
        <form method="GET">
            <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
            <select name="cuisine">
                <option value="">All Cuisines</option>
                <?php while($cuisine = $cuisines->fetch_assoc()): ?>
                    <option value="<?= $cuisine['CuisineID'] ?>" <?= 
                        $cuisine_filter == $cuisine['CuisineID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cuisine['Name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <select name="sort">
                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low-High)</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High-Low)</option>
                <option value="cuisine_asc" <?= $sort === 'cuisine_asc' ? 'selected' : '' ?>>Cuisine (A-Z)</option>
            </select>
            <button type="submit">Apply</button>
            <a href="manage_menu.php">Reset</a>
        </form>
    </div>
    
    <!-- Menu Items Table -->
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Price</th>
                <th>Cuisine</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($menu_items->num_rows > 0): ?>
                <?php while($item = $menu_items->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($item['Name']) ?></td>
                    <td><?= htmlspecialchars($item['Description']) ?></td>
                    <td>$<?= number_format($item['Price'], 2) ?></td>
                    <td><?= htmlspecialchars($item['CuisineName']) ?></td>
                    <td>
                        <form class="action-form" method="POST">
                            <input type="hidden" name="food_id" value="<?= $item['FoodID'] ?>">
                            <button type="button" class="edit-btn" 
                                data-id="<?= $item['FoodID'] ?>"
                                data-name="<?= htmlspecialchars($item['Name'], ENT_QUOTES) ?>"
                                data-desc="<?= htmlspecialchars($item['Description'], ENT_QUOTES) ?>"
                                data-price="<?= $item['Price'] ?>"
                                data-cuisine="<?= $item['CuisineID'] ?>">
                                Edit
                            </button>
                            <button type="submit" name="delete_item">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No menu items found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Add/Edit Forms -->
    <div class="tab-buttons">
        <button onclick="showTab('add-form')">Add New Item</button>
        <button onclick="showTab('edit-form')" id="edit-btn" disabled>Edit Item</button>
    </div>
    
    <!-- Add Form -->
    <div id="add-form" class="form-container">
        <h2>Add New Menu Item</h2>
        <form method="POST">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <input type="text" id="description" name="description">
            </div>
            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" step="0.01" id="price" name="price" required>
            </div>
            <div class="form-group">
                <label for="cuisine_id">Cuisine:</label>
                <select id="cuisine_id" name="cuisine_id" required>
                    <?php $cuisines->data_seek(0); ?>
                    <?php while($cuisine = $cuisines->fetch_assoc()): ?>
                        <option value="<?= $cuisine['CuisineID'] ?>"><?= htmlspecialchars($cuisine['Name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="add_item">Add Item</button>
        </form>
    </div>
    
    <!-- Edit Form -->
    <div id="edit-form" class="form-container" style="display:none;">
        <h2>Edit Menu Item</h2>
        <form method="POST">
            <input type="hidden" id="edit-food-id" name="food_id">
            <div class="form-group">
                <label for="edit-name">Name:</label>
                <input type="text" id="edit-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="edit-description">Description:</label>
                <input type="text" id="edit-description" name="description">
            </div>
            <div class="form-group">
                <label for="edit-price">Price:</label>
                <input type="number" step="0.01" id="edit-price" name="price" required>
            </div>
            <div class="form-group">
                <label for="edit-cuisine-id">Cuisine:</label>
                <select id="edit-cuisine-id" name="cuisine_id" required>
                    <?php $cuisines->data_seek(0); ?>
                    <?php while($cuisine = $cuisines->fetch_assoc()): ?>
                        <option value="<?= $cuisine['CuisineID'] ?>"><?= htmlspecialchars($cuisine['Name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="update_item">Update Item</button>
            <button type="button" onclick="hideEditForm()">Cancel</button>
        </form>
    </div>
    
    <p><a href="owner_dashboard.php">← Back to Dashboard</a></p>
    
    <script>
        // Initialize edit buttons
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-btn');
            
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const foodId = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const description = this.getAttribute('data-desc');
                    const price = this.getAttribute('data-price');
                    const cuisineId = this.getAttribute('data-cuisine');
                    
                    // Populate edit form
                    document.getElementById('edit-food-id').value = foodId;
                    document.getElementById('edit-name').value = name;
                    document.getElementById('edit-description').value = description;
                    document.getElementById('edit-price').value = parseFloat(price).toFixed(2);
                    document.getElementById('edit-cuisine-id').value = cuisineId;
                    
                    // Show edit form
                    document.getElementById('add-form').style.display = 'none';
                    document.getElementById('edit-form').style.display = 'block';
                    document.getElementById('edit-btn').disabled = false;
                    
                    // Scroll to form
                    document.getElementById('edit-form').scrollIntoView({ behavior: 'smooth' });
                });
            });
        });

        function hideEditForm() {
            document.getElementById('add-form').style.display = 'block';
            document.getElementById('edit-form').style.display = 'none';
        }
        
        function showTab(tabId) {
            document.getElementById('add-form').style.display = 'none';
            document.getElementById('edit-form').style.display = 'none';
            document.getElementById(tabId).style.display = 'block';
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>