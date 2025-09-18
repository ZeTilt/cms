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
}