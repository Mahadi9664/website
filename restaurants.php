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

// Get filter parameters
$search = $_GET['search'] ?? '';
$cuisine_filter = $_GET['cuisine'] ?? '';
$rating_filter = $_GET['rating'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

// Base query with average rating
$query = "
    SELECT r.RestaurantID, r.Name, r.Location, r.CuisineType, 
           AVG(rm.Rating) as AvgRating,
           COUNT(rm.ReviewID) as ReviewCount
    FROM restaurant r
    LEFT JOIN reviewmetabase rm ON r.RestaurantID = rm.RestaurantID AND rm.IsDeleted = 0
    WHERE r.IsDeleted = 0
";

// Add search condition
if (!empty($search)) {
    $query .= " AND (r.Name LIKE '%".$conn->real_escape_string($search)."%' 
                OR r.Location LIKE '%".$conn->real_escape_string($search)."%')";
}

// Add cuisine filter
if (!empty($cuisine_filter)) {
    $query .= " AND r.CuisineType = '".$conn->real_escape_string($cuisine_filter)."'";
}

// Group by restaurant
$query .= " GROUP BY r.RestaurantID";

// Add rating filter
if (!empty($rating_filter) && is_numeric($rating_filter)) {
    $query .= " HAVING AvgRating >= ".floatval($rating_filter);
}

// Add sorting
switch ($sort) {
    case 'name_desc':
        $query .= " ORDER BY r.Name DESC";
        break;
    case 'rating_asc':
        $query .= " ORDER BY AvgRating ASC";
        break;
    case 'rating_desc':
        $query .= " ORDER BY AvgRating DESC";
        break;
    default: // name_asc
        $query .= " ORDER BY r.Name ASC";
}

$restaurants = $conn->query($query);

// Get unique cuisine types
$cuisines = $conn->query("SELECT DISTINCT CuisineType FROM restaurant WHERE IsDeleted = 0 ORDER BY CuisineType");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Restaurants</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        input, select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        button {
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0069d9;
        }
        .restaurant-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .restaurant-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .restaurant-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        .restaurant-name {
            font-size: 1.2rem;
            margin-bottom: 5px;
            color: #212529;
        }
        .restaurant-meta {
            color: #6c757d;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .rating {
            color: #ffc107;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .review-count {
            color: #6c757d;
            font-size: 0.8rem;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 16px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <h1>Restaurants</h1>
    
    <!-- Filter Section -->
    <div class="filters">
        <form method="GET">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Restaurant name or location">
                </div>
                
                <div class="filter-group">
                    <label for="cuisine">Cuisine:</label>
                    <select id="cuisine" name="cuisine">
                        <option value="">All Cuisines</option>
                        <?php while($cuisine = $cuisines->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cuisine['CuisineType']) ?>"
                                <?= $cuisine_filter == $cuisine['CuisineType'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cuisine['CuisineType']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="rating">Minimum Rating:</label>
                    <select id="rating" name="rating">
                        <option value="">Any Rating</option>
                        <option value="1" <?= $rating_filter == '1' ? 'selected' : '' ?>>★+ (1+)</option>
                        <option value="2" <?= $rating_filter == '2' ? 'selected' : '' ?>>★★+ (2+)</option>
                        <option value="3" <?= $rating_filter == '3' ? 'selected' : '' ?>>★★★+ (3+)</option>
                        <option value="4" <?= $rating_filter == '4' ? 'selected' : '' ?>>★★★★+ (4+)</option>
                        <option value="5" <?= $rating_filter == '5' ? 'selected' : '' ?>>★★★★★ (5)</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort By:</label>
                    <select id="sort" name="sort">
                        <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                        <option value="rating_asc" <?= $sort == 'rating_asc' ? 'selected' : '' ?>>Rating (Low-High)</option>
                        <option value="rating_desc" <?= $sort == 'rating_desc' ? 'selected' : '' ?>>Rating (High-Low)</option>
                    </select>
                </div>
            </div>
            
            <button type="submit">Apply Filters</button>
            <a href="restaurants.php">Reset Filters</a>
        </form>
    </div>
    
    <!-- Restaurant List -->
    <div class="restaurant-list">
        <?php if ($restaurants->num_rows > 0): ?>
            <?php while($restaurant = $restaurants->fetch_assoc()): ?>
                <div class="restaurant-card" onclick="window.location='restaurant_details.php?id=<?= $restaurant['RestaurantID'] ?>'">
                    <div class="restaurant-name"><?= htmlspecialchars($restaurant['Name']) ?></div>
                    <div class="restaurant-meta">
                        <div><strong>Location:</strong> <?= htmlspecialchars($restaurant['Location']) ?></div>
                        <div><strong>Cuisine:</strong> <?= htmlspecialchars($restaurant['CuisineType']) ?></div>
                    </div>
                    
                    <div class="rating">
                        <?= str_repeat('★', round($restaurant['AvgRating'])) ?>
                        <?= str_repeat('☆', 5 - round($restaurant['AvgRating'])) ?>
                        (<?= number_format($restaurant['AvgRating'], 1) ?>)
                        <span class="review-count">from <?= $restaurant['ReviewCount'] ?> reviews</span>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center; padding: 20px;">
                No restaurants found matching your criteria.
            </p>
        <?php endif; ?>
    </div>
    
    <a href="user_home.php" class="back-link">← Back to Home</a>
</body>
</html>
<?php
$conn->close();
?>