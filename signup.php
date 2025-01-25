<?php
include 'db.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $description = trim($_POST['description']);

    if (empty($name) || empty($email) || empty($password)) {
        $message = "Please fill out all required fields.";
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $sql = "INSERT INTO users (name, email, password, description) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$name, $email, $hashedPassword, $description])) {
                $message = "Registration successful! <a href='signin.php'>Go to Sign in</a>";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "This email address is already registered.";
            } else {
                $message = "Registration failed. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Hub - Join Our Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #2196F3;
            --background-color: #f0f4f8;
            --text-color: #333;
            --accent-color: #FF5722;
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            background: url('https://thumbs.dreamstime.com/b/recipe-template-29639531.jpg') no-repeat;
            background-size: cover;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            margin: 0;
            color: var(--text-color);
        }

        .signup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 2.5rem;
            max-width: 500px;
            width: 100%;
            margin: auto;
            position: relative;
            overflow: hidden;
        }

        .signup-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .form-group label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-group input, 
        .form-group textarea {
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            width: 100%;
            background-color: rgba(76, 175, 80, 0.05);
            transition: all 0.3s ease;
        }

        .form-group input:focus, 
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }

        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .btn:hover {
            background-color: #45a049;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        @media (max-width: 576px) {
            .signup-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="signup-container">
            <h1>Create Your Digital Recipe App Account</h1>
            
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'successful') !== false ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form id="signupForm" method="post">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                </div>
                
                <div class="form-group">
                    <label for="description">About You</label>
                    <textarea id="description" name="description" rows="4" placeholder="Tell us a bit about your culinary interests"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn">Create Account</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById("signupForm").addEventListener("submit", function (event) {
            const name = document.getElementById("name").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value.trim();
        
            // Enhanced client-side validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const passwordRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;

            if (!name) {
                alert("Please enter your name.");
                event.preventDefault();
                return;
            }

            if (!emailRegex.test(email)) {
                alert("Please enter a valid email address.");
                event.preventDefault();
                return;
            }

            if (!passwordRegex.test(password)) {
                alert("Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.");
                event.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>