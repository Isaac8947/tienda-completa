<?php
/**
 * Header Scroll Animation Component
 * Incluye CSS y JavaScript para animaciones de header con scroll
 */
?>

<style>
/* Header Scroll Animation Styles */
.header-visible {
    transform: translateY(0);
    opacity: 1;
}

.header-hidden {
    transform: translateY(-100%);
    opacity: 0;
}

.header-scroll-animation {
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
               opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1),
               backdrop-filter 0.3s ease,
               box-shadow 0.3s ease;
}

/* Header states for different scroll positions */
.header-scrolled {
    backdrop-filter: blur(20px);
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.header-compact {
    backdrop-filter: blur(25px);
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

/* Mobile header scroll animations */
.mobile-header-scroll-animation {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
               opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
               backdrop-filter 0.2s ease,
               box-shadow 0.2s ease;
}

.mobile-header-scrolled {
    backdrop-filter: blur(20px);
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

/* Smooth transitions for better UX */
@media (prefers-reduced-motion: reduce) {
    .header-scroll-animation,
    .mobile-header-scroll-animation {
        transition: none;
    }
}
</style>

<script>
/**
 * Header Scroll Animation Script
 * Maneja la animación suave de ocultación/aparición del header basada en el scroll
 */
document.addEventListener('DOMContentLoaded', function() {
    // Header scroll animation variables
    let lastScrollTop = 0;
    let scrollTimeout = null;
    let ticking = false;
    
    const desktopHeader = document.getElementById('desktop-header');
    const mobileHeader = document.getElementById('mobile-header');
    
    // Performance optimization - use requestAnimationFrame
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(handleHeaderScroll);
            ticking = true;
        }
    }
    
    // Main header scroll animation function
    function handleHeaderScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollDifference = scrollTop - lastScrollTop;
        const scrollThreshold = 50; // Minimum scroll distance to trigger animation
        const headerHeight = desktopHeader ? desktopHeader.offsetHeight : 0;
        
        // Clear existing timeout
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        
        // Desktop Header Animation Logic
        if (desktopHeader) {
            handleDesktopHeader(scrollTop, scrollDifference, scrollThreshold, headerHeight);
        }
        
        // Mobile Header Animation Logic
        if (mobileHeader) {
            handleMobileHeader(scrollTop, scrollDifference, scrollThreshold);
        }
        
        // Auto-show header if user stops scrolling
        scrollTimeout = setTimeout(() => {
            showHeadersOnScrollStop();
        }, 1500);
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        ticking = false;
    }
    
    // Handle desktop header animations
    function handleDesktopHeader(scrollTop, scrollDifference, scrollThreshold, headerHeight) {
        if (scrollTop > headerHeight) {
            // Add visual effects when scrolled
            desktopHeader.classList.add('header-scrolled');
            
            if (scrollTop > headerHeight * 2) {
                desktopHeader.classList.add('header-compact');
            } else {
                desktopHeader.classList.remove('header-compact');
            }
            
            // Hide/Show based on scroll direction
            if (scrollDifference > scrollThreshold && scrollTop > headerHeight) {
                // Scrolling down - hide header
                desktopHeader.classList.remove('header-visible');
                desktopHeader.classList.add('header-hidden');
            } else if (scrollDifference < -scrollThreshold) {
                // Scrolling up - show header
                desktopHeader.classList.remove('header-hidden');
                desktopHeader.classList.add('header-visible');
            }
        } else {
            // At top of page - always show header in clean state
            desktopHeader.classList.remove('header-scrolled', 'header-compact', 'header-hidden');
            desktopHeader.classList.add('header-visible');
        }
    }
    
    // Handle mobile header animations
    function handleMobileHeader(scrollTop, scrollDifference, scrollThreshold) {
        const mobileHeaderHeight = mobileHeader.offsetHeight;
        
        if (scrollTop > mobileHeaderHeight) {
            mobileHeader.classList.add('mobile-header-scrolled');
            
            // Hide/Show based on scroll direction
            if (scrollDifference > scrollThreshold && scrollTop > mobileHeaderHeight) {
                // Scrolling down - hide header
                mobileHeader.classList.remove('header-visible');
                mobileHeader.classList.add('header-hidden');
            } else if (scrollDifference < -scrollThreshold) {
                // Scrolling up - show header
                mobileHeader.classList.remove('header-hidden');
                mobileHeader.classList.add('header-visible');
            }
        } else {
            // At top of page - always show header in clean state
            mobileHeader.classList.remove('mobile-header-scrolled', 'header-hidden');
            mobileHeader.classList.add('header-visible');
        }
    }
    
    // Show headers when user stops scrolling
    function showHeadersOnScrollStop() {
        if (desktopHeader && !desktopHeader.classList.contains('header-visible')) {
            desktopHeader.classList.remove('header-hidden');
            desktopHeader.classList.add('header-visible');
        }
        if (mobileHeader && !mobileHeader.classList.contains('header-visible')) {
            mobileHeader.classList.remove('header-hidden');
            mobileHeader.classList.add('header-visible');
        }
    }
    
    // Optimized scroll event listener
    window.addEventListener('scroll', requestTick, { passive: true });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        lastScrollTop = 0; // Reset scroll position on resize
    }, { passive: true });
    
    // Initialize headers as visible
    setTimeout(() => {
        if (desktopHeader) {
            desktopHeader.classList.add('header-visible');
        }
        if (mobileHeader) {
            mobileHeader.classList.add('header-visible');
        }
    }, 100);
});
</script>