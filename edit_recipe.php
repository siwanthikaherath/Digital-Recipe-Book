<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Get user information
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Check if recipe ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my_recipes.php');
    exit;
}

$recipe_id = $_GET['id'];

// Get recipe details and verify ownership
$stmt = $conn->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $recipe_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Recipe not found or user doesn't own it
    $_SESSION['error_message'] = "You don't have permission to edit this recipe or it doesn't exist.";
    header('Location: my_recipes.php');
    exit;
}

$recipe = $result->fetch_assoc();
$stmt->close();

// Parse JSON data
$ingredients = json_decode($recipe['ingredients'], true);
$steps = json_decode($recipe['steps'], true);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $food_name = trim($_POST['food_name']);
    
    if (empty($food_name)) {
        $errors[] = "Recipe name is required.";
    }
    
    // Process ingredients
    $new_ingredients = [];
    if (isset($_POST['ingredients']) && is_array($_POST['ingredients'])) {
        foreach ($_POST['ingredients'] as $ingredient) {
            $ingredient = trim($ingredient);
            if (!empty($ingredient)) {
                $new_ingredients[] = $ingredient;
            }
        }
    }
    
    if (empty($new_ingredients)) {
        $errors[] = "At least one ingredient is required.";
    }
    
    // Process steps
    $new_steps = [];
    if (isset($_POST['steps']) && is_array($_POST['steps'])) {
        foreach ($_POST['steps'] as $step) {
            $step = trim($step);
            if (!empty($step)) {
                $new_steps[] = $step;
            }
        }
    }
    
    if (empty($new_steps)) {
        $errors[] = "At least one step is required.";
    }
    
    // Process image upload if provided
    $image_path = $recipe['image']; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_dir = 'uploads/recipes/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('recipe_') . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } elseif ($_FILES['image']['size'] > 5000000) { // 5MB limit
            $errors[] = "File size should be less than 5MB.";
        } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_path = $upload_path;
            
            // Delete old image if it exists and is not the default
            if (!empty($recipe['image']) && $recipe['image'] !== 'images/placeholder.jpg' && file_exists($recipe['image'])) {
                unlink($recipe['image']);
            }
        } else {
            $errors[] = "Failed to upload image. Please try again.";
        }
    }
    
    // If no errors, update recipe in database
    if (empty($errors)) {
        $ingredients_json = json_encode($new_ingredients);
        $steps_json = json_encode($new_steps);
        
        $stmt = $conn->prepare("UPDATE recipes SET food_name = ?, ingredients = ?, steps = ?, image = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssssii", $food_name, $ingredients_json, $steps_json, $image_path, $recipe_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Recipe updated successfully!";
            
            // Refresh recipe data
            $stmt = $conn->prepare("SELECT * FROM recipes WHERE id = ?");
            $stmt->bind_param("i", $recipe_id);
            $stmt->execute();
            $recipe = $stmt->get_result()->fetch_assoc();
            
            // Update parsed JSON data
            $ingredients = json_decode($recipe['ingredients'], true);
            $steps = json_decode($recipe['steps'], true);
        } else {
            $errors[] = "Failed to update recipe: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Recipe - Recipe App</title>
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

    <!-- Edit Recipe Form -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Edit Recipe</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="edit_recipe.php?id=<?php echo $recipe_id; ?>" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label for="food_name" class="form-label">Recipe Name</label>
                                <input type="text" class="form-control" id="food_name" name="food_name" value="<?php echo htmlspecialchars($recipe['food_name']); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Current Image</label>
                                <div class="mb-3">
                                    <?php if (!empty($recipe['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="<?php echo htmlspecialchars($recipe['food_name']); ?>" class="img-thumbnail" style="max-height: 200px;">
                                    <?php else: ?>
                                        <img src="images/placeholder.jpg" alt="Recipe placeholder" class="img-thumbnail" style="max-height: 200px;">
                                    <?php endif; ?>
                                </div>
                                <label for="image" class="form-label">Update Image (Optional)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">Leave empty to keep the current image.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Ingredients</label>
                                <div id="ingredients_container">
                                    <?php 
                                    if (is_array($ingredients)) {
                                        foreach ($ingredients as $index => $ingredient) {
                                            $ingredient_value = is_array($ingredient) ? (isset($ingredient['name']) ? $ingredient['name'] : json_encode($ingredient)) : $ingredient;
                                    ?>
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="ingredients[]" value="<?php echo htmlspecialchars($ingredient_value); ?>" required>
                                            <button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>
                                        </div>
                                    <?php 
                                        }
                                    } else { 
                                    ?>
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="ingredients[]" required>
                                            <button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>
                                        </div>
                                    <?php } ?>
                                </div>
                                <button type="button" class="btn btn-outline-primary mt-2" id="add_ingredient">
                                    <i class="fas fa-plus me-1"></i> Add Ingredient
                                </button>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Preparation Steps</label>
                                <div id="steps_container">
                                    <?php 
                                    if (is_array($steps)) {
                                        foreach ($steps as $index => $step) {
                                            $step_value = is_array($step) ? json_encode($step) : $step;
                                    ?>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text step-number"><?php echo $index + 1; ?></span>
                                            <textarea class="form-control" name="steps[]" rows="2" required><?php echo htmlspecialchars($step_value); ?></textarea>
                                            <button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>
                                        </div>
                                    <?php 
                                        }
                                    } else { 
                                    ?>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text step-number">1</span>
                                            <textarea class="form-control" name="steps[]" rows="2" required></textarea>
                                            <button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>
                                        </div>
                                    <?php } ?>
                                </div>
                                <button type="button" class="btn btn-outline-primary mt-2" id="add_step">
                                    <i class="fas fa-plus me-1"></i> Add Step
                                </button>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="view_recipe.php?id=<?php echo $recipe_id; ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Recipe</button>
                            </div>
                        </form>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Add ingredient functionality
            document.getElementById('add_ingredient').addEventListener('click', function() {
                const container = document.getElementById('ingredients_container');
                const newIngredient = document.createElement('div');
                newIngredient.className = 'input-group mb-2';
                newIngredient.innerHTML = `
                    <input type="text" class="form-control" name="ingredients[]" required>
                    <button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>
                `;
                container.appendChild(newIngredient);
                
                // Focus on the new input
                newIngredient.querySelector('input').focus();
                
                // Add event listener to the new remove button
                addRemoveEventListener(newIngredient.querySelector('.remove-item'));
            });
            
            // Add step functionality
            document.getElementById('add_step').addEventListener('click', function() {
                const container = document.getElementById('steps_container');
                const stepCount = container.children.length + 1;
                
                const newStep = document.createElement('div');
                newStep.className = 'input-group mb-2';
                newStep.innerHTML = `
                    <span class="input-group-text step-number">${stepCount}</span>
                    <textarea class="form-control" name="steps[]" rows="2" required></textarea>
                    <button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>
                `;
                container.appendChild(newStep);
                
                // Focus on the new textarea
                newStep.querySelector('textarea').focus();
                
                // Add event listener to the new remove button
                addRemoveEventListener(newStep.querySelector('.remove-item'));
            });
            
            // Add remove functionality to existing items
            document.querySelectorAll('.remove-item').forEach(function(button) {
                addRemoveEventListener(button);
            });
            
            function addRemoveEventListener(button) {
                button.addEventListener('click', function() {
                    const parentElement = this.parentElement;
                    const container = parentElement.parentElement;
                    
                    // Don't allow removing if it's the last item
                    if (container.children.length > 1) {
                        parentElement.remove();
                        
                        // Update step numbers if this is a step container
                        if (container.id === 'steps_container') {
                            updateStepNumbers();
                        }
                    } else {
                        // If it's the last item, just clear the value
                        const input = parentElement.querySelector('input, textarea');
                        if (input) {
                            input.value = '';
                        }
                    }
                });
            }
            
            function updateStepNumbers() {
                const stepContainers = document.querySelectorAll('#steps_container > div');
                stepContainers.forEach(function(container, index) {
                    const stepNumber = container.querySelector('.step-number');
                    if (stepNumber) {
                        stepNumber.textContent = index + 1;
                    }
                });
            }
        });
    </script>
</body>
</html>