/**
 * ============================================================================
 * Code Snippet Manager - Main JavaScript
 * ============================================================================
 * 
 * Handles all client-side functionality including:
 * - AJAX requests for CRUD operations
 * - Real-time search with autocomplete
 * - One-click copy to clipboard
 * - Favorites toggling
 * - Share modal interactions
 * - Toast notifications
 * - Keyboard shortcuts
 * - Syntax highlighting
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

'use strict';

// ============================================================================
// Configuration & Utilities
// ============================================================================

/**
 * Main SnippetManager object
 * Uses window.SnippetConfig set by PHP in footer.php
 */
const SnippetManager = {
    
    /**
     * Get the configured AJAX URL
     * @returns {string} AJAX handler URL
     */
    getAjaxUrl: function() {
        if (window.SnippetConfig && window.SnippetConfig.ajaxUrl) {
            return window.SnippetConfig.ajaxUrl;
        }
        // Fallback: construct from current location
        const path = window.location.pathname;
        const basePath = path.substring(0, path.lastIndexOf('/'));
        return basePath + '/ajax/handler.php';
    },
    
    /**
     * Get the configured App URL
     * @returns {string} Application base URL
     */
    getAppUrl: function() {
        if (window.SnippetConfig && window.SnippetConfig.appUrl) {
            return window.SnippetConfig.appUrl;
        }
        // Fallback: construct from current location
        const path = window.location.pathname;
        return window.location.origin + path.substring(0, path.lastIndexOf('/'));
    },
    
    /**
     * Get the CSRF token
     * @returns {string} CSRF token
     */
    getCsrfToken: function() {
        if (window.SnippetConfig && window.SnippetConfig.csrfToken) {
            return window.SnippetConfig.csrfToken;
        }
        // Fallback: try to get from hidden input
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return tokenInput ? tokenInput.value : '';
    },

    /**
     * Make an AJAX request
     * 
     * @param {string} action  - Action identifier
     * @param {object} data    - Request data
     * @param {string} method  - HTTP method (GET/POST)
     * @returns {Promise<object>} Response data
     */
    ajax: async function(action, data = {}, method = 'POST') {
        try {
            const ajaxUrl = this.getAjaxUrl();
            const csrfToken = this.getCsrfToken();
            
            const options = {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            };

            let url = ajaxUrl + '?action=' + encodeURIComponent(action);

            if (method === 'POST') {
                const formData = new FormData();
                // Add csrf_token first, then add data
                // If data already has csrf_token (from a form), skip adding it again
                if (!data.hasOwnProperty('csrf_token')) {
                    formData.append('csrf_token', csrfToken);
                }
                for (const [key, value] of Object.entries(data)) {
                    if (value !== null && value !== undefined) {
                        formData.append(key, value);
                    }
                }
                options.body = formData;
            } else {
                // GET request - append params to URL
                const params = new URLSearchParams();
                for (const [key, value] of Object.entries(data)) {
                    if (value !== null && value !== undefined && value !== '') {
                        params.append(key, value);
                    }
                }
                const paramStr = params.toString();
                if (paramStr) {
                    url += '&' + paramStr;
                }
            }

            const response = await fetch(url, options);
            const result = await response.json();

            // Don't auto-show toast here — let the caller handle it
            // This prevents duplicate toasts when caller also shows error

            return result;
        } catch (error) {
            console.error('AJAX Error:', error);
            this.showToast('Network error. Please try again.', 'danger');
            return { success: false, message: 'Network error' };
        }
    },

    /**
     * Show a Bootstrap toast notification
     * 
     * @param {string} message - Toast message
     * @param {string} type    - Bootstrap color type (success, danger, warning, info)
     * @param {number} duration - Display duration in ms
     */
    showToast: function(message, type = 'success', duration = 3000) {
        // Create toast container if it doesn't exist
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        // Icon mapping
        const icons = {
            success: 'bi-check-circle-fill',
            danger: 'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-circle-fill',
            info: 'bi-info-circle-fill',
        };

        const toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-bg-' + type + ' border-0';
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = 
            '<div class="d-flex">' +
                '<div class="toast-body d-flex align-items-center gap-2">' +
                    '<i class="bi ' + (icons[type] || icons.info) + '"></i>' +
                    this.escapeHtml(message) +
                '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div>';

        container.appendChild(toastEl);

        const toast = new bootstrap.Toast(toastEl, { delay: duration });
        toast.show();

        // Clean up after hide
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    },

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Copy text to clipboard
     * 
     * @param {string} text - Text to copy
     * @param {number} snippetId - Optional snippet ID to track copy
     */
    copyToClipboard: async function(text, snippetId = null) {
        try {
            await navigator.clipboard.writeText(text);
            this.showToast('Code copied to clipboard!', 'success');

            // Track the copy action
            if (snippetId) {
                this.ajax('copy_snippet', { id: snippetId });
            }
        } catch (err) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                this.showToast('Code copied to clipboard!', 'success');
                if (snippetId) {
                    this.ajax('copy_snippet', { id: snippetId });
                }
            } catch (e) {
                this.showToast('Failed to copy. Please copy manually.', 'danger');
            }
            document.body.removeChild(textarea);
        }
    },

    /**
     * Toggle favorite status for a snippet
     * 
     * @param {number} snippetId - Snippet ID
     * @param {HTMLElement} btn  - Button element to update
     */
    toggleFavorite: async function(snippetId, btn) {
        const result = await this.ajax('toggle_favorite', { snippet_id: snippetId });
        
        if (result.success) {
            const icon = btn.querySelector('i');
            if (result.favorited) {
                icon.className = 'bi bi-heart-fill text-danger';
                btn.classList.add('favorited');
            } else {
                icon.className = 'bi bi-heart';
                btn.classList.remove('favorited');
            }
            this.showToast(result.message, 'success');
        }
    },

    /**
     * Toggle pin status for a snippet
     * 
     * @param {number} snippetId - Snippet ID
     * @param {HTMLElement} btn  - Button element
     */
    togglePin: async function(snippetId, btn) {
        const result = await this.ajax('toggle_pin', { id: snippetId });
        
        if (result.success) {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = result.pinned ? 'bi bi-pin-fill text-warning' : 'bi bi-pin';
            }
            this.showToast(result.message, 'success');
        }
    },

    /**
     * Delete a snippet with confirmation
     * 
     * @param {number} snippetId - Snippet ID
     * @param {string} title     - Snippet title for confirmation
     */
    deleteSnippet: async function(snippetId, title) {
        const confirmed = confirm('Are you sure you want to delete "' + title + '"?\nThis action cannot be undone.');
        
        if (confirmed) {
            const result = await this.ajax('delete_snippet', { id: snippetId });
            if (result.success) {
                this.showToast(result.message, 'success');
                // Remove the snippet card from DOM
                const card = document.getElementById('snippet-' + snippetId);
                if (card) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(function() { card.remove(); }, 300);
                } else {
                    // Redirect to snippets page
                    window.location.href = SnippetManager.getAppUrl() + '/index.php';
                }
            }
        }
    },

    /**
     * Share a snippet with a user
     * 
     * @param {number} snippetId - Snippet ID
     */
    shareSnippet: function(snippetId) {
        const modal = document.getElementById('shareModal');
        if (modal) {
            const snippetInput = modal.querySelector('[name="snippet_id"]');
            if (snippetInput) {
                snippetInput.value = snippetId;
            }
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    },

    /**
     * Initialize syntax highlighting for all code blocks
     */
    initHighlighting: function() {
        if (typeof hljs !== 'undefined') {
            document.querySelectorAll('pre code').forEach(function(block) {
                hljs.highlightElement(block);
            });
        }
    },

    /**
     * Debounce function for search input
     * 
     * @param {Function} func  - Function to debounce
     * @param {number}   delay - Delay in ms
     * @returns {Function} Debounced function
     */
    debounce: function(func, delay = 300) {
        let timer;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() {
                func.apply(context, args);
            }, delay);
        };
    },
};

