<?php
/**
 * Setup Installation - E-Surat-PTUN-BJM
 * Script untuk instalasi otomatis sistem
 */

// Disable error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sistem Sudah Terinstall</title>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 100px; background: #f5f5f5; }
            .container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); display: inline-block; }
            .success { color: #27ae60; font-size: 24px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="success">✓ Sistem E-Surat-PTUN-BJM sudah terinstall!</div>
            <p><a href="index.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Masuk ke Sistem</a></p>
            <p><a href="login.php" style="background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Halaman Login</a></p>
        </div>
    </body>
    </html>
    ');
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$errors = [];
$success = [];

// Database configuration
$db_config = [
    'host' => $_POST['db_host'] ?? 'localhost',
    'user' => $_POST['db_user'] ?? 'root',
    'pass' => $_POST['db_pass'] ?? '',
    'name' => $_POST['db_name'] ?? 'e_surat_ptun_bjm'
];

// Admin user configuration
$admin_config = [
    'username' => $_POST['admin_username'] ?? 'admin',
    'email' => $_POST['admin_email'] ?? 'admin@ptun-banjarmasin.go.id',
    'password' => $_POST['admin_password'] ?? '',
    'full_name' => $_POST['admin_name'] ?? 'Administrator PKL'
];

// Step processing
switch ($step) {
    case 2: // Database setup
        if ($_POST) {
            $result = setupDatabase($db_config);
            if ($result['success']) {
                $success[] = $result['message'];
                $step = 3;
            } else {
                $errors[] = $result['message'];
                $step = 2;
            }
        }
        break;
        
    case 3: // Admin setup
        if ($_POST) {
            $result = setupAdmin($admin_config);
            if ($result['success']) {
                $success[] = $result['message'];
                createInstallLock();
                $step = 4;
            } else {
                $errors[] = $result['message'];
                $step = 3;
            }
        }
        break;
}

/**
 * Setup database and tables
 */
function setupDatabase($config) {
    try {
        // Test connection
        $dsn = "mysql:host={$config['host']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$config['name']}`");
        
        // Read and execute SQL file
        $sql = file_get_contents(__DIR__ . '/database.sql');
        if (!$sql) {
            return ['success' => false, 'message' => 'File database.sql tidak ditemukan'];
        }
        
        // Split SQL into individual statements
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Update config file
        updateConfigFile($config);
        
        return ['success' => true, 'message' => 'Database berhasil dibuat dan tabel berhasil diinstall'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error database: ' . $e->getMessage()];
    }
}

/**
 * Setup admin user
 */
function setupAdmin($config) {
    try {
        require_once 'config/database.php';
        $pdo = getConnection();
        
        // Check if admin already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$config['username'], $config['email']]);
        
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Username atau email admin sudah ada'];
        }
        
        // Create admin user
        $hashedPassword = password_hash($config['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, role, status) 
            VALUES (?, ?, ?, ?, 'admin', 'active')
        ");
        
        $result = $stmt->execute([
            $config['username'],
            $config['email'], 
            $hashedPassword,
            $config['full_name']
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Admin user berhasil dibuat'];
        } else {
            return ['success' => false, 'message' => 'Gagal membuat admin user'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Update config file
 */
function updateConfigFile($config) {
    $configContent = "<?php
// Konfigurasi Database
define('DB_HOST', '{$config['host']}');
define('DB_USER', '{$config['user']}');
define('DB_PASS', '{$config['pass']}');
define('DB_NAME', '{$config['name']}');

// Konfigurasi Aplikasi
define('APP_NAME', 'E-Surat-PTUN-BJM');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['SCRIPT_NAME']) . '/');

// Fungsi koneksi database
function getConnection() {
    try {
        \$pdo = new PDO(
            \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4\"
            ]
        );
        return \$pdo;
    } catch (PDOException \$e) {
        die(\"Koneksi database gagal: \" . \$e->getMessage());
    }
}
?>";
    
    file_put_contents('config/database.php', $configContent);
}

/**
 * Create installation lock file
 */
function createInstallLock() {
    if (!file_exists('config')) {
        mkdir('config', 0755, true);
    }
    file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
}

/**
 * Check system requirements
 */
function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'GD Extension' => extension_loaded('gd'),
        'Config Directory Writable' => is_writable(dirname(__FILE__)) || is_writable('config'),
        'Uploads Directory Writable' => is_writable('uploads') || !file_exists('uploads')
    ];
    
    return $requirements;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Installation - E-Surat-PTUN-BJM</title>
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
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .steps {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .step {
            padding: 10px 20px;
            background: #f8f9fa;
            border-radius: 20px;
            margin: 0 5px;
            color: #7f8c8d;
            font-weight: 500;
            position: relative;
        }

        .step.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .step.completed {
            background: #27ae60;
            color: white;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .requirements {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .req-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .req-item:last-child {
            border-bottom: none;
        }

        .req-status {
            font-weight: 600;
        }

        .req-ok {
            color: #27ae60;
        }

        .req-fail {
            color: #e74c3c;
        }

        .progress {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .final-info {
            background: #e8f5e8;
            border: 2px solid #27ae60;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
        }

        .final-info h3 {
            color: #27ae60;
            font-size: 1.5em;
            margin-bottom: 20px;
        }

        .login-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .login-info h4 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .credential-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .credential-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .steps {
                flex-wrap: wrap;
            }
            
            .step {
                margin: 5px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-balance-scale"></i> E-Surat-PTUN-BJM</h1>
            <p>Setup Installation - Sistem Persuratan Digital PTUN Banjarmasin</p>
        </div>

        <div class="content">
            <!-- Progress Steps -->
            <div class="steps">
                <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">
                    <i class="fas fa-check-circle"></i> 1. Persyaratan
                </div>
                <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">
                    <i class="fas fa-database"></i> 2. Database
                </div>
                <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">
                    <i class="fas fa-user-shield"></i> 3. Admin
                </div>
                <div class="step <?= $step >= 4 ? 'active' : '' ?>">
                    <i class="fas fa-flag-checkered"></i> 4. Selesai
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="progress">
                <div class="progress-bar" style="width: <?= ($step / 4) * 100 ?>%"></div>
            </div>

            <!-- Messages -->
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endforeach; ?>

            <?php foreach ($success as $msg): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endforeach; ?>

            <?php if ($step == 1): ?>
                <!-- Step 1: Requirements Check -->
                <h3>Pemeriksaan Persyaratan Sistem</h3>
                <div class="requirements">
                    <?php $requirements = checkRequirements(); ?>
                    <?php foreach ($requirements as $name => $status): ?>
                        <div class="req-item">
                            <span><?= $name ?></span>
                            <span class="req-status <?= $status ? 'req-ok' : 'req-fail' ?>">
                                <i class="fas <?= $status ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                <?= $status ? 'OK' : 'GAGAL' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (in_array(false, $requirements)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Beberapa persyaratan sistem belum terpenuhi. Silakan perbaiki terlebih dahulu.
                    </div>
                <?php else: ?>
                    <form method="GET">
                        <input type="hidden" name="step" value="2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Lanjutkan ke Database
                        </button>
                    </form>
                <?php endif; ?>

            <?php elseif ($step == 2): ?>
                <!-- Step 2: Database Setup -->
                <h3>Konfigurasi Database</h3>
                <p>Masukkan informasi koneksi database MySQL Anda:</p>

                <form method="POST">
                    <input type="hidden" name="step" value="2">
                    
                    <div class="form-group">
                        <label class="form-label">Host Database</label>
                        <input type="text" name="db_host" class="form-input" 
                               value="<?= htmlspecialchars($db_config['host']) ?>" 
                               placeholder="localhost" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Username Database</label>
                        <input type="text" name="db_user" class="form-input" 
                               value="<?= htmlspecialchars($db_config['user']) ?>" 
                               placeholder="root" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password Database</label>
                        <input type="password" name="db_pass" class="form-input" 
                               value="<?= htmlspecialchars($db_config['pass']) ?>" 
                               placeholder="(kosongkan jika tidak ada)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Database</label>
                        <input type="text" name="db_name" class="form-input" 
                               value="<?= htmlspecialchars($db_config['name']) ?>" 
                               placeholder="e_surat_ptun_bjm" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-database"></i> Setup Database
                    </button>
                    <a href="?step=1" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </form>

            <?php elseif ($step == 3): ?>
                <!-- Step 3: Admin Setup -->
                <h3>Konfigurasi Admin User</h3>
                <p>Buat akun administrator untuk sistem:</p>

                <form method="POST">
                    <input type="hidden" name="step" value="3">
                    
                    <div class="form-group">
                        <label class="form-label">Username Admin</label>
                        <input type="text" name="admin_username" class="form-input" 
                               value="<?= htmlspecialchars($admin_config['username']) ?>" 
                               placeholder="admin" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Admin</label>
                        <input type="email" name="admin_email" class="form-input" 
                               value="<?= htmlspecialchars($admin_config['email']) ?>" 
                               placeholder="admin@ptun-banjarmasin.go.id" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password Admin</label>
                        <input type="password" name="admin_password" class="form-input" 
                               value="<?= htmlspecialchars($admin_config['password']) ?>" 
                               placeholder="Minimal 8 karakter" minlength="8" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="admin_name" class="form-input" 
                               value="<?= htmlspecialchars($admin_config['full_name']) ?>" 
                               placeholder="Administrator PKL" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Buat Admin User
                    </button>
                    <a href="?step=2" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </form>

            <?php elseif ($step == 4): ?>
                <!-- Step 4: Installation Complete -->
                <div class="final-info">
                    <h3><i class="fas fa-check-circle"></i> Instalasi Berhasil!</h3>
                    <p>Sistem E-Surat-PTUN-BJM telah berhasil diinstall dan siap digunakan.</p>

                    <div class="login-info">
                        <h4><i class="fas fa-key"></i> Informasi Login Demo</h4>
                        <div class="credential-item">
                            <strong>Admin:</strong>
                            <span>admin / password123</span>
                        </div>
                        <div class="credential-item">
                            <strong>Kepala PTUN:</strong>
                            <span>kepala_ptun / password123</span>
                        </div>
                        <div class="credential-item">
                            <strong>Operator:</strong>
                            <span>operator1 / password123</span>
                        </div>
                        <div class="credential-item">
                            <strong>Staff:</strong>
                            <span>staff1 / password123</span>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <a href="login.php" class="btn btn-primary" style="margin: 10px; text-decoration: none; display: inline-block;">
                            <i class="fas fa-sign-in-alt"></i> Login ke Sistem
                        </a>
                        <a href="index.php" class="btn btn-secondary" style="margin: 10px; text-decoration: none; display: inline-block;">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </div>

                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px; color: #856404;">
                        <strong>Catatan Keamanan:</strong>
                        <ul style="text-align: left; margin-top: 10px;">
                            <li>Hapus file setup.php setelah instalasi selesai</li>
                            <li>Ubah password default untuk semua user</li>
                            <li>Pastikan folder uploads/ dapat ditulis oleh web server</li>
                            <li>Lakukan backup database secara berkala</li>
                        </ul>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-focus pada input pertama
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('.form-input');
            if (firstInput) {
                firstInput.focus();
            }
        });

        // Form validation
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.tagName === 'FORM') {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                    
                    // Re-enable after 10 seconds as fallback
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = submitBtn.innerHTML.replace('<i class="fas fa-spinner fa-spin"></i> Memproses...', 'Submit');
                    }, 10000);
                }
            }
        });

        // Password strength indicator
        const passwordInput = document.querySelector('input[name="admin_password"]');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                const colors = ['#e74c3c', '#f39c12', '#f1c40f', '#27ae60'];
                const texts = ['Lemah', 'Sedang', 'Baik', 'Kuat'];
                
                let indicator = this.nextElementSibling;
                if (!indicator || !indicator.classList.contains('password-strength')) {
                    indicator = document.createElement('div');
                    indicator.className = 'password-strength';
                    indicator.style.cssText = 'margin-top: 5px; font-size: 12px; font-weight: 600;';
                    this.parentNode.appendChild(indicator);
                }
                
                if (password.length > 0) {
                    indicator.textContent = 'Kekuatan Password: ' + (texts[strength - 1] || 'Lemah');
                    indicator.style.color = colors[strength - 1] || '#e74c3c';
                } else {
                    indicator.textContent = '';
                }
            });
        }
    </script>
</body>
</html>