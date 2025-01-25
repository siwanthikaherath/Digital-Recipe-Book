<?php
// Configuration
$to = 'sanduniherath0516@gmail.com';
$subject_prefix = 'Digital Recipe App Contact Form: ';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Initialize variables
$name = $email = $subject = $message = '';
$errors = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate Name
    if (empty($_POST['name'])) {
        $errors[] = "Name is required";
    } else {
        $name = sanitize_input($_POST['name']);
        // Check if name contains only letters and spaces
        if (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
            $errors[] = "Only letters and spaces allowed in name";
        }
    }

    // Validate Email
    if (empty($_POST['email'])) {
        $errors[] = "Email is required";
    } else {
        $email = sanitize_input($_POST['email']);
        // Check if email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
    }

    // Validate Subject
    $allowed_subjects = ['recipe', 'technical', 'feedback'];
    if (empty($_POST['subject']) || !in_array($_POST['subject'], $allowed_subjects)) {
        $errors[] = "Please select a valid subject";
    } else {
        $subject = sanitize_input($_POST['subject']);
    }

    // Validate Message
    if (empty($_POST['message'])) {
        $errors[] = "Message is required";
    } else {
        $message = sanitize_input($_POST['message']);
        // Optional: Check message length
        if (strlen($message) < 10) {
            $errors[] = "Message is too short";
        }
    }

    // Check Terms Checkbox
    if (empty($_POST['terms'])) {
        $errors[] = "You must accept the terms and conditions";
    }

    // If no errors, send email
    if (empty($errors)) {
        // Prepare email content
        $email_content = "Name: $name\n";
        $email_content .= "Email: $email\n";
        $email_content .= "Subject Type: $subject\n\n";
        $email_content .= "Message:\n$message";

        // Additional headers
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Attempt to send email
        if (mail($to, $subject_prefix . ucfirst($subject), $email_content, $headers)) {
            $success_message = "Your message has been sent successfully!";
        } else {
            $errors[] = "Message sending failed. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Recipe App - Contact Us</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff6b6b;
            --secondary-color: #4ecdc4;
            --background-color: #f9f9f9;
        }
        body {
            font-family: 'Arial', sans-serif;
            background: url('https://starhospitals.in/build/assets/contact-caf2cbb3.webp') no-repeat;
            background-size: cover;
            color: #333;
        }
        .contact-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
        }
        .success-message {
            color: #28a745;
            margin-bottom: 15px;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255,107,107,0.25);
        }
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #ff5252;
            transform: translateY(-3px);
        }
    </style>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header Section Starts -->
    <section class="header">
        <a href="home.html" class="logo">Digital Recipe App</a>
    
        <nav class="navbar">
            <a href="home.html">Home</a>
            <a href="signin.php">Add Recipe</a>
            <a href="recipies.html">Search Recipes</a>
            <a href="contactUs.php">Contact US</a>
            <a href="about.html">About Us</a>
            <a href="signin.php"><button class="loginbtn">Login</button></a>
        </nav>
    
        <div id="menu-btn" class="fas fa-bars" aria-label="Menu"></div>
    </section>

    

    <div class="container">
        <div class="contact-container">
            <?php
            // Display errors if any
            if (!empty($errors)) {
                echo '<div class="error-message alert alert-danger">';
                foreach ($errors as $error) {
                    echo "<p>$error</p>";
                }
                echo '</div>';
            }

            // Display success message
            if (isset($success_message)) {
                echo '<div class="success-message alert alert-success">' . $success_message . '</div>';
            }
            ?>

            <form id="contactForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <h2 class="text-center mb-4"><b>Reach our Team</b></h2>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <select class="form-select" id="subject" name="subject" required>
                        <option value="">Select Issue Type</option>
                        <option value="recipe" <?php echo ($subject == 'recipe') ? 'selected' : ''; ?>>Recipe Suggestion</option>
                        <option value="technical" <?php echo ($subject == 'technical') ? 'selected' : ''; ?>>Technical Issue</option>
                        <option value="feedback" <?php echo ($subject == 'feedback') ? 'selected' : ''; ?>>General Feedback</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="4" required><?php echo htmlspecialchars($message); ?></textarea>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms">I accept the Terms & Conditions</label>
                </div>
                
                <button type="submit" class="btn btn-submit w-100">Send Message</button>
            </form>
        </div>
    </div>
     <!-- Contact Section Starts -->
    <section class="contact-section py-5 bg-light">
        <div class="container text-center">
            <h2>Contact Us</h2>
            <div class="row justify-content-center gap-3">
                <div class="col-md-3">
                    <i class="fas fa-envelope fa-2x"></i>
                    <p><a href="mailto:sanduniherath0516.com">Email Us</a></p>
                </div>
                <div class="col-md-3">
                    <i class="fab fa-whatsapp fa-2x"></i>
                    <p><a href="https://wa.me/+94765875407" target="_blank">Start New Chat</a></p>
                </div>
                <div class="col-md-3">
                    <i class="fas fa-phone-alt fa-2x"></i>
                    <p><a href="tel:+94765875407">Call Us</a></p>
                </div>
                <div class="col-md-3">
                    <i class="fas fa-map-marker-alt fa-2x"></i>
                    <p><a href="https://maps.google.com/?q=your+office+address" target="_blank">Find Our Office</a></p>
                </div>
            </div>
        </div>
    </section>
    <!-- Contact Section Ends -->

    <!-- Footer Section Starts -->
<section class="footer py-4 bg-dark text-light">
    <div class="container">
        <!-- Quick Links and Social Icons -->
        
         <div class="quickLinks mb-3">
            <a href="home.html" class="text-decoration-none text-light">Home</a> | 
            <a href="recipies.html" class="text-decoration-none text-light">Search Recipe</a> |  
            <a href="signin.php" class="text-decoration-none text-light">Add Recipe</a> | 
            <a href="signin.php" class="text-decoration-none text-light">Login</a>

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
                <a href="contactUs.php">Contact Us</a>
            
        </div>
    </div>
</section>
<!-- Footer Section Ends -->


    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!--Javacript file-->
    <script src="js/script.js"></script>       
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const contactForm = document.getElementById('contactForm');
            
            contactForm.addEventListener('submit', function(event) {
                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('email').value.trim();
                const subject = document.getElementById('subject').value;
                const message = document.getElementById('message').value.trim();
                
                if (!name || !email || !subject || !message) {
                    event.preventDefault();
                    alert('Please fill out all fields.');
                }
            });
        });
    </script>
</body>
</html>