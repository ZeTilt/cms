<?php

namespace App\Controller;

use App\Entity\Gallery;
use App\Entity\Image;
use App\Repository\GalleryRepository;
use App\Service\ImageUploadService;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/galleries')]
#[IsGranted('ROLE_ADMIN')]
class GalleryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GalleryRepository $galleryRepository,
        private ImageUploadService $imageUploadService,
        private ModuleManager $moduleManager
    ) {}

    #[Route('', name: 'admin_galleries_list', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->moduleManager->isModuleActive('gallery')) {
            throw $this->createNotFoundException('Gallery module is not active');
        }

        $galleries = $this->galleryRepository->findByAuthor($this->getUser());

        return $this->render('admin/galleries/index.html.twig', [
            'galleries' => $galleries,
            'totalStorageUsed' => $this->imageUploadService->getFormattedStorageUsed()
        ]);
    }

    #[Route('/new', name: 'admin_galleries_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('gallery')) {
            throw $this->createNotFoundException('Gallery module is not active');
        }
        if ($request->isMethod('POST')) {
            $result = $this->handleSave($request);
            if ($result instanceof Response) {
                return $result;
            }
        }

        return $this->render('admin/galleries/edit.html.twig', [
            'gallery' => new Gallery(),
            'isEdit' => false
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_galleries_edit', methods: ['GET', 'POST'])]
    public function edit(Gallery $gallery, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('gallery')) {
            throw $this->createNotFoundException('Gallery module is not active');
        }
        if ($request->isMethod('POST')) {
            $result = $this->handleSave($request, $gallery);
            if ($result instanceof Response) {
                return $result;
            }
        }

        return $this->render('admin/galleries/edit.html.twig', [
            'gallery' => $gallery,
            'isEdit' => true
        ]);
    }

    #[Route('/{id}', name: 'admin_galleries_show', methods: ['GET'])]
    public function show(Gallery $gallery): Response
    {
        if (!$this->moduleManager->isModuleActive('gallery')) {
            throw $this->createNotFoundException('Gallery module is not active');
        }
        return $this->render('admin/galleries/show.html.twig', [
            'gallery' => $gallery
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_galleries_delete', methods: ['POST'])]
    public function delete(Gallery $gallery, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('gallery')) {
            throw $this->createNotFoundException('Gallery module is not active');
        }
        if ($this->isCsrfTokenValid('delete_gallery', $request->request->get('_token'))) {
            // Delete all images in the gallery
            foreach ($gallery->getImages() as $image) {
                $this->imageUploadService->deleteImage($image);
            }
            
            $this->entityManager->remove($gallery);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Gallery deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid security token.');
        }

        return $this->redirectToRoute('admin_galleries_list');
    }

    #[Route('/{id}/upload', name: 'admin_galleries_upload', methods: ['POST'])]
    public function uploadImages(Gallery $gallery, Request $request): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('gallery')) {
            return new JsonResponse(['error' => 'Gallery module is not active'], 404);
        }
        
        // Ensure we always return JSON, even for auth errors
        try {
            // Check authentication first
            if (!$this->getUser()) {
                return new JsonResponse(['error' => 'Authentication required'], 401);
            }

            // Get files from request
            $files = $request->files->get('images', []);
            
            // Handle array form data
            if (empty($files) && isset($request->files->all()['images'])) {
                $files = $request->files->all()['images'];
            }
            
            if (empty($files)) {
                return new JsonResponse(['error' => 'No files provided'], 400);
            }

            // Ensure files is always an array
            if (!is_array($files)) {
                $files = [$files];
            }

            error_log('Starting image upload for gallery: ' . $gallery->getId());
            error_log('Number of files: ' . count($files));
            
            $images = $this->imageUploadService->uploadMultipleImages($files, $gallery, $this->getUser());
            
            error_log('Images uploaded successfully: ' . count($images));
            
            $imageData = [];
            foreach ($images as $image) {
                try {
                    $imageData[] = [
                        'id' => $image->getId(),
                        'filename' => $image->getFilename(),
                        'originalName' => $image->getOriginalName(),
                        'url' => $image->getUrl(),
                        'thumbnailUrl' => $image->getThumbnailUrl(),
                        'alt' => $image->getAlt(),
                        'caption' => $image->getCaption(),
                        'size' => $image->getFormattedSize(),
                        'dimensions' => $image->getDimensions()
                    ];
                } catch (\Exception $e) {
                    error_log('Error processing image data: ' . $e->getMessage());
                    throw new \Exception('Error processing uploaded image: ' . $e->getMessage());
                }
            }

            return new JsonResponse([
                'success' => true,
                'message' => count($images) . ' image(s) uploaded successfully',
                'images' => $imageData
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Gallery upload error: ' . $e->getMessage());
            
            return new JsonResponse([
                'error' => $e->getMessage(),
                'debug' => $e->getFile() . ':' . $e->getLine()
            ], 500);
        }
    }

    #[Route('/{galleryId}/images/{imageId}/delete', name: 'admin_galleries_delete_image', methods: ['POST'])]
    public function deleteImage(int $galleryId, Image $image, Request $request): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('gallery')) {
            return new JsonResponse(['error' => 'Gallery module is not active'], 404);
        }
        if (!$this->isCsrfTokenValid('delete_image', $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid security token'], 403);
        }

        try {
            $this->imageUploadService->deleteImage($image);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{galleryId}/images/{imageId}/update', name: 'admin_galleries_update_image', methods: ['POST'])]
    public function updateImage(int $galleryId, Image $image, Request $request): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('gallery')) {
            return new JsonResponse(['error' => 'Gallery module is not active'], 404);
        }
        try {
            $data = json_decode($request->getContent(), true);
            
            if (isset($data['alt'])) {
                $image->setAlt($data['alt']);
            }
            
            if (isset($data['caption'])) {
                $image->setCaption($data['caption']);
            }
            
            if (isset($data['position'])) {
                $image->setPosition((int)$data['position']);
            }

            $this->entityManager->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function handleSave(Request $request, Gallery $gallery = null): ?Response
    {
        $isEdit = $gallery !== null;
        if (!$isEdit) {
            $gallery = new Gallery();
        }

        // Validation
        $errors = [];
        
        $title = trim($request->request->get('title', ''));
        if (empty($title)) {
            $errors[] = 'Title is required.';
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return null;
        }

        // Update gallery (toutes les galeries sont maintenant publiques)
        $gallery->setTitle($title);
        $gallery->setDescription($request->request->get('description', ''));

        if (!$isEdit) {
            $gallery->setAuthor($this->getUser());
        }

        // Save gallery first to get ID
        $this->entityManager->persist($gallery);
        $this->entityManager->flush();

        // Handle cover image upload (after gallery is saved and has ID)
        $coverImageFile = $request->files->get('cover_image');
        if ($coverImageFile instanceof UploadedFile && $coverImageFile->isValid()) {
            try {
                // Validate MIME type
                if (!in_array($coverImageFile->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                    $this->addFlash('error', 'Cover image must be a valid image file.');
                    return null;
                }
                
                // Create cover images directory if it doesn't exist
                $coverDir = $this->getParameter('kernel.project_dir') . '/public/uploads/covers';
                if (!is_dir($coverDir)) {
                    mkdir($coverDir, 0755, true);
                }
                
                // Generate unique filename for cover image
                $extension = $coverImageFile->getClientOriginalExtension();
                $filename = 'gallery_' . $gallery->getId() . '_' . uniqid() . '.' . $extension;
                
                // Remove old cover image if exists
                if ($gallery->getCoverImage()) {
                    $oldCoverPath = $this->getParameter('kernel.project_dir') . '/public' . $gallery->getCoverImage();
                    if (file_exists($oldCoverPath)) {
                        unlink($oldCoverPath);
                    }
                }
                
                // Move uploaded file
                $coverImageFile->move($coverDir, $filename);
                
                // Set cover image path
                $gallery->setCoverImage('/uploads/covers/' . $filename);
                
                // Save again with cover image
                $this->entityManager->flush();
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to upload cover image: ' . $e->getMessage());
                return null;
            }
        }

        $this->addFlash('success', ($isEdit ? 'Gallery updated' : 'Gallery created') . ' successfully!');
        
        return $this->redirectToRoute('admin_galleries_show', ['id' => $gallery->getId()]);
    }
}