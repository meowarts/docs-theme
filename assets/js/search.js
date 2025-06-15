/**
 * Advanced Search with REST API
 */
(function() {
    'use strict';

    let searchModal = null;
    let searchInput = null;
    let searchResults = null;
    let searchTimer = null;
    let currentIndex = -1;

    // Initialize search when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        createSearchModal();
        initSearchKeyboard();
    });

    /**
     * Create the search modal HTML
     */
    function createSearchModal() {
        // Create modal container
        searchModal = document.createElement('div');
        searchModal.className = 'docs-search-modal';
        searchModal.innerHTML = `
            <div class="docs-search-overlay"></div>
            <div class="docs-search-container">
                <div class="docs-search-header">
                    <svg class="docs-search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M17.5 17.5L13.875 13.875M15.8333 9.16667C15.8333 12.8486 12.8486 15.8333 9.16667 15.8333C5.48477 15.8333 2.5 12.8486 2.5 9.16667C2.5 5.48477 5.48477 2.5 9.16667 2.5C12.8486 2.5 15.8333 5.48477 15.8333 9.16667Z" stroke="currentColor" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <input type="search" class="docs-search-input" placeholder="Search documentation..." autocomplete="off">
                    <button class="docs-search-close" aria-label="Close search">
                        <span>ESC</span>
                    </button>
                </div>
                <div class="docs-search-results">
                    <div class="docs-search-empty">
                        <p>Type to search documentation...</p>
                    </div>
                </div>
                <div class="docs-search-footer">
                    <div class="docs-search-hints">
                        <span><kbd>↑↓</kbd> Navigate</span>
                        <span><kbd>↵</kbd> Select</span>
                        <span><kbd>ESC</kbd> Close</span>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(searchModal);

        // Cache elements
        searchInput = searchModal.querySelector('.docs-search-input');
        searchResults = searchModal.querySelector('.docs-search-results');

        // Event listeners
        searchModal.querySelector('.docs-search-overlay').addEventListener('click', closeSearch);
        searchModal.querySelector('.docs-search-close').addEventListener('click', closeSearch);
        searchInput.addEventListener('input', handleSearchInput);
        searchInput.addEventListener('keydown', handleSearchNavigation);
    }

    /**
     * Initialize keyboard shortcuts
     */
    function initSearchKeyboard() {
        document.addEventListener('keydown', function(e) {
            // Open search with Cmd/Ctrl + K
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                openSearch();
            }

            // Close search with Escape
            if (e.key === 'Escape' && searchModal.classList.contains('is-open')) {
                e.preventDefault();
                closeSearch();
            }
        });
    }

    /**
     * Open search modal
     */
    function openSearch() {
        searchModal.classList.add('is-open');
        searchInput.focus();
        searchInput.select();
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close search modal
     */
    function closeSearch() {
        searchModal.classList.remove('is-open');
        searchInput.value = '';
        searchResults.innerHTML = '<div class="docs-search-empty"><p>Type to search documentation...</p></div>';
        document.body.style.overflow = '';
        currentIndex = -1;
    }

    /**
     * Handle search input
     */
    function handleSearchInput(e) {
        const query = e.target.value.trim();

        // Clear previous timer
        if (searchTimer) {
            clearTimeout(searchTimer);
        }

        // Reset selection
        currentIndex = -1;

        if (query.length < 2) {
            searchResults.innerHTML = '<div class="docs-search-empty"><p>Type to search documentation...</p></div>';
            return;
        }

        // Show loading state
        searchResults.innerHTML = '<div class="docs-search-loading">Searching...</div>';

        // Debounce search
        searchTimer = setTimeout(() => {
            performSearch(query);
        }, 300);
    }

    /**
     * Perform search using WordPress REST API
     */
    async function performSearch(query) {
        try {
            // Search pages
            const response = await fetch(`${window.docsTheme.restUrl}wp/v2/pages?search=${encodeURIComponent(query)}&per_page=10&_fields=id,title,excerpt,link,parent,page_categories,emoticon`);
            
            if (!response.ok) {
                throw new Error('Search failed');
            }

            const pages = await response.json();

            // Get parent page titles for hierarchy
            const parentIds = [...new Set(pages.filter(p => p.parent).map(p => p.parent))];
            const parentTitles = {};
            
            if (parentIds.length > 0) {
                const parentsResponse = await fetch(`${window.docsTheme.restUrl}wp/v2/pages?include=${parentIds.join(',')}&_fields=id,title`);
                const parents = await parentsResponse.json();
                parents.forEach(p => {
                    parentTitles[p.id] = p.title.rendered;
                });
            }

            displayResults(pages, query, parentTitles);

        } catch (error) {
            console.error('Search error:', error);
            searchResults.innerHTML = '<div class="docs-search-error">Search failed. Please try again.</div>';
        }
    }

    /**
     * Display search results
     */
    function displayResults(pages, query, parentTitles) {
        if (pages.length === 0) {
            searchResults.innerHTML = `
                <div class="docs-search-empty">
                    <p>No results found for "<strong>${escapeHtml(query)}</strong>"</p>
                    <p class="docs-search-suggestion">Try different keywords or check your spelling</p>
                </div>
            `;
            return;
        }

        const resultsHtml = pages.map((page, index) => {
            const title = highlightMatch(page.title.rendered, query);
            const excerpt = page.excerpt.rendered ? 
                highlightMatch(stripHtml(page.excerpt.rendered), query) : 
                'No excerpt available';
            
            // Build breadcrumb from category and parent
            let breadcrumb = '';
            if (page.page_categories && page.page_categories.length > 0) {
                breadcrumb = `<span class="docs-search-breadcrumb">${page.page_categories[0]}</span>`;
            }
            if (page.parent && parentTitles[page.parent]) {
                breadcrumb += `<span class="docs-search-breadcrumb">${parentTitles[page.parent]}</span>`;
            }

            // Add emoticon if available
            const emoticon = page.emoticon ? `<span class="docs-search-result-emoticon">${escapeHtml(page.emoticon)}</span> ` : '';

            return `
                <a href="${page.link}" class="docs-search-result" data-index="${index}">
                    <div class="docs-search-result-content">
                        ${breadcrumb ? `<div class="docs-search-breadcrumbs">${breadcrumb}</div>` : ''}
                        <h4 class="docs-search-result-title">${emoticon}${title}</h4>
                        <p class="docs-search-result-excerpt">${excerpt}</p>
                    </div>
                    <svg class="docs-search-result-arrow" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            `;
        }).join('');

        searchResults.innerHTML = `
            <div class="docs-search-results-list">
                ${resultsHtml}
            </div>
        `;

        // Add click handlers
        searchResults.querySelectorAll('.docs-search-result').forEach(result => {
            result.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = result.href;
            });
        });
    }

    /**
     * Handle keyboard navigation
     */
    function handleSearchNavigation(e) {
        const results = searchResults.querySelectorAll('.docs-search-result');
        
        if (results.length === 0) return;

        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                currentIndex = Math.min(currentIndex + 1, results.length - 1);
                updateSelection(results);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                currentIndex = Math.max(currentIndex - 1, -1);
                updateSelection(results);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (currentIndex >= 0 && results[currentIndex]) {
                    window.location.href = results[currentIndex].href;
                }
                break;
        }
    }

    /**
     * Update keyboard selection
     */
    function updateSelection(results) {
        results.forEach((result, index) => {
            if (index === currentIndex) {
                result.classList.add('is-selected');
                result.scrollIntoView({ block: 'nearest' });
            } else {
                result.classList.remove('is-selected');
            }
        });
    }

    /**
     * Highlight search matches
     */
    function highlightMatch(text, query) {
        const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    /**
     * Strip HTML tags
     */
    function stripHtml(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Escape regex
     */
    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Export for use in theme
    window.docsSearch = {
        open: openSearch,
        close: closeSearch
    };

})();