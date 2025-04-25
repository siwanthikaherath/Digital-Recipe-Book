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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Recipe App</title>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <form class="d-flex me-2" action="search.php" method="GET">
                        <input class="form-control me-2" type="search" name="q" placeholder="Search recipes" aria-label="Search">
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

    <!-- About Header Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold">About Recipe App</h1>
                    <p class="lead">A platform for food enthusiasts to discover, share, and celebrate culinary creativity</p>
                </div>
                <div class="col-md-6 text-end">
                    <img src="https://c1.wallpaperflare.com/preview/513/870/409/cook-healthy-food-eat.jpg" alt="Cooking illustration" class="img-fluid rounded" onerror="this.src='images/placeholder.jpg'">
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="container py-5">
        <div class="row mb-5">
            <div class="col-md-12">
                <h2 class="section-title">Our Story</h2>
                <div class="row">
                    <div class="col-md-8">
                        <p>Recipe App began as a passion project by Sanduni Herath in 2023, driven by a love for cooking and a desire to create a space where food enthusiasts could connect and share their culinary creations.</p>
                        <p>What started as a simple recipe-sharing platform has evolved into a vibrant community where users from around the world can discover new dishes, share their own recipes, and engage with fellow food lovers.</p>
                        <p>Our mission is to make cooking accessible and enjoyable for everyone, from beginners to experienced chefs. We believe that food brings people together, and our platform aims to facilitate that connection through the universal language of delicious meals.</p>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body bg-light">
                                <h5 class="card-title">Our Vision</h5>
                                <p class="card-text">To create the most inclusive and innovative recipe-sharing platform where culinary creativity knows no bounds.</p>
                            </div>
                        </div>
                        <div class="card mt-3">
                            <div class="card-body bg-light">
                                <h5 class="card-title">Our Mission</h5>
                                <p class="card-text">To inspire home cooks of all skill levels to explore new cuisines, techniques, and flavor combinations.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">Key Features</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-search fa-3x text-primary mb-3"></i>
                            <h4>Discover Recipes</h4>
                            <p>Browse through hundreds of recipes across various cuisines, dietary preferences, and skill levels.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                            <h4>Share Your Creations</h4>
                            <p>Easily upload your own recipes, complete with ingredients, instructions, and mouth-watering photos.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-comments fa-3x text-primary mb-3"></i>
                            <h4>Engage & Connect</h4>
                            <p>Comment on recipes, share cooking tips, and connect with other food enthusiasts in our community.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Developer Section -->
    <section class="container py-5">
        <h2 class="section-title mb-5">Meet the Developer</h2>
        <div class="row align-items-center">
            <div class="col-md-4 mb-4 mb-md-0">
                <img src="https://i.pinimg.com/236x/95/11/23/9511233395fdbb5e2049095a3225d110.jpg" alt="Sanduni Herath" class="img-fluid rounded-circle shadow" onerror="this.src='images/profile-placeholder.jpg'" style="max-width: 250px;">
            </div>
            <div class="col-md-8">
                <h3>Sanduni Herath</h3>
                <p class="lead">Web Developer & Food Enthusiast</p>
                <p>Sanduni is a passionate web developer with a love for creating interactive and user-friendly applications. Having studied Computer Science, she combines her technical skills with her passion for cooking to bring Recipe App to life.</p>
                <p>When not coding or cooking, Sanduni enjoys exploring new restaurants, traveling, and experimenting with fusion cuisine in her home kitchen.</p>
                <div class="mt-3">
                    <a href="https://github.com/siwanthikaherath" target="_blank" class="btn btn-outline-dark me-2"><i class="fab fa-github"></i> GitHub</a>
                    <a href="https://www.linkedin.com/in/sanduni-herath-7a7256330/" target="_blank" class="btn btn-outline-primary me-2"><i class="fab fa-linkedin"></i> LinkedIn</a>
                    <a href="mailto:sanduniherath0516@gmail.com" class="btn btn-outline-secondary"><i class="fas fa-envelope"></i> Email</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Technologies Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">Technologies Used</h2>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="row text-center g-4">
                                <div class="col-4 col-md-2">
                                    <i class="fab fa-php fa-3x text-primary"></i>
                                    <p class="mt-2">PHP</p>
                                </div>
                                <div class="col-4 col-md-2">
                                    <i class="fas fa-database fa-3x text-primary"></i>
                                    <p class="mt-2">MySQL</p>
                                </div>
                                <div class="col-4 col-md-2">
                                    <i class="fab fa-html5 fa-3x text-primary"></i>
                                    <p class="mt-2">HTML5</p>
                                </div>
                                <div class="col-4 col-md-2">
                                    <i class="fab fa-css3-alt fa-3x text-primary"></i>
                                    <p class="mt-2">CSS3</p>
                                </div>
                                <div class="col-4 col-md-2">
                                    <i class="fab fa-js fa-3x text-primary"></i>
                                    <p class="mt-2">JavaScript</p>
                                </div>
                                <div class="col-4 col-md-2">
                                    <i class="fab fa-bootstrap fa-3x text-primary"></i>
                                    <p class="mt-2">Bootstrap</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact CTA -->
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card bg-primary text-white">
                    <div class="card-body p-5 text-center">
                        <h3>Get in Touch</h3>
                        <p class="lead">Have questions, suggestions, or just want to say hello? We'd love to hear from you!</p>
                        <a href="contact.php" class="btn btn-light btn-lg mt-3">Contact Us</a>
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