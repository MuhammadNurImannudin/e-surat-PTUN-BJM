/**
 * E-Surat-PTUN-BJM Main JavaScript
 * Sistem Persuratan Digital Banjarmasin
 */

// Global variables
let sidebarCollapsed = false;
let notificationModal = null;
let userDropdown = null;

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    startRealTimeUpdates();
});

// Initialize Application
function initializeApp() {
    // Get DOM elements
    notificationModal = document.getElementById('notification-modal');
    userDropdown = document.getElementById('user-dropdown');
    
    // Initialize sidebar state
    const sidebar = document.querySelector('.sidebar');
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
        toggleSidebar();
    }
    
    // Initialize tooltips
    initializeTooltips();
    
    // Load initial data
    loadDashboardData();
    
    console.log('E-Surat-PTUN-BJM initialized successfully');
}

// Setup Event Listeners
function setupEventListeners() {
    // Sidebar toggle
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }
    
    // Mobile menu toggle
    setupMobileMenu();
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.user-dropdown')) {
            closeUserDropdown();
        }
        if (!event.target.closest('.notification-btn') && !event.target.closest('.notification-modal')) {
            closeNotifications();
        }
    });
    
    // Keyboard shortcuts
    setupKeyboardShortcuts();
    
    // Form validations
    setupFormValidations();
}

// Toggle Sidebar
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const header = document.querySelector('.header');
    const mainContent = document.querySelector('.main-content');
    const footer = document.querySelector('.footer');
    const toggleIcon = document.querySelector('.sidebar-toggle-btn i');
    
    sidebarCollapsed = !sidebarCollapsed;
    
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
        header.classList.add('sidebar-collapsed');
        mainContent.classList.add('sidebar-collapsed');
        footer.classList.add('sidebar-collapsed');
        toggleIcon.classList.remove('fa-angle-double-left');
        toggleIcon.classList.add('fa-angle-double-right');
    } else {
        sidebar.classList.remove('collapsed');
        header.classList.remove('sidebar-collapsed');
        mainContent.classList.remove('sidebar-collapsed');
        footer.classList.remove('sidebar-collapsed');
        toggleIcon.classList.remove('fa-angle-double-right');
        toggleIcon.classList.add('fa-angle-double-left');
    }
    
    // Save state to localStorage
    localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
}

// Mobile Menu Setup
function setupMobileMenu() {
    // Add mobile menu button to header for small screens
    if (window.innerWidth <= 768) {
        addMobileMenuButton();
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            addMobileMenuButton();
        } else {
            removeMobileMenuButton();
        }
    });
}

// Add Mobile Menu Button
function addMobileMenuButton() {
    const headerLeft = document.querySelector('.header-left');
    let mobileBtn = document.querySelector('.mobile-menu-btn');
    
    if (!mobileBtn) {
        mobileBtn = document.createElement('button');
        mobileBtn.className = 'mobile-menu-btn';
        mobileBtn.innerHTML = '<i class="fas fa-bars"></i>';
        mobileBtn.addEventListener('click', toggleMobileSidebar);
        headerLeft.insertBefore(mobileBtn, headerLeft.firstChild);
    }
}

// Remove Mobile Menu Button
function removeMobileMenuButton() {
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    if (mobileBtn) {
        mobileBtn.remove();
    }
}

// Toggle Mobile Sidebar
function toggleMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('mobile-open');
}

// Toggle Submenu
function toggleSubmenu(event, submenuId) {
    event.preventDefault();
    
    const submenu = document.getElementById(submenuId);
    const arrow = event.currentTarget.querySelector('.menu-arrow');
    const parentLink = event.currentTarget;
    
    // Close other submenus
    document.querySelectorAll('.submenu').forEach(menu => {
        if (menu.id !== submenuId) {
            menu.classList.remove('active');
        }
    });
    
    document.querySelectorAll('.menu-arrow').forEach(arr => {
        if (arr !== arrow) {
            arr.classList.remove('rotated');
        }
    });
    
    // Toggle current submenu
    submenu.classList.toggle('active');
    arrow.classList.toggle('rotated');
    
    // Update active state for parent
    document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
    parentLink.classList.add('active');
    
    // Close mobile sidebar after selection (mobile only)
    if (window.innerWidth <= 768) {
        setTimeout(() => {
            document.querySelector('.sidebar').classList.remove('mobile-open');
        }, 300);
    }
}

// Show Notifications
function showNotifications() {
    if (notificationModal) {
        notificationModal.classList.add('show');
        
        // Mark notifications as read
        setTimeout(() => {
            const badge = document.querySelector('.notification-badge');
            const newItems = document.querySelectorAll('.notification-item.new');
            
            newItems.forEach(item => {
                item.classList.remove('new');
            });
            
            // Update badge count
            const currentCount = parseInt(badge.textContent);
            if (currentCount > 0) {
                badge.textContent = Math.max(0, currentCount - newItems.length);
                if (badge.textContent === '0') {
                    badge.style.display = 'none';
                }
            }
        }, 1000);
    }
}

// Close Notifications
function closeNotifications() {
    if (notificationModal) {
        notificationModal.classList.remove('show');
    }
}

// Toggle User Dropdown
function toggleUserDropdown() {
    if (userDropdown) {
        userDropdown.classList.toggle('show');
    }
}

