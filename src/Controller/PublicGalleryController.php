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
        // Toutes les galeries sont maintenant publiques
        $galleries = $this->galleryRepository->findAll();

        return $this->render('public/galleries/list.html.twig', [
            'galleries' => $galleries
        ]);
    }

    #[Route('/gallery/{slug}', name: 'public_gallery_show', methods: ['GET'])]
    public function show(string $slug): Response
    {
        $gallery = $this->galleryRepository->findOneBy(['slug' => $slug]);

        if (!$gallery) {
            throw $this->createNotFoundException('Gallery not found');
        }

        return $this->render('public/galleries/show.html.twig', [
            'gallery' => $gallery
        ]);
    }

    #[Route('/gallery/{slug}/image/{imageId}', name: 'public_gallery_image', methods: ['GET'])]
    public function showImage(string $slug, int $imageId): Response
    {
        $gallery = $this->galleryRepository->findOneBy(['slug' => $slug]);

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