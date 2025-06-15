/**
 * Docs Theme JavaScript
 */

(function() {
	'use strict';

	// Initialize when DOM is ready
	document.addEventListener('DOMContentLoaded', function() {
		initCopyCodeButtons();
		initSearchShortcut();
		initSmoothAnchors();
		initCollapsibleMenus();
		initHighlightJS();
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
	 * Add keyboard shortcut for search (Cmd/Ctrl + K)
	 */
	function initSearchShortcut() {
		const searchInput = document.querySelector('.wp-block-search__input');
		if (!searchInput) return;
		
		document.addEventListener('keydown', function(e) {
			// Check for Cmd+K (Mac) or Ctrl+K (Windows/Linux)
			if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
				e.preventDefault();
				searchInput.focus();
				searchInput.select();
			}
			
			// Allow Escape to blur the search input
			if (e.key === 'Escape' && document.activeElement === searchInput) {
				searchInput.blur();
			}
		});
	}

	/**
	 * Smooth scrolling for anchor links
	 */
	function initSmoothAnchors() {
		const anchorLinks = document.querySelectorAll('a[href^="#"]');
		
		anchorLinks.forEach(function(link) {
			link.addEventListener('click', function(e) {
				const href = this.getAttribute('href');
				if (href === '#') return;
				
				const target = document.querySelector(href);
				if (!target) return;
				
				e.preventDefault();
				
				target.scrollIntoView({
					behavior: 'smooth',
					block: 'start'
				});
				
				// Update URL without jumping
				if (history.pushState) {
					history.pushState(null, null, href);
				}
			});
		});
	}

	/**
	 * Make sidebar page hierarchy collapsible
	 */
	function initCollapsibleMenus() {
		const toggleButtons = document.querySelectorAll('.toggle-children');
		
		toggleButtons.forEach(function(button) {
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
		
		// Also initialize table of contents
		initTableOfContents();
	}

	/**
	 * Generate and manage table of contents
	 */
	function initTableOfContents() {
		const tocContainer = document.getElementById('table-of-contents');
		const content = document.querySelector('.entry-content');
		
		if (!tocContainer || !content) return;
		
		// Find all headings
		const headings = content.querySelectorAll('h2, h3, h4');
		
		if (headings.length === 0) {
			tocContainer.innerHTML = '<p class="no-headings">No headings found</p>';
			return;
		}
		
		// Build TOC
		const tocList = document.createElement('ul');
		tocList.className = 'docs-toc__list';
		
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
			
			const level = heading.tagName.toLowerCase();
			const item = document.createElement('li');
			item.className = 'docs-toc__item docs-toc__item--' + level;
			
			const link = document.createElement('a');
			link.href = '#' + heading.id;
			link.className = 'docs-toc__link';
			link.textContent = heading.textContent;
			
			item.appendChild(link);
			tocList.appendChild(item);
		});
		
		tocContainer.appendChild(tocList);
		
		// Update active state on scroll
		let scrollTimer;
		window.addEventListener('scroll', function() {
			if (scrollTimer) clearTimeout(scrollTimer);
			scrollTimer = setTimeout(function() {
				updateActiveTocItem(headings);
			}, 10);
		});
		
		// Initial update
		updateActiveTocItem(headings);
	}

	/**
	 * Update active TOC item based on scroll position
	 */
	function updateActiveTocItem(headings) {
		const scrollPosition = window.scrollY + 100;
		let activeHeading = null;
		
		// Find current active heading
		for (let i = headings.length - 1; i >= 0; i--) {
			if (scrollPosition >= headings[i].offsetTop) {
				activeHeading = headings[i];
				break;
			}
		}
		
		// Update active classes
		const tocLinks = document.querySelectorAll('.docs-toc__link');
		tocLinks.forEach(function(link) {
			link.classList.remove('is-active');
			if (activeHeading && link.getAttribute('href') === '#' + activeHeading.id) {
				link.classList.add('is-active');
			}
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

})();