// ============================================================================
// DOM Ready - Initialize all event handlers
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================================================
    // Global Search (Autocomplete)
    // ========================================================================
    /**
     * Initialize search functionality for a given input + results container
     */
    function initSearchField(inputEl, resultsEl) {
        if (!inputEl || !resultsEl) return;

        var performSearch = SnippetManager.debounce(async function(query) {
            if (query.length < 2) {
                resultsEl.classList.add('d-none');
                resultsEl.innerHTML = '';
                return;
            }

            var result = await SnippetManager.ajax('quick_search', { q: query }, 'GET');

            if (result.success && result.data && result.data.length > 0) {
                var html = '<div class="list-group list-group-flush">';
                result.data.forEach(function(item) {
                    html += '<a href="' + SnippetManager.getAppUrl() + '/view.php?id=' + item.id + '" ' +
                           'class="list-group-item list-group-item-action bg-dark text-light border-secondary py-2">' +
                            '<div class="d-flex align-items-center gap-2">' +
                                '<span class="badge bg-secondary" style="font-size:0.7rem;">' + SnippetManager.escapeHtml(item.language) + '</span>' +
                                '<div class="text-truncate">' +
                                    '<strong style="font-size:0.85rem;">' + SnippetManager.escapeHtml(item.title) + '</strong>' +
                                    (item.category_name ? '<small class="text-muted ms-2">' + SnippetManager.escapeHtml(item.category_name) + '</small>' : '') +
                                '</div>' +
                            '</div>' +
                        '</a>';
                });
                html += '</div>';
                resultsEl.innerHTML = html;
                resultsEl.classList.remove('d-none');
            } else if (result.success) {
                resultsEl.innerHTML = '<div class="p-3 text-muted text-center small">No snippets found</div>';
                resultsEl.classList.remove('d-none');
            }
        }, 300);

        inputEl.addEventListener('input', function(e) {
            performSearch(e.target.value.trim());
        });

        document.addEventListener('click', function(e) {
            if (!inputEl.contains(e.target) && !resultsEl.contains(e.target)) {
                resultsEl.classList.add('d-none');
            }
        });
    }

    // Desktop search
    initSearchField(document.getElementById('globalSearch'), document.getElementById('searchResults'));
    // Mobile search
    initSearchField(document.getElementById('globalSearchMobile'), document.getElementById('searchResultsMobile'));

    // Keyboard shortcut: Ctrl+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            var desktopSearch = document.getElementById('globalSearch');
            var mobileSearch = document.getElementById('globalSearchMobile');
            // Focus whichever is visible
            if (desktopSearch && desktopSearch.offsetParent !== null) {
                desktopSearch.focus();
            } else if (mobileSearch) {
                // Open the mobile collapse first
                var collapse = document.getElementById('mobileSearch');
                if (collapse && !collapse.classList.contains('show')) {
                    new bootstrap.Collapse(collapse, { toggle: true });
                }
                setTimeout(function() { mobileSearch.focus(); }, 350);
            }
        }
        if (e.key === 'Escape') {
            var sr = document.getElementById('searchResults');
            var srm = document.getElementById('searchResultsMobile');
            if (sr) sr.classList.add('d-none');
            if (srm) srm.classList.add('d-none');
        }
    });

    // ========================================================================
    // Initialize syntax highlighting
    // ========================================================================
    SnippetManager.initHighlighting();

    // ========================================================================
    // Global click event delegation
    // ========================================================================
    document.addEventListener('click', function(e) {
        // Copy to clipboard button
        const copyBtn = e.target.closest('[data-copy]');
        if (copyBtn) {
            e.preventDefault();
            const snippetId = copyBtn.dataset.snippetId;
            const codeEl = document.getElementById('code-' + snippetId);
            if (codeEl) {
                SnippetManager.copyToClipboard(codeEl.textContent, snippetId);
            } else {
                // Try to find code in the same card
                const card = copyBtn.closest('.snippet-card, .code-container');
                if (card) {
                    const code = card.querySelector('code');
                    if (code) {
                        SnippetManager.copyToClipboard(code.textContent, snippetId);
                    }
                }
            }
        }

        // Favorite toggle button
        const favBtn = e.target.closest('[data-favorite]');
        if (favBtn) {
            e.preventDefault();
            SnippetManager.toggleFavorite(favBtn.dataset.snippetId, favBtn);
        }

        // Pin toggle button
        const pinBtn = e.target.closest('[data-pin]');
        if (pinBtn) {
            e.preventDefault();
            SnippetManager.togglePin(pinBtn.dataset.snippetId, pinBtn);
        }

        // Delete button
        const deleteBtn = e.target.closest('[data-delete]');
        if (deleteBtn) {
            e.preventDefault();
            SnippetManager.deleteSnippet(deleteBtn.dataset.snippetId, deleteBtn.dataset.title || 'this snippet');
        }

        // Share button
        const shareBtn = e.target.closest('[data-share]');
        if (shareBtn) {
            e.preventDefault();
            SnippetManager.shareSnippet(shareBtn.dataset.snippetId);
        }
    });

    // ========================================================================
    // Share Modal - User Search
    // ========================================================================
    const shareUserSearch = document.getElementById('shareUserSearch');
    const userSearchResults = document.getElementById('userSearchResults');
    
    if (shareUserSearch && userSearchResults) {
        const searchUsers = SnippetManager.debounce(async function(query) {
            if (query.length < 2) {
                userSearchResults.innerHTML = '';
                return;
            }

            const result = await SnippetManager.ajax('search_users', { q: query }, 'GET');
            
            if (result.success && result.data && result.data.length > 0) {
                let html = '';
                result.data.forEach(function(user) {
                    html += '<div class="d-flex align-items-center justify-content-between p-2 border-bottom border-secondary user-result">' +
                            '<div class="d-flex align-items-center gap-2">' +
                                '<div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center">' +
                                    '<span class="text-white fw-bold small">' + user.full_name.charAt(0).toUpperCase() + '</span>' +
                                '</div>' +
                                '<div>' +
                                    '<strong>' + SnippetManager.escapeHtml(user.full_name) + '</strong>' +
                                    '<small class="text-muted d-block">@' + SnippetManager.escapeHtml(user.username) + '</small>' +
                                '</div>' +
                            '</div>' +
                            '<button type="button" class="btn btn-sm btn-outline-primary select-user-btn" ' +
                                    'data-user-id="' + user.id + '" data-user-name="' + SnippetManager.escapeHtml(user.full_name) + '">' +
                                'Select' +
                            '</button>' +
                        '</div>';
                });
                userSearchResults.innerHTML = html;
                
                // Attach click handlers to select buttons
                userSearchResults.querySelectorAll('.select-user-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const userId = this.dataset.userId;
                        const userName = this.dataset.userName;
                        document.getElementById('selectedUserId').value = userId;
                        const nameDisplay = document.getElementById('selectedUserName');
                        if (nameDisplay) {
                            nameDisplay.textContent = 'Selected: ' + userName;
                            nameDisplay.classList.remove('d-none');
                        }
                        userSearchResults.innerHTML = '';
                        shareUserSearch.value = '';
                    });
                });
            } else {
                userSearchResults.innerHTML = '<div class="text-muted p-2">No users found</div>';
            }
        }, 300);

        shareUserSearch.addEventListener('input', function(e) {
            searchUsers(e.target.value.trim());
        });
    }

    // ========================================================================
    // Snippet Form Submission
    // ========================================================================
    const snippetForm = document.getElementById('snippetForm');
    if (snippetForm) {
        snippetForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(snippetForm);
            const data = {};
            formData.forEach(function(value, key) {
                // Handle tags[] array
                if (key === 'tags[]') {
                    if (!data.tags) data.tags = [];
                    data.tags.push(value);
                } else {
                    data[key] = value;
                }
            });
            
            // Convert tags array to comma-separated string
            if (data.tags && Array.isArray(data.tags)) {
                data.tags = data.tags.join(',');
            }

            const action = data.id ? 'update_snippet' : 'create_snippet';
            const submitBtn = snippetForm.querySelector('[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // Disable button during submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

            const result = await SnippetManager.ajax(action, data);

            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;

            if (result.success) {
                SnippetManager.showToast(result.message, 'success');
                
                // Redirect to the snippet view
                const redirectId = result.id || data.id;
                setTimeout(function() {
                    window.location.href = SnippetManager.getAppUrl() + '/view.php?id=' + redirectId;
                }, 1000);
            } else {
                SnippetManager.showToast(result.message || 'Failed to save snippet.', 'danger');
            }
        });
    }

    // ========================================================================
    // Share Form Submission
    // ========================================================================
    const shareForm = document.getElementById('shareForm');
    if (shareForm) {
        shareForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(shareForm);
            const data = {};
            formData.forEach(function(value, key) {
                data[key] = value;
            });
            
            // Validate user is selected
            if (!data.shared_with) {
                SnippetManager.showToast('Please select a user to share with.', 'warning');
                return;
            }

            const result = await SnippetManager.ajax('share_snippet', data);
            
            if (result.success) {
                SnippetManager.showToast(result.message, 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('shareModal'));
                if (modal) modal.hide();
                shareForm.reset();
                const nameDisplay = document.getElementById('selectedUserName');
                if (nameDisplay) nameDisplay.classList.add('d-none');
                if (userSearchResults) userSearchResults.innerHTML = '';
            } else {
                SnippetManager.showToast(result.message || 'Failed to share snippet.', 'danger');
            }
        });
    }

    // ========================================================================
    // Category Form (for category management page)
    // ========================================================================
    const categoryForm = document.getElementById('categoryForm');
    if (categoryForm) {
        categoryForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Prevent duplicate submits / duplicate toasts
            if (categoryForm.dataset.submitting === '1') {
                return;
            }
            categoryForm.dataset.submitting = '1';
            
            const formData = new FormData(categoryForm);
            const data = {};
            formData.forEach(function(value, key) {
                data[key] = value;
            });
            
            const action = data.id ? 'update_category' : 'create_category';
            const submitBtn = categoryForm.querySelector('[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Save Category';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
            }
            
            const result = await SnippetManager.ajax(action, data);

            categoryForm.dataset.submitting = '0';
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }

            if (result.success) {
                SnippetManager.showToast(result.message, 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                SnippetManager.showToast(result.message || 'Failed to save category.', 'danger');
            }
        });
    }

    // ========================================================================
    // Theme toggle (dark/light)
    // ========================================================================
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-bs-theme', savedTheme);
    
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const html = document.documentElement;
            const current = html.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
        });
    }
});

