# Anti-Scrapping Protection System

This document describes the anti-scrapping protection system implemented for ZeTilt CMS image galleries.

## Overview

The anti-scrapping protection system provides multiple layers of security to prevent unauthorized access and mass downloading of gallery images:

1. **Secure Image URLs**: Images are served through secure routes instead of direct file access
2. **Access Control**: Proper authentication and authorization checks
3. **Watermarking**: Automatic watermarking for non-owners
4. **Scrapping Detection**: Detection of suspicious user agents and behavior
5. **Rate Limiting**: Configurable request limits (requires web server configuration)

## Features

### Secure Image Routes

Images are now served through these secure routes:
- `/secure/images/gallery/{galleryId}/{filename}` - Full-size images
- `/secure/images/thumbnail/{galleryId}/{filename}` - Thumbnails

### Access Control

Access is granted based on:
- **Admin users**: Full access to all galleries
- **Gallery owners**: Full access to their own galleries
- **Public galleries**: Access for all users
- **Private galleries with access code**: Access after providing correct code
- **Private galleries**: Access only for authenticated users

### Watermarking

- Automatically applied to images for non-owners
- Configurable template with placeholders: `{gallery_title}`, `{owner_name}`
- Can be enabled/disabled via configuration
- Uses the ImageProcessingService for high-quality watermarks

### Scrapping Detection

The system detects and blocks:
- Suspicious user agents (wget, curl, scrapy, etc.)
- Direct image access without referer
- Rapid automated requests

Allowed user agents for SEO:
- Google bots
- Social media crawlers (Facebook, Twitter, LinkedIn)

### Security Headers

All images are served with appropriate security headers:
- `Cache-Control`: Private caching only
- `X-Robots-Tag`: Prevent indexing
- `Content-Disposition`: Inline display

## Configuration

Configure the system in `config/packages/security_images.yaml`:

```yaml
parameters:
    # Enable/disable features
    image.watermark.enabled: true
    image.anti_scrapping.enabled: true
    
    # Watermark template (use {gallery_title} and {owner_name} placeholders)
    image.watermark.template: '{gallery_title} - {owner_name}'
    
    # Cache settings (seconds)
    image.cache.private_max_age: 3600
    image.cache.thumbnail_max_age: 7200
    
    # User agent lists
    image.anti_scrapping.suspicious_agents:
        - 'wget'
        - 'curl'
        - 'python'
        # ... more agents
    
    image.anti_scrapping.allowed_agents:
        - 'googlebot'
        - 'facebook'
        # ... more agents
```

## Template Usage

In Twig templates, use these functions instead of direct image URLs:

```twig
{# For thumbnails #}
<img src="{{ secure_thumbnail_url(image, access_code) }}" alt="...">

{# For full images #}
<img src="{{ secure_image_url(image, access_code) }}" alt="...">
```

The `access_code` parameter is optional and should be passed when the gallery requires an access code.

## Web Server Configuration

For additional protection, configure your web server:

### Apache (.htaccess)

```apache
# Block direct access to gallery images
<LocationMatch "^/uploads/galleries/">
    Require all denied
</LocationMatch>

# Rate limiting (requires mod_evasive)
<IfModule mod_evasive24.c>
    DOSHashTableSize    5000
    DOSPageCount        3
    DOSPageInterval     1
    DOSInterval         600
    DOSBlockingPeriod   600
</IfModule>
```

### Nginx

```nginx
# Block direct access to gallery images
location ~* ^/uploads/galleries/ {
    deny all;
    return 403;
}

# Rate limiting
limit_req_zone $binary_remote_addr zone=images:10m rate=10r/m;

location ~* ^/secure/images/ {
    limit_req zone=images burst=20 nodelay;
}
```

## Monitoring

Monitor these metrics for security:
- Failed access attempts
- Blocked user agents
- Rate limit violations
- Watermark application frequency

## Performance Considerations

- Thumbnails are cached for longer periods (2 hours default)
- Original images are cached for shorter periods (1 hour default)
- Watermarking adds processing overhead
- Consider using a CDN with signed URLs for high-traffic sites

## Migration

When upgrading existing galleries:
1. Update templates to use secure URL functions
2. Ensure gallery directory structure matches expected format
3. Test access controls for different user types
4. Verify watermarking works correctly

## Troubleshooting

### Images not loading
- Check that galleries are active
- Verify access permissions
- Ensure files exist in expected directory structure

### Watermarks not appearing
- Check that GD extension is installed
- Verify `image.watermark.enabled` is true
- Ensure ImageProcessingService is working

### False positives in scrapping detection
- Add legitimate user agents to `allowed_agents` list
- Consider disabling for specific galleries if needed
- Check referer requirements

## Security Best Practices

1. Regularly update suspicious agent lists
2. Monitor access logs for unusual patterns
3. Use HTTPS for all image requests
4. Consider implementing CAPTCHA for repeated access
5. Backup original images separately from public uploads