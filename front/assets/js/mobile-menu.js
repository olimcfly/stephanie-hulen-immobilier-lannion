// ======================================================
// NAVIGATION MENU - JavaScript Corrigé
// À placer dans front.php ou en fichier séparé
// ======================================================

// Toggle menu mobile
function toggleMobileMenu() {
    const nav = document.getElementById('mainNav');
    const body = document.body;
    
    nav.classList.toggle('active');
    body.classList.toggle('menu-open');
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    
    // Sur mobile, toggle les dropdowns au clic
    if (window.innerWidth <= 1024) {
        document.querySelectorAll('.dropdown > a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Fermer les autres dropdowns
                document.querySelectorAll('.dropdown').forEach(dropdown => {
                    if (dropdown !== this.parentElement) {
                        dropdown.classList.remove('active');
                    }
                });
                
                // Toggle celui-ci
                this.parentElement.classList.toggle('active');
            });
        });
    }
    
    // Fermer le menu si on clique sur l'overlay
    document.addEventListener('click', function(e) {
        const nav = document.getElementById('mainNav');
        const toggle = document.querySelector('.mobile-menu-toggle');
        
        // Si on clique en dehors du menu et du bouton
        if (!nav.contains(e.target) && e.target !== toggle) {
            nav.classList.remove('active');
            document.body.classList.remove('menu-open');
            
            // Fermer tous les dropdowns
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
    
    // Fermer le menu au clic sur un lien (sauf dropdowns)
    document.querySelectorAll('.main-nav a:not(.dropdown > a)').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                document.getElementById('mainNav').classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });
    });
    
    // Gérer le redimensionnement de la fenêtre
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Si on passe en desktop, fermer le menu mobile
            if (window.innerWidth > 1024) {
                const nav = document.getElementById('mainNav');
                nav.classList.remove('active');
                document.body.classList.remove('menu-open');
                
                // Retirer tous les active des dropdowns
                document.querySelectorAll('.dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        }, 250);
    });
    
    // Empêcher le scroll du body quand menu ouvert
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                const body = document.body;
                if (body.classList.contains('menu-open')) {
                    body.style.overflow = 'hidden';
                } else {
                    body.style.overflow = '';
                }
            }
        });
    });
    
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
    
});

// Fermer le menu avec la touche Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const nav = document.getElementById('mainNav');
        if (nav.classList.contains('active')) {
            nav.classList.remove('active');
            document.body.classList.remove('menu-open');
            
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    }
});