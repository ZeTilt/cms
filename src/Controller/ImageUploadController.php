<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/upload', name: 'admin_upload_')]
#[IsGranted('ROLE_ADMIN')]
class ImageUploadController extends AbstractController
{
    #[Route('/image', name: 'image', methods: ['POST'])]
    public function uploadImage(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $uploadedFile = $request->files->get('file');
        
        if (!$uploadedFile) {
            return new JsonResponse(['error' => 'Aucun fichier envoyé'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier le type de fichier
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
            return new JsonResponse(['error' => 'Type de fichier non autorisé'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier la taille (max 5MB)
        if ($uploadedFile->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse(['error' => 'Fichier trop volumineux (max 5MB)'], Response::HTTP_BAD_REQUEST);
        }

        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

        try {
            // Créer le dossier s'il n'existe pas
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/images';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadedFile->move($uploadDir, $newFilename);
            
            $imageUrl = '/uploads/images/' . $newFilename;
            
            return new JsonResponse([
                'success' => true,
                'url' => $imageUrl,
                'filename' => $newFilename
            ]);
            
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'Erreur lors de l\'upload: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}