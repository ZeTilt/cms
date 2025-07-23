<?php

namespace App\Service;

use App\Entity\Article;
use App\Repository\ArticleRepository;

class ArticleValidator
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private ContentSanitizer $contentSanitizer
    ) {}

    /**
     * Validate article data
     */
    public function validate(array $data, ?Article $existingArticle = null): array
    {
        $errors = [];

        // Title validation
        if (empty(trim($data['title'] ?? ''))) {
            $errors['title'] = 'Title is required.';
        } elseif (strlen(trim($data['title'])) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters.';
        }

        // Content validation
        if (empty(trim($data['content'] ?? ''))) {
            $errors['content'] = 'Content is required.';
        } elseif (strlen(trim($data['content'])) < 10) {
            $errors['content'] = 'Content must be at least 10 characters long.';
        }

        // Status validation
        if (!empty($data['status']) && !in_array($data['status'], ['draft', 'published'])) {
            $errors['status'] = 'Invalid status. Must be "draft" or "published".';
        }

        // Category validation
        if (!empty($data['category'])) {
            $category = trim($data['category']);
            if (strlen($category) > 100) {
                $errors['category'] = 'Category must not exceed 100 characters.';
            }
            if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $category)) {
                $errors['category'] = 'Category contains invalid characters.';
            }
        }

        // Tags validation
        if (!empty($data['tags'])) {
            $tags = is_array($data['tags']) ? $data['tags'] : explode(',', $data['tags']);
            $tags = array_map('trim', $tags);
            $tags = array_filter($tags);

            if (count($tags) > 10) {
                $errors['tags'] = 'Maximum 10 tags allowed.';
            }

            foreach ($tags as $tag) {
                if (strlen($tag) > 50) {
                    $errors['tags'] = 'Each tag must not exceed 50 characters.';
                    break;
                }
                if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $tag)) {
                    $errors['tags'] = 'Tags contain invalid characters.';
                    break;
                }
            }
        }

        // Slug uniqueness validation (for new articles or when title changes)
        if (!empty($data['title'])) {
            $slug = $this->generateSlug(trim($data['title']));
            
            // Check if slug already exists (for different article)
            $existingBySlug = $this->articleRepository->findOneBy(['slug' => $slug]);
            if ($existingBySlug && (!$existingArticle || $existingBySlug->getId() !== $existingArticle->getId())) {
                $errors['title'] = 'An article with this title already exists.';
            }
        }

        // Excerpt validation
        if (!empty($data['excerpt']) && strlen(trim($data['excerpt'])) > 500) {
            $errors['excerpt'] = 'Excerpt must not exceed 500 characters.';
        }

        return $errors;
    }

    /**
     * Sanitize and prepare article data
     */
    public function sanitizeData(array $data): array
    {
        $sanitized = [];

        $sanitized['title'] = trim($data['title'] ?? '');
        $sanitized['content'] = $this->contentSanitizer->sanitizeContent($data['content'] ?? '');
        $sanitized['excerpt'] = trim($data['excerpt'] ?? '');
        $sanitized['category'] = trim($data['category'] ?? '');
        $sanitized['status'] = $data['status'] ?? 'draft';

        // Handle tags
        if (!empty($data['tags'])) {
            $tags = is_array($data['tags']) ? $data['tags'] : explode(',', $data['tags']);
            $tags = array_map('trim', $tags);
            $tags = array_filter($tags);
            $tags = array_unique($tags);
            $sanitized['tags'] = array_values($tags);
        } else {
            $sanitized['tags'] = [];
        }

        // Auto-generate excerpt if empty
        if (empty($sanitized['excerpt']) && !empty($sanitized['content'])) {
            $sanitized['excerpt'] = $this->contentSanitizer->generateExcerpt($sanitized['content'], 160);
        }

        return $sanitized;
    }

    /**
     * Generate URL-friendly slug from title
     */
    private function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
        $slug = preg_replace('/[\s\-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Check if article can be published
     */
    public function canPublish(Article $article): array
    {
        $issues = [];

        if (empty($article->getTitle())) {
            $issues[] = 'Article must have a title.';
        }

        if (empty($article->getContent())) {
            $issues[] = 'Article must have content.';
        }

        if (empty($article->getSlug())) {
            $issues[] = 'Article must have a valid slug.';
        }

        return $issues;
    }
}