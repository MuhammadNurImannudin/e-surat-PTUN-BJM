<?php
// Laporan Surat Masuk - E-Surat-PTUN-BJM
require_once 'config/database.php';

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';

try {
    $pdo = getConnection();
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    $where_conditions[] = "DATE(tanggal_terima) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    
    if (!empty($status_filter)) {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($type_filter)) {
        $where_conditions[] = "jenis_surat = ?";
        $params[] = $type_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get filtered data
    $stmt = $pdo->prepare("
        SELECT sm.*, u.full_name as input_by
        FROM surat_masuk sm
        LEFT JOIN users u ON sm.user_id = u.id
        WHERE {$where_clause}
        ORDER BY sm.tanggal_terima DESC, sm.created_at DESC
    ");
    $stmt->execute($params);
    $surat_masuk = $stmt->fetchAll();
    
    // Get statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'baru' THEN 1 END) as baru,
            COUNT(CASE WHEN status = 'proses' THEN 1 END) as proses,
            COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai,
            COUNT(CASE WHEN status = 'urgent' THEN 1 END) as urgent,
            COUNT(CASE WHEN DATE(tanggal_terima) = CURDATE() THEN 1 END) as hari_ini
        FROM surat_masuk 
        WHERE {$where_clause}
    ";
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute($params);
    $statistics = $stats_stmt->fetch();
    
    // Get monthly trend
    $trend_stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(tanggal_terima, '%Y-%m') as bulan,
            COUNT(*) as jumlah
        FROM surat_masuk 
        WHERE tanggal_terima >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(tanggal_terima, '%Y-%m')
        ORDER BY bulan ASC
    ");
    $trend_stmt->execute();
    $monthly_trend = $trend_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Error mengambil data: " . $e->getMessage();
    $surat_masuk = [];
    $statistics = [
        'total' => 0, 'baru' => 0, 'proses' => 0, 
        'selesai' => 0, 'urgent' => 0, 'hari_ini' => 0
    ];
    $monthly_trend = [];
}
?>

<link href="assets/css/laporan.css" rel="stylesheet">

<!-- Header Section -->
<div class="laporan-header">
    <div class="header-content">
        <div class="header-icon">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="header-text">
            <h1>Laporan Surat Masuk</h1>
            <p>Sistem pelaporan dan monitoring surat masuk Pengadilan Tata Usaha Negara Banjarmasin dengan analisis data yang komprehensif dan real-time</p>
        </div>
    </div>
    
    <div class="header-actions">
        <button class="btn btn-success" onclick="exportExcel()">
            <i class="fas fa-file-excel"></i> Export Excel
        </button>
        <button class="btn btn-danger" onclick="exportPDF()">
            <i class="fas fa-file-pdf"></i> Export PDF
        </button>
        <button class="btn btn-info" onclick="printReport()">
            <i class="fas fa-print"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <div class="filter-header">
        <h3><i class="fas fa-filter"></i> Filter & Pencarian Data</h3>
    </div>
    
    <form method="GET" class="filter-form" id="filterForm">
        <input type="hidden" name="page" value="laporan">
        <input type="hidden" name="subpage" value="surat-masuk">
        
        <div class="filter-grid">
            <div class="filter-group">
                <label for="start_date">Tanggal Mulai</label>
                <input type="date" id="start_date" name="start_date" class="filter-input" 
                       value="<?= htmlspecialchars($start_date) ?>">
            </div>
            
            <div class="filter-group">
                <label for="end_date">Tanggal Akhir</label>
                <input type="date" id="end_date" name="end_date" class="filter-input" 
                       value="<?= htmlspecialchars($end_date) ?>">
            </div>
            
            <div class="filter-group">
                <label for="status">Status Surat</label>
                <select id="status" name="status" class="filter-input">
                    <option value="">Semua Status</option>
                    <option value="baru" <?= $status_filter == 'baru' ? 'selected' : '' ?>>Baru</option>
                    <option value="proses" <?= $status_filter == 'proses' ? 'selected' : '' ?>>Dalam Proses</option>
                    <option value="selesai" <?= $status_filter == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="urgent" <?= $status_filter == 'urgent' ? 'selected' : '' ?>>Urgent</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="type">Jenis Surat</label>
                <select id="type" name="type" class="filter-input">
                    <option value="">Semua Jenis</option>
                    <option value="pengaduan" <?= $type_filter == 'pengaduan' ? 'selected' : '' ?>>Surat Pengaduan</option>
                    <option value="permohonan" <?= $type_filter == 'permohonan' ? 'selected' : '' ?>>Surat Permohonan</option>
                    <option value="gugatan" <?= $type_filter == 'gugatan' ? 'selected' : '' ?>>Surat Gugatan</option>
                    <option value="keberatan" <?= $type_filter == 'keberatan' ? 'selected' : '' ?>>Surat Keberatan</option>
                    <option value="lainnya" <?= $type_filter == 'lainnya' ? 'selected' : '' ?>>Lainnya</option>
                </select>
            </div>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Terapkan Filter
            </button>
            <button type="button" class="btn btn-secondary" onclick="resetFilter()">
                <i class="fas fa-refresh"></i> Reset Filter
            </button>
        </div>
    </form>
</div>

<!-- Statistics Cards -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-icon total">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= number_format($statistics['total']) ?></div>
            <div class="stat-label">Total Surat Masuk</div>
            <div class="stat-period">Periode: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon today">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= number_format($statistics['hari_ini']) ?></div>
            <div class="stat-label">Surat Hari Ini</div>
            <div class="stat-period"><?= date('d M Y') ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon urgent">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= number_format($statistics['urgent']) ?></div>
            <div class="stat-label">Surat Urgent</div>
            <div class="stat-period">Perlu Perhatian</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon processed">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= number_format($statistics['selesai']) ?></div>
            <div class="stat-label">Telah Selesai</div>
            <div class="stat-period"><?= number_format(($statistics['total'] > 0 ? ($statistics['selesai'] / $statistics['total']) * 100 : 0), 1) ?>% dari total</div>
        </div>
    </div>
</div>

<!-- Chart Section -->
<div class="chart-section">
    <div class="chart-card">
        <div class="chart-header">
            <h3><i class="fas fa-chart-line"></i> Trend Bulanan Surat Masuk</h3>
        </div>
        <div class="chart-body">
            <canvas id="monthlyChart" width="800" height="300"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <div class="chart-header">
            <h3><i class="fas fa-chart-pie"></i> Distribusi Status</h3>
        </div>
        <div class="chart-body">
            <div class="status-distribution">
                <div class="status-item">
                    <div class="status-bar">
                        <div class="status-fill status-baru" style="width: <?= $statistics['total'] > 0 ? ($statistics['baru'] / $statistics['total']) * 100 : 0 ?>%"></div>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Baru</span>
                        <span class="status-count"><?= $statistics['baru'] ?> surat</span>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-bar">
                        <div class="status-fill status-proses" style="width: <?= $statistics['total'] > 0 ? ($statistics['proses'] / $statistics['total']) * 100 : 0 ?>%"></div>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Dalam Proses</span>
                        <span class="status-count"><?= $statistics['proses'] ?> surat</span>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-bar">
                        <div class="status-fill status-selesai" style="width: <?= $statistics['total'] > 0 ? ($statistics['selesai'] / $statistics['total']) * 100 : 0 ?>%"></div>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Selesai</span>
                        <span class="status-count"><?= $statistics['selesai'] ?> surat</span>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-bar">
                        <div class="status-fill status-urgent" style="width: <?= $statistics['total'] > 0 ? ($statistics['urgent'] / $statistics['total']) * 100 : 0 ?>%"></div>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Urgent</span>
                        <span class="status-count"><?= $statistics['urgent'] ?> surat</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Table Section -->
<div class="table-section">
    <div class="table-header">
        <h3><i class="fas fa-table"></i> Data Detail Surat Masuk</h3>
        <div class="table-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchTable" placeholder="Cari dalam tabel..." onkeyup="searchTable()">
            </div>
            <select id="entriesPerPage" onchange="changeEntries()">
                <option value="10">10 per halaman</option>
                <option value="25">25 per halaman</option>
                <option value="50">50 per halaman</option>
                <option value="100">100 per halaman</option>
            </select>
        </div>
    </div>
    
    <div class="table-container">
        <table class="data-table" id="dataTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">No. <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(1)">No. Surat <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(2)">Tanggal Terima <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(3)">Pengirim <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(4)">Perihal <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(5)">Jenis <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(6)">Status <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(7)">Input Oleh <i class="fas fa-sort"></i></th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($surat_masuk)): ?>
                    <?php foreach ($surat_masuk as $index => $surat): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($surat['no_surat']) ?></strong></td>
                            <td><?= date('d M Y', strtotime($surat['tanggal_terima'])) ?></td>
                            <td><?= htmlspecialchars($surat['pengirim']) ?></td>
                            <td class="perihal-col">
                                <div class="perihal-text" title="<?= htmlspecialchars($surat['perihal']) ?>">
                                    <?= htmlspecialchars(substr($surat['perihal'], 0, 50)) ?><?= strlen($surat['perihal']) > 50 ? '...' : '' ?>
                                </div>
                            </td>
                            <td>
                                <span class="jenis-badge jenis-<?= $surat['jenis_surat'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $surat['jenis_surat'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $surat['status'] ?>">
                                    <?= ucfirst($surat['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($surat['input_by'] ?? 'System') ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-view" onclick="viewSurat(<?= $surat['id'] ?>)" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action btn-edit" onclick="editSurat(<?= $surat['id'] ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-download" onclick="downloadFile(<?= $surat['id'] ?>)" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="no-data">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>Tidak ada data surat masuk untuk periode yang dipilih</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="table-footer">
        <div class="showing-entries">
            Menampilkan <span id="showingStart">1</span> - <span id="showingEnd"><?= min(10, count($surat_masuk)) ?></span> 
            dari <span id="totalEntries"><?= count($surat_masuk) ?></span> data
        </div>
        <div class="pagination" id="pagination">
            <!-- Pagination will be generated by JavaScript -->
        </div>
    </div>
</div>

<!-- Summary Section -->
<div class="summary-section">
    <div class="summary-card">
        <h3><i class="fas fa-clipboard-list"></i> Ringkasan Laporan</h3>
        <div class="summary-content">
            <div class="summary-row">
                <span class="summary-label">Periode Laporan:</span>
                <span class="summary-value"><?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Total Surat Masuk:</span>
                <span class="summary-value"><?= number_format($statistics['total']) ?> surat</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Persentase Selesai:</span>
                <span class="summary-value"><?= number_format(($statistics['total'] > 0 ? ($statistics['selesai'] / $statistics['total']) * 100 : 0), 1) ?>%</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Rata-rata per Hari:</span>
                <span class="summary-value">
                    <?php 
                    $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
                    echo number_format($statistics['total'] / $days, 1);
                    ?> surat
                </span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Dibuat pada:</span>
                <span class="summary-value"><?= date('d M Y H:i:s') ?></span>
            </div>
        </div>
        
        <div class="summary-actions">
            <button class="btn btn-primary" onclick="generateDetailReport()">
                <i class="fas fa-file-alt"></i> Generate Laporan Detail
            </button>
            <button class="btn btn-success" onclick="scheduleReport()">
                <i class="fas fa-calendar-plus"></i> Jadwalkan Laporan
            </button>
        </div>
    </div>
</div>

<script src="assets/js/laporan.js"></script>
<script>
// Page specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize chart
    initializeMonthlyChart();
    
    // Initialize table pagination
    initializeTablePagination();
    
    // Set default date if empty
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    if (!startDate.value) {
        startDate.value = '<?= date('Y-m-01') ?>';
    }
    
    if (!endDate.value) {
        endDate.value = '<?= date('Y-m-d') ?>';
    }
});

// Initialize monthly trend chart
function initializeMonthlyChart() {
    const canvas = document.getElementById('monthlyChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const monthlyData = <?= json_encode($monthly_trend) ?>;
    
    drawMonthlyTrendChart(ctx, monthlyData, canvas.width, canvas.height);
}

// Draw monthly trend chart
function drawMonthlyTrendChart(ctx, data, width, height) {
    const padding = 60;
    const chartWidth = width - (padding * 2);
    const chartHeight = height - (padding * 2);
    
    // Clear canvas
    ctx.clearRect(0, 0, width, height);
    
    // Draw background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    
    if (data.length === 0) {
        ctx.fillStyle = '#7f8c8d';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Tidak ada data untuk ditampilkan', width / 2, height / 2);
        return;
    }
    
    // Find max value
    const maxValue = Math.max(...data.map(d => parseInt(d.jumlah)));
    const maxDisplayValue = Math.ceil(maxValue * 1.1);
    
    // Draw grid lines
    ctx.strokeStyle = '#e1e8ed';
    ctx.lineWidth = 1;
    
    // Horizontal grid lines
    for (let i = 0; i <= 5; i++) {
        const y = padding + (i * chartHeight / 5);
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(width - padding, y);
        ctx.stroke();
        
        // Y-axis labels
        const value = maxDisplayValue - (i * maxDisplayValue / 5);
        ctx.fillStyle = '#7f8c8d';
        ctx.font = '12px Arial';
        ctx.textAlign = 'right';
        ctx.fillText(Math.round(value), padding - 10, y + 4);
    }
    
    // Vertical grid lines and labels
    for (let i = 0; i < data.length; i++) {
        const x = padding + (i * chartWidth / (data.length - 1));
        
        // Vertical lines
        ctx.beginPath();
        ctx.moveTo(x, padding);
        ctx.lineTo(x, height - padding);
        ctx.stroke();
        
        // X-axis labels
        ctx.fillStyle = '#7f8c8d';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        const monthName = formatMonth(data[i].bulan);
        ctx.fillText(monthName, x, height - padding + 20);
    }
    
    // Draw data line
    ctx.strokeStyle = '#667eea';
    ctx.lineWidth = 3;
    ctx.beginPath();
    
    for (let i = 0; i < data.length; i++) {
        const x = padding + (i * chartWidth / (data.length - 1));
        const y = height - padding - (parseInt(data[i].jumlah) / maxDisplayValue * chartHeight);
        
        if (i === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
        
        // Draw data points
        ctx.fillStyle = '#667eea';
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, 2 * Math.PI);
        ctx.fill();
        
        // Draw value labels
        ctx.fillStyle = '#2c3e50';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(data[i].jumlah, x, y - 10);
    }
    
    ctx.stroke();
}

// Format month for display
function formatMonth(monthStr) {
    const [year, month] = monthStr.split('-');
    const monthNames = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    return monthNames[parseInt(month) - 1] + ' ' + year.substr(2);
}

// Export functions
function exportExcel() {
    showLoading();
    
    // Create form for export
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export/surat-masuk-excel.php';
    
    // Add current filter parameters
    const params = new URLSearchParams(window.location.search);
    for (const [key, value] of params.entries()) {
        if (key !== 'page' && key !== 'subpage') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    setTimeout(hideLoading, 2000);
}

function exportPDF() {
    showLoading();
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export/surat-masuk-pdf.php';
    
    const params = new URLSearchParams(window.location.search);
    for (const [key, value] of params.entries()) {
        if (key !== 'page' && key !== 'subpage') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    setTimeout(hideLoading, 2000);
}

function printReport() {
    window.print();
}

function resetFilter() {
    document.getElementById('start_date').value = '<?= date('Y-m-01') ?>';
    document.getElementById('end_date').value = '<?= date('Y-m-d') ?>';
    document.getElementById('status').value = '';
    document.getElementById('type').value = '';
    
    // Submit form
    document.getElementById('filterForm').submit();
}

// Table functions
function viewSurat(id) {
    window.open('?page=surat-masuk&action=view&id=' + id, '_blank');
}

function editSurat(id) {
    window.location.href = '?page=surat-masuk&action=edit&id=' + id;
}

function downloadFile(id) {
    window.location.href = 'download/surat-masuk.php?id=' + id;
}

function generateDetailReport() {
    showAlert('Laporan detail sedang diproses...', 'info');
    // Implementation for detailed report generation
}

function scheduleReport() {
    showAlert('Fitur penjadwalan laporan akan segera tersedia', 'info');
    // Implementation for report scheduling
}

// Print specific styles
window.addEventListener('beforeprint', function() {
    document.body.classList.add('printing');
});

window.addEventListener('afterprint', function() {
    document.body.classList.remove('printing');
});
</script>