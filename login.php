<?php
session_start();
require_once 'config/database.php';
require_once 'functions/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_POST && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password harus diisi';
    } else {
        $result = login($username, $password);
        
        if ($result['success']) {
            header('Location: index.php');
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}

// Handle demo login
if (isset($_GET['demo'])) {
    $demo_users = [
        'admin' => ['username' => 'admin', 'password' => 'password123'],
        'kepala' => ['username' => 'kepala_ptun', 'password' => 'password123'],
        'operator' => ['username' => 'operator1', 'password' => 'password123']
    ];
    
    if (isset($demo_users[$_GET['demo']])) {
        $demo_user = $demo_users[$_GET['demo']];
        $result = login($demo_user['username'], $demo_user['password']);
        
        if ($result['success']) {
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Surat-PTUN-BJM</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.5;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .login-title {
            color: #2c3e50;
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .login-subtitle {
            color: #7f8c8d;
            font-size: 0.95em;
            line-height: 1.4;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #383;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-group {
            position: relative;
        }

        .input-group .form-input {
            padding-left: 45px;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 16px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #7f8c8d;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            color: #2c3e50;
            background: rgba(0, 0, 0, 0.05);
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #2c3e50;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }

        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: #5a67d8;
        }

        .login-button {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .demo-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e1e8ed;
        }

        .demo-title {
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .demo-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .demo-btn {
            padding: 10px 12px;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .demo-btn:hover {
            background: rgba(102, 126, 234, 0.15);
            border-color: rgba(102, 126, 234, 0.3);
            transform: translateY(-1px);
        }

        .system-info {
            margin-top: 25px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            font-size: 13px;
            color: #7f8c8d;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            font-size: 13px;
            color: #7f8c8d;
        }

        /* Loading spinner */
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .demo-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-balance-scale"></i>
            </div>
            <h1 class="login-title">E-Surat-PTUN-BJM</h1>
            <p class="login-subtitle">Sistem Persuratan Digital<br>Pengadilan Tata Usaha Negara Banjarmasin</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="username" class="form-label">Username / Email</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" class="form-input" 
                           placeholder="Masukkan username atau email" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Masukkan password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ingat saya</span>
                </label>
                <a href="#" class="forgot-password">Lupa password?</a>
            </div>

            <button type="submit" name="login" class="login-button" id="loginButton">
                <span class="spinner" id="loadingSpinner"></span>
                <span id="buttonText">Masuk ke Sistem</span>
            </button>
        </form>

        <div class="demo-section">
            <div class="demo-title">Login Demo (Password: password123)</div>
            <div class="demo-buttons">
                <a href="?demo=admin" class="demo-btn">
                    <i class="fas fa-user-shield"></i><br>Admin
                </a>
                <a href="?demo=kepala" class="demo-btn">
                    <i class="fas fa-user-tie"></i><br>Kepala
                </a>
                <a href="?demo=operator" class="demo-btn">
                    <i class="fas fa-user-cog"></i><br>Operator
                </a>
            </div>
        </div>

        <div class="system-info">
            <div class="info-row">
                <span>Versi Sistem:</span>
                <span>v1.0.0</span>
            </div>
            <div class="info-row">
                <span>Status Database:</span>
                <span style="color: #27ae60;">
                    <i class="fas fa-circle" style="font-size: 8px;"></i> Online
                </span>
            </div>
            <div class="info-row">
                <span>Terakhir Update:</span>
                <span><?= date('d M Y') ?></span>
            </div>
        </div>

        <div class="footer-text">
            &copy; <?= date('Y') ?> PTUN Banjarmasin - E-Surat Digital
        </div>
    </div>

    <script>
        // Password toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form submission with loading
        document.getElementById('loginForm').addEventListener('submit', function() {
            const button = document.getElementById('loginButton');
            const spinner = document.getElementById('loadingSpinner');
            const buttonText = document.getElementById('buttonText');
            
            button.disabled = true;
            spinner.style.display = 'inline-block';
            buttonText.textContent = 'Memproses...';
        });

        // Auto focus on username field
        document.getElementById('username').focus();

        // Demo user info tooltip
        const demoButtons = document.querySelectorAll('.demo-btn');
        demoButtons.forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                const role = this.textContent.trim().toLowerCase();
                const tooltips = {
                    'admin': 'Akses penuh ke semua fitur sistem',
                    'kepala': 'Approval surat dan akses laporan',
                    'operator': 'Input surat dan disposisi'
                };
                
                this.title = tooltips[role] || '';
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + 1, 2, 3 for demo login
            if (e.altKey) {
                if (e.key === '1') {
                    window.location.href = '?demo=admin';
                } else if (e.key === '2') {
                    window.location.href = '?demo=kepala';
                } else if (e.key === '3') {
                    window.location.href = '?demo=operator';
                }
            }
        });
    </script>
</body>
</html>