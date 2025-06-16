/**
 * Async Navigation for Docs Theme
 * Provides smooth, app-like navigation without full page reloads
 */

(function() {
    'use strict';

    // Check if async navigation is supported
    if (!window.history || !window.history.pushState) {
        return;
    }

    // Configuration
    const config = {
        contentSelector: '.entry-content',
        titleSelector: '.entry-title',
        subtitleSelector: '.page-subtitle',
        breadcrumbsSelector: '.docs-breadcrumbs',
        badgesSelector: '.docs-badges-wrapper',
        tocSelector: '#table-of-contents',
        sidebarLinksSelector: '.page-link',
        loadingClass: 'is-loading',
        fadeInClass: 'fade-in',
        transitionDuration: 200
    };

    // State
    let isNavigating = false;
    let currentRequest = null;
    const pageCache = new Map();
    const maxCacheSize = 20; // Maximum number of pages to cache

    /**
     * Initialize async navigation
     */
    function init() {
        // Intercept clicks on internal links
        document.addEventListener('click', handleLinkClick);
        
        // Handle browser back/forward buttons
        window.addEventListener('popstate', handlePopState);
        
        // Add current page to history
        if (window.location.pathname !== '/') {
            // Try to get page ID from current active link in sidebar
            const currentPageLink = document.querySelector('.current-page .page-link[data-page-id]');
            if (currentPageLink) {
                const pageId = currentPageLink.getAttribute('data-page-id');
                history.replaceState({ pageId: pageId }, '', window.location.href);
            }
        }
    }

    /**
     * Handle link clicks
     */
    function handleLinkClick(e) {
        // Check if it's a left click without modifiers
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        const link = e.target.closest('a');
        if (!link) return;

        // Check if it's an internal page link
        const pageId = link.getAttribute('data-page-id');
        if (!pageId) return;

        // Check if link is within our site
        const linkUrl = new URL(link.href);
        const currentUrl = new URL(window.location.href);
        if (linkUrl.origin !== currentUrl.origin) return;

        // Prevent default navigation
        e.preventDefault();

        // Get breadcrumb data if available
        const breadcrumbData = link.getAttribute('data-breadcrumbs');
        let targetBreadcrumbs = null;
        if (breadcrumbData) {
            try {
                targetBreadcrumbs = JSON.parse(breadcrumbData);
            } catch (e) {
                console.error('Invalid breadcrumb data:', e);
            }
        }

        // Navigate to the page with breadcrumb data
        navigateToPage(pageId, linkUrl.href, true, targetBreadcrumbs);
    }

    /**
     * Handle browser back/forward
     */
    function handlePopState(e) {
        if (e.state && e.state.pageId) {
            navigateToPage(e.state.pageId, window.location.href, false);
        }
    }

    /**
     * Navigate to a page
     */
    async function navigateToPage(pageId, url, updateHistory = true, targetBreadcrumbs = null) {
        // Prevent multiple simultaneous navigations
        if (isNavigating) return;
        isNavigating = true;

        // Cancel any pending request
        if (currentRequest) {
            currentRequest.abort();
        }

        try {
            let data;
            let breadcrumbRemovalPromise = null;
            
            // Start breadcrumb removal animation immediately if we have target breadcrumbs
            if (targetBreadcrumbs) {
                const currentBreadcrumbs = document.querySelector('.docs-breadcrumbs');
                if (currentBreadcrumbs) {
                    breadcrumbRemovalPromise = animateBreadcrumbRemoval(currentBreadcrumbs, targetBreadcrumbs);
                }
            }
            
            // Check cache first
            if (pageCache.has(pageId)) {
                // Use cached data
                data = pageCache.get(pageId);
                
                // Update browser history
                if (updateHistory) {
                    history.pushState({ pageId: pageId }, '', url);
                }

                // Wait for breadcrumb removal if in progress
                if (breadcrumbRemovalPromise) {
                    await breadcrumbRemovalPromise;
                }
                
                // Update page content (skip breadcrumb update if we're handling it separately)
                const breadcrumbData = await updatePageContent(data, !!targetBreadcrumbs);
                
                // Add new breadcrumbs if we have target data
                if (targetBreadcrumbs) {
                    await animateBreadcrumbAddition(targetBreadcrumbs);
                } else if (breadcrumbData) {
                    // Fallback to normal breadcrumb animation
                    setTimeout(() => {
                        animateBreadcrumbChange(breadcrumbData.existingBreadcrumbs, breadcrumbData.newBreadcrumbs);
                    }, 0);
                }
                
            } else {
                // Show loading state only for uncached pages
                showLoadingState();
                
                // Create abort controller for this request
                const controller = new AbortController();
                currentRequest = controller;

                // Fetch page content
                const response = await fetch(`${docsThemeAsync.restUrl}page-content/${pageId}`, {
                    headers: {
                        'X-WP-Nonce': docsThemeAsync.nonce
                    },
                    signal: controller.signal
                });

                if (!response.ok) {
                    throw new Error('Failed to load page');
                }

                data = await response.json();
                
                // Store in cache with size limit
                if (pageCache.size >= maxCacheSize) {
                    // Remove oldest entry (first one added)
                    const firstKey = pageCache.keys().next().value;
                    pageCache.delete(firstKey);
                }
                pageCache.set(pageId, data);

                // Update browser history
                if (updateHistory) {
                    history.pushState({ pageId: pageId }, '', url);
                }

                // Wait for breadcrumb removal if in progress
                if (breadcrumbRemovalPromise) {
                    await breadcrumbRemovalPromise;
                }
                
                // Update page content (skip breadcrumb update if we're handling it separately)
                const breadcrumbData = await updatePageContent(data, !!targetBreadcrumbs);
                
                // Hide loading immediately after content is shown
                await hideLoadingState();
                
                // Add new breadcrumbs if we have target data
                if (targetBreadcrumbs) {
                    await animateBreadcrumbAddition(targetBreadcrumbs);
                } else if (breadcrumbData) {
                    // Fallback to normal breadcrumb animation
                    setTimeout(() => {
                        animateBreadcrumbChange(breadcrumbData.existingBreadcrumbs, breadcrumbData.newBreadcrumbs);
                    }, 0);
                }
            }

            // Update active states
            updateActiveStates(pageId);

            // Scroll to top smoothly
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Navigation error:', error);
                // Fallback to normal navigation
                window.location.href = url;
            }
        } finally {
            isNavigating = false;
            currentRequest = null;
        }
    }

    /**
     * Update page content with animation
     */
    async function updatePageContent(data, skipBreadcrumbs = false) {
        // Get content containers
        const contentEl = document.querySelector(config.contentSelector);
        const titleEl = document.querySelector(config.titleSelector);
        const subtitleEl = document.querySelector(config.subtitleSelector);
        const breadcrumbsContainer = document.querySelector(config.breadcrumbsSelector);
        const badgesContainer = document.querySelector('.docs-breadcrumbs-wrapper');
        const tocContainer = document.querySelector(config.tocSelector);

        // Fade out current content
        const elementsToFade = [contentEl, titleEl, subtitleEl, tocContainer].filter(Boolean);
        elementsToFade.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';
        });

        // Wait for fade out
        await sleep(config.transitionDuration);

        // Update content
        if (contentEl) {
            if (data.child_pages_html) {
                contentEl.innerHTML = data.content + data.child_pages_html;
            } else {
                contentEl.innerHTML = data.content;
            }
        }

        if (titleEl) {
            titleEl.textContent = data.title;
            // Ensure title has ID for TOC
            if (!titleEl.id) {
                titleEl.id = 'page-title';
            }
        }

        // Update subtitle
        if (data.subtitle) {
            if (!subtitleEl) {
                // Create subtitle element if it doesn't exist
                const newSubtitle = document.createElement('p');
                newSubtitle.className = 'page-subtitle';
                // Convert line breaks to <br> tags like PHP does
                newSubtitle.innerHTML = data.subtitle.replace(/\n/g, '<br>');
                titleEl.parentNode.insertBefore(newSubtitle, titleEl.nextSibling);
            } else {
                // Convert line breaks to <br> tags like PHP does
                subtitleEl.innerHTML = data.subtitle.replace(/\n/g, '<br>');
                subtitleEl.style.display = 'block';
            }
        } else if (subtitleEl) {
            subtitleEl.style.display = 'none';
        }

        // Store breadcrumb update for later animation
        let breadcrumbUpdateData = null;
        if (!skipBreadcrumbs) {
            const breadcrumbsWrapper = document.querySelector('.docs-breadcrumbs-wrapper');
            if (breadcrumbsWrapper && data.breadcrumbs_html) {
                const existingBreadcrumbs = breadcrumbsWrapper.querySelector('.docs-breadcrumbs');
                if (existingBreadcrumbs) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.breadcrumbs_html;
                    const newBreadcrumbs = tempDiv.querySelector('.docs-breadcrumbs');
                    
                    if (newBreadcrumbs) {
                        // Store for animation after page loads
                        breadcrumbUpdateData = { existingBreadcrumbs, newBreadcrumbs };
                    }
                } else {
                    // No existing breadcrumbs, insert the new ones immediately
                    breadcrumbsWrapper.insertAdjacentHTML('afterbegin', data.breadcrumbs_html);
                }
            }
        }

        // Update badges
        if (badgesContainer) {
            const existingBadges = badgesContainer.querySelector('.docs-badges-wrapper');
            if (data.badges_html) {
                if (existingBadges) {
                    existingBadges.outerHTML = data.badges_html;
                } else {
                    badgesContainer.insertAdjacentHTML('beforeend', data.badges_html);
                }
            } else if (existingBadges) {
                existingBadges.remove();
            }
        }

        // Update table of contents
        if (tocContainer) {
            updateTableOfContents(data.headings, data.title);
        }

        // Update document title
        const siteName = document.title.split(' – ').pop();
        document.title = data.title + ' – ' + siteName;

        // Initialize any new interactive elements
        initializeNewContent();

        // Fade in new content
        await sleep(50); // Small delay to ensure DOM is updated
        
        // Re-query elements after DOM updates
        const elementsToFadeIn = [
            document.querySelector(config.contentSelector),
            document.querySelector(config.titleSelector),
            document.querySelector(config.subtitleSelector),
            document.querySelector(config.tocSelector)
        ].filter(Boolean);
        
        elementsToFadeIn.forEach(el => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        });
        
        // Return breadcrumb data for animation after page is shown
        return breadcrumbUpdateData;
    }

    /**
     * Update table of contents
     */
    function updateTableOfContents(headings, pageTitle) {
        const tocContainer = document.querySelector(config.tocSelector);
        if (!tocContainer) return;

        // Clear current TOC and any existing event listeners
        tocContainer.innerHTML = '';

        // Reinitialize TOC completely
        if (window.initTableOfContents) {
            // Let the existing function build the TOC from current DOM
            window.initTableOfContents();
        } else {
            // Fallback: build TOC manually
            const tocList = document.createElement('ul');
            tocList.className = 'docs-toc__list';

            // Add page title as first item
            const titleItem = document.createElement('li');
            titleItem.className = 'docs-toc__item docs-toc__item--h1';
            
            const titleLink = document.createElement('a');
            titleLink.href = '#page-title';
            titleLink.className = 'docs-toc__link';
            titleLink.textContent = pageTitle;
            
            titleItem.appendChild(titleLink);
            tocList.appendChild(titleItem);

            // Add other headings
            headings.forEach(heading => {
                const item = document.createElement('li');
                item.className = `docs-toc__item docs-toc__item--h${heading.level}`;
                
                const link = document.createElement('a');
                link.href = `#${heading.id}`;
                link.className = 'docs-toc__link';
                link.textContent = heading.text;
                
                item.appendChild(link);
                tocList.appendChild(item);
            });

            tocContainer.appendChild(tocList);
        }
    }

    /**
     * Update active states in sidebar
     */
    function updateActiveStates(pageId) {
        // Remove current active states
        document.querySelectorAll('.current-page').forEach(el => {
            el.classList.remove('current-page');
        });

        // Find and activate new current page
        const newActiveLink = document.querySelector(`a[data-page-id="${pageId}"]`);
        if (newActiveLink) {
            const pageItem = newActiveLink.closest('.page-item');
            if (pageItem) {
                pageItem.classList.add('current-page');
                
                // Expand parent items if needed
                let parent = pageItem.parentElement;
                while (parent) {
                    if (parent.classList.contains('children')) {
                        parent.style.display = 'block';
                        const toggle = parent.previousElementSibling?.querySelector('.toggle-children');
                        if (toggle) {
                            toggle.setAttribute('aria-expanded', 'true');
                        }
                    }
                    parent = parent.parentElement?.closest('.page-item');
                }
            }
        }
    }

    /**
     * Initialize new content
     */
    function initializeNewContent() {
        // Reinitialize syntax highlighting
        if (window.initHighlightJS) {
            window.initHighlightJS();
        }

        // Reinitialize copy code buttons
        if (window.initCopyCodeButtons) {
            window.initCopyCodeButtons();
        }
        
        // Reinitialize smooth anchors for TOC and other anchor links
        if (window.initSmoothAnchors) {
            window.initSmoothAnchors();
        }

        // Add data-page-id to new links
        document.querySelectorAll('.entry-content a, .docs-page-card').forEach(link => {
            const href = link.getAttribute('href');
            if (href && !link.hasAttribute('data-page-id')) {
                const pageId = getPageIdFromUrl(href);
                if (pageId) {
                    link.setAttribute('data-page-id', pageId);
                }
            }
        });
    }

    /**
     * Show loading state
     */
    function showLoadingState() {
        // Create full-screen overlay
        const overlay = document.createElement('div');
        overlay.className = 'async-loader-overlay';
        overlay.innerHTML = `
            <div class="async-loader">
                <div class="async-loader-spinner"></div>
                <span>${docsThemeAsync.loadingText}</span>
            </div>
        `;
        document.body.appendChild(overlay);
        
        // Trigger fade in
        requestAnimationFrame(() => {
            overlay.classList.add('is-visible');
        });
    }

    /**
     * Hide loading state
     */
    async function hideLoadingState() {
        // Fade out loading overlay
        const overlay = document.querySelector('.async-loader-overlay');
        if (overlay) {
            overlay.classList.remove('is-visible');
            // Wait for fade out animation
            await sleep(200);
            overlay.remove();
        }
    }

    /**
     * Get page ID from URL
     */
    function getPageIdFromUrl(url) {
        // Try exact match first
        let link = document.querySelector(`a[href="${url}"]`);
        if (link && link.hasAttribute('data-page-id')) {
            return link.getAttribute('data-page-id');
        }
        
        // Try without trailing slash
        const urlWithoutSlash = url.replace(/\/$/, '');
        link = document.querySelector(`a[href="${urlWithoutSlash}"]`);
        if (link && link.hasAttribute('data-page-id')) {
            return link.getAttribute('data-page-id');
        }
        
        // Try with trailing slash
        const urlWithSlash = urlWithoutSlash + '/';
        link = document.querySelector(`a[href="${urlWithSlash}"]`);
        if (link && link.hasAttribute('data-page-id')) {
            return link.getAttribute('data-page-id');
        }
        
        return null;
    }

    /**
     * Sleep utility
     */
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * Animate breadcrumb removal
     */
    async function animateBreadcrumbRemoval(currentBreadcrumbs, targetBreadcrumbs) {
        const currentItems = Array.from(currentBreadcrumbs.querySelectorAll('.docs-breadcrumbs__item'));
        const currentTexts = currentItems.map(item => item.textContent.trim());
        
        // Find where breadcrumbs will diverge
        let divergeIndex = 0;
        for (let i = 0; i < Math.min(currentTexts.length, targetBreadcrumbs.length); i++) {
            if (currentTexts[i] !== targetBreadcrumbs[i]) {
                divergeIndex = i;
                break;
            }
            divergeIndex = i + 1;
        }
        
        // Fade out items that will change (from the end)
        for (let i = currentItems.length - 1; i >= divergeIndex; i--) {
            const item = currentItems[i];
            item.style.opacity = '0';
            item.style.transform = 'translateX(-10px)';
            await sleep(100);
        }
        
        // Remove the faded items
        for (let i = divergeIndex; i < currentItems.length; i++) {
            currentItems[i].remove();
        }
    }
    
    /**
     * Animate breadcrumb addition
     */
    async function animateBreadcrumbAddition(targetBreadcrumbs) {
        const breadcrumbsWrapper = document.querySelector('.docs-breadcrumbs-wrapper');
        if (!breadcrumbsWrapper) return;
        
        let breadcrumbsEl = breadcrumbsWrapper.querySelector('.docs-breadcrumbs');
        if (!breadcrumbsEl) {
            // Create breadcrumbs container if it doesn't exist
            breadcrumbsEl = document.createElement('nav');
            breadcrumbsEl.className = 'docs-breadcrumbs';
            breadcrumbsEl.setAttribute('aria-label', 'Breadcrumb');
            breadcrumbsWrapper.insertBefore(breadcrumbsEl, breadcrumbsWrapper.firstChild);
        }
        
        let list = breadcrumbsEl.querySelector('.docs-breadcrumbs__list');
        if (!list) {
            list = document.createElement('ul');
            list.className = 'docs-breadcrumbs__list';
            breadcrumbsEl.appendChild(list);
        }
        
        // Get existing items
        const existingItems = Array.from(list.querySelectorAll('.docs-breadcrumbs__item'));
        const startIndex = existingItems.length;
        
        // Add new breadcrumb items
        for (let i = startIndex; i < targetBreadcrumbs.length; i++) {
            const item = document.createElement('li');
            item.className = 'docs-breadcrumbs__item';
            
            if (i < targetBreadcrumbs.length - 1) {
                // Link for non-current items
                const link = document.createElement('a');
                link.className = 'docs-breadcrumbs__link';
                link.textContent = targetBreadcrumbs[i];
                // We don't have the URL here, so leave href empty for now
                link.href = '#';
                item.appendChild(link);
            } else {
                // Current page
                const current = document.createElement('span');
                current.className = 'docs-breadcrumbs__current';
                current.setAttribute('aria-current', 'page');
                current.textContent = targetBreadcrumbs[i];
                item.appendChild(current);
            }
            
            // Add with fade effect
            item.style.opacity = '0';
            item.style.transform = 'translateX(-10px)';
            list.appendChild(item);
            
            // Trigger reflow
            item.offsetHeight;
            
            // Fade in
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
            await sleep(100);
        }
    }
    
    /**
     * Animate breadcrumb changes with fade effect
     */
    async function animateBreadcrumbChange(oldBreadcrumbs, newBreadcrumbs) {
        const oldItems = Array.from(oldBreadcrumbs.querySelectorAll('.docs-breadcrumbs__item'));
        const newItems = Array.from(newBreadcrumbs.querySelectorAll('.docs-breadcrumbs__item'));
        
        // Find where breadcrumbs diverge
        let divergeIndex = 0;
        for (let i = 0; i < Math.min(oldItems.length, newItems.length); i++) {
            const oldText = oldItems[i].textContent.trim();
            const newText = newItems[i].textContent.trim();
            if (oldText !== newText) {
                divergeIndex = i;
                break;
            }
            divergeIndex = i + 1;
        }
        
        // Fade out old items (from the end)
        for (let i = oldItems.length - 1; i >= divergeIndex; i--) {
            const item = oldItems[i];
            item.style.opacity = '0';
            item.style.transform = 'translateX(-10px)';
            await sleep(100);
        }
        
        // Wait a bit then remove old items
        await sleep(100);
        for (let i = divergeIndex; i < oldItems.length; i++) {
            oldItems[i].remove();
        }
        
        // Add new items with fade in
        const list = oldBreadcrumbs.querySelector('.docs-breadcrumbs__list');
        for (let i = divergeIndex; i < newItems.length; i++) {
            const newItem = newItems[i].cloneNode(true);
            newItem.style.opacity = '0';
            newItem.style.transform = 'translateX(-10px)';
            list.appendChild(newItem);
            
            // Trigger reflow
            newItem.offsetHeight;
            
            // Fade in
            newItem.style.opacity = '1';
            newItem.style.transform = 'translateX(0)';
            await sleep(100);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Add CSS for transitions
    const style = document.createElement('style');
    style.textContent = `
        .entry-content,
        .entry-title,
        .page-subtitle,
        #table-of-contents {
            transition: opacity ${config.transitionDuration}ms ease, transform ${config.transitionDuration}ms ease;
        }
        
        .docs-breadcrumbs__item {
            transition: opacity 100ms ease, transform 100ms ease;
        }
        
        .breadcrumb-caret {
            color: var(--color-accent);
            font-weight: bold;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        .async-loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 200ms ease, background 200ms ease;
        }
        
        .async-loader-overlay.is-visible {
            opacity: 1;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .async-loader {
            background: var(--color-widget-bg);
            border-radius: 12px;
            padding: var(--spacing-md) var(--spacing-lg);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .async-loader-spinner {
            width: 24px;
            height: 24px;
            border: 3px solid var(--color-border-ui);
            border-top-color: var(--color-accent);
            border-radius: 50%;
            animation: async-spin 0.75s linear infinite;
            flex-shrink: 0;
        }
        
        .async-loader span {
            color: var(--color-muted);
            font-size: var(--font-size-sm);
            white-space: nowrap;
        }
        
        @keyframes async-spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

})();