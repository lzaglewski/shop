<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Application\Cart\CartService;
use App\Application\Form\ProductType;
use App\Application\Pricing\ClientPriceService;
use App\Domain\Pricing\Service\PricingService;
use App\Domain\Product\Model\Product;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\Service\ProductVisibilityService;
use App\Domain\User\Model\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/products')]
class ProductController extends AbstractController
{
    private ProductRepositoryInterface $productRepository;
    private ProductCategoryRepositoryInterface $categoryRepository;
    private PricingService $pricingService;
    private ClientPriceService $clientPriceService;
    private ProductImageService $productImageService;
    private ProductVisibilityService $productVisibilityService;
    private CartService $cartService;
    private int $productsPerPage;
    private int $newOrderProductsPerPage;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductCategoryRepositoryInterface $categoryRepository,
        PricingService $pricingService,
        ClientPriceService $clientPriceService,
        ProductImageService $productImageService,
        ProductVisibilityService $productVisibilityService,
        CartService $cartService,
        int $productsPerPage,
        int $newOrderProductsPerPage
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->pricingService = $pricingService;
        $this->clientPriceService = $clientPriceService;
        $this->productImageService = $productImageService;
        $this->productVisibilityService = $productVisibilityService;
        $this->cartService = $cartService;
        $this->productsPerPage = $productsPerPage;
        $this->newOrderProductsPerPage = $newOrderProductsPerPage;
    }

    #[Route('', name: 'product_list', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function list(Request $request): Response
    {
        $queryBuilder = $this->productRepository->createActiveProductsQueryBuilder();
        $user = $this->getUser();

        // Apply filters
        $categoryId = $request->query->get('category');
        $search = $request->query->get('search');

        if ($categoryId) {
            $this->productRepository->addCategoryFilter($queryBuilder, $categoryId);
        }

        if ($search) {
            $this->productRepository->addSearchFilter($queryBuilder, $search);
        }

        // Filter by client visibility if user is a client
        if ($user && $this->productVisibilityService->shouldFilterForClient($user)) {
            $this->productRepository->addClientVisibilityFilter($queryBuilder, $user);
        }

        // Add client price join for efficient price loading
        if ($user && $user->getRole() === UserRole::CLIENT) {
            $this->productRepository->addClientPriceJoin($queryBuilder, $user);
        }

        // DEBUG: Log SQL query to see what's happening
        $query = $queryBuilder->getQuery();
        error_log("SQL Query: " . $query->getSQL());
        error_log("Parameters: " . json_encode($query->getParameters()));

        // Get paginated results
        $pagination = $this->productRepository->getPaginatedProducts(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $this->productsPerPage
        );

        $categories = $this->categoryRepository->findAll();

        return $this->render('product/list.html.twig', [
            'products' => $pagination,
            'categories' => $categories,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'product_admin_list', methods: ['GET'])]
    public function adminList(Request $request): Response
    {
        $queryBuilder = $this->productRepository->createAllProductsQueryBuilder();

        // Get filter parameters
        $categoryId = $request->query->get('category');
        $search = $request->query->get('search');
        $status = $request->query->get('status');

        // Apply filters if provided
        if ($categoryId) {
            $this->productRepository->addCategoryFilter($queryBuilder, $categoryId);
        }

        if ($search) {
            $this->productRepository->addSearchFilter($queryBuilder, $search);
        }

        if ($status !== null && $status !== 'All' && $status !== '') {
            $this->productRepository->addStatusFilter($queryBuilder, $status === '1');
        }

        // Get paginated results
        $pagination = $this->productRepository->getPaginatedProducts(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $this->productsPerPage
        );

        $categories = $this->categoryRepository->findAll();

        return $this->render('product/admin_list.html.twig', [
            'products' => $pagination,
            'categories' => $categories,
            'selectedCategory' => $categoryId,
            'searchTerm' => $search,
            'selectedStatus' => $status,
        ]);
    }

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/new-order', name: 'product_new_order', methods: ['GET'])]
    public function newOrder(Request $request): Response
    {
        $queryBuilder = $this->productRepository->createActiveProductsQueryBuilder();
        $user = $this->getUser();

        // Apply filters
        $categoryId = $request->query->get('category');
        $search = $request->query->get('search');

        if ($categoryId) {
            $this->productRepository->addCategoryFilter($queryBuilder, $categoryId);
        }

        if ($search) {
            $this->productRepository->addSearchFilter($queryBuilder, $search);
        }

        // Filter by client visibility if user is a client
        if ($user && $this->productVisibilityService->shouldFilterForClient($user)) {
            $this->productRepository->addClientVisibilityFilter($queryBuilder, $user);
        }

        // Add client price join for efficient price loading
        if ($user && $user->getRole() === UserRole::CLIENT) {
            $this->productRepository->addClientPriceJoin($queryBuilder, $user);
        }

        // Get paginated results
        $pagination = $this->productRepository->getPaginatedProducts(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $this->newOrderProductsPerPage
        );

        $categories = $this->categoryRepository->findAll();

        // Get current cart to check which products are already in cart
        $cart = $this->cartService->getCart();
        $cartProductIds = [];
        foreach ($cart->getItems() as $cartItem) {
            $cartProductIds[] = $cartItem->getProduct()->getId();
        }

        return $this->render('product/new_order.html.twig', [
            'products' => $pagination,
            'categories' => $categories,
            'selectedCategory' => $categoryId,
            'searchTerm' => $search,
            'cartProductIds' => $cartProductIds,
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

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->addFlash('danger', 'Form has validation errors. Please check your input.');
                return $this->render('product/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            try {
                $this->handleImageUploads($form, $product);
            } catch (FileException $e) {
                $this->addFlash('danger', $e->getMessage());
            }

            // Save the product
            try {
                $this->productRepository->save($product);
                $this->addFlash('success', 'Product created successfully.');
                return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error creating product: ' . $e->getMessage());
                return $this->render('product/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    public function show($id): Response
    {
        $product = $this->productRepository->findById((int)$id);

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
        $product = $this->productRepository->findById((int)$id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->addFlash('danger', 'Form has validation errors. Please check your input.');
                return $this->render('product/edit.html.twig', [
                    'form' => $form->createView(),
                    'product' => $product,
                ]);
            }

            try {
                $this->handleImageUploads($form, $product, true);
            } catch (FileException $e) {
                $this->addFlash('danger', $e->getMessage());
            }

            // Save the product
            try {
                $this->productRepository->save($product);
                $this->addFlash('success', 'Product updated successfully.');
                return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error updating product: ' . $e->getMessage());
                return $this->render('product/edit.html.twig', [
                    'form' => $form->createView(),
                    'product' => $product,
                ]);
            }
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
        $product = $this->productRepository->findById((int)$id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        // Validate CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-product-'.$id, $submittedToken)) {
            $this->addFlash('danger', 'Invalid CSRF token');
            return $this->redirectToRoute('product_list');
        }

        $product->setIsActive(false);
        $this->productRepository->save($product);
        $this->addFlash('success', 'Product has been deactivated.');

        return $this->redirectToRoute('product_list');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete-image', name: 'product_delete_image', methods: ['POST'])]
    public function deleteImage(int $id, Request $request): Response
    {
        $product = $this->productRepository->findById($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        // Validate CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-image-'.$id, $submittedToken)) {
            $this->addFlash('danger', 'Invalid CSRF token');
            return $this->redirectToRoute('product_edit', ['id' => $id]);
        }

        $imageType = $request->request->get('image_type');
        $imageFilename = $request->request->get('image_filename');

        if ($imageType === 'main' && $imageFilename === $product->getImageFilename()) {
            $this->productImageService->deleteImage($imageFilename);
            $product->setImageFilename(null);
            $this->addFlash('success', 'Main image deleted successfully.');
        } elseif ($imageType === 'additional') {
            $this->productImageService->deleteImage($imageFilename);
            $product->removeImage($imageFilename);
            $this->addFlash('success', 'Additional image deleted successfully.');
        } else {
            $this->addFlash('danger', 'Invalid image specified.');
            return $this->redirectToRoute('product_edit', ['id' => $id]);
        }

        $this->productRepository->save($product);

        return $this->redirectToRoute('product_edit', ['id' => $id]);
    }

    private function handleImageUploads($form, Product $product, bool $isEdit = false): void
    {
        // Handle single image upload (for backward compatibility)
        $imageFile = $form->get('imageFile')->getData();
        if ($imageFile) {
            $newFilename = $this->productImageService->handleImageUpload($imageFile);

            if ($isEdit) {
                $this->productImageService->replaceMainImage($product->getImageFilename(), $newFilename);
            }

            $product->setImageFilename($newFilename);
        }

        // Handle multiple images upload
        $imageFiles = $form->get('imageFiles')->getData();
        if ($imageFiles) {
            $uploadedFilenames = $this->productImageService->handleMultipleImageUpload($imageFiles);

            foreach ($uploadedFilenames as $filename) {
                $product->addImage($filename);
            }
        }
    }
}
