<?php

declare(strict_types=1);

namespace App\Application\Pricing;

use App\Application\Form\ClientPriceType;
use App\Application\User\UserService;
use App\Domain\Pricing\Model\ClientPrice;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/client-prices')]
#[IsGranted('ROLE_ADMIN')]
class ClientPriceController extends AbstractController
{
    private ClientPriceService $clientPriceService;
    private UserService $userService;
    private ProductRepositoryInterface $productRepository;
    private ProductCategoryRepositoryInterface $categoryRepository;
    private PaginatorInterface $paginator;

    public function __construct(
        ClientPriceService $clientPriceService,
        UserService $userService,
        ProductRepositoryInterface $productRepository,
        ProductCategoryRepositoryInterface $categoryRepository,
        PaginatorInterface $paginator
    ) {
        $this->clientPriceService = $clientPriceService;
        $this->userService = $userService;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->paginator = $paginator;
    }

    #[Route('', name: 'client_price_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $clients = $this->userService->getActiveClients();
        $products = $this->productRepository->findActive();
        $clientPrices = $this->clientPriceService->getAllClientPrices();

        // Filter parameters
        $clientId = $request->query->get('client');
        $productId = $request->query->get('product');
        $status = $request->query->get('status');

        // Apply filters if provided
        if ($clientId || $productId || $status !== null) {
            $filteredPrices = [];

            foreach ($clientPrices as $clientPrice) {
                $matchesClient = !$clientId || $clientPrice->getClient()->getId() == $clientId;
                $matchesProduct = !$productId || $clientPrice->getProduct()->getId() == $productId;
                $matchesStatus = $status === null ||
                    ($status === '1' && $clientPrice->isActive()) ||
                    ($status === '0' && !$clientPrice->isActive());

                if ($matchesClient && $matchesProduct && $matchesStatus) {
                    $filteredPrices[] = $clientPrice;
                }
            }

            $clientPrices = $filteredPrices;
        }

        return $this->render('client_price/index.html.twig', [
            'clientPrices' => $clientPrices,
            'clients' => $clients,
            'products' => $products,
            'selectedClient' => $clientId,
            'selectedProduct' => $productId,
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/client/{id}', name: 'client_price_list', methods: ['GET'])]
    public function listForClient(int $id): Response
    {
        $client = $this->userService->getUserById($id);

        if (!$client) {
            throw $this->createNotFoundException('Client not found');
        }

        $clientPrices = $this->clientPriceService->getClientPricesForClient($client);
        $products = $this->productRepository->findActive();

        // Create a map of product IDs to client prices
        $priceMap = [];
        foreach ($clientPrices as $clientPrice) {
            $priceMap[$clientPrice->getProduct()->getId()] = $clientPrice->getPrice();
        }

        return $this->render('client_price/list.html.twig', [
            'client' => $client,
            'products' => $products,
            'priceMap' => $priceMap,
        ]);
    }

    #[Route('/{id}/edit', name: 'client_price_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $clientPrice = $this->clientPriceService->getClientPriceById($id);

        if (!$clientPrice) {
            throw $this->createNotFoundException('Client price not found');
        }

        $form = $this->createForm(ClientPriceType::class, $clientPrice, [
            'client_id' => $clientPrice->getClient()->getId(),
            'product_id' => $clientPrice->getProduct()->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->clientPriceService->saveClientPrice($clientPrice);

            $this->addFlash('success', 'Client price updated successfully.');
            return $this->redirectToRoute('client_price_index');
        }

        return $this->render('client_price/edit.html.twig', [
            'form' => $form->createView(),
            'clientPrice' => $clientPrice,
        ]);
    }

    #[Route('/{id}/delete', name: 'client_price_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $clientPrice = $this->clientPriceService->getClientPriceById($id);

        if (!$clientPrice) {
            throw $this->createNotFoundException('Client price not found');
        }

        // Validate CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-client-price-'.$id, $submittedToken)) {
            $this->addFlash('danger', 'Invalid CSRF token');
            return $this->redirectToRoute('client_price_index');
        }

        $this->clientPriceService->deleteClientPrice($clientPrice);

        $this->addFlash('success', 'Client price has been deleted successfully.');
        return $this->redirectToRoute('client_price_index');
    }

