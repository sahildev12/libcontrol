function initLibraryWebsite() {
    const header = document.querySelector('[data-lw-header]');
    const menuToggle = document.querySelector('[data-lw-menu-toggle]');
    const mobileMenu = document.querySelector('[data-lw-mobile-menu]');
    const navLinks = document.querySelectorAll('[data-lw-nav-link]');
    const sections = document.querySelectorAll('[data-lw-section]');
    const revealItems = document.querySelectorAll('[data-lw-reveal]');
    const galleryItems = document.querySelectorAll('[data-lw-gallery-item]');
    const lightbox = document.querySelector('[data-lw-lightbox]');
    const lightboxImage = document.querySelector('[data-lw-lightbox-image]');
    const lightboxClose = document.querySelector('[data-lw-lightbox-close]');
    const reviewsTrack = document.querySelector('[data-lw-reviews-track]');
    const reviewsPrev = document.querySelector('[data-lw-reviews-prev]');
    const reviewsNext = document.querySelector('[data-lw-reviews-next]');

    let reviewsTimer = null;

    const setHeaderState = () => {
        if (! header) {
            return;
        }

        header.classList.toggle('is-scrolled', window.scrollY > 12);
    };

    const closeMobileMenu = () => {
        if (! mobileMenu || ! menuToggle) {
            return;
        }

        mobileMenu.classList.add('hidden');
        menuToggle.setAttribute('aria-expanded', 'false');
    };

    menuToggle?.addEventListener('click', () => {
        const isOpen = mobileMenu?.classList.toggle('hidden') === false;
        menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    navLinks.forEach((link) => {
        link.addEventListener('click', () => closeMobileMenu());
    });

    const updateActiveNav = () => {
        let current = 'home';

        sections.forEach((section) => {
            if (section.getBoundingClientRect().top <= 120) {
                current = section.id;
            }
        });

        navLinks.forEach((link) => {
            const href = link.getAttribute('href') || '';
            const target = href.replace('#', '');
            link.classList.toggle('is-active', target === current);
        });
    };

    const openLightbox = (src, alt) => {
        if (! lightbox || ! lightboxImage) {
            return;
        }

        lightboxImage.src = src;
        lightboxImage.alt = alt || '';
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const closeLightbox = () => {
        if (! lightbox || ! lightboxImage) {
            return;
        }

        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        lightboxImage.src = '';
        document.body.classList.remove('overflow-hidden');
    };

    galleryItems.forEach((item) => {
        item.addEventListener('click', () => {
            const image = item.querySelector('img');
            if (! image) {
                return;
            }

            openLightbox(image.src, image.alt);
        });
    });

    lightboxClose?.addEventListener('click', closeLightbox);
    lightbox?.addEventListener('click', (event) => {
        if (event.target === lightbox) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeLightbox();
            closeMobileMenu();
        }
    });

    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealItems.forEach((item) => revealObserver.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }

    const scrollReviews = (direction) => {
        if (! reviewsTrack) {
            return;
        }

        const card = reviewsTrack.querySelector('[data-lw-review]');
        const step = card ? card.getBoundingClientRect().width + 16 : reviewsTrack.clientWidth;
        const atEnd = reviewsTrack.scrollLeft + reviewsTrack.clientWidth >= reviewsTrack.scrollWidth - 4;
        const atStart = reviewsTrack.scrollLeft <= 4;

        if (direction > 0 && atEnd) {
            reviewsTrack.scrollTo({ left: 0, behavior: 'smooth' });
        } else if (direction < 0 && atStart) {
            reviewsTrack.scrollTo({ left: reviewsTrack.scrollWidth, behavior: 'smooth' });
        } else {
            reviewsTrack.scrollBy({ left: step * direction, behavior: 'smooth' });
        }
    };

    const restartReviewsTimer = () => {
        if (reviewsTimer) {
            window.clearInterval(reviewsTimer);
        }

        if (! reviewsTrack || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        reviewsTimer = window.setInterval(() => scrollReviews(1), 5000);
    };

    reviewsPrev?.addEventListener('click', () => {
        scrollReviews(-1);
        restartReviewsTimer();
    });

    reviewsNext?.addEventListener('click', () => {
        scrollReviews(1);
        restartReviewsTimer();
    });

    reviewsTrack?.addEventListener('pointerenter', () => reviewsTimer && window.clearInterval(reviewsTimer));
    reviewsTrack?.addEventListener('pointerleave', restartReviewsTimer);

    const syncReviewToggles = () => {
        document.querySelectorAll('[data-lw-review]').forEach((card) => {
            const text = card.querySelector('[data-lw-review-text]');
            const toggle = card.querySelector('[data-lw-review-toggle]');
            if (! text || ! toggle || card.classList.contains('is-expanded')) {
                return;
            }

            toggle.classList.toggle('is-hidden', text.scrollHeight <= text.clientHeight + 1);
        });
    };

    document.querySelectorAll('[data-lw-review-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const card = toggle.closest('[data-lw-review]');
            const expanded = card?.classList.toggle('is-expanded');
            toggle.textContent = expanded ? 'Hide' : 'Read more';
        });
    });

    window.addEventListener('scroll', () => {
        setHeaderState();
        updateActiveNav();
    }, { passive: true });

    window.addEventListener('resize', syncReviewToggles);

    setHeaderState();
    updateActiveNav();
    syncReviewToggles();
    restartReviewsTimer();
}

document.addEventListener('DOMContentLoaded', initLibraryWebsite);