// Close User Dropdown
function closeUserDropdown() {
    if (userDropdown) {
        userDropdown.classList.remove('show');
    }
}

// Load Dashboard Data
function loadDashboardData() {
    // Simulate API call to load dashboard statistics
    showLoading();
    
    setTimeout(() => {
        updateDashboardStats();
        hideLoading();
    }, 1000);
}

// Update Dashboard Statistics
function updateDashboardStats() {
    // Simulate real-time data updates
    const stats = {
        totalLetters: Math.floor(Math.random() * 1000) + 1200,
        todayIncoming: Math.floor(Math.random() * 20) + 15,
        todayOutgoing: Math.floor(Math.random() * 15) + 10,
        pendingDisposition: Math.floor(Math.random() * 10) + 5,
        completedThisMonth: Math.floor(Math.random() * 100) + 150
    };
    
    // Update stat cards
    const statCards = document.querySelectorAll('.stat-number');
    if (statCards.length >= 4) {
        statCards[0].textContent = stats.todayIncoming;
        statCards[1].textContent = stats.todayOutgoing;
        statCards[2].textContent = stats.pendingDisposition;
        statCards[3].textContent = stats.completedThisMonth;
    }
    
    // Update footer total
    const totalElement = document.getElementById('total-letters');
    if (totalElement) {
        totalElement.textContent = stats.totalLetters.toLocaleString('id-ID');
    }
}

// Real-time Updates
function startRealTimeUpdates() {
    // Update time every second
    setInterval(updateTime, 1000);
    
    // Update statistics every 30 seconds
    setInterval(updateDashboardStats, 30000);
    
    // Check for new notifications every 60 seconds
    setInterval(checkNewNotifications, 60000);
}

// Update Time Display
function updateTime() {
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID');
        const dateString = now.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
        
        timeElement.innerHTML = timeString + '<br><small>' + dateString + '</small>';
    }
}

// Check for New Notifications
function checkNewNotifications() {
    // Simulate checking for new notifications
    const random = Math.random();
    if (random > 0.8) { // 20% chance
        addNewNotification();
    }
}

// Add New Notification
function addNewNotification() {
    const badge = document.querySelector('.notification-badge');
    const currentCount = parseInt(badge.textContent) || 0;
    
    // Update badge
    badge.textContent = currentCount + 1;
    badge.style.display = 'flex';
    
    // Add pulse animation to notification button
    const notificationBtn = document.querySelector('.notification-btn');
    notificationBtn.style.animation = 'none';
    setTimeout(() => {
        notificationBtn.style.animation = 'pulse 0.5s ease';
    }, 10);
    
    // Add notification to modal (you can customize this)
    console.log('New notification received');
}

// Setup Keyboard Shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(event) {
        // Ctrl/Cmd + K for search
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            event.preventDefault();
            // Focus search input if available
            const searchInput = document.querySelector('input[type="search"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Ctrl/Cmd + B for toggle sidebar
        if ((event.ctrlKey || event.metaKey) && event.key === 'b') {
            event.preventDefault();
            toggleSidebar();
        }
        
        // Escape to close modals/dropdowns
        if (event.key === 'Escape') {
            closeNotifications();
            closeUserDropdown();
        }
    });
}

// Initialize Tooltips
function initializeTooltips() {
    // Add tooltips to elements with title attributes
    const elementsWithTitles = document.querySelectorAll('[title]');
    elementsWithTitles.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

// Show Tooltip
function showTooltip(event) {
    const element = event.target;
    const title = element.getAttribute('title');
    
    if (title) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = title;
        tooltip.style.position = 'absolute';
        tooltip.style.background = 'rgba(0, 0, 0, 0.8)';
        tooltip.style.color = 'white';
        tooltip.style.padding = '5px 10px';
        tooltip.style.borderRadius = '4px';
        tooltip.style.fontSize = '12px';
        tooltip.style.zIndex = '9999';
        tooltip.style.pointerEvents = 'none';
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
        
        element.tooltipElement = tooltip;
        element.removeAttribute('title');
        element.originalTitle = title;
    }
}

// Hide Tooltip
function hideTooltip(event) {
    const element = event.target;
    
    if (element.tooltipElement) {
        element.tooltipElement.remove();
        element.tooltipElement = null;
        element.setAttribute('title', element.originalTitle);
    }
}

// Form Validations
function setupFormValidations() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!validateForm(form)) {
                event.preventDefault();
            }
        });
    });
}

// Validate Form
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Field ini wajib diisi');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    return isValid;
}

// Show Field Error
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '12px';
    errorDiv.style.marginTop = '4px';
    
    field.parentNode.appendChild(errorDiv);
}

// Clear Field Error
function clearFieldError(field) {
    field.classList.remove('error');
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Utility Functions
function showLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function showAlert(message, type = 'info') {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">
            <i class="fas fa-info-circle"></i>
            <span>${message}</span>
            <button class="alert-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Style the alert
    alert.style.cssText = `
        position: fixed;
        top: 90px;
        right: 20px;
        min-width: 300px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Export functions for global use
window.toggleSubmenu = toggleSubmenu;
window.showNotifications = showNotifications;
window.closeNotifications = closeNotifications;
window.toggleUserDropdown = toggleUserDropdown;
window.toggleSidebar = toggleSidebar;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.showAlert = showAlert;