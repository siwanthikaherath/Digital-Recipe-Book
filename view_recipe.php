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

// Check if recipe ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: recipes.php');
    exit;
}

$recipe_id = $_GET['id'];

// Get recipe details
$stmt = $conn->prepare("SELECT r.*, u.name as author_name FROM recipes r 
                      LEFT JOIN users u ON r.user_id = u.id 
                      WHERE r.id = ?");
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Recipe not found
    header('Location: recipes.php');
    exit;
}

$recipe = $result->fetch_assoc();
$stmt->close();

// Parse JSON data - Fixed proper JSON parsing
$ingredients = json_decode($recipe['ingredients'], true);
$steps = json_decode($recipe['steps'], true);

// Get comments for this recipe
$stmt = $conn->prepare("SELECT c.id, c.recipe_id, c.user_id, c.username, c.comment, c.created_at, 
                      u.name as commenter_name
                      FROM comments c 
                      LEFT JOIN users u ON c.user_id = u.id 
                      WHERE c.recipe_id = ? 
                      ORDER BY c.created_at DESC");
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Process comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment_text = trim($_POST['comment']);
    
    if (!empty($comment_text)) {
        $stmt = $conn->prepare("INSERT INTO comments (recipe_id, user_id, username, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $recipe_id, $user_id, $user['name'], $comment_text);
        
        if ($stmt->execute()) {
            // Refresh page to show new comment
            header("Location: view_recipe.php?id=$recipe_id");
            exit;
        }
        $stmt->close();
    }
}

// Check if current user is the author of this recipe
$is_author = ($recipe['user_id'] == $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['food_name']); ?> - Recipe App</title>
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
                            <li><a class="dropdown-item" href="my_recipes.php">My Recipes</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Recipe Details -->
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8">
                <div class="mb-4">
                    <h1 class="recipe-title"><?php echo htmlspecialchars($recipe['food_name']); ?></h1>
                    <div class="d-flex align-items-center mb-3">
                        <span class="me-3">By <a href="user_recipes.php?user_id=<?php echo $recipe['user_id']; ?>"><?php echo htmlspecialchars($recipe['author_name']); ?></a></span>
                        <span class="text-muted">Posted on <?php echo date('F d, Y', strtotime($recipe['created_at'])); ?></span>
                    </div>
                    <?php if ($is_author): ?>
                    <div class="mb-3">
                        <a href="edit_recipe.php?id=<?php echo $recipe_id; ?>" class="btn btn-outline-primary me-2">
                            <i class="fas fa-edit me-1"></i> Edit Recipe
                        </a>
                        <a href="delete_recipe.php?id=<?php echo $recipe_id; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this recipe?')">
                            <i class="fas fa-trash-alt me-1"></i> Delete Recipe
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="recipe-image mb-4">
                    <?php if (!empty($recipe['image'])): ?>
                        <img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="<?php echo htmlspecialchars($recipe['food_name']); ?>" class="img-fluid rounded">
                    <?php else: ?>
                        <img src="images/placeholder.jpg" alt="Recipe placeholder" class="img-fluid rounded">
                    <?php endif; ?>
                </div>
                
                <div class="mb-5">
                    <h3 class="mb-3">Ingredients</h3>
                    <div class="card">
                        <div class="card-body">
                            <ul class="list-group list-group-flush ingredients-list">
                                <?php 
                                // Fixed ingredient display to handle arrays properly
                                if (is_array($ingredients)) {
                                    foreach ($ingredients as $ingredient) {
                                        // Check if the ingredient is a string or an array
                                        if (is_string($ingredient)) {
                                            echo "<li class='list-group-item'>" . htmlspecialchars($ingredient) . "</li>";
                                        } elseif (is_array($ingredient)) {
                                            // If it's an array, check for a 'name' key or just display the first value
                                            if (isset($ingredient['name'])) {
                                                echo "<li class='list-group-item'>" . htmlspecialchars($ingredient['name']);
                                                
                                                // If there's an amount or any other info, add it
                                                if (isset($ingredient['amount'])) {
                                                    echo " - " . htmlspecialchars($ingredient['amount']);
                                                }
                                                
                                                echo "</li>";
                                            } else {
                                                // Just display the first value if no 'name' key exists
                                                echo "<li class='list-group-item'>" . 
                                                      htmlspecialchars(reset($ingredient) ?: json_encode($ingredient)) . 
                                                     "</li>";
                                            }
                                        } else {
                                            // Fallback for any other data type
                                            echo "<li class='list-group-item'>Ingredient: " . 
                                                  htmlspecialchars((string)$ingredient) . 
                                                 "</li>";
                                        }
                                    }
                                } else {
                                    echo "<li class='list-group-item'>No ingredients available</li>";
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="mb-5">
                    <h3 class="mb-3">Preparation Steps</h3>
                    <div class="card">
                        <div class="card-body">
                            <ol class="steps-list">
                                <?php 
                                // Fixed steps display with better error handling
                                if (is_array($steps)) {
                                    foreach ($steps as $step) {
                                        if (is_string($step)) {
                                            echo "<li class='mb-3'>" . htmlspecialchars($step) . "</li>";
                                        } elseif (is_array($step)) {
                                            // Handle steps that might be in arrays
                                            echo "<li class='mb-3'>" . htmlspecialchars(json_encode($step)) . "</li>";
                                        } else {
                                            echo "<li class='mb-3'>Step: " . htmlspecialchars((string)$step) . "</li>";
                                        }
                                    }
                                } else {
                                    echo "<li class='mb-3'>No steps available</li>";
                                }
                                ?>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <!-- Comments Section -->
                <div class="comments-section">
                    <h3 class="mb-4">Comments (<?php echo count($comments); ?>)</h3>
                    
                    <!-- Comment Form -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="POST" action="view_recipe.php?id=<?php echo $recipe_id; ?>">
                                <div class="mb-3">
                                    <label for="comment" class="form-label">Add a Comment</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Post Comment</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Comments List -->
                    <?php if (!empty($comments)): ?>
                        <div class="comments-list">
                            <?php foreach ($comments as $comment): ?>
                                <div class="card mb-3 comment-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="comment-author">
                                                <strong>
                                                <?php 
                                                // Fixed comment author display
                                                if (!empty($comment['commenter_name'])) {
                                                    echo htmlspecialchars($comment['commenter_name']);
                                                } elseif (!empty($comment['username'])) {
                                                    echo htmlspecialchars($comment['username']);
                                                } else {
                                                    echo 'Anonymous';
                                                }
                                                ?>
                                                </strong>
                                            </div>
                                            <div class="comment-date text-muted small">
                                                <?php echo date('F d, Y g:i A', strtotime($comment['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="comment-text">
                                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No comments yet. Be the first to comment!</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Recipe Details</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-utensils me-2"></i> Ingredients</span>
                                <span class="badge bg-primary rounded-pill"><?php echo is_array($ingredients) ? count($ingredients) : 0; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-list-ol me-2"></i> Steps</span>
                                <span class="badge bg-primary rounded-pill"><?php echo is_array($steps) ? count($steps) : 0; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-comment me-2"></i> Comments</span>
                                <span class="badge bg-primary rounded-pill"><?php echo count($comments); ?></span>
                            </li>
                        </ul>
                        <div class="d-grid gap-2 mt-3">
                            <a href="#" class="btn btn-outline-success" id="shareRecipe">
                                <i class="fas fa-share-alt me-2"></i> Share Recipe
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
    <script>
        // Share recipe functionality
        document.getElementById('shareRecipe').addEventListener('click', function(e) {
            e.preventDefault();
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($recipe['food_name']); ?>',
                    text: 'Check out this delicious recipe!',
                    url: window.location.href
                })
                .catch(console.error);
            } else {
                // Fallback for browsers that don't support Web Share API
                const tempInput = document.createElement('input');
                tempInput.value = window.location.href;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                alert('Recipe link copied to clipboard!');
            }
        });
    </script>
</body>
</html>