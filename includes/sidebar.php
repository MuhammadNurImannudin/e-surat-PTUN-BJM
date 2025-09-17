<?php
// Fungsi untuk menentukan menu aktif
function isActiveMenu($current_page, $menu_page, $current_subpage = '', $menu_subpage = '') {
    if ($current_page === $menu_page) {
        if (!empty($menu_subpage)) {
            return $current_subpage === $menu_subpage ? 'active' : '';
        }
        return 'active';
    }
    return '';
}

$current_page = $page ?? 'dashboard';
$current_subpage = $subpage ?? '';
?>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-balance-scale"></i>
            </div>
            <div class="logo-text">
                <h2>E-Surat-PTUN-BJM</h2>
                <p>Banjarmasin Digital</p>
            </div>
        </div>
    </div>
    
    <ul class="menu-items">
        <!-- Dashboard -->
        <li class="menu-item">
            <a href="?page=dashboard" class="menu-link <?= isActiveMenu($current_page, 'dashboard') ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span class="menu-text">Dashboard</span>
            </a>
        </li>
        
        <!-- Surat Masuk -->
        <li class="menu-item">
            <a href="?page=surat-masuk" class="menu-link <?= isActiveMenu($current_page, 'surat-masuk') ?>">
                <i class="fas fa-inbox"></i>
                <span class="menu-text">Surat Masuk</span>
            </a>
        </li>
        
        <!-- Surat Keluar -->
        <li class="menu-item">
            <a href="?page=surat-keluar" class="menu-link <?= isActiveMenu($current_page, 'surat-keluar') ?>">
                <i class="fas fa-paper-plane"></i>
                <span class="menu-text">Surat Keluar</span>
            </a>
        </li>
        
        <!-- Arsip -->
        <li class="menu-item">
            <a href="?page=arsip" class="menu-link <?= isActiveMenu($current_page, 'arsip') ?>">
                <i class="fas fa-archive"></i>
                <span class="menu-text">Arsip Surat</span>
            </a>
        </li>
        
        <!-- Laporan dengan Submenu -->
        <li class="menu-item">
            <a href="#" class="menu-link <?= isActiveMenu($current_page, 'laporan') ?>" 
               onclick="toggleSubmenu(event, 'laporan-submenu')">
                <i class="fas fa-chart-bar"></i>
                <span class="menu-text">Laporan</span>
                <i class="fas fa-chevron-right menu-arrow <?= $current_page === 'laporan' ? 'rotated' : '' ?>"></i>
            </a>
            <div class="submenu <?= $current_page === 'laporan' ? 'active' : '' ?>" id="laporan-submenu">
                <div class="submenu-item">
                    <a href="?page=laporan&subpage=surat-masuk" 
                       class="submenu-link <?= isActiveMenu($current_page, 'laporan', $current_subpage, 'surat-masuk') ?>">
                        <span>Laporan Surat Masuk</span>
                    </a>
                </div>
                <div class="submenu-item">
                    <a href="?page=laporan&subpage=surat-keluar" 
                       class="submenu-link <?= isActiveMenu($current_page, 'laporan', $current_subpage, 'surat-keluar') ?>">
                        <span>Laporan Surat Keluar</span>
                    </a>
                </div>
                <div class="submenu-item">
                    <a href="?page=laporan&subpage=disposisi" 
                       class="submenu-link <?= isActiveMenu($current_page, 'laporan', $current_subpage, 'disposisi') ?>">
                        <span>Laporan Disposisi</span>
                    </a>
                </div>
                <div class="submenu-item">
                    <a href="?page=laporan&subpage=arsip" 
                       class="submenu-link <?= isActiveMenu($current_page, 'laporan', $current_subpage, 'arsip') ?>">
                        <span>Laporan Arsip</span>
                    </a>
                </div>
                <div class="submenu-item">
                    <a href="?page=laporan&subpage=statistik" 
                       class="submenu-link <?= isActiveMenu($current_page, 'laporan', $current_subpage, 'statistik') ?>">
                        <span>Laporan Statistik</span>
                    </a>
                </div>
            </div>
        </li>
        
        <!-- Disposisi -->
        <li class="menu-item">
            <a href="?page=disposisi" class="menu-link <?= isActiveMenu($current_page, 'disposisi') ?>">
                <i class="fas fa-share-alt"></i>
                <span class="menu-text">Disposisi</span>
            </a>
        </li>
        
        <!-- Pengaturan -->
        <li class="menu-item">
            <a href="?page=pengaturan" class="menu-link <?= isActiveMenu($current_page, 'pengaturan') ?>">
                <i class="fas fa-cogs"></i>
                <span class="menu-text">Pengaturan</span>
            </a>
        </li>
        
        <!-- Bantuan -->
        <li class="menu-item">
            <a href="?page=help" class="menu-link <?= isActiveMenu($current_page, 'help') ?>">
                <i class="fas fa-question-circle"></i>
                <span class="menu-text">Bantuan</span>
            </a>
        </li>
    </ul>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-status">
            <div class="status-indicator online"></div>
            <span>Online</span>
        </div>
        <div class="sidebar-toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-angle-double-left"></i>
        </div>
    </div>
</nav>