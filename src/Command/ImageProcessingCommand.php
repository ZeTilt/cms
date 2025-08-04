<?php

namespace App\Command;

use App\Service\ImageProcessingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'zetilt:images:process',
    description: 'Process images with watermarking, compression, and thumbnail generation'
)]
class ImageProcessingCommand extends Command
{
    public function __construct(
        private ImageProcessingService $imageProcessor
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('image_path', InputArgument::REQUIRED, 'Path to the image file')
            ->addOption('watermark', 'w', InputOption::VALUE_NONE, 'Add watermark to image')
            ->addOption('watermark-text', 'wt', InputOption::VALUE_OPTIONAL, 'Custom watermark text')
            ->addOption('compress', 'c', InputOption::VALUE_NONE, 'Compress image')
            ->addOption('thumbnails', 't', InputOption::VALUE_NONE, 'Generate thumbnails')
            ->addOption('quality', null, InputOption::VALUE_OPTIONAL, 'Image quality (1-100)', 80)
            ->addOption('max-width', null, InputOption::VALUE_OPTIONAL, 'Maximum width for compression')
            ->addOption('max-height', null, InputOption::VALUE_OPTIONAL, 'Maximum height for compression')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path')
            ->addOption('position', 'p', InputOption::VALUE_OPTIONAL, 'Watermark position (top-left, top-right, bottom-left, bottom-right, center)', 'bottom-right')
            ->addOption('opacity', null, InputOption::VALUE_OPTIONAL, 'Watermark opacity (0-100)', 70)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Process complete (watermark + compress + thumbnails)')
            ->setHelp('
This command processes images with various options:

Examples:
  # Add watermark only
  php bin/console zetilt:images:process image.jpg --watermark
  
  # Compress image with custom quality
  php bin/console zetilt:images:process image.jpg --compress --quality=90
  
  # Generate thumbnails
  php bin/console zetilt:images:process image.jpg --thumbnails
  
  # Complete processing (all operations)
  php bin/console zetilt:images:process image.jpg --all
  
  # Custom watermark with positioning
  php bin/console zetilt:images:process image.jpg --watermark --watermark-text="© My Studio" --position=center --opacity=50
  
  # Resize and compress
  php bin/console zetilt:images:process image.jpg --compress --max-width=1200 --max-height=800 --quality=85
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $imagePath = $input->getArgument('image_path');

        $io->title('ZeTilt CMS - Image Processing');

        // Validate image file
        if (!file_exists($imagePath)) {
            $io->error("Image file not found: {$imagePath}");
            return Command::FAILURE;
        }

        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            $io->error("Invalid image file: {$imagePath}");
            return Command::FAILURE;
        }

        $io->section("Processing image: {$imagePath}");
        $io->text("Original dimensions: {$imageInfo[0]}x{$imageInfo[1]}");
        $io->text("File size: " . $this->formatFileSize(filesize($imagePath)));
        $io->text("MIME type: {$imageInfo['mime']}");

        try {
            $results = [];

            // Complete processing
            if ($input->getOption('all')) {
                $io->section('Complete Image Processing');
                
                $options = [
                    'add_watermark' => true,
                    'compress' => true,
                    'generate_thumbnails' => true,
                    'watermark_text' => $input->getOption('watermark-text'),
                    'quality' => (int) $input->getOption('quality'),
                ];

                if ($input->getOption('max-width')) {
                    $options['max_width'] = (int) $input->getOption('max-width');
                }
                if ($input->getOption('max-height')) {
                    $options['max_height'] = (int) $input->getOption('max-height');
                }

                $results = $this->imageProcessor->processImageComplete($imagePath, $options);
                
                $this->displayCompleteResults($io, $results);
                
            } else {
                // Individual operations
                if ($input->getOption('watermark')) {
                    $io->section('Adding Watermark');
                    
                    $watermarkOptions = [
                        'position' => $input->getOption('position'),
                        'opacity' => (int) $input->getOption('opacity')
                    ];
                    
                    $results['watermarked'] = $this->imageProcessor->addWatermark(
                        $imagePath,
                        $input->getOption('watermark-text'),
                        $input->getOption('output'),
                        $watermarkOptions
                    );
                    
                    $io->success("✅ Watermark added: {$results['watermarked']}");
                }

                if ($input->getOption('compress')) {
                    $io->section('Compressing Image');
                    
                    $compressOptions = [
                        'quality' => (int) $input->getOption('quality')
                    ];
                    
                    if ($input->getOption('max-width')) {
                        $compressOptions['max_width'] = (int) $input->getOption('max-width');
                    }
                    if ($input->getOption('max-height')) {
                        $compressOptions['max_height'] = (int) $input->getOption('max-height');
                    }
                    
                    $results['compressed'] = $this->imageProcessor->compressImage(
                        $imagePath,
                        $input->getOption('output'),
                        $compressOptions
                    );
                    
                    $this->displayCompressionResults($io, $imagePath, $results['compressed']);
                }

                if ($input->getOption('thumbnails')) {
                    $io->section('Generating Thumbnails');
                    
                    $results['thumbnails'] = $this->imageProcessor->generateThumbnails($imagePath);
                    
                    $this->displayThumbnailResults($io, $results['thumbnails']);
                }
            }

            if (empty($results)) {
                $io->warning('No processing options specified. Use --help to see available options.');
                return Command::SUCCESS;
            }

            $io->success('Image processing completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Image processing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function displayCompleteResults(SymfonyStyle $io, array $results): void
    {
        if (isset($results['processed'])) {
            $io->text("📄 Processed: {$results['processed']}");
            $this->displayCompressionResults($io, $results['original'], $results['processed']);
        }

        if (isset($results['watermarked'])) {
            $io->text("🔒 Watermarked: {$results['watermarked']}");
        }

        if (isset($results['thumbnails']) && !empty($results['thumbnails'])) {
            $io->text("🖼️  Thumbnails generated:");
            foreach ($results['thumbnails'] as $size => $path) {
                $io->text("   - {$size}: {$path}");
            }
        }
    }

    private function displayCompressionResults(SymfonyStyle $io, string $originalPath, string $compressedPath): void
    {
        $originalSize = filesize($originalPath);
        $compressedSize = filesize($compressedPath);
        $compressionRatio = round((1 - $compressedSize / $originalSize) * 100, 2);

        $originalInfo = getimagesize($originalPath);
        $compressedInfo = getimagesize($compressedPath);

        $io->text("📊 Compression Results:");
        $io->text("   Original: {$this->formatFileSize($originalSize)} ({$originalInfo[0]}x{$originalInfo[1]})");
        $io->text("   Compressed: {$this->formatFileSize($compressedSize)} ({$compressedInfo[0]}x{$compressedInfo[1]})");
        $io->text("   Reduction: {$compressionRatio}%");
        
        if ($compressionRatio > 0) {
            $io->success("✅ Compressed: {$compressedPath}");
        } else {
            $io->note("No size reduction achieved (image may already be optimized)");
        }
    }

    private function displayThumbnailResults(SymfonyStyle $io, array $thumbnails): void
    {
        $io->table(
            ['Size', 'Path', 'Dimensions', 'File Size'],
            array_map(function($size, $path) {
                $info = getimagesize($path);
                return [
                    $size,
                    $path,
                    "{$info[0]}x{$info[1]}",
                    $this->formatFileSize(filesize($path))
                ];
            }, array_keys($thumbnails), $thumbnails)
        );

        $io->success("✅ " . count($thumbnails) . " thumbnails generated");
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}