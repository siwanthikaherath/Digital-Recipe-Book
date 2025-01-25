<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';
    header('Content-Type: application/json');
    
    try {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;

        if (!$email || !$password) {
            throw new Exception('Invalid credentials');
        }

        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('Invalid email or password');
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expires]);
            
            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
        }

        echo json_encode([
            'success' => true,
            'redirect' => 'add_recipe.php',
            'message' => 'Login successful'
        ]);
        exit;

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Digital Recipe App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #2196F3;
            --background-color: #f0f4f8;
            --text-color: #333;
            --error-color: #F44336;
            --success-color: #4CAF50;
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            background: url('https://png.pngtree.com/thumb_back/fw800/background/20241113/pngtree-antique-cooking-recipe-template-background-image_16571894.jpg') no-repeat;
            background-size: cover;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            margin: 0;
            color: var(--text-color);
        }

        .signin-container {
            max-width: 450px;
            margin: 2rem auto;
            padding: 2.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 15px;
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
            transform: perspective(1000px) rotateX(-10deg);
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: perspective(1000px) rotateX(-10deg) translateY(0); }
            50% { transform: perspective(1000px) rotateX(-10deg) translateY(-15px); }
        }

        .signin-container h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }

        .form-floating {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .form-control {
            border-color: rgba(0,0,0,0.1);
            padding: 0.75rem 1rem;
            background: rgba(76, 175, 80, 0.05);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary-color);
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .toggle-password:hover {
            opacity: 1;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #45a049;
            border-color: #45a049;
        }

        .error-message {
            color: var(--error-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
            animation: shake 0.4s linear;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .is-invalid {
            border-color: var(--error-color);
        }

        .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }
    </style>
    
</head>
<body>
   
    <div class="container">
        <div class="signin-container">
            <h2>Digital Recipe App <br> Sign In</h2>
            <div id="alertBox" class="alert d-none"></div>
            
            <form id="signinForm" novalidate>
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" placeholder="name@example.com" required>
                    <label for="email">Email address</label>
                    <div class="error-message"></div>
                </div>
                
                <div class="form-floating password-container">
                    <input type="password" class="form-control" id="password" placeholder="Password" required>
                    <label for="password">Password</label>
                    <i class="fas fa-eye toggle-password"></i>
                    <div class="error-message"></div>
                </div>
                
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    Sign In
                </button>
                
                <div class="text-center">
                    <div class="mt-2">
                        Don't have an account? <a href="signup.php" class="text-decoration-none text-primary">Sign up</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // The script remains the same as in the previous version
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('signinForm');
            const alertBox = document.getElementById('alertBox');
            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', () => {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                togglePassword.classList.toggle('fa-eye');
                togglePassword.classList.toggle('fa-eye-slash');
            });
            
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                if (!validateForm()) return;
                
                const spinner = document.querySelector('.spinner-border');
                const submitBtn = form.querySelector('button[type="submit"]');
                
                try {
                    spinner.classList.remove('d-none');
                    submitBtn.disabled = true;
                    
                    const formData = new FormData();
                    formData.append('email', document.getElementById('email').value);
                    formData.append('password', passwordInput.value);
                    formData.append('remember', document.getElementById('rememberMe').checked);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        window.location.href = result.redirect;
                    } else {
                        showAlert(result.message, 'danger');
                    }
                } catch (error) {
                    showAlert('An error occurred. Please try again.', 'danger');
                } finally {
                    spinner.classList.add('d-none');
                    submitBtn.disabled = false;
                }
            });
            
            function validateForm() {
                let isValid = true;
                const email = document.getElementById('email');
                const password = document.getElementById('password');
                
                document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
                
                if (!email.value) {
                    showError(email, 'Email is required');
                    isValid = false;
                } else if (!isValidEmail(email.value)) {
                    showError(email, 'Please enter a valid email address');
                    isValid = false;
                }
                
                if (!password.value) {
                    showError(password, 'Password is required');
                    isValid = false;
                }
                
                return isValid;
            }
            
            function showError(input, message) {
                input.classList.add('is-invalid');
                input.nextElementSibling.nextElementSibling.textContent = message;
            }
            
            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }
            
            function showAlert(message, type) {
                alertBox.className = `alert alert-${type}`;
                alertBox.textContent = message;
                alertBox.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>