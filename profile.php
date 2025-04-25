<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user information
$stmt = $conn->prepare("SELECT name, email, description FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Count user's recipes
$stmt = $conn->prepare("SELECT COUNT(*) as recipe_count FROM recipes WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$recipe_count = $result->fetch_assoc()['recipe_count'];
$stmt->close();

// Process form submission to update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $description = trim($_POST['description']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required fields";
    } else {
        // Check if email is already in use by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error_message = "Email is already in use by another account";
        } else {
            // Process password change if requested
            $password_update = false;
            if (!empty($current_password) && !empty($new_password)) {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $stored_password = $result->fetch_assoc()['password'];
                
                if (!password_verify($current_password, $stored_password)) {
                    $error_message = "Current password is incorrect";
                } elseif ($new_password !== $confirm_password) {
                    $error_message = "New passwords do not match";
                } elseif (strlen($new_password) < 8) {
                    $error_message = "New password must be at least 8 characters long";
                } else {
                    $password_update = true;
                }
            }
            
            if (empty($error_message)) {
                if ($password_update) {
                    // Update profile with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, description = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $name, $email, $description, $hashed_password, $user_id);
                } else {
                    // Update profile without changing password
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, description = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $email, $description, $user_id);
                }
                
                if ($stmt->execute()) {
                    $success_message = "Profile updated successfully";
                    // Update session variables
                    $_SESSION['user_name'] = $name;
                    // Refresh user data
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $user['description'] = $description;
                } else {
                    $error_message = "Error updating profile: " . $conn->error;
                }
            }
        }
    }
}

// Get user's recipes
$stmt = $conn->prepare("SELECT id, food_name, image, created_at FROM recipes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_recipes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Recipe App</title>
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
                            <li><a class="dropdown-item active" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="my_recipes.php">My Recipes</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Section -->
    <section class="container py-5">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="profile-pic mb-4">
                            <i class="fas fa-user-circle fa-6x text-primary"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="d-flex justify-content-center mt-3">
                            <div class="me-3 text-center">
                                <h5><?php echo $recipe_count; ?></h5>
                                <small class="text-muted">Recipes</small>
                            </div>
                            <div class="ms-3 text-center">
                                <h5>
                                    <?php
                                    // Count comments by user
                                    $stmt = $conn->prepare("SELECT COUNT(*) as comment_count FROM comments WHERE user_id = ?");
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    echo $result->fetch_assoc()['comment_count'];
                                    $stmt->close();
                                    ?>
                                </h5>
                                <small class="text-muted">Comments</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Recipes Card -->
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Recent Recipes</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if (empty($recent_recipes)): ?>
                                <li class="list-group-item">
                                    <p class="mb-0 text-muted">You haven't added any recipes yet.</p>
                                </li>
                            <?php else: ?>
                                <?php foreach ($recent_recipes as $recipe): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <?php if (!empty($recipe['image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="Recipe" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <img src="images/placeholder.jpg" alt="Recipe" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($recipe['food_name']); ?></h6>
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($recipe['created_at'])); ?></small>
                                            </div>
                                            <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="my_recipes.php" class="btn btn-outline-primary btn-sm">View All My Recipes</a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Edit Profile</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="profile.php">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">About Me</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($user['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <hr>
                            <h5>Change Password</h5>
                            <p class="text-muted small">Leave blank if you don't want to change your password</p>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">Password must be at least 8 characters long.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
                
                <!-- Account Settings Card -->
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Account Settings</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label d-block">Account Created</label>
                            <p class="mb-0">
                                <?php 
                                $stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $created_at = $result->fetch_assoc()['created_at'];
                                echo date('F d, Y', strtotime($created_at));
                                $stmt->close();
                                ?>
                            </p>
                        </div>
                        
                        <div class="text-danger">
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                <i class="fas fa-exclamation-triangle me-1"></i> Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Warning!</p>
                    <p>Deleting your account is permanent and cannot be undone. All of your recipes and comments will be removed.</p>
                    <p>Are you sure you want to delete your account?</p>
                    <form id="deleteAccountForm" action="delete_account.php" method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">Enter your password to confirm:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="deleteAccountForm" class="btn btn-danger">Delete Account</button>
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
</body>
</html>