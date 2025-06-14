/******/ (() => { // webpackBootstrap
/*!*********************!*\
  !*** ./src/view.js ***!
  \*********************/
/**
 * Table of Contents frontend script.
 */

(function () {
  'use strict';

  // Wait for DOM to be ready.
  document.addEventListener('DOMContentLoaded', function () {
    const tocElements = document.querySelectorAll('.docs-toc');
    tocElements.forEach(function (toc) {
      const links = toc.querySelectorAll('.docs-toc__link');
      const headings = [];

      // Collect all headings that match our TOC links.
      links.forEach(function (link) {
        const hash = link.getAttribute('href').substring(1);
        const heading = document.getElementById(hash);
        if (heading) {
          headings.push({
            element: heading,
            link: link,
            top: heading.offsetTop
          });
        }
      });
      if (headings.length === 0) {
        return;
      }

      // Function to update active state.
      function updateActiveHeading() {
        const scrollPosition = window.scrollY + 100; // Offset for better UX.
        let activeHeading = null;

        // Find the current active heading.
        for (let i = headings.length - 1; i >= 0; i--) {
          if (scrollPosition >= headings[i].element.offsetTop) {
            activeHeading = headings[i];
            break;
          }
        }

        // Update active classes.
        links.forEach(function (link) {
          link.classList.remove('is-active');
        });
        if (activeHeading) {
          activeHeading.link.classList.add('is-active');
        }
      }

      // Update on scroll.
      let scrollTimer;
      window.addEventListener('scroll', function () {
        if (scrollTimer) {
          clearTimeout(scrollTimer);
        }
        scrollTimer = setTimeout(updateActiveHeading, 10);
      });

      // Initial update.
      updateActiveHeading();

      // Smooth scroll on click.
      links.forEach(function (link) {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          const hash = this.getAttribute('href').substring(1);
          const target = document.getElementById(hash);
          if (target) {
            window.scrollTo({
              top: target.offsetTop - 80,
              behavior: 'smooth'
            });

            // Update URL without jumping.
            if (history.pushState) {
              history.pushState(null, null, '#' + hash);
            }
          }
        });
      });
    });

    // Also ensure headings have IDs for anchoring.
    const content = document.querySelector('.wp-block-post-content');
    if (content) {
      const headings = content.querySelectorAll('h2, h3, h4');
      headings.forEach(function (heading) {
        if (!heading.id) {
          const text = heading.textContent || heading.innerText;
          const id = text.toLowerCase().replace(/[^\w\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').trim();
          heading.id = id;
        }
      });
    }
  });
})();
/******/ })()
;
//# sourceMappingURL=view.js.map