(function() {
    function initHeaderMenu() {
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const mobileNav = document.getElementById('mobileNav');
        const navOverlay = document.getElementById('navOverlay');

        if (!hamburgerMenu || !mobileNav || !navOverlay) return;

        function toggleMenu() {
            hamburgerMenu.classList.toggle('active');
            mobileNav.classList.toggle('active');
            navOverlay.classList.toggle('active');

            if (mobileNav.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        // Clear existing event listeners by cloning
        const newHamburger = hamburgerMenu.cloneNode(true);
        hamburgerMenu.parentNode.replaceChild(newHamburger, hamburgerMenu);

        const newOverlay = navOverlay.cloneNode(true);
        navOverlay.parentNode.replaceChild(newOverlay, navOverlay);

        // Add event listeners to the new elements
        document.getElementById('hamburgerMenu').addEventListener('click', toggleMenu);
        document.getElementById('navOverlay').addEventListener('click', toggleMenu);

        // Add keyboard support for closing the menu
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('mobileNav').classList.contains('active')) {
                toggleMenu();
            }
        });
    }

    // Run the script once the DOM is fully loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderMenu);
    } else {
        initHeaderMenu();
    }
})();
