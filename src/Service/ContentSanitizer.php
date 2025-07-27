<?php

namespace App\Service;

use HTMLPurifier;
use HTMLPurifier_Config;

class ContentSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        
        // Configure basic settings first
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.TidyLevel', 'medium');
        
        // Allow safe HTML tags and attributes for blog content
        $config->set('HTML.Allowed', 
            'p,br,strong,b,em,i,u,s,strike,del,ins,sup,sub,small,mark,abbr,cite,code,pre,blockquote,' .
            'h1,h2,h3,h4,h5,h6,' .
            'ul,ol,li,dl,dt,dd,' .
            'a[href|title|target],img[src|alt|title|width|height],' .
            'table,thead,tbody,tfoot,tr,th[scope],td[colspan|rowspan],' .
            'div[class],span[class],time[datetime],figure,figcaption'
        );
        
        // Configure links - MUST be set BEFORE getHTMLDefinition
        $config->set('HTML.Nofollow', true); // Add rel="nofollow" to external links
        $config->set('HTML.TargetBlank', true); // Add target="_blank" to external links
        
        // Enable cache for better performance - MUST be set BEFORE getHTMLDefinition
        $config->set('Cache.SerializerPath', sys_get_temp_dir());
        
        // Allow safe CSS properties - MUST be set BEFORE getHTMLDefinition
        $config->set('CSS.AllowedProperties', 
            'font-weight,font-style,text-decoration,text-align,color,background-color,' .
            'margin,padding,width,height,max-width,max-height,border,border-radius'
        );
        
        // Add custom definition for HTML5 elements like <mark> - MUST be LAST
        $def = $config->getHTMLDefinition(true);
        $def->addElement('mark', 'Inline', 'Inline', 'Common');
        $def->addElement('time', 'Inline', 'Inline', 'Common', ['datetime' => 'Text']);
        $def->addElement('figure', 'Block', 'Flow', 'Common');
        $def->addElement('figcaption', 'Inline', 'Inline', 'Common');
        
        $this->purifier = new HTMLPurifier($config);
    }

    /**
     * Sanitize HTML content for safe display
     */
    public function sanitizeContent(string $content): string
    {
        return $this->purifier->purify($content);
    }

    /**
     * Strip all HTML tags and return plain text
     */
    public function stripHtml(string $content): string
    {
        return strip_tags($content);
    }

    /**
     * Generate excerpt from HTML content
     */
    public function generateExcerpt(string $content, int $maxLength = 200): string
    {
        $plainText = $this->stripHtml($content);
        $plainText = preg_replace('/\s+/', ' ', trim($plainText));
        
        if (strlen($plainText) <= $maxLength) {
            return $plainText;
        }
        
        $excerpt = substr($plainText, 0, $maxLength);
        $lastSpace = strrpos($excerpt, ' ');
        
        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . '...';
    }

    /**
     * Validate and clean URLs
     */
    public function sanitizeUrl(string $url): ?string
    {
        $url = trim($url);
        
        if (empty($url)) {
            return null;
        }
        
        // Add protocol if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        return $url;
    }
}