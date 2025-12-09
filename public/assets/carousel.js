/**
 * Blog Article Carousel Component
 * Transforms carousel shortcodes into interactive image carousels
 */

class ArticleCarousel {
    constructor() {
        this.carousels = [];
        this.init();
    }

    init() {
        this.processCarouselShortcodes();
        this.initializeCarousels();
    }

    /**
     * Process carousel shortcodes in article content
     * Syntax: [carousel]image1.jpg,image2.jpg,image3.jpg[/carousel]
     */
    processCarouselShortcodes() {
        const content = document.querySelector('.article-content, .article-detail-content, .prose');
        if (!content) return;

        const shortcodeRegex = /\[carousel\](.*?)\[\/carousel\]/g;
        let html = content.innerHTML;
        let match;
        let carouselId = 0;

        while ((match = shortcodeRegex.exec(html)) !== null) {
            const images = match[1].split(',').map(img => img.trim()).filter(img => img);
            if (images.length > 0) {
                const carouselHtml = this.generateCarouselHtml(images, carouselId);
                html = html.replace(match[0], carouselHtml);
                carouselId++;
            }
        }

        content.innerHTML = html;
    }

    /**
     * Generate HTML for a carousel
     */
    generateCarouselHtml(images, carouselId) {
        const slides = images.map((img, index) => `
            <div class="carousel-slide ${index === 0 ? 'active' : ''}" data-slide="${index}">
                <img src="${img}" alt="Image ${index + 1}" loading="lazy">
            </div>
        `).join('');

        const indicators = images.map((_, index) => `
            <button class="carousel-dot ${index === 0 ? 'active' : ''}" data-slide="${index}"></button>
        `).join('');

        return `
            <div class="article-carousel" data-carousel-id="${carouselId}">
                <div class="carousel-container">
                    ${slides}
                    ${images.length > 1 ? `
                        <button class="carousel-prev" aria-label="Image précédente">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button class="carousel-next" aria-label="Image suivante">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <div class="carousel-indicators">
                            ${indicators}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Initialize carousel functionality
     */
    initializeCarousels() {
        document.querySelectorAll('.article-carousel, .widget-gallery-carousel').forEach(carousel => {
            this.setupCarousel(carousel);
        });
    }

    setupCarousel(carousel) {
        const slides = carousel.querySelectorAll('.carousel-slide');
        const dots = carousel.querySelectorAll('.carousel-dot');
        const prevBtn = carousel.querySelector('.carousel-prev');
        const nextBtn = carousel.querySelector('.carousel-next');
        
        let currentSlide = 0;

        const showSlide = (index) => {
            // Hide all slides
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Show current slide
            slides[index].classList.add('active');
            if (dots[index]) dots[index].classList.add('active');
            
            currentSlide = index;
        };

        const nextSlide = () => {
            const next = (currentSlide + 1) % slides.length;
            showSlide(next);
        };

        const prevSlide = () => {
            const prev = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(prev);
        };

        // Event listeners for buttons
        if (prevBtn) prevBtn.addEventListener('click', (e) => { e.preventDefault(); prevSlide(); });
        if (nextBtn) nextBtn.addEventListener('click', (e) => { e.preventDefault(); nextSlide(); });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', (e) => { e.preventDefault(); showSlide(index); });
        });

        // Touch/swipe support
        let touchStartX = 0;
        let touchEndX = 0;
        const container = carousel.querySelector('.carousel-container');

        container.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        container.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) nextSlide();
                else prevSlide();
            }
        }, { passive: true });

        // Mouse drag support
        let isDragging = false;
        let dragStartX = 0;

        container.addEventListener('mousedown', (e) => {
            isDragging = true;
            dragStartX = e.clientX;
            container.style.cursor = 'grabbing';
        });

        container.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
        });

        container.addEventListener('mouseup', (e) => {
            if (!isDragging) return;
            isDragging = false;
            container.style.cursor = 'grab';
            const diff = dragStartX - e.clientX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) nextSlide();
                else prevSlide();
            }
        });

        container.addEventListener('mouseleave', () => {
            isDragging = false;
            container.style.cursor = 'grab';
        });

        // Auto-advance
        let autoAdvance = setInterval(nextSlide, 5000);

        // Pause on hover/touch
        carousel.addEventListener('mouseenter', () => clearInterval(autoAdvance));
        carousel.addEventListener('mouseleave', () => {
            autoAdvance = setInterval(nextSlide, 5000);
        });
        carousel.addEventListener('touchstart', () => clearInterval(autoAdvance), { passive: true });

        // Store carousel instance
        this.carousels.push({
            element: carousel,
            currentSlide,
            totalSlides: slides.length,
            showSlide,
            nextSlide,
            prevSlide
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new ArticleCarousel();
});

// Re-initialize if content is dynamically loaded
window.initializeCarousels = () => {
    new ArticleCarousel();
};