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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
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

        .card-grow {
            transition: transform 0.2s ease-in-out;
            cursor: pointer;
        }

        .card-grow:hover {
            transform: scale(1.03);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.245) !important;
        }

        main {
            flex: 1;
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

        .card {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg bg-body-tertiary mb-2">
            <div class="container-fluid ">

                <div class="collapse navbar-collapse d-flex justify-content-between" id="navbarTogglerDemo01">
                    <a class="navbar-brand ms-3" href="./user_home.php">Koi Khabo</a>
                    <div>
                        <a class="btn btn-outline-info me-2" href="my_profile.php">My Profile</a>
                        <a type="button me-3" class="btn btn-outline-danger" href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <main>
        <h1 class="m-4 p-4">Welcome
            <?php echo htmlspecialchars(strtoupper($_SESSION['username'])); ?>!
        </h1>
        <div class="justify-content-evenly">
            <div class="card mb-3 shadow card-grow" onclick="location.href='write_review.php'"
                style="max-width: 540px;">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="./Media/DudeWritingReview.png" class="img-fluid rounded-start" alt="Write Review">
                    </div>
                    <div class="col-md-8 d-flex justify-content-center align-items-center">
                        <div class="card-body text-center">
                            <h5 class="card-title fw-bold">WRITE YOUR REVIEW</h5>
                            <p class="card-text">Click here to let others know your own experience!</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 shadow card-grow" onclick="location.href='restaurants.php'" style="max-width: 540px;">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="./Media/DudeLookingAtRestaurants.png" class="img-fluid rounded-start" alt="Browse Restaurants">
                    </div>
                    <div class="col-md-8 d-flex justify-content-center align-items-center">
                        <div class="card-body text-center">
                            <h5 class="card-title fw-semibold">BROWSE RESTAURANTS</h5>
                            <p class="card-text">Click here to explore and see what others have to say!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
        crossorigin="anonymous"></script>

</body>

</html>
<?php
$conn->close();
?>