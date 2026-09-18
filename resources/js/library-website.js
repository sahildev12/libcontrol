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
    const testimonialTrack = document.querySelector('[data-lw-testimonial-track]');
    const testimonialDots = document.querySelectorAll('[data-lw-testimonial-dot]');

    let testimonialIndex = 0;
    let testimonialTimer = null;

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
            const rect = section.getBoundingClientRect();
            if (rect.top <= 120 && rect.bottom > 120) {
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

    const showTestimonial = (index) => {
        if (! testimonialTrack) {
            return;
        }

        const slides = testimonialTrack.querySelectorAll('[data-lw-testimonial-slide]');
        if (slides.length === 0) {
            return;
        }

        testimonialIndex = (index + slides.length) % slides.length;

        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('hidden', slideIndex !== testimonialIndex);
        });

        testimonialDots.forEach((dot, dotIndex) => {
            dot.classList.toggle('bg-blue-700', dotIndex === testimonialIndex);
            dot.classList.toggle('bg-slate-300', dotIndex !== testimonialIndex);
        });
    };

    const startTestimonialCarousel = () => {
        if (! testimonialTrack) {
            return;
        }

        showTestimonial(0);
        testimonialTimer = window.setInterval(() => {
            showTestimonial(testimonialIndex + 1);
        }, 6000);
    };

    testimonialDots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            if (testimonialTimer) {
                window.clearInterval(testimonialTimer);
            }
            showTestimonial(index);
            startTestimonialCarousel();
        });
    });

    window.addEventListener('scroll', () => {
        setHeaderState();
        updateActiveNav();
    }, { passive: true });

    setHeaderState();
    updateActiveNav();
    startTestimonialCarousel();
}

document.addEventListener('DOMContentLoaded', initLibraryWebsite);
