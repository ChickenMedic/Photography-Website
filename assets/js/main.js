document.addEventListener('DOMContentLoaded', () => {

    // 1. Navbar Glassmorphism Effect on Scroll
    const nav = document.querySelector('.glass-nav');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            nav.style.padding = '10px 5%';
            nav.style.background = 'rgba(10, 10, 10, 0.8)';
        } else {
            nav.style.padding = '20px 5%';
            nav.style.background = 'rgba(10, 10, 10, 0.6)';
        }
    });

    // 2. Intersection Observer for Scroll Animations
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target); // Stop observing once visible
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-in').forEach(element => {
        observer.observe(element);
    });

    // 3. Lightbox Functionality
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxTitle = document.getElementById('lightbox-title');
    const lightboxDesc = document.getElementById('lightbox-desc');
    const closeBtn = document.querySelector('.close-btn');

    function openLightbox(imgSrc, title, desc) {
        lightboxImg.src = imgSrc;
        lightboxTitle.textContent = title;
        lightboxDesc.textContent = desc;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Event delegation for masonry items (both static and dynamic)
    document.body.addEventListener('click', (e) => {
        let item = e.target.closest('.photo-item');
        if (item) {
            openLightbox(
                item.getAttribute('data-image'),
                item.getAttribute('data-title'),
                item.getAttribute('data-desc')
            );
        }
    });

    // Close lightbox functions
    const closeLightbox = () => {
        lightbox.classList.remove('active');
        if (!document.getElementById('location-gallery-modal').classList.contains('active')) {
            document.body.style.overflow = 'auto'; // Only re-enable if gallery not open
        }

        setTimeout(() => {
            if (!lightbox.classList.contains('active')) {
                lightboxImg.src = '';
            }
        }, 400);
    };

    closeBtn.addEventListener('click', closeLightbox);

    // Close on background click
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) {
            closeLightbox();
        }
    });

    // 4. Hero Slider & Dynamic Quotes
    const heroSlides = document.querySelectorAll('.hero-slide');
    const dynamicQuote = document.getElementById('dynamic-quote');

    if (heroSlides.length > 0 && dynamicQuote) {
        const quotes = [
            "Capturing light, shadows, and everything in between.",
            "Photography takes an instant out of time, altering life by holding it still.",
            "The world is a canvas to the imagination.",
            "To travel is to discover that everyone is wrong about other countries.",
            "A camera is a save button for the mind's eye.",
            "Wandering through cities, discovering new perspectives."
        ];

        const fonts = [
            "'Inter', sans-serif",
            "'Playfair Display', serif",
            "'Cormorant Garamond', serif",
            "'Cinzel', serif",
            "'Dancing Script', cursive",
            "'Great Vibes', cursive"
        ];

        let currentSlide = 0;

        setInterval(() => {
            // Change Slide
            heroSlides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % heroSlides.length;
            heroSlides[currentSlide].classList.add('active');

            // Change Quote & Font
            dynamicQuote.classList.add('fade-out');

            setTimeout(() => {
                const randomQuote = quotes[Math.floor(Math.random() * quotes.length)];
                const randomFont = fonts[Math.floor(Math.random() * fonts.length)];

                dynamicQuote.textContent = randomQuote;
                dynamicQuote.style.fontFamily = randomFont;

                dynamicQuote.classList.remove('fade-out');
            }, 800);

        }, 6000);
    }

    // 5. Randomizer Button (Surprise Me)
    const randomPicBtn = document.getElementById('random-pic-btn');
    if (randomPicBtn && typeof allPhotosDB !== 'undefined' && allPhotosDB.length > 0) {
        randomPicBtn.addEventListener('click', () => {
            const randomPhoto = allPhotosDB[Math.floor(Math.random() * allPhotosDB.length)];
            openLightbox(
                'uploads/' + randomPhoto.filename,
                randomPhoto.title || 'Untitled',
                randomPhoto.description || ''
            );
        });
    }

    // 6. Location Gallery Modal & Randomized Layouts
    const locationCards = document.querySelectorAll('.location-card');
    const galleryModal = document.getElementById('location-gallery-modal');
    const galleryContainer = document.getElementById('gallery-container');
    const closeGalleryBtn = document.querySelector('.close-gallery-btn');
    const galleryTitle = document.getElementById('gallery-title');

    locationCards.forEach(card => {
        card.addEventListener('click', () => {
            if (typeof allPhotosDB === 'undefined') return;

            const locId = card.getAttribute('data-location-id');
            const locName = card.getAttribute('data-name');
            const photos = allPhotosDB.filter(p => p.location_id == locId);

            galleryTitle.textContent = locName;
            galleryContainer.innerHTML = ''; // Clear previous

            if (photos.length === 0) {
                galleryContainer.innerHTML = '<p style="text-align:center; width:100%; color:#94a3b8; margin-top:100px;">No photos in this location.</p>';
            } else {
                photos.forEach((photo, index) => {
                    const item = document.createElement('div');
                    item.className = 'photo-item fade-in visible';
                    // Special classes for CSS grid mosaic layout random sizing
                    if (index % 5 === 0) item.classList.add('large-item');
                    if (index % 7 === 0) item.classList.add('wide-item');

                    item.setAttribute('data-image', 'uploads/' + photo.filename);
                    item.setAttribute('data-title', photo.title || 'Untitled');
                    item.setAttribute('data-desc', photo.description || '');

                    item.innerHTML = `
                        <img src="uploads/${photo.filename}" alt="${photo.title || ''}" loading="lazy">
                        <div class="overlay">
                            <div class="overlay-content">
                                <h3 class="photo-quote">"${photoQuotes[Math.floor(Math.random() * photoQuotes.length)]}"</h3>
                            </div>
                        </div>
                    `;
                    galleryContainer.appendChild(item);
                });
            }

            // Apply Random Layout (1 to 8)
            const randomLayoutNum = Math.floor(Math.random() * 8) + 1;
            galleryContainer.className = 'gallery-container gallery-layout-' + randomLayoutNum;

            galleryModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            galleryModal.scrollTop = 0; // Reset scroll position
        });
    });

    if (closeGalleryBtn) {
        closeGalleryBtn.addEventListener('click', () => {
            galleryModal.classList.remove('active');
            document.body.style.overflow = 'auto';
            setTimeout(() => { galleryContainer.innerHTML = ''; }, 400);
        });
    }
});
