<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Get current user information
$current_user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$stmt->close();

// Check if user_id is provided in the URL
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header('Location: recipes.php');
    exit;
}

$user_id = $_GET['user_id'];

// Get profile information of the user whose recipes we're viewing
$stmt = $conn->prepare("SELECT name, description, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found
    header('Location: recipes.php');
    exit;
}

$profile_user = $result->fetch_assoc();
$stmt->close();

// Get all recipes by this user
$stmt = $conn->prepare("SELECT r.*, 
                      (SELECT COUNT(*) FROM comments WHERE recipe_id = r.id) as comment_count 
                      FROM recipes r 
                      WHERE r.user_id = ? 
                      ORDER BY r.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recipes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile_user['name']); ?>'s Recipes - Recipe App</title>
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
                        <a class="nav-link" href="index.php">Home</a>
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
                            <?php echo htmlspecialchars($current_user['name']); ?>
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

    <!-- User Profile and Recipes Section -->
    <div class="container py-5">
        <!-- User Profile Header -->
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="user-avatar me-3">
                                <!-- User avatar placeholder -->
                                <div class="avatar-placeholder bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; font-size: 28px;">
                                    <?php echo strtoupper(substr($profile_user['name'], 0, 1)); ?>
                                </div>
                            </div>
                            <div class="user-info">
                                <h2 class="mb-1"><?php echo htmlspecialchars($profile_user['name']); ?>'s Recipes</h2>
                                <p class="text-muted mb-0">Member since <?php echo date('F Y', strtotime($profile_user['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($profile_user['description'])): ?>
                        <div class="user-bio mt-3">
                            <p><?php echo nl2br(htmlspecialchars($profile_user['description'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="user-stats mt-3">
                            <span class="badge bg-primary me-2"><?php echo count($recipes); ?> Recipes</span>
                            <?php if ($current_user_id == $user_id): ?>
                            <a href="profile.php" class="btn btn-sm btn-outline-primary">Edit Profile</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recipes Grid -->
        <h3 class="mb-4">All Recipes by <?php echo htmlspecialchars($profile_user['name']); ?></h3>
        
        <?php if (empty($recipes)): ?>
            <div class="alert alert-info">
                <?php echo ($current_user_id == $user_id) ? 'You haven\'t added any recipes yet. <a href="add_recipe.php">Add your first recipe</a>!' : 'This user hasn\'t added any recipes yet.'; ?>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($recipes as $recipe): ?>
                    <div class="col">
                        <div class="card h-100 recipe-card">
                            <div class="recipe-image-container">
                                <?php if (!empty($recipe['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($recipe['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($recipe['food_name']); ?>">
                                <?php else: ?>
                                    <img src="images/placeholder.jpg" class="card-img-top" alt="Recipe placeholder">
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($recipe['food_name']); ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">Posted on <?php echo date('F d, Y', strtotime($recipe['created_at'])); ?></small>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-light text-dark me-1">
                                            <i class="fas fa-comment me-1"></i><?php echo $recipe['comment_count']; ?>
                                        </span>
                                    </div>
                                    <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-primary">View Recipe</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

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