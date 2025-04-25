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

// Set default pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 9;
$offset = ($page - 1) * $items_per_page;

// Handle search query if present
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Create the base SQL query
$sql_count = "SELECT COUNT(*) AS total FROM recipes";
$sql_recipes = "SELECT r.*, u.name as author_name, 
               (SELECT COUNT(*) FROM comments WHERE recipe_id = r.id) as comment_count 
               FROM recipes r 
               LEFT JOIN users u ON r.user_id = u.id";

// Add search condition if search term is provided
if (!empty($search_term)) {
    $search_condition = " WHERE r.food_name LIKE ?";
    $sql_count .= $search_condition;
    $sql_recipes .= $search_condition;
    $search_param = "%$search_term%";
}

// Add sorting and pagination
$sql_recipes .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

// Get total count of recipes
if (!empty($search_term)) {
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param("s", $search_param);
} else {
    $stmt = $conn->prepare($sql_count);
}
$stmt->execute();
$total_recipes = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Calculate total pages
$total_pages = ceil($total_recipes / $items_per_page);

// Get recipes for current page
if (!empty($search_term)) {
    $stmt = $conn->prepare($sql_recipes);
    $stmt->bind_param("sii", $search_param, $items_per_page, $offset);
} else {
    $stmt = $conn->prepare($sql_recipes);
    $stmt->bind_param("ii", $items_per_page, $offset);
}
$stmt->execute();
$recipes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Recipes - Recipe App</title>
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
                        <a class="nav-link active" href="recipes.php">All Recipes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_recipe.php">Add Recipe</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <form class="d-flex me-2" action="recipes.php" method="GET">
                        <input class="form-control me-2" type="search" name="search" placeholder="Search recipes" aria-label="Search" value="<?php echo htmlspecialchars($search_term); ?>">
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

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1>
                    <?php if (!empty($search_term)): ?>
                        Search Results: "<?php echo htmlspecialchars($search_term); ?>"
                    <?php else: ?>
                        All Recipes
                    <?php endif; ?>
                </h1>
                <p class="text-muted">
                    <?php echo $total_recipes; ?> recipe<?php echo ($total_recipes != 1) ? 's' : ''; ?> found
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="add_recipe.php" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i>Add New Recipe
                </a>
            </div>
        </div>

        <!-- Filter and Sort Options -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form class="row g-3" action="recipes.php" method="GET">
                            <?php if (!empty($search_term)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                            <?php endif; ?>
                            <div class="col-md-6">
                                <label for="sort" class="form-label">Sort By</label>
                                <select class="form-select" id="sort" name="sort">
                                    <option value="newest">Newest First</option>
                                    <option value="oldest">Oldest First</option>
                                    <option value="name_asc">Name (A-Z)</option>
                                    <option value="name_desc">Name (Z-A)</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recipes Grid -->
        <?php if (empty($recipes)): ?>
            <div class="alert alert-info">
                <?php if (!empty($search_term)): ?>
                    No recipes found matching "<?php echo htmlspecialchars($search_term); ?>". <a href="recipes.php">View all recipes</a>
                <?php else: ?>
                    No recipes available yet. <a href="add_recipe.php">Add your first recipe</a>!
                <?php endif; ?>
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
                                    <small class="text-muted">By <a href="user_recipes.php?user_id=<?php echo $recipe['user_id']; ?>"><?php echo htmlspecialchars($recipe['author_name']); ?></a></small>
                                </p>
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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Recipe pagination" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
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