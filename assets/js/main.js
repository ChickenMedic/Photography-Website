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
    const dynamicQuoteContainer = document.getElementById('dynamic-quote-container');
    const dynamicQuoteText = document.getElementById('dynamic-quote-text');
    const dynamicQuoteAuthor = document.getElementById('dynamic-quote-author');
    
    // Set initial name font immediately
    const INITIAL_NAME_FONT = "'Outfit', sans-serif";
    const letterSpans = document.querySelectorAll('.letter-span');
    letterSpans.forEach(span => {
        span.style.fontFamily = INITIAL_NAME_FONT;
    });

    if (heroSlides.length > 0 && dynamicQuoteContainer) {
        const quotes = [
            "To me, photography is an art of observation. It's about finding something interesting in an ordinary place.|Elliott Erwitt",
            "Your first 10,000 photographs are your worst.|Henri Cartier-Bresson",
            "There are no rules for good photographs, there are only good photographs.|Ansel Adams",
            "Photography is the story I fail to put into words.|Destin Sparks",
            "If your pictures aren't good enough, you aren't close enough.|Robert Capa",
            "You don't take a photograph, you make it.|Ansel Adams",
            "The camera is an instrument that teaches people how to see without a camera.|Dorothea Lange",
            "A tear contains an ocean. A photographer is aware of the tiny moments in a person's life.|Robert Frank",
            "It is an illusion that photos are made with the camera... they are made with the eye, heart and head.|Henri Cartier-Bresson",
            "The best thing about a picture is that it never changes, even when the people in it do.|Andy Warhol",
            "Photography is a way of feeling, of touching, of loving.|Aaron Siskind",
            "A good photograph is one that communicates a fact, touches the heart and leaves the viewer a changed person.|Irving Penn",
            "Photography takes an instant out of time, altering life by holding it still.|Dorothea Lange",
            "I never have taken a picture I've intended. They're always better or worse.|Diane Arbus",
            "When words become unclear, I shall focus with photographs. When images become inadequate, I shall be content with silence.|Ansel Adams",
            "To photograph is to hold one's breath, when all faculties converge to capture fleeting reality.|Henri Cartier-Bresson",
            "The eye should learn to listen before it looks.|Robert Frank",
            "A camera is a tool for learning how to see without a camera.|Dorothea Lange",
            "Look and think before opening the shutter. The heart and mind are the true lens of the camera.|Yousuf Karsh",
            "Which of my photographs is my favorite? The one I’m going to take tomorrow.|Imogen Cunningham",
            "I walk, I look, I see, I stop, I photograph.|Leon Levinstein",
            "Character, like a photograph, develops in darkness.|Yousuf Karsh",
            "A portrait is not made in the camera but on either side of it.|Edward Steichen",
            "Great photography is about depth of feeling, not depth of field.|Peter Adams",
            "There is one thing the photograph must contain, the humanity of the moment.|Robert Frank",
            "Taking pictures is savoring life intensely, every hundredth of a second.|Marc Riboud",
            "The painter constructs, the photographer discloses.|Susan Sontag",
            "To consult the rules of composition before making a picture is a little like consulting the law of gravitation before going for a walk.|Edward Weston",
            "We are making photographs to understand what our lives mean to us.|Ralph Hattersley",
            "Photography for me is not looking, it's feeling.|Don McCullin"
        ];

        // Readable, strong styling fonts for quotes
        const readableFonts = [
            "'Inter', sans-serif",
            "'Oswald', sans-serif",
            "'Roboto Mono', monospace",
            "'Permanent Marker', cursive", // Graffiti style
            "'Playfair Display', serif",
            "'Lora', serif",
            "'Anton', sans-serif", // Block style
            "'Bebas Neue', sans-serif" // Block style
        ];

        // All fonts for the name letters (kept wilder)
        const nameFonts = [
            "'Inter', sans-serif",
            "'Playfair Display', serif",
            "'Cormorant Garamond', serif",
            "'Cinzel', serif",
            "'Dancing Script', cursive",
            "'Great Vibes', cursive",
            "'Outfit', sans-serif",
            "'Oswald', sans-serif",
            "'Permanent Marker', cursive"
        ];
        
        // Randomize letter spans continuously
        if (letterSpans.length > 0) {
            setInterval(() => {
                const randomLetter = letterSpans[Math.floor(Math.random() * letterSpans.length)];
                if (randomLetter.innerHTML === '&nbsp;') return;
                const randomFont = nameFonts[Math.floor(Math.random() * nameFonts.length)];
                randomLetter.style.fontFamily = randomFont;
            }, 500); // Change a random letter's font every 500ms
        }

        let currentSlide = 0;

        setInterval(() => {
            // Change Slide Images
            heroSlides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % heroSlides.length;
            heroSlides[currentSlide].classList.add('active');

            // Change Quote & Font simultaneously
            dynamicQuoteContainer.classList.add('fade-out');

            setTimeout(() => {
                const randomQuoteIndex = Math.floor(Math.random() * quotes.length);
                const quoteParts = quotes[randomQuoteIndex].split('|');
                
                const quoteText = quoteParts[0] || '';
                const quoteAuthor = quoteParts[1] ? quoteParts[1] : '';

                const randomFont = readableFonts[Math.floor(Math.random() * readableFonts.length)];

                dynamicQuoteText.textContent = `"${quoteText}"`;
                dynamicQuoteText.style.fontFamily = randomFont;
                dynamicQuoteAuthor.textContent = quoteAuthor;

                dynamicQuoteContainer.classList.remove('fade-out');
            }, 800);

        }, 16000); // 16 seconds per slide/quote
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
                                <h3>${photo.title || 'Untitled'}</h3>
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

    // 7. Featured Slideshow Logic
    const featuredSlideshowInner = document.getElementById('featured-slideshow-inner');
    if (featuredSlideshowInner && typeof allPhotosDB !== 'undefined' && allPhotosDB.length > 0) {
        
        const transitionClasses = [
            'fx-fade-in', 'fx-slide-left', 'fx-slide-right', 'fx-slide-up', 'fx-slide-down', 
            'fx-zoom-in', 'fx-zoom-out', 'fx-rotate-zoom', 'fx-flip-x', 'fx-flip-y', 
            'fx-blur-in', 'fx-drop-in', 'fx-spin-in', 'fx-skew-left', 'fx-skew-right', 
            'fx-fold-top', 'fx-fold-bottom', 'fx-slide-down-zoom', 'fx-squeeze', 'fx-expand'
        ];

        let currentFeaturedIndex = 0;
        let currentImgElement = null;

        function showNextFeaturedPhoto() {
            if (allPhotosDB.length === 0) return;

            const photo = allPhotosDB[currentFeaturedIndex];
            currentFeaturedIndex = (currentFeaturedIndex + 1) % allPhotosDB.length;

            if (!photo || !photo.filename) {
                showNextFeaturedPhoto(); // skip recursively
                return;
            }

            // Load image to determine aspect ratio device filtering
            const tempImg = new Image();
            tempImg.src = 'uploads/' + photo.filename;
            tempImg.onload = function() {
                const isDesktop = window.innerWidth > 768;
                const isLandscape = tempImg.width > tempImg.height;

                // Filter logic
                // If Desktop + Portrait, or Mobile + Landscape -> skip it
                if ((isDesktop && !isLandscape) || (!isDesktop && isLandscape)) {
                    // Skip and recursively call next
                    showNextFeaturedPhoto();
                    return;
                }

                // If check passed, show it
                const imgElement = document.createElement('img');
                imgElement.src = tempImg.src;
                imgElement.className = 'slideshow-img incoming';
                imgElement.alt = photo.title || 'Featured Photo';
                
                const randomFx = transitionClasses[Math.floor(Math.random() * transitionClasses.length)];
                imgElement.classList.add(randomFx);
                
                featuredSlideshowInner.appendChild(imgElement);
                
                // Allow animation to run, then clean up
                setTimeout(() => {
                    if (currentImgElement) {
                        currentImgElement.remove();
                    }
                    imgElement.classList.remove('incoming');
                    imgElement.classList.add('active');
                    imgElement.classList.remove(randomFx); // Remove animation class to prevent re-trigger
                    currentImgElement = imgElement;
                }, 1500); // 1.5s is the duration of the animations
            };
            tempImg.onerror = function() { // Fallback if image load fails
                showNextFeaturedPhoto();
            }
        }

        // Start slideshow immediately with first photo
        showNextFeaturedPhoto();
        // Cycle every 4.5 seconds to allow time for reading/viewing
        setInterval(showNextFeaturedPhoto, 4500);
    }
});
