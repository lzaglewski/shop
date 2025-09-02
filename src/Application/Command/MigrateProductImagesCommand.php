<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Product\Repository\ProductRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-product-images',
    description: 'Migrate existing product images to product-specific directories'
)]
class MigrateProductImagesCommand extends Command
{
    private ProductRepositoryInterface $productRepository;
    private string $productImagesDirectory;

    public function __construct(ProductRepositoryInterface $productRepository, string $productImagesDirectory)
    {
        $this->productRepository = $productRepository;
        $this->productImagesDirectory = $productImagesDirectory;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Migrating Product Images to Product-specific Directories');
        
        $products = $this->productRepository->findAll();
        
        if (empty($products)) {
            $io->success('No products found. Nothing to migrate.');
            return Command::SUCCESS;
        }
        
        $io->progressStart(count($products));
        
        $migratedCount = 0;
        $errorCount = 0;
        
        foreach ($products as $product) {
            try {
                $productId = $product->getId();
                $productDir = $this->productImagesDirectory . '/' . $productId;
                
                // Create product-specific directory if it doesn't exist
                if (!is_dir($productDir)) {
                    if (!mkdir($productDir, 0755, true)) {
                        $io->error("Failed to create directory: {$productDir}");
                        $errorCount++;
                        continue;
                    }
                }
                
                $imagesToMove = [];
                
                // Collect main image
                if ($product->getImageFilename()) {
                    $imagesToMove[] = $product->getImageFilename();
                }
                
                // Collect additional images
                if ($product->getImages()) {
                    $imagesToMove = array_merge($imagesToMove, $product->getImages());
                }
                
                // Remove duplicates
                $imagesToMove = array_unique($imagesToMove);
                
                foreach ($imagesToMove as $imageFilename) {
                    $oldPath = $this->productImagesDirectory . '/' . $imageFilename;
                    $newPath = $productDir . '/' . $imageFilename;
                    
                    // Skip if source file doesn't exist
                    if (!file_exists($oldPath)) {
                        continue;
                    }
                    
                    // Skip if already in the right location
                    if ($oldPath === $newPath) {
                        continue;
                    }
                    
                    // Move the file
                    if (rename($oldPath, $newPath)) {
                        $io->writeln("Moved: {$imageFilename} -> {$productId}/{$imageFilename}", OutputInterface::VERBOSITY_VERBOSE);
                    } else {
                        $io->error("Failed to move: {$oldPath} -> {$newPath}");
                        $errorCount++;
                    }
                }
                
                $migratedCount++;
                
            } catch (\Exception $e) {
                $io->error("Error processing product {$product->getId()}: " . $e->getMessage());
                $errorCount++;
            }
            
            $io->progressAdvance();
        }
        
        $io->progressFinish();
        
        $io->section('Migration Results');
        $io->table(
            ['Metric', 'Count'],
            [
                ['Total Products', count($products)],
                ['Successfully Migrated', $migratedCount],
                ['Errors', $errorCount],
            ]
        );
        
        if ($errorCount === 0) {
            $io->success('All product images migrated successfully!');
            return Command::SUCCESS;
        } else {
            $io->warning("Migration completed with {$errorCount} errors. Check the output above for details.");
            return Command::FAILURE;
        }
    }
}