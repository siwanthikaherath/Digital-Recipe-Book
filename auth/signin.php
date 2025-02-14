<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require '../includes/db.php';
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

    <link rel="stylesheet" href="../css/signin.css">

    
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

    <script src="../js/signin.js"></script>
</body>
</html>