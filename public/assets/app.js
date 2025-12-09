/**
 * Hero Carousel - Vanilla JS replacement for Alpine.js
 */
class HeroCarousel {
    constructor(element) {
        this.container = element;
        this.slides = [];
        this.currentSlide = 0;
        this.autoAdvanceInterval = null;
        this.init();
    }

    init() {
        // Read slides from data attribute
        let slidesData = this.container.dataset.slides;
        if (slidesData) {
            try {
                // Decode HTML entities if present
                const textarea = document.createElement('textarea');
                textarea.innerHTML = slidesData;
                slidesData = textarea.value;

                this.slides = JSON.parse(slidesData);
            } catch (e) {
                console.error('Error parsing slides data:', e);
                return;
            }
        }

        if (this.slides.length === 0) return;

        this.render();
        this.bindEvents();
        this.startAutoAdvance();
    }

    render() {
        const slidesContainer = this.container.querySelector('.hero-slides');
        if (!slidesContainer) return;

        // Create slides HTML
        let slidesHtml = this.slides.map((slide, index) => `
            <div class="hero-slide ${index === 0 ? 'active' : ''}"
                 data-index="${index}"
                 style="background-image: url(${slide.image})"></div>
        `).join('');

        // Add navigation
        slidesHtml += `
            <button class="hero-nav-btn hero-nav-prev" aria-label="Image précédente">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button class="hero-nav-btn hero-nav-next" aria-label="Image suivante">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div class="hero-dots">
                ${this.slides.map((_, index) => `
                    <button class="hero-dot ${index === 0 ? 'active' : ''}" data-index="${index}"></button>
                `).join('')}
            </div>
        `;

        slidesContainer.innerHTML = slidesHtml;
    }

    bindEvents() {
        const prevBtn = this.container.querySelector('.hero-nav-prev');
        const nextBtn = this.container.querySelector('.hero-nav-next');
        const dots = this.container.querySelectorAll('.hero-dot');

        if (prevBtn) prevBtn.addEventListener('click', () => this.prevSlide());
        if (nextBtn) nextBtn.addEventListener('click', () => this.nextSlide());

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                const index = parseInt(dot.dataset.index);
                this.goToSlide(index);
            });
        });

        // Pause on hover
        this.container.addEventListener('mouseenter', () => this.stopAutoAdvance());
        this.container.addEventListener('mouseleave', () => this.startAutoAdvance());

        // Touch support
        let touchStartX = 0;
        this.container.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            this.stopAutoAdvance();
        }, { passive: true });

        this.container.addEventListener('touchend', (e) => {
            const diff = touchStartX - e.changedTouches[0].screenX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) this.nextSlide();
                else this.prevSlide();
            }
            this.startAutoAdvance();
        }, { passive: true });
    }

    showSlide(index) {
        const slides = this.container.querySelectorAll('.hero-slide');
        const dots = this.container.querySelectorAll('.hero-dot');

        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));

        if (slides[index]) slides[index].classList.add('active');
        if (dots[index]) dots[index].classList.add('active');

        this.currentSlide = index;
    }

    nextSlide() {
        const next = (this.currentSlide + 1) % this.slides.length;
        this.showSlide(next);
    }

    prevSlide() {
        const prev = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.showSlide(prev);
    }

    goToSlide(index) {
        this.showSlide(index);
    }

    startAutoAdvance() {
        if (this.autoAdvanceInterval) return;
        this.autoAdvanceInterval = setInterval(() => this.nextSlide(), 5000);
    }

    stopAutoAdvance() {
        if (this.autoAdvanceInterval) {
            clearInterval(this.autoAdvanceInterval);
            this.autoAdvanceInterval = null;
        }
    }
}

// Initialize hero carousel if present
document.addEventListener('DOMContentLoaded', () => {
    const heroCarousel = document.querySelector('.hero-carousel');
    if (heroCarousel) {
        new HeroCarousel(heroCarousel);
    }
});

// Photo Gallery JavaScript
class PhotoGallery {
    constructor() {
        this.lightbox = null;
        this.currentIndex = 0;
        this.images = [];
        this.init();
    }

    init() {
        this.createLightbox();
        this.bindEvents();
    }

