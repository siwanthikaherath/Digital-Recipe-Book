<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Recipe App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
      <!-- Custom CSS -->
      <link rel="stylesheet" href="../css/style.css">
      <link rel="stylesheet" href="../css/recipies.css">

</head>
<body>
    <section class="header">
        <a href="../home.html" class="logo">Digital Recipe App</a>
    
        <nav class="navbar">
            <a href="../home.html">Home</a>
            <a href="../auth/signin.php">Add Recipe</a>
            <a href="recipies.php">Search Recipes</a>
            <a href="../auth/contactUs.php">Contact US</a>
            <a href="../about.html">About Us</a>
            <a href="../auth/signin.php"><button class="loginbtn">Login</button></a>
        </nav>
    
        <div id="menu-btn" class="fas fa-bars" aria-label="Menu"></div>
    </section>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-6 offset-md-3 search-container">
                <div class="search-input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search recipes by name...">
                    <button id="searchButton" class="btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <div id="recipeGrid" class="recipe-grid"></div>
    </div>

    <!-- Recipe Modal -->
    <div class="modal fade" id="recipeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recipeModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="recipeModalBody"></div>
            </div>
        </div>
    </div>
    <!-- Footer Section Starts -->
<section class="footer py-4 bg-dark text-light">
    <div class="container">
        <!-- Quick Links and Social Icons -->
        
         <div class="quickLinks mb-3">
            <a href="../home.html" class="text-decoration-none text-light">Home</a> | 
            <a href="../includes/recipies.php" class="text-decoration-none text-light">Search Recipe</a> |  
            <a href="../auth/signin.php" class="text-decoration-none text-light">Add Recipe</a> | 
            <a href="../auth/signin.php" class="text-decoration-none text-light">Login</a>

        </div>

        <div class="social-icons mb-3">
            <a href="https://www.facebook.com" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://www.instagram.com" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://www.twitter.com" target="_blank" title="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://www.linkedin.com" target="_blank" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            <a href="https://www.youtube.com" target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>
        </div>
        
        <!-- Horizontal line dividing sections -->
        <div class="line"></div>

        
        

        <!-- Credit Section -->
        <div class="credit">
            © <span>Digital Recipe App</span> | All Rights Reserved! | Sanduni Herath |
                <a href="#">Privacy Policy</a> | 
                <a href="#">Terms of Service</a> | 
                <a href="../contactUs.php">Contact Us</a>
            
        </div>
    </div>
</section>
<!-- Footer Section Ends -->


    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!--Javacript file-->
    <script src="../js/script.js"></script>
    <script src="../js/recipies.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    
</body>
</html>
