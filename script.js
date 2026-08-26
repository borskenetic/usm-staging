document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('header');
    const navToggle = document.querySelector('#navMenuToggle');
    const primaryNav = document.querySelector('#primaryNav');

    if (navToggle && primaryNav) {
        const setMenuState = (isOpen) => {
            primaryNav.classList.toggle('is-open', isOpen);
            navToggle.classList.toggle('is-open', isOpen);
            navToggle.setAttribute('aria-expanded', String(isOpen));
            navToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
        };

        navToggle.addEventListener('click', () => {
            setMenuState(!primaryNav.classList.contains('is-open'));
        });

        primaryNav.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setMenuState(false));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setMenuState(false);
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 1100) {
                setMenuState(false);
            }
        });
    }

    if (header) {
        const hero = document.querySelector('.hero-section');
        const updateHeaderShadow = () => {
            if (header.classList.contains('navbar--hero') && hero) {
                const isOverHero = window.scrollY < hero.offsetTop + hero.offsetHeight - header.offsetHeight;
                header.classList.toggle('navbar--solid', !isOverHero);
                header.style.boxShadow = isOverHero ? 'none' : '0 2px 10px rgba(0,0,0,0.1)';
                return;
            }

            header.style.boxShadow = window.scrollY > 50 ? '0 2px 10px rgba(0,0,0,0.1)' : 'none';
        };

        updateHeaderShadow();
        window.addEventListener('scroll', updateHeaderShadow, { passive: true });
    }

    // When hero videos fail to load, keep the KEPLRC banner visible via poster/fallback bg.
    document.querySelectorAll('.hero-section .bg-video, .zendy-banner .bg-video').forEach((video) => {
        const showFallback = () => {
            const parent = video.closest('.hero-section, .zendy-banner');
            if (parent) {
                parent.classList.add('video-fallback');
            }
        };

        video.addEventListener('error', showFallback);
        video.querySelectorAll('source').forEach((source) => {
            source.addEventListener('error', showFallback);
        });

        // Empty/missing source often leaves a black frame; detect after a short wait.
        setTimeout(() => {
            if (video.readyState < 2 && video.networkState === HTMLMediaElement.NETWORK_NO_SOURCE) {
                showFallback();
            }
        }, 800);
    });
});
