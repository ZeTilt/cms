<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ContentBlock;
use App\Repository\ContentBlockRepository;
use App\Service\ContentSanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/api/blocks')]
#[IsGranted('ROLE_ADMIN')]
class ContentBlockController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContentBlockRepository $blockRepository,
        private ContentSanitizer $contentSanitizer
    ) {}

    /**
     * Get all blocks for an article
     */
    #[Route('/article/{id}', name: 'admin_blocks_list', methods: ['GET'])]
    public function list(Article $article): JsonResponse
    {
        $blocks = $this->blockRepository->findByArticle($article);

        return $this->json([
            'success' => true,
            'blocks' => array_map(fn($block) => $this->serializeBlock($block), $blocks),
        ]);
    }

    /**
     * Create a new block
     */
    #[Route('/article/{id}', name: 'admin_blocks_create', methods: ['POST'])]
    public function create(Article $article, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['type']) || !array_key_exists($data['type'], ContentBlock::TYPES)) {
            return $this->json(['success' => false, 'error' => 'Type de bloc invalide'], 400);
        }

        $block = new ContentBlock();
        $block->setArticle($article);
        $block->setType($data['type']);
        $block->setPosition($this->blockRepository->getNextPosition($article));

        // Set default data based on type
        $blockData = $data['data'] ?? $this->getDefaultDataForType($data['type']);
        if ($data['type'] === ContentBlock::TYPE_TEXT && isset($blockData['content'])) {
            $blockData['content'] = $this->contentSanitizer->sanitizeContent($blockData['content']);
        }
        $block->setData($blockData);

        $this->entityManager->persist($block);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'block' => $this->serializeBlock($block),
        ]);
    }

    /**
     * Update a block
     */
    #[Route('/{id}', name: 'admin_blocks_update', methods: ['PUT', 'PATCH'])]
    public function update(ContentBlock $block, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['data'])) {
            $blockData = $data['data'];
            if ($block->getType() === ContentBlock::TYPE_TEXT && isset($blockData['content'])) {
                $blockData['content'] = $this->contentSanitizer->sanitizeContent($blockData['content']);
            }
            $block->setData($blockData);
        }

        if (isset($data['position'])) {
            $block->setPosition((int) $data['position']);
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'block' => $this->serializeBlock($block),
        ]);
    }

    /**
     * Delete a block
     */
    #[Route('/{id}', name: 'admin_blocks_delete', methods: ['DELETE'])]
    public function delete(ContentBlock $block): JsonResponse
    {
        $this->entityManager->remove($block);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Reorder blocks
     */
    #[Route('/article/{id}/reorder', name: 'admin_blocks_reorder', methods: ['POST'])]
    public function reorder(Article $article, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['order']) || !is_array($data['order'])) {
            return $this->json(['success' => false, 'error' => 'Ordre invalide'], 400);
        }

        $this->blockRepository->reorderBlocks($article, $data['order']);

        return $this->json(['success' => true]);
    }

    /**
     * Duplicate a block
     */
    #[Route('/{id}/duplicate', name: 'admin_blocks_duplicate', methods: ['POST'])]
    public function duplicate(ContentBlock $block): JsonResponse
    {
        $article = $block->getArticle();

        $newBlock = new ContentBlock();
        $newBlock->setArticle($article);
        $newBlock->setType($block->getType());
        $newBlock->setData($block->getData());
        $newBlock->setPosition($block->getPosition() + 1);

        // Shift positions of blocks after this one
        $blocks = $this->blockRepository->findByArticle($article);
        foreach ($blocks as $existingBlock) {
            if ($existingBlock->getPosition() > $block->getPosition()) {
                $existingBlock->setPosition($existingBlock->getPosition() + 1);
            }
        }

        $this->entityManager->persist($newBlock);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'block' => $this->serializeBlock($newBlock),
        ]);
    }

    /**
     * Move block up or down
     */
    #[Route('/{id}/move/{direction}', name: 'admin_blocks_move', methods: ['POST'])]
    public function move(ContentBlock $block, string $direction): JsonResponse
    {
        if (!in_array($direction, ['up', 'down'])) {
            return $this->json(['success' => false, 'error' => 'Direction invalide'], 400);
        }

        $article = $block->getArticle();
        $blocks = $this->blockRepository->findByArticle($article);
        $currentPos = $block->getPosition();

        if ($direction === 'up' && $currentPos > 0) {
            // Find block at position - 1 and swap
            foreach ($blocks as $other) {
                if ($other->getPosition() === $currentPos - 1) {
                    $other->setPosition($currentPos);
                    $block->setPosition($currentPos - 1);
                    break;
                }
            }
        } elseif ($direction === 'down') {
            // Find block at position + 1 and swap
            foreach ($blocks as $other) {
                if ($other->getPosition() === $currentPos + 1) {
                    $other->setPosition($currentPos);
                    $block->setPosition($currentPos + 1);
                    break;
                }
            }
        }

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Get available block types
     */
    #[Route('/types', name: 'admin_blocks_types', methods: ['GET'])]
    public function types(): JsonResponse
    {
        $types = [];
        foreach (ContentBlock::TYPES as $type => $label) {
            $types[] = [
                'type' => $type,
                'label' => $label,
                'icon' => $this->getIconForType($type),
            ];
        }

        return $this->json(['types' => $types]);
    }

    private function serializeBlock(ContentBlock $block): array
    {
        return [
            'id' => $block->getId(),
            'type' => $block->getType(),
            'typeName' => $block->getTypeName(),
            'data' => $block->getData(),
            'position' => $block->getPosition(),
            'icon' => $this->getIconForType($block->getType()),
        ];
    }

    private function getDefaultDataForType(string $type): array
    {
        return match ($type) {
            ContentBlock::TYPE_TEXT => ['content' => ''],
            ContentBlock::TYPE_IMAGE => ['url' => '', 'alt' => '', 'caption' => '', 'alignment' => 'center', 'size' => 'large'],
            ContentBlock::TYPE_GALLERY => ['images' => [], 'layout' => 'grid', 'columns' => 3],
            ContentBlock::TYPE_VIDEO => ['url' => '', 'caption' => ''],
            ContentBlock::TYPE_QUOTE => ['text' => '', 'author' => ''],
            ContentBlock::TYPE_ACCORDION => ['items' => []],
            ContentBlock::TYPE_CTA => ['text' => 'En savoir plus', 'url' => '#', 'style' => 'primary'],
            default => [],
        };
    }

    private function getIconForType(string $type): string
    {
        return match ($type) {
            ContentBlock::TYPE_TEXT => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>',
            ContentBlock::TYPE_IMAGE => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            ContentBlock::TYPE_GALLERY => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>',
            ContentBlock::TYPE_VIDEO => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            ContentBlock::TYPE_QUOTE => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
            ContentBlock::TYPE_ACCORDION => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>',
            ContentBlock::TYPE_CTA => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>',
            default => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>',
        };
    }
}
