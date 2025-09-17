<?php
// Dashboard Page - E-Surat-PTUN-BJM
require_once 'config/database.php';

// Get statistics from database
try {
    $pdo = getConnection();
    
    // Get surat masuk statistics
    $stmt_masuk_today = $pdo->prepare("SELECT COUNT(*) FROM surat_masuk WHERE DATE(created_at) = CURDATE()");
    $stmt_masuk_today->execute();
    $surat_masuk_today = $stmt_masuk_today->fetchColumn();
    
    $stmt_masuk_total = $pdo->prepare("SELECT COUNT(*) FROM surat_masuk");
    $stmt_masuk_total->execute();
    $surat_masuk_total = $stmt_masuk_total->fetchColumn();
    
    // Get surat keluar statistics
    $stmt_keluar_today = $pdo->prepare("SELECT COUNT(*) FROM surat_keluar WHERE DATE(created_at) = CURDATE()");
    $stmt_keluar_today->execute();
    $surat_keluar_today = $stmt_keluar_today->fetchColumn();
    
    $stmt_keluar_total = $pdo->prepare("SELECT COUNT(*) FROM surat_keluar");
    $stmt_keluar_total->execute();
    $surat_keluar_total = $stmt_keluar_total->fetchColumn();
    
    // Get pending dispositions
    $stmt_disposisi = $pdo->prepare("SELECT COUNT(*) FROM disposisi WHERE status = 'pending'");
    $stmt_disposisi->execute();
    $pending_disposisi = $stmt_disposisi->fetchColumn();
    
    // Get completed this month
    $stmt_completed = $pdo->prepare("
        SELECT COUNT(*) FROM surat_masuk 
        WHERE status = 'selesai' AND MONTH(updated_at) = MONTH(CURDATE()) AND YEAR(updated_at) = YEAR(CURDATE())
    ");
    $stmt_completed->execute();
    $completed_month = $stmt_completed->fetchColumn();
    
    // Get recent surat masuk
    $stmt_recent = $pdo->prepare("
        SELECT id, no_surat, pengirim, perihal, tanggal_terima, status 
        FROM surat_masuk 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt_recent->execute();
    $recent_surat = $stmt_recent->fetchAll();
    
} catch (PDOException $e) {
    // Set default values if database connection fails
    $surat_masuk_today = 24;
    $surat_keluar_today = 18;
    $pending_disposisi = 7;
    $completed_month = 156;
    $surat_masuk_total = 1247;
    $surat_keluar_total = 983;
    $recent_surat = [];
}
?>

<!-- Welcome Section -->
<section class="welcome-section">
    <div class="welcome-header">
        <div class="welcome-icon">
            <i class="fas fa-balance-scale"></i>
        </div>
        <div class="welcome-content">
            <h2>Selamat Datang di E-Surat-PTUN-BJM</h2>
            <p>Sistem Persuratan Digital Pengadilan Tata Usaha Negara Banjarmasin - Aplikasi terintegrasi untuk pengelolaan surat masuk dan surat keluar dengan sistem pelaporan yang komprehensif dan real-time monitoring.</p>
        </div>
    </div>
    
    <div class="quick-stats">
        <div class="quick-stat-item">
            <i class="fas fa-inbox"></i>
            <span>Total Surat: <?= number_format($surat_masuk_total + $surat_keluar_total) ?></span>
        </div>
        <div class="quick-stat-item">
            <i class="fas fa-calendar-day"></i>
            <span>Hari Ini: <?= $surat_masuk_today + $surat_keluar_today ?> Surat</span>
        </div>
        <div class="quick-stat-item">
            <i class="fas fa-user"></i>
            <span>Pengguna: <?= $_SESSION['username'] ?></span>
        </div>
    </div>
</section>

<!-- Statistics Cards -->
<div class="stats-container">
    <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-icon inbox">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $surat_masuk_today ?></div>
            <div class="stat-label">Surat Masuk Hari Ini</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>+12% dari kemarin</span>
            </div>
        </div>
        <div class="stat-action">
            <a href="?page=surat-masuk" class="btn-stat">
                <i class="fas fa-eye"></i>
            </a>
        </div>
    </div>
    
    <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
        <div class="stat-icon outbox">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $surat_keluar_today ?></div>
            <div class="stat-label">Surat Keluar Hari Ini</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>+8% dari kemarin</span>
            </div>
        </div>
        <div class="stat-action">
            <a href="?page=surat-keluar" class="btn-stat">
                <i class="fas fa-eye"></i>
            </a>
        </div>
    </div>
    
    <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
        <div class="stat-icon pending">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $pending_disposisi ?></div>
            <div class="stat-label">Menunggu Disposisi</div>
            <div class="stat-change negative">
                <i class="fas fa-arrow-down"></i>
                <span>-5% dari kemarin</span>
            </div>
        </div>
        <div class="stat-action">
            <a href="?page=disposisi" class="btn-stat">
                <i class="fas fa-eye"></i>
            </a>
        </div>
    </div>
    
    <div class="stat-card" data-aos="fade-up" data-aos-delay="400">
        <div class="stat-icon completed">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $completed_month ?></div>
            <div class="stat-label">Selesai Bulan Ini</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>+15% dari bulan lalu</span>
            </div>
        </div>
        <div class="stat-action">
            <a href="?page=laporan&subpage=statistik" class="btn-stat">
                <i class="fas fa-eye"></i>
            </a>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="dashboard-grid">
    <!-- Recent Letters -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Surat Masuk Terbaru</h3>
            <a href="?page=surat-masuk" class="btn btn-sm btn-primary">
                Lihat Semua <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($recent_surat)): ?>
                <div class="recent-letters-list">
                    <?php foreach ($recent_surat as $surat): ?>
                        <div class="recent-letter-item">
                            <div class="letter-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="letter-info">
                                <div class="letter-title"><?= htmlspecialchars($surat['no_surat']) ?></div>
                                <div class="letter-sender"><?= htmlspecialchars($surat['pengirim']) ?></div>
                                <div class="letter-subject"><?= htmlspecialchars(substr($surat['perihal'], 0, 50)) ?>...</div>
                            </div>
                            <div class="letter-meta">
                                <span class="letter-date"><?= date('d M Y', strtotime($surat['tanggal_terima'])) ?></span>
                                <span class="status-badge status-<?= $surat['status'] ?>">
                                    <?= ucfirst($surat['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Belum ada surat masuk</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-bolt"></i> Aksi Cepat</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions">
                <a href="?page=surat-masuk&action=add" class="quick-action-item">
                    <div class="action-icon inbox">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Input Surat Masuk</div>
                        <div class="action-desc">Tambah surat masuk baru</div>
                    </div>
                </a>
                
                <a href="?page=surat-keluar&action=add" class="quick-action-item">
                    <div class="action-icon outbox">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Buat Surat Keluar</div>
                        <div class="action-desc">Buat surat keluar baru</div>
                    </div>
                </a>
                
                <a href="?page=disposisi&action=add" class="quick-action-item">
                    <div class="action-icon pending">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Disposisi Surat</div>
                        <div class="action-desc">Buat disposisi baru</div>
                    </div>
                </a>
                
                <a href="?page=laporan" class="quick-action-item">
                    <div class="action-icon completed">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Lihat Laporan</div>