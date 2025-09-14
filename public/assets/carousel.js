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
        const content = document.querySelector('.article-content, .prose');
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
                <img src="${img}" alt="Image ${index + 1}" class="w-full h-64 md:h-80 object-cover rounded-lg">
            </div>
        `).join('');

        const indicators = images.map((_, index) => `
            <button class="carousel-dot ${index === 0 ? 'active' : ''}" data-slide="${index}"></button>
        `).join('');

        return `
            <div class="article-carousel my-8" data-carousel-id="${carouselId}">
                <div class="relative overflow-hidden rounded-lg bg-gray-100">
                    <div class="carousel-container relative">
                        ${slides}
                    </div>
                    
                    ${images.length > 1 ? `
                        <!-- Navigation arrows -->
                        <button class="carousel-prev absolute left-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-70 text-white p-2 rounded-full transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button class="carousel-next absolute right-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-70 text-white p-2 rounded-full transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        
                        <!-- Indicators -->
                        <div class="carousel-indicators absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
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
        document.querySelectorAll('.article-carousel').forEach(carousel => {
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

        // Event listeners
        if (prevBtn) prevBtn.addEventListener('click', prevSlide);
        if (nextBtn) nextBtn.addEventListener('click', nextSlide);

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => showSlide(index));
        });

        // Auto-advance (optional)
        let autoAdvance = setInterval(nextSlide, 5000);

        // Pause on hover
        carousel.addEventListener('mouseenter', () => clearInterval(autoAdvance));
        carousel.addEventListener('mouseleave', () => {
            autoAdvance = setInterval(nextSlide, 5000);
        });

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