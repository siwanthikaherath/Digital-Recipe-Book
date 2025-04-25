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

// Handle search
$search_results = [];
$search_term = '';
$no_results = false;

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_term = trim($_GET['q']);
    
    // Prepare search query (search in food_name column)
    $search_query = "%{$search_term}%";
    $stmt = $conn->prepare("SELECT r.id, r.food_name, r.image, r.created_at, u.name as author 
                           FROM recipes r 
                           LEFT JOIN users u ON r.user_id = u.id 
                           WHERE r.food_name LIKE ? 
                           ORDER BY r.created_at DESC");
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Check if no results were found
    if (empty($search_results)) {
        $no_results = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Recipes - Recipe App</title>
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
                    <form class="d-flex me-2" action="search.php" method="GET">
                        <input class="form-control me-2" type="search" name="q" placeholder="Search recipes" aria-label="Search" value="<?php echo htmlspecialchars($search_term); ?>">
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

    <!-- Search Results Header -->
    <section class="py-5 bg-light">
        <div class="container">
            <h1 class="text-center mb-4">Search Results</h1>
            <?php if (!empty($search_term)): ?>
                <p class="text-center lead">
                    <?php if (!$no_results): ?>
                        Found <?php echo count($search_results); ?> result<?php echo count($search_results) !== 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($search_term); ?>"
                    <?php else: ?>
                        No results found for "<?php echo htmlspecialchars($search_term); ?>"
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="text-center lead">Enter a search term to find recipes</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Search Results -->
    <section class="container py-5">
        <?php if (empty($search_term)): ?>
            <div class="text-center">
                <i class="fas fa-search fa-4x mb-3 text-muted"></i>
                <h3>Start your search</h3>
                <p class="lead">Enter a keyword in the search box above to find delicious recipes</p>
            </div>
        <?php elseif ($no_results): ?>
            <div class="text-center">
                <i class="fas fa-exclamation-circle fa-4x mb-3 text-muted"></i>
                <h3>No recipes found</h3>
                <p class="lead">Try different keywords or <a href="add_recipe.php">add your own recipe</a>!</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($search_results as $recipe): ?>
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
        <?php endif; ?>
    </section>

    <!-- Suggestions Section (only show if search returned no results) -->
    <?php if ($no_results && !empty($search_term)): ?>
    <section class="container py-4">
        <h3 class="mb-4">You might be interested in</h3>
        
        <?php
        // Get some random recipes as suggestions
        $stmt = $conn->prepare("SELECT r.id, r.food_name, r.image, u.name as author 
                               FROM recipes r 
                               LEFT JOIN users u ON r.user_id = u.id 
                               ORDER BY RAND() 
                               LIMIT 4");
        $stmt->execute();
        $suggestion_recipes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        ?>
        
        <div class="row g-4">
            <?php foreach ($suggestion_recipes as $recipe): ?>
                <div class="col-12 col-sm-6 col-md-3">
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
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-primary w-100">View Recipe</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

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
    
    <script>
    // Focus on search input when page loads if no search term
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (empty($search_term)): ?>
        document.querySelector('input[name="q"]').focus();
        <?php endif; ?>
    });
    </script>
</body>
</html>