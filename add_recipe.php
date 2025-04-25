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

// Initialize variables for form data
$food_name = '';
$ingredients = [];
$steps = [];
$image = '';
$error_msg = '';
$success_msg = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $food_name = trim($_POST['food_name']);
    
    // Process ingredients (convert from form array to JSON)
    $ingredients_array = [];
    if (isset($_POST['ingredients']) && is_array($_POST['ingredients'])) {
        foreach ($_POST['ingredients'] as $ingredient) {
            if (!empty(trim($ingredient))) {
                $ingredients_array[] = trim($ingredient);
            }
        }
    }
    
    // Process steps (convert from form array to JSON)
    $steps_array = [];
    if (isset($_POST['steps']) && is_array($_POST['steps'])) {
        foreach ($_POST['steps'] as $step) {
            if (!empty(trim($step))) {
                $steps_array[] = trim($step);
            }
        }
    }
    
    // Validate inputs
    if (empty($food_name)) {
        $error_msg = 'Please enter a recipe name.';
    } elseif (count($ingredients_array) < 1) {
        $error_msg = 'Please add at least one ingredient.';
    } elseif (count($steps_array) < 1) {
        $error_msg = 'Please add at least one step.';
    } else {
        // Handle image upload if present
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
            $target_path = $upload_dir . $file_name;
            
            // Check file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = $target_path;
                } else {
                    $error_msg = 'Failed to upload image. Please try again.';
                }
            } else {
                $error_msg = 'Invalid image format. Please use JPG, PNG, or GIF.';
            }
        }
        
        // If no errors, insert recipe into database
        if (empty($error_msg)) {
            // Convert arrays to JSON for storage
            $ingredients_json = json_encode($ingredients_array);
            $steps_json = json_encode($steps_array);
            
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO recipes (food_name, ingredients, steps, image, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $food_name, $ingredients_json, $steps_json, $image_path, $user_id);
            
            if ($stmt->execute()) {
                $recipe_id = $stmt->insert_id;
                $success_msg = 'Recipe added successfully!';
                
                // Redirect to view the new recipe after a short delay
                header("Refresh: 2; URL=view_recipe.php?id=$recipe_id");
            } else {
                $error_msg = 'Error adding recipe. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Recipe - Recipe App</title>
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
                        <a class="nav-link active" href="add_recipe.php">Add Recipe</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <form class="d-flex me-2" action="recipes.php" method="GET">
                        <input class="form-control me-2" type="search" name="search" placeholder="Search recipes" aria-label="Search">
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
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">Add New Recipe</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success"><?php echo $success_msg; ?></div>
                        <?php endif; ?>
                        
                        <form action="add_recipe.php" method="POST" enctype="multipart/form-data">
                            <!-- Recipe Name -->
                            <div class="mb-4">
                                <label for="food_name" class="form-label">Recipe Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="food_name" name="food_name" value="<?php echo htmlspecialchars($food_name); ?>" required>
                            </div>
                            
                            <!-- Ingredients -->
                            <div class="mb-4">
                                <label class="form-label">Ingredients <span class="text-danger">*</span></label>
                                <div id="ingredients_container">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="ingredients[]" placeholder="Enter ingredient">
                                        <button type="button" class="btn btn-danger remove-field" disabled><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary mt-2" id="add_ingredient">
                                    <i class="fas fa-plus me-1"></i> Add Ingredient
                                </button>
                            </div>
                            
                            <!-- Preparation Steps -->
                            <div class="mb-4">
                                <label class="form-label">Preparation Steps <span class="text-danger">*</span></label>
                                <div id="steps_container">
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">1</span>
                                        <textarea class="form-control" name="steps[]" rows="2" placeholder="Enter step instructions"></textarea>
                                        <button type="button" class="btn btn-danger remove-step" disabled><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary mt-2" id="add_step">
                                    <i class="fas fa-plus me-1"></i> Add Step
                                </button>
                            </div>
                            
                            <!-- Recipe Image -->
                            <div class="mb-4">
                                <label for="image" class="form-label">Recipe Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">Upload an image of your finished dish (optional)</div>
                                
                                <div class="mt-3 d-none" id="image_preview_container">
                                    <label class="form-label">Image Preview</label>
                                    <div class="text-center">
                                        <img id="image_preview" src="#" alt="Recipe preview" class="img-fluid rounded" style="max-height: 200px;">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="recipes.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Recipe
                                </button>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dynamic ingredient fields
            const ingredientsContainer = document.getElementById('ingredients_container');
            const addIngredientBtn = document.getElementById('add_ingredient');
            
            addIngredientBtn.addEventListener('click', function() {
                const newField = document.createElement('div');
                newField.className = 'input-group mb-2';
                newField.innerHTML = `
                    <input type="text" class="form-control" name="ingredients[]" placeholder="Enter ingredient">
                    <button type="button" class="btn btn-danger remove-field"><i class="fas fa-times"></i></button>
                `;
                ingredientsContainer.appendChild(newField);
                
                // Update the first field's remove button status
                updateRemoveButtonStatus();
                
                // Add event listener to the new remove button
                newField.querySelector('.remove-field').addEventListener('click', function() {
                    ingredientsContainer.removeChild(newField);
                    updateRemoveButtonStatus();
                });
            });
            
            // Dynamic step fields
            const stepsContainer = document.getElementById('steps_container');
            const addStepBtn = document.getElementById('add_step');
            
            addStepBtn.addEventListener('click', function() {
                const stepCount = stepsContainer.querySelectorAll('.input-group').length + 1;
                const newStep = document.createElement('div');
                newStep.className = 'input-group mb-2';
                newStep.innerHTML = `
                    <span class="input-group-text">${stepCount}</span>
                    <textarea class="form-control" name="steps[]" rows="2" placeholder="Enter step instructions"></textarea>
                    <button type="button" class="btn btn-danger remove-step"><i class="fas fa-times"></i></button>
                `;
                stepsContainer.appendChild(newStep);
                
                // Update the first step's remove button status
                updateRemoveStepButtonStatus();
                
                // Add event listener to the new remove button
                newStep.querySelector('.remove-step').addEventListener('click', function() {
                    stepsContainer.removeChild(newStep);
                    // Renumber the steps
                    renumberSteps();
                    updateRemoveStepButtonStatus();
                });
            });
            
            // Function to update remove button status for ingredients
            function updateRemoveButtonStatus() {
                const removeButtons = ingredientsContainer.querySelectorAll('.remove-field');
                if (removeButtons.length === 1) {
                    removeButtons[0].disabled = true;
                } else {
                    removeButtons.forEach(btn => btn.disabled = false);
                }
            }
            
            // Function to update remove button status for steps
            function updateRemoveStepButtonStatus() {
                const removeButtons = stepsContainer.querySelectorAll('.remove-step');
                if (removeButtons.length === 1) {
                    removeButtons[0].disabled = true;
                } else {
                    removeButtons.forEach(btn => btn.disabled = false);
                }
            }
            
            // Function to renumber steps
            function renumberSteps() {
                const steps = stepsContainer.querySelectorAll('.input-group');
                steps.forEach((step, index) => {
                    step.querySelector('.input-group-text').textContent = index + 1;
                });
            }
            
            // Image preview
            const imageInput = document.getElementById('image');
            const imagePreview = document.getElementById('image_preview');
            const previewContainer = document.getElementById('image_preview_container');
            
            imageInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        previewContainer.classList.remove('d-none');
                    }
                    
                    reader.readAsDataURL(file);
                } else {
                    previewContainer.classList.add('d-none');
                }
            });
        });
    </script>
</body>
</html>