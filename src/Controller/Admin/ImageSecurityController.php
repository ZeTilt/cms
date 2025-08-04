<?php

namespace App\Controller\Admin;

use App\Service\ModuleManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Yaml\Yaml;

#[Route('/admin/image-security')]
#[IsGranted('ROLE_ADMIN')]
class ImageSecurityController extends AbstractController
{
    private string $configFile;
    private string $projectDir;

    public function __construct(
        string $projectDir,
        private ModuleManager $moduleManager
    ) {
        $this->projectDir = $projectDir;
        $this->configFile = $projectDir . '/config/packages/security_images.yaml';
    }

    #[Route('/', name: 'admin_image_security_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('image_security')) {
            throw $this->createNotFoundException('Image Security module is not active');
        }
        
        // Load current configuration
        $config = Yaml::parseFile($this->configFile);
        $params = $config['parameters'] ?? [];

        if ($request->isMethod('POST')) {
            // Update configuration from form
            $formData = $request->request->all();
            
            $params['image.watermark.enabled'] = isset($formData['watermark_enabled']);
            $params['image.watermark.type'] = $formData['watermark_type'] ?? 'text';
            $params['image.watermark.template'] = $formData['watermark_template'] ?? '{gallery_title} - {owner_name}';
            
            // Handle file upload for watermark image
            $watermarkImagePath = $formData['watermark_image_path'] ?? 'public/assets/watermark.png';
            $uploadedFile = $request->files->get('watermark_file');
            
            if ($uploadedFile instanceof UploadedFile) {
                // Validate file type
                $allowedTypes = ['image/png'];
                if (!in_array($uploadedFile->getMimeType(), $allowedTypes)) {
                    $this->addFlash('error', 'Seuls les fichiers PNG sont autorisés pour le watermark.');
                    return $this->redirectToRoute('admin_image_security_settings');
                }
                
                // Generate unique filename
                $filename = 'watermark_' . uniqid() . '.png';
                $assetsDir = $this->projectDir . '/public/assets';
                
                // Create assets directory if it doesn't exist
                if (!is_dir($assetsDir)) {
                    mkdir($assetsDir, 0755, true);
                }
                
                try {
                    // Move uploaded file
                    $uploadedFile->move($assetsDir, $filename);
                    $watermarkImagePath = 'public/assets/' . $filename;
                    
                    // Remove old watermark file if it's not the default
                    $oldPath = $params['image.watermark.image_path'] ?? '';
                    if ($oldPath && $oldPath !== 'public/assets/watermark.png' && file_exists($this->projectDir . '/' . $oldPath)) {
                        unlink($this->projectDir . '/' . $oldPath);
                    }
                    
                    $this->addFlash('success', 'Fichier watermark uploadé avec succès !');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier : ' . $e->getMessage());
                    return $this->redirectToRoute('admin_image_security_settings');
                }
            }
            
            $params['image.watermark.image_path'] = $watermarkImagePath;
            $params['image.watermark.opacity'] = (int)($formData['watermark_opacity'] ?? 70);
            $params['image.watermark.position'] = $formData['watermark_position'] ?? 'bottom-right';
            $params['image.watermark.margin'] = (int)($formData['watermark_margin'] ?? 20);
            $params['image.watermark.scale'] = (int)($formData['watermark_scale'] ?? 100);
            $params['image.anti_scrapping.enabled'] = isset($formData['anti_scrapping_enabled']);
            $params['image.rate_limit.requests_per_minute'] = (int)($formData['rate_limit'] ?? 60);
            
            // Update suspicious agents
            $suspiciousAgents = array_filter(array_map('trim', explode("\n", $formData['suspicious_agents'] ?? '')));
            $params['image.anti_scrapping.suspicious_agents'] = $suspiciousAgents;
            
            // Update allowed agents  
            $allowedAgents = array_filter(array_map('trim', explode("\n", $formData['allowed_agents'] ?? '')));
            $params['image.anti_scrapping.allowed_agents'] = $allowedAgents;
            
            // Cache settings
            $params['image.cache.private_max_age'] = (int)($formData['private_cache'] ?? 3600);
            $params['image.cache.thumbnail_max_age'] = (int)($formData['thumbnail_cache'] ?? 7200);

            // Save configuration
            $config['parameters'] = $params;
            $yamlContent = Yaml::dump($config, 4, 2);
            
            // Add header comment
            $yamlContent = "# Image Security Configuration\n" . $yamlContent;
            
            file_put_contents($this->configFile, $yamlContent);
            
            $this->addFlash('success', 'Configuration mise à jour avec succès ! Redémarrez le serveur pour appliquer les changements.');
            
            return $this->redirectToRoute('admin_image_security_settings');
        }

        return $this->render('admin/image_security/settings.html.twig', [
            'params' => $params
        ]);
    }
}