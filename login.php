<?php
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, full_name FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_username'] = $row['username'];
                $_SESSION['admin_fullname'] = $row['full_name'];
                header("Location: index.php");
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Username not found';
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
    <title>Librarian Login - FDSA OPAC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.cdnfonts.com/css/amoera');
        
        :root {
            --deep-space-blue: #003049ff;
            --flag-red: #d62828ff;
            --vivid-tangerine: #f77f00ff;
            --sunflower-gold: #fcbf49ff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--deep-space-blue) 0%, #1a4a6e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
        }
        
        .login-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeInUp 0.5s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .logo i {
            font-size: 3rem;
            color: var(--deep-space-blue);
            background: #f0f4f8;
            padding: 1rem;
            border-radius: 50%;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            margin-top: 1rem;
            color: var(--deep-space-blue);
            font-family: 'amoera', sans-serif;
        }
        
        .logo p {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: #475569;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--vivid-tangerine);
            box-shadow: 0 0 0 3px rgba(247,127,0,0.1);
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .input-icon input {
            padding-left: 2.5rem;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: var(--flag-red);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
        }
        
        .btn-login:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-text {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        .info-text a {
            color: var(--deep-space-blue);
            text-decoration: none;
        }
        
        .info-text a:hover {
            color: var(--vivid-tangerine);
        }
        
        .demo-credentials {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 0.75rem;
            margin-top: 1rem;
            font-size: 0.75rem;
        }
        
        .demo-credentials p {
            margin: 0.25rem 0;
            color: #475569;
        }
        
        .demo-credentials code {
            background: #e2e8f0;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="bi bi-shield-lock"></i>
                <h1>Librarian Login</h1>
                <p>Access the administrative panel</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-icon">
                        <i class="bi bi-person"></i>
                        <input type="text" name="username" placeholder="Enter your username" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-icon">
                        <i class="bi bi-key"></i>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>
            
            <div class="demo-credentials">
                <p><i class="bi bi-info-circle"></i> Demo Credentials:</p>
                <p>Username: <code>librarian</code></p>
                <p>Password: <code>password123</code></p>
            </div>
            
            <div class="info-text">
                <a href="index.php"><i class="bi bi-arrow-left"></i> Back to Catalog</a>
            </div>
        </div>
    </div>
</body>
</html>