document.addEventListener('DOMContentLoaded', () => {

    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');

    if (!menuToggle || !mobileMenu) {
        console.warn("Mobile menu elements missing (menu-toggle or mobile-menu).");
        return;
    }

    // --- OUVERTURE / FERMETURE DU MENU ---
    menuToggle.addEventListener('click', () => {
        menuToggle.classList.toggle('active');
        mobileMenu.classList.toggle('active');
        document.body.classList.toggle('menu-open');
    });

    // --- FERMETURE AU CLICK SUR UN LIEN ---
    const mobileLinks = mobileMenu.querySelectorAll('a:not(.dropdown-toggle)');
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.remove('active');
            menuToggle.classList.remove('active');
            document.body.classList.remove('menu-open');
        });
    });

    // --- GESTION DES SOUS-MENUS (dropdown) ---
    const dropdownToggles = mobileMenu.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();

            const parent = toggle.closest('.dropdown');
            if (!parent) return;

            // referme les autres
            document.querySelectorAll('.mobile-menu .dropdown.active').forEach(d => {
                if (d !== parent) d.classList.remove('active');
            });

            parent.classList.toggle('active');
        });
    });

    // --- FERMETURE SI CLICK EN DEHORS ---
    document.addEventListener('click', (e) => {
        if (!mobileMenu.classList.contains('active')) return;

        if (!mobileMenu.contains(e.target) && !menuToggle.contains(e.target)) {
            mobileMenu.classList.remove('active');
            menuToggle.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    });

    // --- RESET SI ON REPASSE EN MODE DESKTOP ---
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024 && mobileMenu.classList.contains('active')) {
            mobileMenu.classList.remove('active');
            menuToggle.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    });

});
