<footer class="footer">
    <div class="footer-content">
        <div class="footer-left">
            <p>&copy; <?= date('Y') ?> Pengadilan Tata Usaha Negara Banjarmasin</p>
            <p>E-Surat-PTUN-BJM v<?= APP_VERSION ?? '1.0.0' ?> - Sistem Persuratan Digital Banjarmasin</p>
        </div>
        
        <div class="footer-center">
            <div class="footer-stats">
                <div class="stat-item">
                    <i class="fas fa-inbox"></i>
                    <span>Total Surat: <strong id="total-letters">1,247</strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <span>Pengguna Aktif: <strong><?= $_SESSION['active_users'] ?? '12' ?></strong></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-server"></i>
                    <span>Status Server: <strong class="text-success">Online</strong></span>
                </div>
            </div>
        </div>
        
        <div class="footer-right">
            <div class="footer-links">
                <a href="?page=help" title="Bantuan">
                    <i class="fas fa-question-circle"></i>
                </a>
                <a href="?page=about" title="Tentang">
                    <i class="fas fa-info-circle"></i>
                </a>
                <a href="mailto:admin@ptun-banjarmasin.go.id" title="Kontak">
                    <i class="fas fa-envelope"></i>
                </a>
            </div>
            
            <div class="footer-time">
                <i class="fas fa-clock"></i>
                <span id="current-time"><?= date('H:i:s') ?></span>
                <br>
                <small><?= date('d M Y') ?></small>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Memuat data...</p>
        </div>
    </div>
</footer>

<script>
// Update waktu real-time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('id-ID');
    const dateString = now.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
    
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.innerHTML = timeString + '<br><small>' + dateString + '</small>';
    }
}

// Update setiap detik
setInterval(updateTime, 1000);
updateTime(); // Jalankan sekali saat load

// Loading functions
function showLoading() {
    document.getElementById('loading-overlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loading-overlay').style.display = 'none';
}
</script>