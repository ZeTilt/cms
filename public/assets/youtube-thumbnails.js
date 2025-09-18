/**
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