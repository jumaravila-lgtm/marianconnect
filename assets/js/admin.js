/**
 * MARIANCONNECT - Admin Panel JavaScript
 * Handles all admin panel interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // SIDEBAR FUNCTIONALITY
    // ==========================================
    
    const sidebar = document.getElementById('adminSidebar');
    const mainContent = document.getElementById('adminMain');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('sidebarToggle');
    
    // Toggle sidebar on desktop
    function toggleSidebar() {
        sidebar?.classList.toggle('collapsed');
        mainContent?.classList.toggle('expanded');
        localStorage.setItem('sidebarCollapsed', sidebar?.classList.contains('collapsed'));
    }
    
    sidebarToggle?.addEventListener('click', function() {
    if (window.innerWidth <= 768) {
        sidebar?.classList.toggle('mobile-open');
    } else {
        toggleSidebar();
    }
});
    
    // Close mobile sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (sidebar?.classList.contains('mobile-open')) {
            if (!sidebar.contains(e.target) && !mobileToggle?.contains(e.target)) {
                sidebar.classList.remove('mobile-open');
            }
        }
    });
    
    // Restore sidebar state from localStorage
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar?.classList.add('collapsed');
        mainContent?.classList.add('expanded');
    }
    
    
    // ==========================================
    // DROPDOWN MENUS
    // ==========================================
    
    // Notification Dropdown
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    notificationBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown?.classList.toggle('active');
        profileDropdown?.classList.remove('active');
    });
    
    // Profile Dropdown
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    
    profileBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown?.classList.toggle('active');
        notificationDropdown?.classList.remove('active');
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        notificationDropdown?.classList.remove('active');
        profileDropdown?.classList.remove('active');
    });
    
    
    // ==========================================
    // ALERT AUTO-DISMISS
    // ==========================================
    
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    
    // ==========================================
    // CONFIRM DELETE DIALOGS
    // ==========================================
    
    document.querySelectorAll('.confirm-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const message = this.dataset.confirmMessage || 'Are you sure you want to delete this item? This action cannot be undone.';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    
    // ==========================================
    // CHARACTER COUNTERS FOR TEXTAREAS
    // ==========================================
    
    document.querySelectorAll('textarea[maxlength]').forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        
        // Create counter element
        const counter = document.createElement('small');
        counter.className = 'char-counter';
        counter.style.cssText = 'display: block; margin-top: 0.5rem; font-size: 0.85rem; color: #6c757d; text-align: right;';
        counter.textContent = `${textarea.value.length} / ${maxLength}`;
        
        // Insert after textarea
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);
        
        // Update on input
        textarea.addEventListener('input', function() {
            const current = this.value.length;
            counter.textContent = `${current} / ${maxLength}`;
            
            // Change color when approaching limit
            if (current > maxLength * 0.9) {
                counter.style.color = '#dc3545';
            } else if (current > maxLength * 0.75) {
                counter.style.color = '#ffc107';
            } else {
                counter.style.color = '#6c757d';
            }
        });
    });
    
    
    // ==========================================
    // TABLE ROW ACTIONS
    // ==========================================
    
    // Make table rows clickable (if they have data-href)
    document.querySelectorAll('tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking on buttons/links
            if (e.target.closest('a, button')) return;
            window.location.href = this.dataset.href;
        });
    });
    
    
    // ==========================================
    // BULK ACTIONS (Select All Checkbox)
    // ==========================================
    
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    
    selectAllCheckbox?.addEventListener('change', function() {
        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });
    
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    function updateBulkActions() {
        const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        if (bulkActionsBar) {
            if (checkedCount > 0) {
                bulkActionsBar.style.display = 'flex';
                if (selectedCount) {
                    selectedCount.textContent = checkedCount;
                }
            } else {
                bulkActionsBar.style.display = 'none';
            }
        }
    }
    
    
    // ==========================================
    // SEARCH WITH DEBOUNCE
    // ==========================================
    
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                // Submit the form or trigger search
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            }, 500); // Wait 500ms after user stops typing
        });
    });
    
    
    // ==========================================
    // COPY TO CLIPBOARD
    // ==========================================
    
    document.querySelectorAll('.copy-to-clipboard').forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.dataset.text || this.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                // Show success feedback
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                this.style.color = '#28a745';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.color = '';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy to clipboard');
            });
        });
    });
    
    
    // ==========================================
    // TOOLTIP INITIALIZATION
    // ==========================================
    
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.style.position = 'relative';
        
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = this.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                padding: 0.5rem 0.75rem;
                background: #333;
                color: white;
                border-radius: 4px;
                font-size: 0.85rem;
                white-space: nowrap;
                z-index: 1000;
                margin-bottom: 0.5rem;
                pointer-events: none;
            `;
            this.appendChild(tooltip);
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('.custom-tooltip');
            tooltip?.remove();
        });
    });
    
    
    // ==========================================
    // FORM DIRTY CHECK (Unsaved Changes Warning)
    // ==========================================
    
    const forms = document.querySelectorAll('form[data-warn-unsaved]');
    forms.forEach(form => {
        let formChanged = false;
        
        form.addEventListener('input', () => {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        form.addEventListener('submit', () => {
            formChanged = false;
        });
    });
    
    
    // ==========================================
    // SMOOTH SCROLL TO TOP
    // ==========================================
    
    const scrollTopBtn = document.createElement('button');
    scrollTopBtn.id = 'scrollTopBtn';
    scrollTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollTopBtn.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        background: var(--admin-primary, #003f87);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: all 0.3s;
        z-index: 999;
    `;
    document.body.appendChild(scrollTopBtn);
    
    // Show/hide button on scroll
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            scrollTopBtn.style.display = 'flex';
        } else {
            scrollTopBtn.style.display = 'none';
        }
    });
    
    // Scroll to top on click
    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    scrollTopBtn.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
    });
    
    scrollTopBtn.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
    
    
    // ==========================================
    // LOADING OVERLAY (for AJAX operations)
    // ==========================================
    
    window.showLoadingOverlay = function(message = 'Loading...') {
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.innerHTML = `
            <div style="text-align: center;">
                <i class="fas fa-spinner fa-spin" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p style="font-size: 1.1rem; font-weight: 500;">${message}</p>
            </div>
        `;
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
        `;
        document.body.appendChild(overlay);
    };
    
    window.hideLoadingOverlay = function() {
        const overlay = document.getElementById('loadingOverlay');
        overlay?.remove();
    };
    
    
    console.log('✅ Admin.js loaded successfully');
});
