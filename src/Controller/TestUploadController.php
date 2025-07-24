<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestUploadController extends AbstractController
{
    #[Route('/test-upload', name: 'test_upload', methods: ['POST'])]
    public function testUpload(Request $request): JsonResponse
    {
        try {
            // Basic test to see what's available
            $phpInfo = [
                'gd_enabled' => extension_loaded('gd'),
                'gd_info' => extension_loaded('gd') ? gd_info() : 'Not available',
                'exif_enabled' => function_exists('exif_read_data'),
                'mime_content_type_enabled' => function_exists('mime_content_type'),
                'fileinfo_enabled' => extension_loaded('fileinfo'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit'),
            ];
            
            $files = $request->files->all();
            
            return new JsonResponse([
                'success' => true,
                'php_info' => $phpInfo,
                'files_received' => count($files),
                'files_info' => array_map(function($file) {
                    return [
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                        'error' => $file->getError()
                    ];
                }, $files)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}