<?php
/**
 * Authentication Functions - E-Surat-PTUN-BJM
 * Fungsi-fungsi untuk autentikasi dan otorisasi pengguna
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Login user
 */
function login($username, $password) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            SELECT id, username, email, password, full_name, role, jabatan, status 
            FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['jabatan'] = $user['jabatan'];
            $_SESSION['login_time'] = time();
            
            // Log activity
            logActivity($user['id'], 'LOGIN', 'User berhasil login');
            
            return [
                'success' => true,
                'message' => 'Login berhasil',
                'user' => $user
            ];
        } else {
            // Log failed attempt
            if ($user) {
                logActivity($user['id'], 'LOGIN_FAILED', 'Percobaan login dengan password salah');
            } else {
                logActivity(null, 'LOGIN_FAILED', "Percobaan login dengan username: $username");
            }
            
            return [
                'success' => false,
                'message' => 'Username/email atau password salah'
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
        ];
    }
}

/**
 * Logout user
 */
function logout() {
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'LOGOUT', 'User logout dari sistem');
    }
    
    // Clear all session data
    session_unset();
    session_destroy();
    
    return [
        'success' => true,
        'message' => 'Logout berhasil'
    ];
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role'],
        'jabatan' => $_SESSION['jabatan'],
        'login_time' => $_SESSION['login_time']
    ];
}

/**
 * Check user role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['role'], $roles);
}

/**
 * Check permission based on role
 */
function canAccess($resource, $action = 'read') {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = $_SESSION['role'];
    
    // Admin has access to everything
    if ($role === 'admin') {
        return true;
    }
    
    // Role-based permissions
    $permissions = [
        'kepala' => [
            'dashboard' => ['read'],
            'surat_masuk' => ['read', 'update', 'approve'],
            'surat_keluar' => ['read', 'create', 'update', 'approve'],
            'disposisi' => ['read', 'create', 'update'],
            'laporan' => ['read'],
            'arsip' => ['read'],
            'users' => ['read'],
            'settings' => ['read']
        ],
        'operator' => [
            'dashboard' => ['read'],
            'surat_masuk' => ['read', 'create', 'update'],
            'surat_keluar' => ['read', 'create', 'update'],
            'disposisi' => ['read', 'create', 'update'],
            'laporan' => ['read'],
            'arsip' => ['read', 'create', 'update']
        ],
        'staff' => [
            'dashboard' => ['read'],
            'surat_masuk' => ['read'],
            'surat_keluar' => ['read'],
            'disposisi' => ['read', 'update'],
            'laporan' => ['read'],
            'arsip' => ['read']
        ]
    ];
    
    if (!isset($permissions[$role])) {
        return false;
    }
    
    if (!isset($permissions[$role][$resource])) {
        return false;
    }
    
    return in_array($action, $permissions[$role][$resource]);
}

/**
 * Register new user (admin only)
 */