// ============================================================================
// Global Helper Functions (for inline onclick handlers)
// ============================================================================

/**
 * Load version history in a modal
 * @param {number} snippetId - Snippet ID
 */
async function loadVersionHistory(snippetId) {
    const container = document.getElementById('versionHistoryList');
    if (!container) return;

    container.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary"></div></div>';

    const result = await SnippetManager.ajax('get_versions', { snippet_id: snippetId }, 'GET');
    
    if (result.success && result.data && result.data.length > 0) {
        let html = '<div class="list-group">';
        result.data.forEach(function(version) {
            html += '<div class="list-group-item bg-dark border-secondary">' +
                '<div class="d-flex justify-content-between align-items-center">' +
                    '<div>' +
                        '<strong>Version ' + version.version + '</strong>' +
                        '<small class="text-muted ms-2">' + SnippetManager.escapeHtml(version.change_note || '') + '</small>' +
                        '<br>' +
                        '<small class="text-muted">by ' + SnippetManager.escapeHtml(version.full_name || version.username || 'Unknown') + ' • ' + version.created_at + '</small>' +
                    '</div>' +
                    '<button class="btn btn-sm btn-outline-warning" onclick="restoreVersion(' + snippetId + ', ' + version.version + ')">' +
                        '<i class="bi bi-arrow-counterclockwise"></i> Restore' +
                    '</button>' +
                '</div>' +
            '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
    } else {
        container.innerHTML = '<div class="text-muted text-center p-3">No version history available.</div>';
    }
}

/**
 * Restore a specific version
 * @param {number} snippetId - Snippet ID
 * @param {number} version   - Version number to restore
 */
async function restoreVersion(snippetId, version) {
    if (!confirm('Restore to version ' + version + '? Current code will be saved as a new version.')) return;

    const result = await SnippetManager.ajax('restore_version', { snippet_id: snippetId, version: version });
    if (result.success) {
        SnippetManager.showToast(result.message, 'success');
        setTimeout(function() { location.reload(); }, 1000);
    } else {
        SnippetManager.showToast(result.message || 'Failed to restore version.', 'danger');
    }
}

/**
 * Delete a category
 * @param {number} id - Category ID
 * @param {string} name - Category name
 */
async function deleteCategory(id, name) {
    if (!confirm('Delete category "' + name + '"? Snippets will be uncategorized.')) return;
    
    const result = await SnippetManager.ajax('delete_category', { id: id });
    if (result.success) {
        SnippetManager.showToast(result.message, 'success');
        setTimeout(function() { location.reload(); }, 800);
    } else {
        SnippetManager.showToast(result.message || 'Failed to delete category.', 'danger');
    }
}

/**
 * Edit a category - populate form and open modal
 * @param {object} cat - Category object
 */
function editCategory(cat) {
    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
    document.getElementById('catId').value = cat.id;
    document.getElementById('catName').value = cat.name;
    document.getElementById('catDesc').value = cat.description || '';
    document.getElementById('catColor').value = cat.color || '#6366f1';
    document.getElementById('catSort').value = cat.sort_order || 0;
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

/**
 * Reset category form for creating new category
 */
function resetCategoryForm() {
    document.getElementById('categoryModalTitle').textContent = 'New Category';
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('catColor').value = '#6366f1';
    document.getElementById('catSort').value = '0';
}
