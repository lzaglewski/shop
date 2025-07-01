<?php

declare(strict_types=1);

namespace App\Application\Controller;

use App\Application\Form\ProductType;
use App\Application\Service\ProductService;
use App\Domain\Model\Product\Product;
use App\Domain\Service\PricingService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/products')]
class ProductController extends AbstractController
{
    private ProductService $productService;
    private PricingService $pricingService;
    private SluggerInterface $slugger;
    private PaginatorInterface $paginator;
    
    public function __construct(
        ProductService $productService,
        PricingService $pricingService,
        SluggerInterface $slugger,
        PaginatorInterface $paginator
    ) {
        $this->productService = $productService;
        $this->pricingService = $pricingService;
        $this->slugger = $slugger;
        $this->paginator = $paginator;
    }

    #[Route('', name: 'product_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $query = $this->productService->getActiveProductsQuery();
        
        // Get filter parameters
        $categoryId = $request->query->get('category');
        $search = $request->query->get('search');
        
        // Apply filters if provided
        if ($categoryId) {
            $query = $this->productService->filterByCategory($query, $categoryId);
        }
        
        if ($search) {
            $query = $this->productService->filterBySearch($query, $search);
        }
        
        // Paginate the results
        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            9 // Items per page
        );
        
        // Get categories for the filter
        $categories = $this->productService->getAllCategories();
        
        return $this->render('product/list.html.twig', [
            'products' => $pagination,
            'categories' => $categories,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Create a new product with temporary values that will be overwritten by the form
        $product = new Product(
            'New Product',  // name
            'SKU-' . uniqid(), // sku
            'Product description', // description
            0.0, // basePrice
            0,   // stock
            null, // category
            null  // imageFilename
        );
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );
                    $product->setImageFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'There was an error uploading the image.');
                }
            }
            
            // Save the product
            $this->productService->saveProduct($product);
            
            $this->addFlash('success', 'Product created successfully.');
            
            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }
        
        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    public function show($id): Response
    {
        $product = $this->productService->getProductById((int)$id);
        
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        
        $clientPrice = null;
        $user = $this->getUser();
        
        if ($user) {
            // Get the client's custom price for this product
            $price = $this->pricingService->getProductPriceForClient($product, $user);
            
            // If the client has a custom price (different from base price), create a ClientPrice object
            if ($price !== $product->getBasePrice()) {
                $clientPrice = new \App\Domain\Model\Pricing\ClientPrice($user, $product, $price);
            }
        }
        
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'clientPrice' => $clientPrice,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit($id, Request $request): Response
    {
        $product = $this->productService->getProductById((int)$id);
        
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );
                    
                    // Remove old image if exists
                    $oldFilename = $product->getImageFilename();
                    if ($oldFilename) {
                        $oldFilePath = $this->getParameter('product_images_directory').'/'.$oldFilename;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    
                    $product->setImageFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'There was an error uploading the image.');
                }
            }
            
            // Save the product
            $this->productService->saveProduct($product);
            
            $this->addFlash('success', 'Product updated successfully.');
            
            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }
        
        return $this->render('product/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete', name: 'product_delete', methods: ['POST'])]
    public function delete($id, Request $request): Response
    {
        $product = $this->productService->getProductById((int)$id);
        
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        
        // Validate CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-product-'.$id, $submittedToken)) {
            $this->addFlash('danger', 'Invalid CSRF token');
            return $this->redirectToRoute('product_list');
        }
        
        $this->productService->deactivateProduct($product);
        $this->addFlash('success', 'Product has been deactivated.');
        
        return $this->redirectToRoute('product_list');
    }
}