function registerUser($data) {
    if (!hasRole('admin')) {
        return [
            'success' => false,
            'message' => 'Akses ditolak'
        ];
    }
    
    try {
        $pdo = getConnection();
        
        // Check if username or email already exists
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM users 
            WHERE username = ? OR email = ?
        ");
        $checkStmt->execute([$data['username'], $data['email']]);
        
        if ($checkStmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'Username atau email sudah digunakan'
            ];
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, role, jabatan, nip, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['full_name'],
            $data['role'],
            $data['jabatan'] ?? null,
            $data['nip'] ?? null
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Log activity
        logActivity($_SESSION['user_id'], 'CREATE_USER', "Menambahkan user baru: {$data['username']}");
        
        return [
            'success' => true,
            'message' => 'User berhasil ditambahkan',
            'user_id' => $userId
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Change password
 */
function changePassword($oldPassword, $newPassword) {
    if (!isLoggedIn()) {
        return [
            'success' => false,
            'message' => 'User tidak login'
        ];
    }
    
    try {
        $pdo = getConnection();
        
        // Get current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Password lama tidak benar'
            ];
        }
        
        // Update password
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$hashedNewPassword, $_SESSION['user_id']]);
        
        // Log activity
        logActivity($_SESSION['user_id'], 'CHANGE_PASSWORD', 'User mengubah password');
        
        return [
            'success' => true,
            'message' => 'Password berhasil diubah'
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Update user profile
 */
function updateProfile($data) {
    if (!isLoggedIn()) {
        return [
            'success' => false,
            'message' => 'User tidak login'
        ];
    }
    
    try {
        $pdo = getConnection();
        
        // Check if email is already used by another user
        if (isset($data['email'])) {
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) FROM users 
                WHERE email = ? AND id != ?
            ");
            $checkStmt->execute([$data['email'], $_SESSION['user_id']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Email sudah digunakan oleh user lain'
                ];
            }
        }
        
        // Build update query dynamically
        $fields = [];
        $values = [];
        
        $allowedFields = ['email', 'full_name', 'jabatan', 'nip'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return [
                'success' => false,
                'message' => 'Tidak ada data yang diupdate'
            ];
        }
        
        $fields[] = "updated_at = NOW()";
        $values[] = $_SESSION['user_id'];
        
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($values);
        
        // Update session data
        if (isset($data['email'])) {
            $_SESSION['email'] = $data['email'];
        }
        if (isset($data['full_name'])) {
            $_SESSION['full_name'] = $data['full_name'];
        }
        if (isset($data['jabatan'])) {
            $_SESSION['jabatan'] = $data['jabatan'];
        }
        
        // Log activity
        logActivity($_SESSION['user_id'], 'UPDATE_PROFILE', 'User mengupdate profil');
        
        return [
            'success' => true,
            'message' => 'Profil berhasil diupdate'
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Get user by ID
 */
function getUserById($id) {
    if (!hasAnyRole(['admin', 'kepala'])) {
        return null;
    }
    
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            SELECT id, username, email, full_name, role, jabatan, nip, status, 
                   avatar, last_login, created_at, updated_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get all users (admin/kepala only)
 */
function getAllUsers($status = null) {
    if (!hasAnyRole(['admin', 'kepala'])) {
        return [];
    }
    
    try {
        $pdo = getConnection();
        
        $query = "
            SELECT id, username, email, full_name, role, jabatan, nip, status, 
                   avatar, last_login, created_at, updated_at
            FROM users
        ";
        
        $params = [];
        if ($status) {
            $query .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY full_name ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Log user activity
 */
function logActivity($userId, $action, $description, $relatedId = null) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
    } catch (PDOException $e) {
        // Log error but don't break the application
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Check session timeout
 */
function checkSessionTimeout($timeoutMinutes = 120) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $loginTime = $_SESSION['login_time'] ?? 0;
    $currentTime = time();
    
    if (($currentTime - $loginTime) > ($timeoutMinutes * 60)) {
        logout();
        return false;
    }
    
    // Update session time
    $_SESSION['login_time'] = $currentTime;
    return true;
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin($redirectTo = 'login.php') {
    if (!isLoggedIn() || !checkSessionTimeout()) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole($role, $redirectTo = 'index.php') {
    requireLogin();
    
    if (!hasRole($role)) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Require any of the specified roles
 */
function requireAnyRole($roles, $redirectTo = 'index.php') {
    requireLogin();
    
    if (!hasAnyRole($roles)) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Generate secure random password
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Reset password (admin only)
 */
function resetPassword($userId, $newPassword = null) {
    if (!hasRole('admin')) {
        return [
            'success' => false,
            'message' => 'Akses ditolak'
        ];
    }
    
    try {
        $pdo = getConnection();
        
        // Generate random password if not provided
        if (!$newPassword) {
            $newPassword = generateRandomPassword();
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        if ($stmt->rowCount() > 0) {
            // Log activity
            logActivity($_SESSION['user_id'], 'RESET_PASSWORD', "Reset password untuk user ID: $userId");
            
            return [
                'success' => true,
                'message' => 'Password berhasil direset',
                'new_password' => $newPassword
            ];
        } else {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan'
            ];
        }
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}
?>