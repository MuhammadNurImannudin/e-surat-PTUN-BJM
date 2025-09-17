<?php
session_start();

// Simulasi login sederhana (untuk development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Admin PKL';
    $_SESSION['role'] = 'administrator';
}

// Mendapatkan halaman yang diminta
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$subpage = isset($_GET['subpage']) ? $_GET['subpage'] : '';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Surat-PTUN-BJM - Sistem Persuratan Digital Banjarmasin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <?php if (strpos($page, 'laporan') !== false): ?>
    <link href="assets/css/laporan.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="main-content" id="main-content">
        <?php
        // Routing halaman
        switch ($page) {
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'surat-masuk':
                include 'pages/surat-masuk.php';
                break;
            case 'surat-keluar':
                include 'pages/surat-keluar.php';
                break;
            case 'arsip':
                include 'pages/arsip.php';
                break;
            case 'disposisi':
                include 'pages/disposisi.php';
                break;
            case 'laporan':
                if (!empty($subpage)) {
                    $laporan_file = 'pages/laporan/' . $subpage . '.php';
                    if (file_exists($laporan_file)) {
                        include $laporan_file;
                    } else {
                        include 'pages/laporan/index.php';
                    }
                } else {
                    include 'pages/laporan/index.php';
                }
                break;
            default:
                include 'pages/dashboard.php';
                break;
        }
        ?>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <?php if (strpos($page, 'laporan') !== false): ?>
    <script src="assets/js/laporan.js"></script>
    <?php endif; ?>
</body>
</html>