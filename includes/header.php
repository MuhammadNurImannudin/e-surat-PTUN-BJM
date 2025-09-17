<?php
// Mendapatkan judul halaman berdasarkan parameter
function getPageTitle($page, $subpage = '') {
    $titles = [
        'dashboard' => 'Dashboard',
        'surat-masuk' => 'Surat Masuk',
        'surat-keluar' => 'Surat Keluar',
        'arsip' => 'Arsip Surat',
        'disposisi' => 'Disposisi',
        'laporan' => 'Laporan'
    ];
    
    $laporan_titles = [
        'surat-masuk' => 'Laporan Surat Masuk',
        'surat-keluar' => 'Laporan Surat Keluar',
        'disposisi' => 'Laporan Disposisi',
        'arsip' => 'Laporan Arsip',
        'statistik' => 'Laporan Statistik'
    ];
    
    if ($page === 'laporan' && !empty($subpage)) {
        return isset($laporan_titles[$subpage]) ? $laporan_titles[$subpage] : 'Laporan';
    }
    
    return isset($titles[$page]) ? $titles[$page] : 'Dashboard';
}

$page_title = getPageTitle($page, $subpage);
?>

<header class="header">
    <div class="header-left">
        <h1><?= $page_title ?> - E-Surat-PTUN-BJM</h1>
    </div>
    
    <div class="header-right">
        <!-- Notifikasi -->
        <button class="notification-btn" onclick="showNotifications()" title="Notifikasi">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" id="notification-count">3</span>
        </button>
        
        <!-- User Info -->
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?= $_SESSION['username'] ?? 'Admin PKL' ?></div>
                <div class="user-role"><?= ucfirst($_SESSION['role'] ?? 'Administrator') ?></div>
            </div>
            <div class="user-dropdown">
                <button class="dropdown-toggle" onclick="toggleUserDropdown()">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu" id="user-dropdown">
                    <a href="?page=profile" class="dropdown-item">
                        <i class="fas fa-user-cog"></i> Profile
                    </a>
                    <a href="?page=settings" class="dropdown-item">
                        <i class="fas fa-cogs"></i> Pengaturan
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="?action=logout" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Notification Modal -->
<div class="notification-modal" id="notification-modal">
    <div class="notification-header">
        <h3><i class="fas fa-bell"></i> Notifikasi</h3>
        <button class="close-btn" onclick="closeNotifications()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="notification-body">
        <div class="notification-item new">
            <div class="notification-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">Surat Masuk Baru</div>
                <div class="notification-text">3 surat masuk baru memerlukan perhatian</div>
                <div class="notification-time">5 menit yang lalu</div>
            </div>
        </div>
        
        <div class="notification-item">
            <div class="notification-icon">
                <i class="fas fa-share-alt"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">Disposisi Pending</div>
                <div class="notification-text">2 disposisi menunggu tindak lanjut</div>
                <div class="notification-time">1 jam yang lalu</div>
            </div>
        </div>
        
        <div class="notification-item">
            <div class="notification-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">Laporan Bulanan</div>
                <div class="notification-text">Laporan bulanan siap untuk review</div>
                <div class="notification-time">2 jam yang lalu</div>
            </div>
        </div>
        
        <div class="notification-item">
            <div class="notification-icon">
                <i class="fas fa-database"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">Backup Sistem</div>
                <div class="notification-text">Sistem backup otomatis berhasil</div>
                <div class="notification-time">1 hari yang lalu</div>
            </div>
        </div>
    </div>
    <div class="notification-footer">
        <a href="?page=notifications" class="btn btn-sm btn-primary">
            Lihat Semua Notifikasi
        </a>
    </div>
</div>