    createLightbox() {
        const lightboxHTML = `
            <div class="lightbox" id="lightbox">
                <div class="lightbox-content">
                    <button class="lightbox-close" id="lightbox-close">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <button class="lightbox-nav lightbox-prev" id="lightbox-prev">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15,18 9,12 15,6"></polyline>
                        </svg>
                    </button>
                    <button class="lightbox-nav lightbox-next" id="lightbox-next">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,18 15,12 9,6"></polyline>
                        </svg>
                    </button>
                    <img id="lightbox-image" src="" alt="">
                    <div class="lightbox-info" id="lightbox-info">
                        <h3 id="lightbox-title"></h3>
                        <p id="lightbox-description"></p>
                    </div>
                    <div class="lightbox-counter" id="lightbox-counter"></div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', lightboxHTML);
        this.lightbox = document.getElementById('lightbox');
    }

    bindEvents() {
        // Gallery item clicks
        document.addEventListener('click', (e) => {
            const galleryItem = e.target.closest('.gallery-item');
            if (galleryItem) {
                e.preventDefault();
                this.openLightbox(galleryItem);
            }
        });

        // Lightbox controls
        document.getElementById('lightbox-close').addEventListener('click', () => this.closeLightbox());
        document.getElementById('lightbox-prev').addEventListener('click', () => this.previousImage());
        document.getElementById('lightbox-next').addEventListener('click', () => this.nextImage());

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!this.lightbox.classList.contains('active')) return;
            
            switch(e.key) {
                case 'Escape':
                    this.closeLightbox();
                    break;
                case 'ArrowLeft':
                    this.previousImage();
                    break;
                case 'ArrowRight':
                    this.nextImage();
                    break;
            }
        });

        // Click outside to close
        this.lightbox.addEventListener('click', (e) => {
            if (e.target === this.lightbox) {
                this.closeLightbox();
            }
        });
    }

    openLightbox(galleryItem) {
        const gallery = galleryItem.closest('.photo-gallery');
        this.images = Array.from(gallery.querySelectorAll('.gallery-item')).map(item => ({
            src: item.querySelector('img').src,
            title: item.dataset.title || item.querySelector('img').alt,
            description: item.dataset.description || ''
        }));

        this.currentIndex = Array.from(gallery.querySelectorAll('.gallery-item')).indexOf(galleryItem);
        this.showImage();
        this.lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    closeLightbox() {
        this.lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    previousImage() {
        this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.images.length - 1;
        this.showImage();
    }

    nextImage() {
        this.currentIndex = this.currentIndex < this.images.length - 1 ? this.currentIndex + 1 : 0;
        this.showImage();
    }

    showImage() {
        const image = this.images[this.currentIndex];
        document.getElementById('lightbox-image').src = image.src;
        document.getElementById('lightbox-title').textContent = image.title;
        document.getElementById('lightbox-description').textContent = image.description;
        document.getElementById('lightbox-counter').textContent = `${this.currentIndex + 1} / ${this.images.length}`;

        // Show/hide navigation buttons
        const prevBtn = document.getElementById('lightbox-prev');
        const nextBtn = document.getElementById('lightbox-next');
        
        if (this.images.length <= 1) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'flex';
            nextBtn.style.display = 'flex';
        }
    }
}

// Initialize gallery when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PhotoGallery();
});

// Utility function to create galleries from image arrays
function createGallery(containerId, images, options = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const {
        size = 'medium', // small, medium, large
        showOverlay = true,
        columns = null
    } = options;

    let galleryClass = 'photo-gallery';
    if (size === 'small') galleryClass += ' small';
    if (size === 'large') galleryClass += ' large';

    const galleryHTML = `
        <div class="${galleryClass}" ${columns ? `style="grid-template-columns: repeat(${columns}, 1fr)"` : ''}>
            ${images.map(image => `
                <div class="gallery-item" data-title="${image.title || ''}" data-description="${image.description || ''}">
                    <img src="${image.src}" alt="${image.alt || image.title || 'Image'}">
                    ${showOverlay && (image.title || image.description) ? `
                        <div class="overlay">
                            ${image.title ? `<h4>${image.title}</h4>` : ''}
                            ${image.description ? `<p>${image.description}</p>` : ''}
                        </div>
                    ` : ''}
                </div>
            `).join('')}
        </div>
    `;

    container.innerHTML = galleryHTML;
}/**
 * Handle YouTube thumbnail loading errors with fallback images
 */
function handleYouTubeThumbnailError(img) {
    const fallbackUrls = JSON.parse(img.getAttribute('data-fallback-urls'));
    let currentIndex = parseInt(img.getAttribute('data-current-index'));
    
    // Try next fallback URL
    currentIndex++;
    
    if (currentIndex < fallbackUrls.length) {
        img.setAttribute('data-current-index', currentIndex);
        img.src = fallbackUrls[currentIndex];
    } else {
        // All thumbnails failed, show a default YouTube placeholder
        showYouTubePlaceholder(img);
    }
}

/**
 * Check if YouTube thumbnail is valid (not too small)
 */
async function validateYouTubeThumbnail(img) {
    // Skip if already validated
    if (img.hasAttribute('data-validated')) {
        return true;
    }
    
    img.setAttribute('data-validated', 'true');
    
    // Alternative method: Check image dimensions directly
    // YouTube's low quality thumbnails are usually 120x90
    if (img.naturalWidth && img.naturalWidth <= 120) {
        console.log(`YouTube thumbnail too small (${img.naturalWidth}x${img.naturalHeight}), trying fallback`);
        handleYouTubeThumbnailError(img);
        return false;
    }
    
    // Try fetch if possible (may fail due to CORS)
    try {
        const response = await fetch(img.src, { 
            method: 'HEAD',
            mode: 'no-cors' // This will limit what we can read but avoids CORS errors
        });
        
        // With no-cors, we can't read headers, so use image size as fallback
        if (img.naturalWidth <= 120 || img.naturalHeight <= 90) {
            console.log(`YouTube thumbnail dimensions too small, trying fallback`);
            handleYouTubeThumbnailError(img);
            return false;
        }
        
        console.log(`YouTube thumbnail appears valid (${img.naturalWidth}x${img.naturalHeight})`);
        return true;
    } catch (error) {
        // Fallback to checking image dimensions
        console.log('Could not fetch thumbnail, checking dimensions instead');
        if (img.naturalWidth <= 120 || img.naturalHeight <= 90) {
            console.log(`YouTube thumbnail dimensions too small, trying fallback`);
            handleYouTubeThumbnailError(img);
            return false;
        }
        return true;
    }
}

/**
 * Show default YouTube placeholder
 */
function showYouTubePlaceholder(img) {
    img.src = 'data:image/svg+xml;base64,' + btoa(`
        <svg xmlns="http://www.w3.org/2000/svg" width="480" height="360" viewBox="0 0 480 360">
            <rect width="480" height="360" fill="#f0f0f0"/>
            <rect x="190" y="130" width="100" height="100" rx="50" fill="#ff0000"/>
            <polygon points="220,155 220,205 250,180" fill="white"/>
            <text x="240" y="280" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" fill="#666">
                Vidéo YouTube
            </text>
        </svg>
    `);
    img.alt = 'Miniature YouTube non disponible';
    img.removeAttribute('onerror'); // Prevent infinite loop
}

/**
 * Initialize YouTube thumbnail handling when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    const youtubeThumbnails = document.querySelectorAll('.youtube-thumbnail');
    console.log('Found YouTube thumbnails:', youtubeThumbnails.length);
    
    youtubeThumbnails.forEach(function(img) {
        console.log('Processing thumbnail:', img.src);
        
        // Check if image is already loaded
        if (img.complete) {
            console.log('Image already complete, validating now');
            validateYouTubeThumbnail(img);
        } else {
            // Add load event listener to validate thumbnail size
            img.addEventListener('load', function() {
                console.log('Image loaded, validating:', img.src);
                validateYouTubeThumbnail(img);
            });
        }
        
        // Set a timeout to fallback if image takes too long to load
        setTimeout(function() {
            if (!img.complete || img.naturalHeight === 0) {
                console.log('Timeout reached, forcing fallback for:', img.src);
                handleYouTubeThumbnailError(img);
            }
        }, 5000); // 5 second timeout
    });
});

// Also check for dynamically loaded content
window.addEventListener('load', function() {
    console.log('Window fully loaded, rechecking thumbnails');
    const youtubeThumbnails = document.querySelectorAll('.youtube-thumbnail');
    
    youtubeThumbnails.forEach(function(img) {
        if (img.complete && !img.hasAttribute('data-validated')) {
            console.log('Found unvalidated complete image:', img.src);
            validateYouTubeThumbnail(img);
        }
    });
});

/**
 * Alternative method: Check if YouTube thumbnail exists using fetch
 */
async function checkYouTubeThumbnail(videoId) {
    const thumbnailSizes = ['maxresdefault', 'hqdefault', 'mqdefault', 'default'];
    
    for (const size of thumbnailSizes) {
        const url = `https://img.youtube.com/vi/${videoId}/${size}.jpg`;
        try {
            const response = await fetch(url, { method: 'HEAD' });
            if (response.ok) {
                return url;
            }
        } catch (error) {
            continue;
        }
    }
    
    return null; // No thumbnail found
}