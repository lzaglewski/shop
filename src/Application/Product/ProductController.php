<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Application\Cart\CartService;
use App\Application\Form\ProductType;
use App\Application\Pricing\ClientPriceService;
use App\Domain\Product\Model\Product;
use App\Application\Product\ProductApplicationService;
use App\Domain\User\Model\UserRole;
use App\Domain\User\Model\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/products')]
class ProductController extends AbstractController
{
    private ProductApplicationService $productApplicationService;
    private ClientPriceService $clientPriceService;
    private ProductImageService $productImageService;
    private CartService $cartService;
    private TranslatorInterface $translator;
    private int $productsPerPage;
    private int $newOrderProductsPerPage;

    public function __construct(
        ProductApplicationService $productApplicationService,
        ClientPriceService        $clientPriceService,
        ProductImageService       $productImageService,
        CartService               $cartService,
        TranslatorInterface       $translator,
        int                       $productsPerPage,
        int                       $newOrderProductsPerPage
    )
    {
        $this->productApplicationService = $productApplicationService;
        $this->clientPriceService = $clientPriceService;
        $this->productImageService = $productImageService;
        $this->cartService = $cartService;
        $this->translator = $translator;
        $this->productsPerPage = $productsPerPage;
        $this->newOrderProductsPerPage = $newOrderProductsPerPage;
    }

    #[Route('', name: 'product_list', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function list(Request $request): Response
    {
        $user = $this->getUser();
        $categoryId = $request->query->get('category');
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sort_by');
        $sortOrder = $request->query->get('sort_order');

        $pagination = $this->productApplicationService->getFilteredProducts(
            $user,
            $categoryId,
            $search,
            $request->query->getInt('page', 1),
            $this->productsPerPage,
            true,
            $sortBy,
            $sortOrder
        );

        $categories = $this->productApplicationService->getCategoriesWithVisibleProducts($user);

        // Get client prices for current user if they are a client
        $clientPrices = [];
        if ($user && $user->getRole() === UserRole::CLIENT) {
            $clientPriceEntities = $this->clientPriceService->getClientPricesForClient($user);
            foreach ($clientPriceEntities as $clientPrice) {
                $clientPrices[$clientPrice->getProduct()->getId()] = $clientPrice->getPrice();
            }
        }

        return $this->render('product/list.html.twig', [
            'products' => $pagination,
            'categories' => $categories,
            'clientPrices' => $clientPrices,
            'selectedCategory' => $categoryId,
            'searchTerm' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'product_admin_list', methods: ['GET'])]
    public function adminList(Request $request): Response
    {
        $categoryId = $request->query->get('category');
        $search = $request->query->get('search');
        $status = $request->query->get('status');

        $pagination = $this->productApplicationService->getAdminFilteredProducts(
            $categoryId,
            $search,
            $status,
            $request->query->getInt('page', 1),
            $this->productsPerPage
        );

        $categories = $this->productApplicationService->getAllCategories();

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
        $user = $this->getUser();
        $categoryId = $request->query->get('category');
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sort_by');
        $sortOrder = $request->query->get('sort_order');

        $pagination = $this->productApplicationService->getFilteredProducts(
            $user,
            $categoryId,
            $search,
            $request->query->getInt('page', 1),
            $this->newOrderProductsPerPage,
            true,
            $sortBy,
            $sortOrder
        );

        $categories = $this->productApplicationService->getCategoriesWithVisibleProducts($user);

        // Get client prices for current user if they are a client
        $clientPrices = [];
        if ($user && $user->getRole() === UserRole::CLIENT) {
            $clientPriceEntities = $this->clientPriceService->getClientPricesForClient($user);
            foreach ($clientPriceEntities as $clientPrice) {
                $clientPrices[$clientPrice->getProduct()->getId()] = $clientPrice->getPrice();
            }
        }

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
            'clientPrices' => $clientPrices,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
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
                $this->addFlash('danger', 'product.form_validation_errors');
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
                $this->productApplicationService->saveProduct($product);
                $this->addFlash('success', 'product.created_successfully');
                return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('danger', $this->translator->trans('product.error_creating_product', ['%message%' => $e->getMessage()]));
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
        $user = $this->getUser();

        // If user is a client, check access permissions
        if ($user && $user->getRole() === UserRole::CLIENT) {
            $product = $this->productApplicationService->getProductWithClientAccess((int)$id, $user);
            if (!$product) {
                throw $this->createAccessDeniedException('You do not have access to view this product.');
            }
            $clientPrice = $this->clientPriceService->getClientPriceByClientAndProduct($user, $product);
        } else {
            $product = $this->productApplicationService->getProductById((int)$id);
            if (!$product) {
                throw $this->createNotFoundException('Product not found');
            }
            $clientPrice = null;
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
        $product = $this->productApplicationService->getProductById((int)$id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->addFlash('danger', 'product.form_validation_errors');
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
                $this->productApplicationService->saveProduct($product);
                $this->addFlash('success', 'product.updated_successfully');
                return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('danger', $this->translator->trans('product.error_updating_product', ['%message%' => $e->getMessage()]));
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
        $product = $this->productApplicationService->getProductById((int)$id);
        $isActive = $product ? $product->isActive() : false;

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        // Validate CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-product-' . $id, $submittedToken)) {
            $this->addFlash('danger', 'common.invalid_csrf_token');
            return $this->redirectToRoute('product_list');
        }

        $this->productApplicationService->deleteProduct($product);

        if ($isActive) {
            $this->addFlash('success', 'product.has_been_deactivated');
        } else {
            $this->addFlash('success', 'product.has_been_deleted');
        }


        return $this->redirectToRoute('product_admin_list');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete-image', name: 'product_delete_image', methods: ['POST'])]
    public function deleteImage(int $id, Request $request): Response
    {
        $product = $this->productApplicationService->getProductById($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        // Validate CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-image-' . $id, $submittedToken)) {
            $this->addFlash('danger', 'common.invalid_csrf_token');
            return $this->redirectToRoute('product_edit', ['id' => $id]);
        }

        $imageType = $request->request->get('image_type');
        $imageFilename = $request->request->get('image_filename');

        if ($imageType === 'main' && $imageFilename === $product->getImageFilename()) {
            $this->productImageService->deleteImage($imageFilename);
            $product->setImageFilename(null);
            $this->addFlash('success', 'product.main_image_deleted_successfully');
        } elseif ($imageType === 'additional') {
            $this->productImageService->deleteImage($imageFilename);
            $product->removeImage($imageFilename);
            $this->addFlash('success', 'product.additional_image_deleted_successfully');
        } else {
            $this->addFlash('danger', 'product.invalid_image_specified');
            return $this->redirectToRoute('product_edit', ['id' => $id]);
        }

        $this->productApplicationService->saveProduct($product);

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
