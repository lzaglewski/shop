<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Application\Form\ProductType;
use App\Application\Service\ClientPriceService;
use App\Application\Service\ProductService;
use App\Domain\Product\Model\Product;
use App\Domain\Pricing\Service\PricingService;
use App\Domain\User\Model\UserRole;
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
    private ClientPriceService $clientPriceService;
    private SluggerInterface $slugger;
    private PaginatorInterface $paginator;

    public function __construct(
        ProductService $productService,
        PricingService $pricingService,
        ClientPriceService $clientPriceService,
        SluggerInterface $slugger,
        PaginatorInterface $paginator
    ) {
        $this->productService = $productService;
        $this->pricingService = $pricingService;
        $this->clientPriceService = $clientPriceService;
        $this->slugger = $slugger;
        $this->paginator = $paginator;
    }

    #[Route('', name: 'product_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $query = $this->productService->getActiveProductsQuery();
        $user = $this->getUser();
        $isClient = $user && $user->getRole() === UserRole::CLIENT;

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
        
        // Get all products before filtering by visibility
        $queryResult = $query->getQuery()->getResult();
        
        // Filter products by visibility if the user is a client
        // Only show products that have a ClientPrice entry for this client
        if ($isClient) {
            $visibleProducts = $this->clientPriceService->getVisibleProductsForClient($user);
            
            // Filter the query result to only include visible products
            $visibleProductIds = array_map(function($product) {
                return $product->getId();
            }, $visibleProducts);
            
            $queryResult = array_filter($queryResult, function($product) use ($visibleProductIds) {
                return in_array($product->getId(), $visibleProductIds);
            });
        }
        
        // Paginate the filtered results
        $pagination = $this->paginator->paginate(
            $queryResult,
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
        
        // Check if the user is a client and if the product is visible to them
        if ($user && $user->getRole() === UserRole::CLIENT) {
            // Check if the client has access to this product
            if (!$this->clientPriceService->isProductVisibleToClient($product, $user)) {
                throw $this->createAccessDeniedException('You do not have access to view this product.');
            }
            
            // Get the client price entry for this product
            $clientPrice = $this->clientPriceService->getClientPriceByClientAndProduct($user, $product);
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
