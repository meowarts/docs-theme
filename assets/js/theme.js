/**
 * Docs Theme JavaScript
 */

(function() {
	'use strict';

	// Initialize when DOM is ready
	document.addEventListener('DOMContentLoaded', function() {
		initCopyCodeButtons();
		initSmoothAnchors();
		initCollapsibleMenus();
		initHighlightJS();
		initMobileMenu();
	});

	/**
	 * Add copy to clipboard functionality to code blocks
	 */
	function initCopyCodeButtons() {
		const codeBlocks = document.querySelectorAll('pre');
		
		codeBlocks.forEach(function(block) {
			const button = document.createElement('button');
			button.className = 'copy-code-button';
			button.textContent = 'Copy';
			button.type = 'button';
			
			button.addEventListener('click', function() {
				const code = block.querySelector('code');
				if (!code) return;
				
				const text = code.textContent || code.innerText;
				
				// Use modern clipboard API if available
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function() {
						button.textContent = 'Copied!';
						button.classList.add('copied');
						
						setTimeout(function() {
							button.textContent = 'Copy';
							button.classList.remove('copied');
						}, 2000);
					});
				} else {
					// Fallback for older browsers
					const textarea = document.createElement('textarea');
					textarea.value = text;
					textarea.style.position = 'fixed';
					textarea.style.opacity = '0';
					document.body.appendChild(textarea);
					textarea.select();
					document.execCommand('copy');
					document.body.removeChild(textarea);
					
					button.textContent = 'Copied!';
					button.classList.add('copied');
					
					setTimeout(function() {
						button.textContent = 'Copy';
						button.classList.remove('copied');
					}, 2000);
				}
			});
			
			block.appendChild(button);
		});
	}


	/**
	 * Smooth scroll handler for anchor links
	 */
	function smoothScrollHandler(e) {
		const href = this.getAttribute('href');
		if (href === '#') return;
		
		// Check if this is a ToC link with an anchor
		const anchorId = this.getAttribute('data-anchor-id');
		let scrollTarget;
		
		if (anchorId) {
			// Use the anchor element for ToC links
			scrollTarget = document.getElementById(anchorId);
		} else {
			// Fallback to the actual target
			scrollTarget = document.querySelector(href);
		}
		
		const target = document.querySelector(href); // Still need the actual heading for active state
		if (!scrollTarget || !target) return;
		
		e.preventDefault();
		
		// Calculate target position
		let targetPosition;
		if (target.id === 'page-title') {
			// For page title, scroll to absolute top to show breadcrumbs
			targetPosition = 0;
		} else {
			// Use the anchor's position for scrolling
			const rect = scrollTarget.getBoundingClientRect();
			const absoluteTop = rect.top + window.pageYOffset;
			const documentHeight = document.documentElement.scrollHeight;
			const viewportHeight = window.innerHeight;
			
			// Check if this is one of the last headings that might not fit in viewport
			const remainingSpace = documentHeight - absoluteTop;
			if (remainingSpace < viewportHeight) {
				// For last headings with little content, position them higher in viewport
				targetPosition = Math.max(0, documentHeight - viewportHeight);
			} else {
				// Normal headings - the anchor is already 125px above the heading
				targetPosition = absoluteTop;
			}
		}
		
		// Set flag to prevent updates during scroll
		isScrollingToTarget = true;
		
		// Immediately update ToC to show where we're going
		const allHeadings = document.querySelectorAll('.entry-title, .entry-content h2, .entry-content h3, .entry-content h4');
		const targetHeading = Array.from(allHeadings).find(h => h.id === target.id);
		if (targetHeading && window.setActiveTocLink) {
			window.currentActiveHeading = targetHeading;
			window.lastKnownActiveHeading = targetHeading;
			window.setActiveTocLink(target.id);
		}
		
		// Update URL without jumping
		if (history.pushState) {
			history.pushState(null, null, href);
		}
		
		window.scrollTo({
			top: targetPosition,
			behavior: 'smooth'
		});
		
		// Clear flag after scroll animation completes
		setTimeout(function() {
			isScrollingToTarget = false;
			// Update positions after scroll
			if (window.updateHeadingPositions && allHeadings) {
				window.updateHeadingPositions(Array.from(allHeadings));
			}
			// Force update to correct heading if needed
			const finalHeading = findActiveHeadingFromPositions();
			if (finalHeading && finalHeading !== currentActiveHeading) {
				currentActiveHeading = finalHeading;
				lastKnownActiveHeading = finalHeading;
				setActiveTocLink(finalHeading.id);
			}
		}, 600); // After smooth scroll animation
	}

	/**
	 * Smooth scrolling for anchor links
	 */
	function initSmoothAnchors() {
		const anchorLinks = document.querySelectorAll('a[href^="#"]');
		
		anchorLinks.forEach(function(link) {
			// Remove any existing listener to avoid duplicates
			link.removeEventListener('click', smoothScrollHandler);
			// Add the listener
			link.addEventListener('click', smoothScrollHandler);
		});
	}

		/**
		 * Initialize parent page click handlers
		 * (No-op: parent titles are links; caret toggles children)
		 */
		function initParentPageHandlers() {}

	/**
	 * Make sidebar page hierarchy collapsible
	 */
	function initCollapsibleMenus() {
		const toggleButtons = document.querySelectorAll('.toggle-children');
		
		toggleButtons.forEach(function(button) {
			// Skip if already initialized
			if (button.hasAttribute('data-initialized')) {
				return;
			}
			
			// Mark as initialized
			button.setAttribute('data-initialized', 'true');
			
			button.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				const pageItem = button.closest('.page-item');
				const childrenList = pageItem.querySelector('.children');
				
				if (!childrenList) return;
				
				const isExpanded = button.getAttribute('aria-expanded') === 'true';
				
				if (isExpanded) {
					childrenList.style.display = 'none';
					button.setAttribute('aria-expanded', 'false');
				} else {
					childrenList.style.display = 'block';
					button.setAttribute('aria-expanded', 'true');
				}
			});
		});
		
		// Initialize parent page handlers
		initParentPageHandlers();
		
		// Also initialize table of contents
		initTableOfContents();
	}
	
	/**
	 * Reinitialize menu handlers (for async navigation)
	 */
	function reinitMenuHandlers() {
		// First, remove all existing event listeners by cloning elements
		document.querySelectorAll('.toggle-children').forEach(function(button) {
			const newButton = button.cloneNode(true);
			newButton.removeAttribute('data-initialized');
			button.parentNode.replaceChild(newButton, button);
		});
		// Parent links are normal anchors; no special handlers
		
		// Now reinitialize all handlers
		const toggleButtons = document.querySelectorAll('.toggle-children');
		toggleButtons.forEach(function(button) {
			button.setAttribute('data-initialized', 'true');
			
			button.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				const pageItem = button.closest('.page-item');
				const childrenList = pageItem.querySelector('.children');
				
				if (!childrenList) return;
				
				const isExpanded = button.getAttribute('aria-expanded') === 'true';
				
				if (isExpanded) {
					childrenList.style.display = 'none';
					button.setAttribute('aria-expanded', 'false');
				} else {
					childrenList.style.display = 'block';
					button.setAttribute('aria-expanded', 'true');
				}
			});
		});
		
		// Parent links navigate; only toggles control expansion
	}

	// Store the current observer so we can disconnect it when needed
	let tocObserver = null;
	let currentActiveHeading = null;
	let lastKnownActiveHeading = null; // Persistent active heading
	let updateTimer = null; // Debounce timer
	let headingPositions = []; // Store exact positions of all headings
	let isScrollingToTarget = false; // Flag to prevent updates during smooth scroll

	/**
	 * Generate and manage table of contents
	 */
	function initTableOfContents() {
		const tocContainer = document.getElementById('table-of-contents');
		const content = document.querySelector('.entry-content');
		const pageTitle = document.querySelector('.entry-title');
		const tocSidebar = document.querySelector('.docs-sidebar-right');
		
		if (!tocContainer || !content) return;
		
		// Clean up any existing observer
		if (tocObserver) {
			tocObserver.disconnect();
			tocObserver = null;
		}
		
		// Clean up any existing scroll handler
		if (window.tocScrollHandler) {
			window.removeEventListener('scroll', window.tocScrollHandler);
			window.tocScrollHandler = null;
		}
		
		// Find all headings
		const headings = content.querySelectorAll('h2, h3, h4');
		
		// Check if we should hide the ToC (only page title, no other headings)
		const tocSection = document.querySelector('.sidebar-toc-section');
		if (headings.length === 0 && tocSection) {
			// Hide the ToC section if there are no headings
			tocSection.style.display = 'none';
			return;
		} else if (tocSection) {
			// Make sure it's visible if there are headings
			tocSection.style.display = '';
		}
		
		// Clear existing TOC content
		tocContainer.innerHTML = '';
		
		// Reset the active ToC tracking
		currentActiveTocId = null;
		
		// Clean up any existing scroll anchors
		document.querySelectorAll('.docs-toc-anchor').forEach(anchor => anchor.remove());
		
		// Build TOC
		const tocList = document.createElement('ul');
		tocList.className = 'docs-toc__list';
		
		// Add page title as first item
		if (pageTitle) {
			// Ensure title has an ID
			if (!pageTitle.id) {
				pageTitle.id = 'page-title';
			}
			
			// Add anchor before page title
			const titleAnchor = document.createElement('div');
			titleAnchor.className = 'docs-toc-anchor';
			titleAnchor.id = pageTitle.id + '-anchor';
			titleAnchor.style.cssText = 'position: relative; top: -140px; height: 0; visibility: hidden; pointer-events: none;';
			pageTitle.parentNode.insertBefore(titleAnchor, pageTitle);
			
			const titleItem = document.createElement('li');
			titleItem.className = 'docs-toc__item docs-toc__item--h1';
			
			const titleLink = document.createElement('a');
			titleLink.href = '#' + pageTitle.id;
			titleLink.className = 'docs-toc__link';
			titleLink.textContent = pageTitle.textContent;
			titleLink.setAttribute('data-anchor-id', titleAnchor.id);
			
			titleItem.appendChild(titleLink);
			tocList.appendChild(titleItem);
		}
		
		// Add other headings
		headings.forEach(function(heading) {
			// Ensure heading has an ID
			if (!heading.id) {
				const text = heading.textContent || heading.innerText;
				heading.id = text.toLowerCase()
					.replace(/[^\w\s-]/g, '')
					.replace(/\s+/g, '-')
					.replace(/-+/g, '-')
					.trim();
			}
			
			// Add anchor before heading
			const anchor = document.createElement('div');
			anchor.className = 'docs-toc-anchor';
			anchor.id = heading.id + '-anchor';
			anchor.style.cssText = 'position: relative; top: -140px; height: 0; visibility: hidden; pointer-events: none;';
			heading.parentNode.insertBefore(anchor, heading);
			
			const level = heading.tagName.toLowerCase();
			const item = document.createElement('li');
			item.className = 'docs-toc__item docs-toc__item--' + level;
			
			const link = document.createElement('a');
			link.href = '#' + heading.id;
			link.className = 'docs-toc__link';
			link.textContent = heading.textContent;
			link.setAttribute('data-anchor-id', anchor.id);
			
			item.appendChild(link);
			tocList.appendChild(item);
		});
		
		tocContainer.appendChild(tocList);
		
		// Create array with all headings including title
		const allHeadings = pageTitle ? [pageTitle, ...headings] : [...headings];
		
		// Set up smooth scrolling for the ToC links
		const tocLinks = tocContainer.querySelectorAll('a[href^="#"]');
		tocLinks.forEach(function(link) {
			// Remove any existing listener first
			link.removeEventListener('click', smoothScrollHandler);
			// Add the listener
			link.addEventListener('click', smoothScrollHandler);
		});
		
		// Set up Intersection Observer for heading visibility
		setupTocObserver(allHeadings);
	}

	/**
	 * Calculate and store heading positions
	 */
	function updateHeadingPositions(headings) {
		headingPositions = headings.map(function(heading) {
			const rect = heading.getBoundingClientRect();
			return {
				element: heading,
				id: heading.id,
				top: rect.top + window.scrollY,
				height: rect.height
			};
		});
	}

	/**
	 * Find active heading using stored positions
	 */
	function findActiveHeadingFromPositions() {
		const scrollTop = window.scrollY;
		const viewportHeight = window.innerHeight;
		const documentHeight = document.documentElement.scrollHeight;
		const activationPoint = scrollTop + viewportHeight * 0.15; // 15% from top
		
		// If at the very top, return first heading
		if (scrollTop < 50) {
			return headingPositions[0]?.element;
		}
		
		// Check if we're at the bottom of the page
		const isAtBottom = scrollTop + viewportHeight >= documentHeight - 50;
		
		// Find the last heading that's above the activation point
		let activeHeading = headingPositions[0]?.element;
		let lastVisibleHeading = null;
		
		for (let i = 0; i < headingPositions.length; i++) {
			const heading = headingPositions[i];
			
			// Check if heading is visible in viewport at all
			const headingBottom = heading.top + heading.height;
			const isVisible = heading.top < scrollTop + viewportHeight && headingBottom > scrollTop;
			
			if (isVisible) {
				lastVisibleHeading = heading.element;
			}
			
			if (heading.top <= activationPoint) {
				activeHeading = heading.element;
			} else {
				// We've passed the activation point
				break;
			}
		}
		
		// Special handling for bottom of page - use the last visible heading
		if (isAtBottom && lastVisibleHeading) {
			return lastVisibleHeading;
		}
		
		return activeHeading;
	}

	/**
	 * Set up ToC tracking system
	 */
	function setupTocObserver(headings) {
		if (!headings || headings.length === 0) return;
		
		// Reset state
		currentActiveHeading = null;
		if (!lastKnownActiveHeading && headings.length > 0) {
			lastKnownActiveHeading = headings[0]; // Default to first heading
		}
		
		// Clean up any existing observer
		if (tocObserver) {
			tocObserver.disconnect();
			tocObserver = null;
		}
		
		// Clean up any existing scroll handler
		if (window.tocScrollHandler) {
			window.removeEventListener('scroll', window.tocScrollHandler);
			window.tocScrollHandler = null;
		}
		
		// Calculate initial positions
		updateHeadingPositions(headings);
		
		// Simple scroll-based system
		let scrollTimer;
		let rafId = null;
		
		const scrollHandler = function() {
			// Skip updates if we're scrolling to a target
			if (isScrollingToTarget) return;
			
			// Cancel any pending animation frame
			if (rafId) {
				cancelAnimationFrame(rafId);
			}
			
			// Use requestAnimationFrame for smooth updates
			rafId = requestAnimationFrame(function() {
				// Double-check the flag in case it changed
				if (isScrollingToTarget) return;
				
				const activeHeading = findActiveHeadingFromPositions();
				if (activeHeading && activeHeading !== currentActiveHeading) {
					currentActiveHeading = activeHeading;
					lastKnownActiveHeading = activeHeading;
					setActiveTocLink(activeHeading.id);
				}
			});
			
			// Debounce position recalculation
			if (scrollTimer) clearTimeout(scrollTimer);
			scrollTimer = setTimeout(function() {
				// Recalculate positions in case of dynamic content
				updateHeadingPositions(headings);
			}, 500);
		};
		
		// Store the handler so we can remove it later
		window.tocScrollHandler = scrollHandler;
		window.addEventListener('scroll', scrollHandler, { passive: true });
		
		// Also update positions on resize
		let resizeTimer;
		window.addEventListener('resize', function() {
			if (resizeTimer) clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function() {
				updateHeadingPositions(headings);
				// Update active heading after resize
				const activeHeading = findActiveHeadingFromPositions();
				if (activeHeading) {
					currentActiveHeading = activeHeading;
					lastKnownActiveHeading = activeHeading;
					setActiveTocLink(activeHeading.id);
				}
			}, 250);
		});
		
		// Set initial active heading
		const initialActive = findActiveHeadingFromPositions();
		if (initialActive) {
			currentActiveHeading = initialActive;
			lastKnownActiveHeading = initialActive;
			setActiveTocLink(initialActive.id);
		}
	}


	// Track the currently active ToC link to avoid unnecessary updates
	let currentActiveTocId = null;

	/**
	 * Set active ToC link
	 */
	function setActiveTocLink(headingId) {
		if (!headingId) return;
		
		// Skip if this is already the active heading
		if (currentActiveTocId === headingId) return;
		
		const tocLinks = document.querySelectorAll('.docs-toc__link');
		let foundActive = false;
		
		tocLinks.forEach(function(link) {
			const href = link.getAttribute('href');
			const isActive = href === '#' + headingId;
			const hasActiveClass = link.classList.contains('is-active');
			
			if (isActive) {
				foundActive = true;
				// Only add class if not already present
				if (!hasActiveClass) {
					link.classList.add('is-active');
				}
			} else if (hasActiveClass) {
				// Only remove class if it exists
				link.classList.remove('is-active');
			}
		});
		
		// Update the tracked active ID
		if (foundActive) {
			currentActiveTocId = headingId;
		}
		
		// Ensure at least one item is active
		if (!foundActive && tocLinks.length > 0 && lastKnownActiveHeading && currentActiveTocId !== lastKnownActiveHeading.id) {
			// Try to activate the last known heading
			tocLinks.forEach(function(link) {
				const href = link.getAttribute('href');
				if (href === '#' + lastKnownActiveHeading.id) {
					if (!link.classList.contains('is-active')) {
						link.classList.add('is-active');
					}
					currentActiveTocId = lastKnownActiveHeading.id;
				}
			});
		}
	}

	/**
	 * Initialize mobile menu toggle
	 */
	function initMobileMenu() {
		const menuToggle = document.querySelector('.mobile-menu-toggle');
		const sidebar = document.querySelector('.docs-sidebar-left');
		const body = document.body;
		
		if (!menuToggle || !sidebar) return;
		
		function openMenu() {
			body.classList.add('mobile-menu-open');
			menuToggle.setAttribute('aria-expanded', 'true');
			menuToggle.setAttribute('aria-label', 'Close navigation menu');
		}
		
		function closeMenu() {
			body.classList.remove('mobile-menu-open');
			menuToggle.setAttribute('aria-expanded', 'false');
			menuToggle.setAttribute('aria-label', 'Open navigation menu');
		}
		
		menuToggle.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			const isOpen = body.classList.contains('mobile-menu-open');
			
			if (isOpen) {
				closeMenu();
			} else {
				openMenu();
			}
		});
		
		// Close menu when clicking outside
		document.addEventListener('click', function(e) {
			if (body.classList.contains('mobile-menu-open') && 
				!sidebar.contains(e.target) && 
				!menuToggle.contains(e.target)) {
				closeMenu();
			}
		});
		
		// Close menu when pressing Escape
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && body.classList.contains('mobile-menu-open')) {
				closeMenu();
			}
		});
		
		// Close menu when clicking on a navigation link (on mobile)
		const pageLinks = sidebar.querySelectorAll('.page-link');
		pageLinks.forEach(function(link) {
			link.addEventListener('click', function() {
				if (window.innerWidth <= 768 && body.classList.contains('mobile-menu-open')) {
					closeMenu();
				}
			});
		});
	}

	/**
	 * Initialize Highlight.js for WordPress code blocks
	 */
	function initHighlightJS() {
		// Wait for hljs to be available
		if (typeof hljs === 'undefined') {
			setTimeout(initHighlightJS, 100);
			return;
		}
		
		// Find all code blocks
		const codeBlocks = document.querySelectorAll('.wp-block-code code, .wp-code-block code, pre code');
		
		codeBlocks.forEach(function(code) {
			// Skip if already highlighted
			if (code.classList.contains('hljs')) {
				return;
			}
			
			// Auto-detect language if not specified
			let languageClass = '';
			const classes = code.className.split(' ');
			for (let cls of classes) {
				if (cls.startsWith('language-')) {
					languageClass = cls;
					break;
				}
			}
			
			// If no language class, try to detect
			if (!languageClass) {
				const content = code.textContent || '';
				
				// PHP detection
				if (content.includes('<?php') || content.includes('<?=') || 
				    content.match(/\$\w+\s*=/) || content.includes('->') || 
				    content.includes('::') || content.match(/function\s+\w+\s*\(/)) {
					code.className += ' language-php';
				}
				// JavaScript detection
				else if (content.match(/\b(const|let|var|function|=>|async|await)\b/) ||
				         content.includes('console.') || content.includes('document.')) {
					code.className += ' language-javascript';
				}
				// Bash detection
				else if (content.match(/^[$#]\s/m) || content.match(/\b(npm|git|cd|mkdir)\b/)) {
					code.className += ' language-bash';
				}
				// CSS detection
				else if (content.match(/[.#]\w+\s*{|\w+\s*:\s*[^;]+;/)) {
					code.className += ' language-css';
				}
			}
			
			// Apply highlighting
			hljs.highlightElement(code);
		});
	}

	/**
	 * Add loading states for dynamic content
	 */
	window.docsTheme = {
		showLoading: function(element) {
			element.classList.add('is-loading');
			element.setAttribute('aria-busy', 'true');
		},
		
		hideLoading: function(element) {
			element.classList.remove('is-loading');
			element.setAttribute('aria-busy', 'false');
		}
	};
	
	// Expose functions for async navigation
	window.initCopyCodeButtons = initCopyCodeButtons;
	window.initSmoothAnchors = initSmoothAnchors;
	window.initTableOfContents = initTableOfContents;
	window.initHighlightJS = initHighlightJS;
	window.setupTocObserver = setupTocObserver;
	window.setActiveTocLink = setActiveTocLink;
	window.currentActiveHeading = currentActiveHeading;
	window.lastKnownActiveHeading = lastKnownActiveHeading;
	window.updateHeadingPositions = updateHeadingPositions;
	window.findActiveHeadingFromPositions = findActiveHeadingFromPositions;
	window.initParentPageHandlers = initParentPageHandlers;
	window.reinitMenuHandlers = reinitMenuHandlers;

})();
