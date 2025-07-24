/**
 * Module Management JavaScript
 * Handles activation/deactivation of CMS modules
 */

// Prevent multiple executions
if (window.moduleManagerLoaded) {
    console.log('Module manager already loaded, skipping...');
    return;
}
window.moduleManagerLoaded = true;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Module management JS loaded (new version)');
    console.log('Current URL:', window.location.href);
    
    const onclickButtons = document.querySelectorAll('button[onclick*="toggleModule"]');
    const dataButtons = document.querySelectorAll('button[data-module]');
    console.log('Available onclick buttons:', onclickButtons.length);
    console.log('Available data buttons:', dataButtons.length);
    
    // Function to show notifications
    function showNotification(message, type) {
        // Remove any existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(n => n.remove());
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 transition-all duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Function to toggle module status
    function toggleModule(moduleName, activate) {
        console.log('toggleModule called with:', moduleName, activate);
        const action = activate ? 'activate' : 'deactivate';
        
        if (!confirm(`Are you sure you want to ${action} the ${moduleName} module?`)) {
            console.log('User cancelled action');
            return;
        }
        
        console.log(`Proceeding with ${moduleName} ${action}`);
        
        // Disable all buttons during the request
        const buttons = document.querySelectorAll('button[onclick*="toggleModule"]');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.classList.add('opacity-50');
        });
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', action);
        
        // Make the request
        fetch(`/admin/modules/${moduleName}/toggle`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                showNotification(data.message, 'success');
                // Reload the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'An error occurred', 'error');
                // Re-enable buttons on error
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50');
                });
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showNotification('Network error: ' + error.message, 'error');
            // Re-enable buttons on error
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('opacity-50');  
            });
        });
    }
    
    // Make toggleModule function globally available
    window.toggleModule = toggleModule;
    
    // Setup event listeners for both onclick and data attributes
    const allButtons = document.querySelectorAll('button[data-module]');
    
    allButtons.forEach(button => {
        console.log('Setting up button:', button.dataset.module, button.dataset.action);
        
        // Remove any existing onclick to avoid conflicts
        if (button.hasAttribute('onclick')) {
            console.log('Removing onclick from', button.dataset.module);
            button.removeAttribute('onclick');
        }
        
        // Add our event listener
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Button clicked via addEventListener:', this.dataset.module, this.dataset.action);
            
            const moduleName = this.dataset.module;
            const activate = this.dataset.action === 'activate';
            
            toggleModule(moduleName, activate);
        });
        
        // Also try adding the onclick back as a backup
        button.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Button clicked via onclick:', this.dataset.module, this.dataset.action);
            
            const moduleName = this.dataset.module;
            const activate = this.dataset.action === 'activate';
            
            toggleModule(moduleName, activate);
            return false;
        };
    });
    
    // Debug: Test if buttons are clickable at all
    setTimeout(() => {
        console.log('Testing button clickability...');
        allButtons.forEach(button => {
            const rect = button.getBoundingClientRect();
            const isVisible = rect.width > 0 && rect.height > 0;
            const isEnabled = !button.disabled;
            console.log(`Button ${button.dataset.module}: visible=${isVisible}, enabled=${isEnabled}, disabled=${button.disabled}`);
        });
    }, 1000);
});