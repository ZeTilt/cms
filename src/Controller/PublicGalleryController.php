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

    #[Route('/gallery/{slug}', name: 'public_gallery_show', methods: ['GET'])]
    public function show(string $slug, Request $request): Response
    {
        $gallery = $this->galleryRepository->findPublicBySlug($slug);

        if (!$gallery) {
            throw $this->createNotFoundException('Gallery not found');
        }

        // Handle private gallery access
        if ($gallery->isPrivate()) {
            $submittedCode = $request->query->get('code');
            
            // If gallery requires access code and none provided or wrong code
            if ($gallery->requiresAccessCode() && $gallery->getAccessCode() !== $submittedCode) {
                return $this->render('public/galleries/access-code.html.twig', [
                    'gallery' => $gallery,
                    'error' => $submittedCode ? 'Invalid access code' : null
                ]);
            }
        }

        return $this->render('public/galleries/show.html.twig', [
            'gallery' => $gallery
        ]);
    }

    #[Route('/gallery/{slug}/image/{imageId}', name: 'public_gallery_image', methods: ['GET'])]
    public function showImage(string $slug, int $imageId): Response
    {
        $gallery = $this->galleryRepository->findPublicBySlug($slug);

        if (!$gallery) {
            throw $this->createNotFoundException('Gallery not found');
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