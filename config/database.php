<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'e_surat_ptun_bjm');

// Konfigurasi Aplikasi
define('APP_NAME', 'E-Surat-PTUN-BJM');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/e-surat-PTUN-BJM/');

// Fungsi koneksi database
function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

// Fungsi untuk membuat database dan tabel jika belum ada
function createDatabaseAndTables() {
    try {
        // Koneksi tanpa database
        $pdo = new PDO(
            "mysql:host=" . DB_HOST,
            DB_USER,
            DB_PASS
        );
        
        // Buat database jika belum ada
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $pdo->exec("USE " . DB_NAME);
        
        // Buat tabel users
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'operator', 'kepala') DEFAULT 'operator',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Buat tabel surat_masuk
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS surat_masuk (
                id INT AUTO_INCREMENT PRIMARY KEY,
                no_surat VARCHAR(100) NOT NULL,
                tanggal_surat DATE NOT NULL,
                tanggal_terima DATE NOT NULL,
                pengirim VARCHAR(200) NOT NULL,
                perihal TEXT NOT NULL,
                jenis_surat ENUM('pengaduan', 'permohonan', 'gugatan', 'keberatan', 'lainnya') NOT NULL,
                status ENUM('baru', 'proses', 'selesai', 'urgent') DEFAULT 'baru',
                file_surat VARCHAR(255),
                keterangan TEXT,
                user_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        
        // Buat tabel surat_keluar
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS surat_keluar (
                id INT AUTO_INCREMENT PRIMARY KEY,
                no_surat VARCHAR(100) NOT NULL,
                tanggal_surat DATE NOT NULL,
                tujuan VARCHAR(200) NOT NULL,
                perihal TEXT NOT NULL,
                jenis_surat ENUM('keputusan', 'surat_edaran', 'undangan', 'pemberitahuan', 'lainnya') NOT NULL,
                status ENUM('draft', 'review', 'approved', 'sent') DEFAULT 'draft',
                file_surat VARCHAR(255),
                keterangan TEXT,
                user_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        
        // Buat tabel disposisi
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS disposisi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                surat_masuk_id INT NOT NULL,
                dari_user_id INT NOT NULL,
                ke_user_id INT NOT NULL,
                catatan TEXT,
                instruksi TEXT,
                batas_waktu DATE,
                status ENUM('pending', 'proses', 'selesai') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (surat_masuk_id) REFERENCES surat_masuk(id),
                FOREIGN KEY (dari_user_id) REFERENCES users(id),
                FOREIGN KEY (ke_user_id) REFERENCES users(id)
            )
        ");
        
        // Insert user default jika belum ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("
                INSERT INTO users (username, email, password, full_name, role) VALUES 
                ('admin', 'admin@ptun-banjarmasin.go.id', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Administrator PKL', 'admin'),
                ('operator1', 'operator1@ptun-banjarmasin.go.id', '" . password_hash('operator123', PASSWORD_DEFAULT) . "', 'Operator Surat', 'operator'),
                ('kepala', 'kepala@ptun-banjarmasin.go.id', '" . password_hash('kepala123', PASSWORD_DEFAULT) . "', 'Kepala Bagian', 'kepala')
            ");
        }
        
        return true;
    } catch (PDOException $e) {
        die("Error membuat database: " . $e->getMessage());
    }
}

// Panggil fungsi untuk membuat database dan tabel
createDatabaseAndTables();
?>