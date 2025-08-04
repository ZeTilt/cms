<?php

namespace App\Controller;

use App\Repository\GalleryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicGalleryController extends AbstractController
{
    public function __construct(
        private GalleryRepository $galleryRepository
    ) {}

    #[Route('/galleries', name: 'public_galleries_list', methods: ['GET'])]
    public function list(): Response
    {
        $galleries = $this->galleryRepository->findPublicGalleries();

        return $this->render('public/galleries/list.html.twig', [
            'galleries' => $galleries
        ]);
    }

    #[Route('/gallery/{slug}', name: 'public_gallery_show', methods: ['GET', 'POST'])]
    public function show(string $slug, Request $request): Response
    {
        // Find both public and private galleries
        $gallery = $this->galleryRepository->findBySlug($slug);

        if (!$gallery) {
            throw $this->createNotFoundException('Gallery not found');
        }

        // If it's a public gallery, show it directly
        if (!$gallery->isPrivate()) {
            return $this->render('public/galleries/show.html.twig', [
                'gallery' => $gallery
            ]);
        }

        // Handle private gallery access
        if ($gallery->requiresAccessCode()) {
            // If POST request, check submitted access code
            if ($request->isMethod('POST')) {
                $submittedCode = $request->request->get('access_code');
                if ($submittedCode === $gallery->getAccessCode()) {
                    // Store access in session for this gallery
                    $request->getSession()->set('gallery_access_' . $gallery->getId(), true);
                    return $this->render('public/galleries/show.html.twig', [
                        'gallery' => $gallery
                    ]);
                } else {
                    return $this->render('public/galleries/access-code.html.twig', [
                        'gallery' => $gallery,
                        'error' => 'Code d\'accès incorrect'
                    ]);
                }
            }

            // Check if user already has access in session
            if ($request->getSession()->get('gallery_access_' . $gallery->getId())) {
                return $this->render('public/galleries/show.html.twig', [
                    'gallery' => $gallery
                ]);
            }

            // Show access code form
            return $this->render('public/galleries/access-code.html.twig', [
                'gallery' => $gallery,
                'error' => null
            ]);
        }

        return $this->render('public/galleries/show.html.twig', [
            'gallery' => $gallery
        ]);
    }

    #[Route('/gallery/{slug}/access/{token}', name: 'public_gallery_magic_link', methods: ['GET'])]
    public function magicLink(string $slug, string $token, Request $request): Response
    {
        // Find the gallery
        $gallery = $this->galleryRepository->findBySlug($slug);

        if (!$gallery) {
            throw $this->createNotFoundException('Gallery not found');
        }

        // Verify this is a private gallery with access code
        if (!$gallery->isPrivate() || !$gallery->requiresAccessCode()) {
            throw $this->createNotFoundException('Invalid access link');
        }

        // Generate expected token based on gallery and access code
        $expectedToken = hash('sha256', $gallery->getId() . ':' . $gallery->getAccessCode() . ':' . $gallery->getSlug());

        // Verify token
        if (!hash_equals($expectedToken, $token)) {
            throw $this->createNotFoundException('Invalid or expired access link');
        }

        // Store access in session for this gallery
        $request->getSession()->set('gallery_access_' . $gallery->getId(), true);

        // Redirect to normal gallery URL to hide the magic link
        return $this->redirectToRoute('public_gallery_show', ['slug' => $slug]);
    }

    #[Route('/gallery/{slug}/image/{imageId}', name: 'public_gallery_image', methods: ['GET'])]
    public function showImage(string $slug, int $imageId, Request $request): Response
    {
        $gallery = $this->galleryRepository->findBySlug($slug);

        if (!$gallery) {
            throw $this->createNotFoundException('Gallery not found');
        }

        // Check if gallery is private and user has access
        if ($gallery->isPrivate() && $gallery->requiresAccessCode()) {
            if (!$request->getSession()->get('gallery_access_' . $gallery->getId())) {
                throw $this->createAccessDeniedException('Access denied to private gallery');
            }
        }

        $image = null;
        foreach ($gallery->getImages() as $img) {
            if ($img->getId() === $imageId) {
                $image = $img;
                break;
            }
        }

        if (!$image) {
            throw $this->createNotFoundException('Image not found');
        }

        return $this->render('public/galleries/image.html.twig', [
            'gallery' => $gallery,
            'image' => $image,
            'images' => $gallery->getImages()->toArray()
        ]);
    }
}