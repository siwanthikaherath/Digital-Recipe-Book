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

// Delete recipe if requested
if (isset($_POST['delete_recipe']) && isset($_POST['recipe_id'])) {
    $recipe_id = $_POST['recipe_id'];
    
    // Verify recipe belongs to user
    $stmt = $conn->prepare("SELECT id FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $recipe_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete recipe
        $delete_stmt = $conn->prepare("DELETE FROM recipes WHERE id = ?");
        $delete_stmt->bind_param("i", $recipe_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Set success message
        $_SESSION['success_message'] = "Recipe deleted successfully!";
    } else {
        $_SESSION['error_message'] = "You don't have permission to delete this recipe.";
    }
    $stmt->close();
    
    // Redirect to avoid form resubmission
    header('Location: my_recipes.php');
    exit;
}

// Get all recipes by the user
$stmt = $conn->prepare("SELECT id, food_name, image, created_at FROM recipes WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$myRecipes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Recipes - Recipe App</title>
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
                            <?php echo htmlspecialchars($user['name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item active" href="my_recipes.php">My Recipes</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="py-5 bg-light">
        <div class="container">
            <h1 class="text-center mb-4">My Recipes</h1>
            <p class="text-center lead">Manage your recipe collection</p>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- My Recipes Section -->
    <section class="container py-5">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>Your Recipe Collection</h2>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="add_recipe.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Recipe
                </a>
            </div>
        </div>

        <?php if (empty($myRecipes)): ?>
            <div class="alert alert-info" role="alert">
                <p class="mb-0">You haven't added any recipes yet. <a href="add_recipe.php">Click here</a> to add your first recipe!</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($myRecipes as $recipe): ?>
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
                                <p class="card-text"><small class="text-muted">
                                    Added on <?php echo date('M d, Y', strtotime($recipe['created_at'])); ?>
                                </small></p>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <div class="btn-group w-100" role="group">
                                    <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-outline-primary">View</a>
                                    <a href="edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-outline-secondary">Edit</a>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $recipe['id']; ?>">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal<?php echo $recipe['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $recipe['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $recipe['id']; ?>">Confirm Delete</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete the recipe "<?php echo htmlspecialchars($recipe['food_name']); ?>"?</p>
                                    <p class="text-danger">This action cannot be undone!</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <form method="post">
                                        <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                        <button type="submit" name="delete_recipe" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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