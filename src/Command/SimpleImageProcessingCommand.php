<?php

namespace App\Command;

use App\Service\SimpleImageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:images:simple-process',
    description: 'Simple image processing (works without GD extension)'
)]
class SimpleImageProcessingCommand extends Command
{
    public function __construct(
        private SimpleImageService $imageService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('image_path', InputArgument::REQUIRED, 'Path to the image file')
            ->addOption('info', 'i', InputOption::VALUE_NONE, 'Show image information')
            ->addOption('watermark', 'w', InputOption::VALUE_NONE, 'Mark image as watermarked (metadata only)')
            ->addOption('compress', 'c', InputOption::VALUE_NONE, 'Create compressed version (metadata only)')
            ->addOption('thumbnails', 't', InputOption::VALUE_NONE, 'Generate thumbnail placeholders')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Process complete (all operations)')
            ->addOption('check-requirements', null, InputOption::VALUE_NONE, 'Check system requirements')
            ->setHelp('
This command provides simple image processing that works without GD extension.
It creates metadata files and file copies instead of actual image manipulation.

Examples:
  # Show image information
  php bin/console zetilt:images:simple-process image.jpg --info
  
  # Mark as watermarked
  php bin/console zetilt:images:simple-process image.jpg --watermark
  
  # Complete processing
  php bin/console zetilt:images:simple-process image.jpg --all
  
  # Check system requirements
  php bin/console zetilt:images:simple-process image.jpg --check-requirements
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $imagePath = $input->getArgument('image_path');

        $io->title('ZeTilt CMS - Simple Image Processing');

        // Check requirements
        if ($input->getOption('check-requirements')) {
            return $this->showSystemRequirements($io);
        }

        // Validate image file
        if (!file_exists($imagePath)) {
            $io->error("Image file not found: {$imagePath}");
            return Command::FAILURE;
        }

        try {
            // Show image info
            if ($input->getOption('info')) {
                return $this->showImageInfo($io, $imagePath);
            }

            $results = [];

            // Complete processing
            if ($input->getOption('all')) {
                $io->section('Complete Image Processing (Simple Mode)');
                
                $results = $this->imageService->processImageComplete($imagePath, [
                    'add_watermark' => true,
                    'compress' => true,
                    'generate_thumbnails' => true,
                    'watermark_text' => '© ZeTilt Photography',
                    'quality' => 80
                ]);
                
                $this->displayCompleteResults($io, $results);
                
            } else {
                // Individual operations
                if ($input->getOption('watermark')) {
                    $io->section('Adding Watermark (Metadata Only)');
                    
                    $results['watermarked'] = $this->imageService->markImageAsWatermarked($imagePath, [
                        'watermark_text' => '© ZeTilt Photography'
                    ]);
                    
                    $io->success("✅ Image marked as watermarked: {$results['watermarked']}");
                }

                if ($input->getOption('compress')) {
                    $io->section('Creating Compressed Version');
                    
                    $results['compressed'] = $this->imageService->createCompressedVersion($imagePath, [
                        'quality' => 80,
                        'max_width' => 1200,
                        'max_height' => 800
                    ]);
                    
                    $io->success("✅ Compressed version created: {$results['compressed']}");
                }

                if ($input->getOption('thumbnails')) {
                    $io->section('Generating Thumbnail Placeholders');
                    
                    $results['thumbnails'] = $this->imageService->generateThumbnailPlaceholders($imagePath);
                    
                    $this->displayThumbnailResults($io, $results['thumbnails']);
                }
            }

            if (empty($results)) {
                $io->warning('No processing options specified. Use --help to see available options.');
                return Command::SUCCESS;
            }

            $io->success('Simple image processing completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Image processing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function showSystemRequirements(SymfonyStyle $io): int
    {
        $io->section('System Requirements for Image Processing');

        $requirements = $this->imageService->getSystemRequirements();
        $table = [];

        foreach ($requirements as $name => $req) {
            $table[] = [
                $name,
                $req['required'] ? 'Required' : 'Optional',
                $req['available'] ? '✅ Available' : '❌ Missing',
                $req['description']
            ];
        }

        $io->table(['Component', 'Status', 'Available', 'Description'], $table);

        if ($this->imageService->isFullProcessingAvailable()) {
            $io->success('✅ Full image processing is available! You can use the advanced ImageProcessingService.');
        } else {
            $io->warning('⚠️  Full image processing is not available. Using simple metadata-based fallback.');
            $io->note('To enable full image processing, install the GD extension: sudo apt-get install php-gd');
        }

        return Command::SUCCESS;
    }

    private function showImageInfo(SymfonyStyle $io, string $imagePath): int
    {
        $io->section("Image Information: {$imagePath}");

        $info = $this->imageService->getImageInfo($imagePath);

        $table = [];
        foreach ($info as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            } elseif (is_numeric($value) && $key === 'modified') {
                $value = date('Y-m-d H:i:s', $value);
            }
            
            $table[] = [ucfirst(str_replace('_', ' ', $key)), $value];
        }

        $io->table(['Property', 'Value'], $table);

        return Command::SUCCESS;
    }

    private function displayCompleteResults(SymfonyStyle $io, array $results): void
    {
        $io->text("🎯 Processing Method: {$results['method']}");
        
        if (isset($results['note'])) {
            $io->note($results['note']);
        }

        if (isset($results['processed'])) {
            $io->text("📄 Processed: {$results['processed']}");
        }

        if (isset($results['watermarked'])) {
            $io->text("🔒 Watermarked: {$results['watermarked']}");
        }

        if (isset($results['thumbnails']) && !empty($results['thumbnails'])) {
            $io->text("🖼️  Thumbnail placeholders generated:");
            foreach ($results['thumbnails'] as $size => $path) {
                $io->text("   - {$size}: {$path}");
            }
        }
    }

    private function displayThumbnailResults(SymfonyStyle $io, array $thumbnails): void
    {
        $table = [];
        foreach ($thumbnails as $size => $path) {
            $table[] = [$size, $path];
        }

        $io->table(['Size', 'Path'], $table);
        $io->success("✅ " . count($thumbnails) . " thumbnail placeholders generated");
    }
}