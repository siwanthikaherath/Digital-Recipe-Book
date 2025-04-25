<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get latest recipes
$stmt = $conn->prepare("SELECT r.id, r.food_name, r.image, r.created_at, u.name as author 
                       FROM recipes r 
                       LEFT JOIN users u ON r.user_id = u.id 
                       ORDER BY r.created_at DESC 
                       LIMIT 8");
$stmt->execute();
$latestRecipes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe App - Sanduni Herath</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Recipe App</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recipes.php">All Recipes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_recipe.php">Add Recipe</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <form class="d-flex me-2" id="searchForm">
                        <input class="form-control me-2" type="search" placeholder="Search recipes" aria-label="Search" id="searchInput">
                        <button class="btn btn-light" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="my_recipes.php">My Recipes</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center text-white">
        <div class="container">
            <h1>Discover & Share Amazing Recipes</h1>
            <p class="lead">Find inspiration for your next meal or share your culinary creations with the world.</p>
            <div class="mt-4">
                <a href="recipes.php" class="btn btn-light btn-lg me-2">Browse Recipes</a>
                <a href="add_recipe.php" class="btn btn-outline-light btn-lg">Add Your Recipe</a>
            </div>
        </div>
    </section>

    <!-- Latest Recipes Section -->
    <section class="container py-5">
        <h2 class="section-title">Latest Recipes</h2>
        <div class="row g-4" id="latestRecipes">
            <?php foreach ($latestRecipes as $recipe): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card recipe-card h-100">
                        <div class="card-img-container">
                            <?php if (!empty($recipe['image'])): ?>
                                <img src="<?php echo htmlspecialchars($recipe['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($recipe['food_name']); ?>">
                            <?php else: ?>
                                <img src="images/placeholder.jpg" class="card-img-top" alt="Recipe placeholder">
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($recipe['food_name']); ?></h5>
                            <p class="card-text text-muted">By <?php echo htmlspecialchars($recipe['author']); ?></p>
                            <p class="card-text"><small class="text-muted">
                                <?php echo date('M d, Y', strtotime($recipe['created_at'])); ?>
                            </small></p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-primary w-100">View Recipe</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="bg-light py-5">
        <div class="container">
            <h2 class="section-title text-center mb-4">Why Use Our Recipe App?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <i class="fas fa-utensils feature-icon mb-3"></i>
                        <h3>Discover New Recipes</h3>
                        <p>Browse through hundreds of recipes shared by our community members.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <i class="fas fa-share-alt feature-icon mb-3"></i>
                        <h3>Share Your Creations</h3>
                        <p>Upload your own recipes and showcase your culinary skills to the world.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <i class="fas fa-comments feature-icon mb-3"></i>
                        <h3>Engage with Others</h3>
                        <p>Comment on recipes, share tips, and connect with fellow food enthusiasts.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Recipe App</h5>
                    <p>Developed by Sanduni Herath</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="index.php" class="text-white">Home</a></li>
                        <li class="list-inline-item"><a href="recipes.php" class="text-white">Recipes</a></li>
                        <li class="list-inline-item"><a href="about.php" class="text-white">About</a></li>
                        <li class="list-inline-item"><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                    <p class="mt-2 mb-0">© <?php echo date('Y'); ?> Recipe App. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/script.js"></script>
</body>
</html>