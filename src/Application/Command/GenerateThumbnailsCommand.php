<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Product\ProductImageService;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-thumbnails',
    description: 'Generate thumbnails for existing product images'
)]
class GenerateThumbnailsCommand extends Command
{
    private ProductRepositoryInterface $productRepository;
    private ProductImageService $productImageService;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductImageService $productImageService
    ) {
        $this->productRepository = $productRepository;
        $this->productImageService = $productImageService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Regenerate thumbnails even if they already exist')
            ->addOption('product-id', 'p', InputOption::VALUE_OPTIONAL, 'Generate thumbnails only for specific product ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $productId = $input->getOption('product-id');
        
        $io->title('Generating Thumbnails for Product Images');
        
        if ($productId) {
            $product = $this->productRepository->find((int)$productId);
            if (!$product) {
                $io->error("Product with ID {$productId} not found.");
                return Command::FAILURE;
            }
            $products = [$product];
            $io->info("Processing single product: {$product->getName()} (ID: {$productId})");
        } else {
            $products = $this->productRepository->findAll();
            $io->info("Processing all products...");
        }
        
        if (empty($products)) {
            $io->success('No products found. Nothing to process.');
            return Command::SUCCESS;
        }
        
        $io->progressStart(count($products));
        
        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $thumbnailsCreated = 0;
        
        foreach ($products as $product) {
            try {
                $productId = $product->getId();
                $imagesToProcess = [];
                
                // Collect main image
                if ($product->getImageFilename()) {
                    $imagesToProcess[] = $product->getImageFilename();
                }
                
                // Collect additional images
                if ($product->getImages()) {
                    $imagesToProcess = array_merge($imagesToProcess, $product->getImages());
                }
                
                // Remove duplicates
                $imagesToProcess = array_unique($imagesToProcess);
                
                if (empty($imagesToProcess)) {
                    $skippedCount++;
                    $io->progressAdvance();
                    continue;
                }
                
                foreach ($imagesToProcess as $imageFilename) {
                    $productDirectory = $this->productImageService->getProductDirectory($productId);
                    $originalPath = $productDirectory . '/' . $imageFilename;
                    
                    // Skip if original file doesn't exist
                    if (!file_exists($originalPath)) {
                        $io->writeln("Original file not found: {$originalPath}", OutputInterface::VERBOSITY_VERBOSE);
                        continue;
                    }
                    
                    // Check if we should skip existing thumbnails
                    if (!$force) {
                        $allThumbnailsExist = true;
                        foreach (array_keys(\App\Application\Image\ThumbnailService::SIZES) as $size) {
                            if (!$this->productImageService->thumbnailExists($imageFilename, $size, $productId)) {
                                $allThumbnailsExist = false;
                                break;
                            }
                        }
                        
                        if ($allThumbnailsExist) {
                            $io->writeln("Thumbnails already exist for: {$imageFilename}", OutputInterface::VERBOSITY_VERBOSE);
                            continue;
                        }
                    }
                    
                    try {
                        $this->productImageService->createThumbnailsForImage($imageFilename, $productId);
                        $thumbnailsCreated += count(\App\Application\Image\ThumbnailService::SIZES);
                        $io->writeln("Created thumbnails for: {$imageFilename}", OutputInterface::VERBOSITY_VERBOSE);
                    } catch (\Exception $e) {
                        $io->writeln("Failed to create thumbnails for {$imageFilename}: " . $e->getMessage(), OutputInterface::VERBOSITY_VERBOSE);
                        $errorCount++;
                    }
                }
                
                $processedCount++;
                
            } catch (\Exception $e) {
                $io->error("Error processing product {$product->getId()}: " . $e->getMessage());
                $errorCount++;
            }
            
            $io->progressAdvance();
        }
        
        $io->progressFinish();
        
        $io->section('Thumbnail Generation Results');
        $io->table(
            ['Metric', 'Count'],
            [
                ['Total Products', count($products)],
                ['Products Processed', $processedCount],
                ['Products Skipped (no images)', $skippedCount],
                ['Thumbnails Created', $thumbnailsCreated],
                ['Errors', $errorCount],
            ]
        );
        
        if ($errorCount === 0) {
            $io->success("All thumbnails generated successfully! Created {$thumbnailsCreated} thumbnails.");
            return Command::SUCCESS;
        } else {
            $io->warning("Thumbnail generation completed with {$errorCount} errors. Created {$thumbnailsCreated} thumbnails. Check the output above for details.");
            return Command::FAILURE;
        }
    }
}