<?php
/**
 * Database Helper Functions - E-Surat-PTUN-BJM
 * Fungsi-fungsi helper untuk operasi database
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("CALL GetDashboardStats()");
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        return [
            'surat_masuk_today' => 0,
            'surat_keluar_today' => 0,
            'pending_disposisi' => 0,
            'completed_month' => 0,
            'total_surat_masuk' => 0,
            'total_surat_keluar' => 0
        ];
    }
}

/**
 * Generate nomor surat
 */
function generateNomorSurat($jenis, $tahun = null) {
    if (!$tahun) {
        $tahun = date('Y');
    }
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("CALL GenerateNomorSurat(?, ?, @nomor_surat)");
        $stmt->execute([$jenis, $tahun]);
        
        $result = $pdo->query("SELECT @nomor_surat as nomor_surat")->fetch();
        return $result['nomor_surat'];
    } catch (PDOException $e) {
        // Fallback manual generation
        if ($jenis === 'masuk') {
            return 'SM-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        } else {
            $bulan = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            return str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT) . '/PTUN-BJM/' . $bulan[date('n') - 1] . '/' . $tahun;
        }
    }
}

/**
 * SURAT MASUK FUNCTIONS
 */

/**
 * Get surat masuk with filters
 */
function getSuratMasuk($filters = []) {
    try {
        $pdo = getConnection();
        
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $where[] = "DATE(tanggal_terima) BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['jenis_surat'])) {
            $where[] = "jenis_surat = ?";
            $params[] = $filters['jenis_surat'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(no_surat LIKE ? OR pengirim LIKE ? OR perihal LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql = "
            SELECT sm.*, u.full_name as input_by
            FROM surat_masuk sm
            LEFT JOIN users u ON sm.user_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY sm.tanggal_terima DESC, sm.created_at DESC
        ";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get surat masuk by ID
 */
function getSuratMasukById($id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT sm.*, u.full_name as input_by
            FROM surat_masuk sm
            LEFT JOIN users u ON sm.user_id = u.id
            WHERE sm.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Insert surat masuk
 */
function insertSuratMasuk($data) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO surat_masuk (
                no_surat, no_agenda, tanggal_surat, tanggal_terima, pengirim, 
                alamat_pengirim, perihal, jenis_surat, sifat_surat, status, 
                file_surat, lampiran, keterangan, user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['no_surat'],
            $data['no_agenda'],
            $data['tanggal_surat'],
            $data['tanggal_terima'],
            $data['pengirim'],
            $data['alamat_pengirim'] ?? null,
            $data['perihal'],
            $data['jenis_surat'],
            $data['sifat_surat'] ?? 'biasa',
            $data['status'] ?? 'baru',
            $data['file_surat'] ?? null,
            $data['lampiran'] ?? null,
            $data['keterangan'] ?? null,
            $data['user_id']
        ]);
        
        if ($result) {
            return $pdo->lastInsertId();
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update surat masuk
 */
function updateSuratMasuk($id, $data) {
    try {
        $pdo = getConnection();
        
        $fields = [];
        $values = [];
        
        $allowedFields = [
            'no_surat', 'no_agenda', 'tanggal_surat', 'tanggal_terima', 
            'pengirim', 'alamat_pengirim', 'perihal', 'jenis_surat', 
            'sifat_surat', 'status', 'file_surat', 'lampiran', 'keterangan'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "updated_at = NOW()";
        $values[] = $id;
        
        $query = "UPDATE surat_masuk SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($query);
        
        return $stmt->execute($values);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete surat masuk
 */
function deleteSuratMasuk($id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM surat_masuk WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * SURAT KELUAR FUNCTIONS
 */

/**
 * Get surat keluar with filters
 */
function getSuratKeluar($filters = []) {
    try {
        $pdo = getConnection();
        
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $where[] = "DATE(tanggal_surat) BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['jenis_surat'])) {
            $where[] = "jenis_surat = ?";
            $params[] = $filters['jenis_surat'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(no_surat LIKE ? OR tujuan LIKE ? OR perihal LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql = "
            SELECT sk.*, u.full_name as input_by, ua.full_name as approved_by_name
            FROM surat_keluar sk
            LEFT JOIN users u ON sk.user_id = u.id
            LEFT JOIN users ua ON sk.approved_by = ua.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY sk.tanggal_surat DESC, sk.created_at DESC
        ";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * DISPOSISI FUNCTIONS
 */

/**
 * Get disposisi with filters
 */
function getDisposisi($filters = []) {
    try {
        $pdo = getConnection();
        
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = "d.ke_user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "d.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['prioritas'])) {
            $where[] = "d.prioritas = ?";
            $params[] = $filters['prioritas'];
        }
        
        $sql = "
            SELECT d.*, sm.no_surat, sm.pengirim, sm.perihal,
                   uf.full_name as dari_user, ut.full_name as ke_user
            FROM disposisi d
            JOIN surat_masuk sm ON d.surat_masuk_id = sm.id
            JOIN users uf ON d.dari_user_id = uf.id
            JOIN users ut ON d.ke_user_id = ut.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY d.created_at DESC
        ";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * NOTIFICATION FUNCTIONS
 */

/**
 * Get notifications for user
 */
function getNotifications($userId, $limit = 10, $unreadOnly = false) {
    try {
        $pdo = getConnection();
        
        $where = "user_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $where .= " AND is_read = 0";
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE $where 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute(array_merge($params, [$limit]));
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Count unread notifications
 */
function countUnreadNotifications($userId) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Mark notification as read
 */
function markNotificationRead($notificationId, $userId) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * STATISTICS FUNCTIONS
 */

/**
 * Get surat masuk statistics
 */
function getSuratMasukStats($startDate = null, $endDate = null) {
    try {
        $pdo = getConnection();
        
        $where = "1=1";
        $params = [];
        
        if ($startDate && $endDate) {
            $where = "DATE(tanggal_terima) BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'baru' THEN 1 END) as baru,
                COUNT(CASE WHEN status = 'proses' THEN 1 END) as proses,
                COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai,
                COUNT(CASE WHEN status = 'urgent' THEN 1 END) as urgent,
                COUNT(CASE WHEN DATE(tanggal_terima) = CURDATE() THEN 1 END) as hari_ini
            FROM surat_masuk 
            WHERE $where
        ");
        $stmt->execute($params);
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        return [
            'total' => 0, 'baru' => 0, 'proses' => 0, 
            'selesai' => 0, 'urgent' => 0, 'hari_ini' => 0
        ];
    }
}

/**
 * Get monthly trend
 */
function getMonthlyTrend($table = 'surat_masuk', $dateField = 'tanggal_terima') {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT($dateField, '%Y-%m') as bulan,
                COUNT(*) as jumlah
            FROM $table 
            WHERE $dateField >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT($dateField, '%Y-%m')
            ORDER BY bulan ASC
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * UTILITY FUNCTIONS
 */

/**
 * Upload file
 */
function uploadFile($file, $destination = 'uploads/') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    // Create directory if not exists
    if (!file_exists($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $destination . $filename;
    
    // Check file type
    $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    if (!in_array(strtolower($extension), $allowedTypes)) {
        return false;
    }
    
    // Check file size (max 10MB)
    if ($file['size'] > 10485760) {
        return false;
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

/**
 * Delete file
 */
function deleteFile($filename, $directory = 'uploads/') {
    $filepath = $directory . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    
    return $bytes;
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate date
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Generate pagination
 */
function generatePagination($currentPage, $totalPages, $baseUrl = '') {
    $pagination = [];
    
    // Previous button
    if ($currentPage > 1) {
        $pagination[] = [
            'type' => 'prev',
            'page' => $currentPage - 1,
            'url' => $baseUrl . '&page=' . ($currentPage - 1),
            'active' => false
        ];
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination[] = [
            'type' => 'page',
            'page' => $i,
            'url' => $baseUrl . '&page=' . $i,
            'active' => $i == $currentPage
        ];
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $pagination[] = [
            'type' => 'next',
            'page' => $currentPage + 1,
            'url' => $baseUrl . '&page=' . ($currentPage + 1),
            'active' => false
        ];
    }
    
    return $pagination;
}
?>