    /**
     * Bulk edit prices for a specific client
     */
    #[Route('/bulk-edit/client/{id}', name: 'client_price_bulk_edit_for_client', methods: ['GET', 'POST'])]
    public function bulkEditForClient(int $id, Request $request): Response
    {
        $client = $this->userService->getUserById($id);

        if (!$client) {
            throw $this->createNotFoundException('Client not found');
        }

        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');
        $categoryId = $request->query->getInt('category', 0);

        $queryBuilder = $this->productRepository->createActiveProductsQueryBuilder();

        if (!empty($search)) {
            $this->productRepository->addSearchFilter($queryBuilder, $search);
        }

        if ($categoryId > 0) {
            $this->productRepository->addCategoryFilter($queryBuilder, [$categoryId]);
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $page,
            25
        );

        $categories = $this->categoryRepository->findAll();

        $clientPrices = $this->clientPriceService->getClientPricesForClient($client);

        // Create a map of product IDs to client prices
        $priceMap = [];
        foreach ($clientPrices as $clientPrice) {
            $priceMap[$clientPrice->getProduct()->getId()] = [
                'price' => $clientPrice->getPrice(),
                'isActive' => $clientPrice->isActive(),
                'id' => $clientPrice->getId()
            ];
        }

        if ($request->isMethod('POST')) {
            $productPrices = $request->request->all('prices');
            $productStatus = $request->request->all('active');
            $updatedCount = 0;

            foreach ($pagination->getItems() as $product) {
                $productId = $product->getId();
                $price = isset($productPrices[$productId]) ? (float)$productPrices[$productId] : null;
                $isActive = isset($productStatus[$productId]);

                // Skip if no price was provided
                if ($price === null) {
                    continue;
                }

                // Get existing client price or create a new one
                $clientPrice = $this->clientPriceService->getClientPriceByClientAndProduct($client, $product);

                if (!$clientPrice) {
                    $clientPrice = new ClientPrice($client, $product, $price);
                } else {
                    $clientPrice->setPrice($price);
                }

                $clientPrice->setIsActive($isActive);
                $this->clientPriceService->saveClientPrice($clientPrice);
                $updatedCount++;
            }

            $this->addFlash('success', sprintf('Successfully updated %d prices for %s', $updatedCount, $client->getCompanyName()));
            return $this->redirectToRoute('client_price_index', ['client' => $client->getId()]);
        }

        return $this->render('client_price/bulk_edit_client.html.twig', [
            'client' => $client,
            'pagination' => $pagination,
            'priceMap' => $priceMap,
            'categories' => $categories,
            'currentSearch' => $search,
            'currentCategory' => $categoryId
        ]);
    }

    /**
     * Bulk edit prices for a specific product
     */
#[Route('/bulk-edit/product/{id}', name: 'client_price_bulk_edit_for_product', methods: ['GET', 'POST'])]
    public function bulkEditForProduct(int $id, Request $request): Response
    {
        $product = $this->productRepository->findById($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');

        $clients = $this->userService->getActiveClients();

        // Filter clients by search if provided
        if (!empty($search)) {
            $clients = array_filter($clients, function($client) use ($search) {
                return stripos($client->getCompanyName(), $search) !== false;
            });
        }

        // Paginate the clients
        $clientsArray = array_values($clients);
        $totalClients = count($clientsArray);
        $itemsPerPage = 5;
        $offset = ($page - 1) * $itemsPerPage;
        $paginatedClients = array_slice($clientsArray, $offset, $itemsPerPage);

        $clientPrices = $this->clientPriceService->getClientPricesForProduct($product);

        // Create a map of client IDs to client prices
        $priceMap = [];
        foreach ($clientPrices as $clientPrice) {
            $priceMap[$clientPrice->getClient()->getId()] = [
                'price' => $clientPrice->getPrice(),
                'isActive' => $clientPrice->isActive(),
                'id' => $clientPrice->getId()
            ];
        }

        if ($request->isMethod('POST')) {
            $clientPrices = $request->request->all('prices');
            $clientStatus = $request->request->all('active');
            $updatedCount = 0;

            foreach ($paginatedClients as $client) {
                $clientId = $client->getId();
                $price = isset($clientPrices[$clientId]) ? (float)$clientPrices[$clientId] : null;
                $isActive = isset($clientStatus[$clientId]);

                // Skip if no price was provided
                if ($price === null) {
                    continue;
                }

                // Get existing client price or create a new one
                $clientPrice = $this->clientPriceService->getClientPriceByClientAndProduct($client, $product);

                if (!$clientPrice) {
                    $clientPrice = new ClientPrice($client, $product, $price);
                } else {
                    $clientPrice->setPrice($price);
                }

                $clientPrice->setIsActive($isActive);
                $this->clientPriceService->saveClientPrice($clientPrice);
                $updatedCount++;
            }

            $this->addFlash('success', sprintf('Successfully updated %d prices for %s', $updatedCount, $product->getName()));
            return $this->redirectToRoute('client_price_index', ['product' => $product->getId()]);
        }

        return $this->render('client_price/bulk_edit_product.html.twig', [
            'product' => $product,
            'clients' => $paginatedClients,
            'priceMap' => $priceMap,
            'basePrice' => $product->getBasePrice(),
            'currentSearch' => $search,
            'currentPage' => $page,
            'totalClients' => $totalClients,
            'itemsPerPage' => $itemsPerPage,
            'totalPages' => ceil($totalClients / $itemsPerPage)
        ]);
    }
}
