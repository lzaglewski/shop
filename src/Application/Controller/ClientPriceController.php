<?php

declare(strict_types=1);

namespace App\Application\Controller;

use App\Application\Form\ClientPriceType;
use App\Application\Service\ClientPriceService;
use App\Application\Service\ProductService;
use App\Application\Service\UserService;
use App\Domain\Model\Pricing\ClientPrice;
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
    private ProductService $productService;

    public function __construct(
        ClientPriceService $clientPriceService,
        UserService $userService,
        ProductService $productService
    ) {
        $this->clientPriceService = $clientPriceService;
        $this->userService = $userService;
        $this->productService = $productService;
    }

    #[Route('', name: 'client_price_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $clients = $this->userService->getActiveClients();
        $products = $this->productService->getActiveProducts();
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
        $products = $this->productService->getActiveProducts();
        
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

    #[Route('/new', name: 'client_price_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Get the first client and product as temporary defaults
        // These will be replaced by the form data
        $clients = $this->userService->getActiveClients();
        $products = $this->productService->getActiveProducts();
        
        if (empty($clients) || empty($products)) {
            $this->addFlash('danger', 'You need at least one active client and one active product to create a client price.');
            return $this->redirectToRoute('client_price_index');
        }
        
        $client = $clients[0];
        $product = $products[0];
        
        // Create a new ClientPrice with required constructor arguments
        $clientPrice = new ClientPrice($client, $product, 0.0);
        $form = $this->createForm(ClientPriceType::class, $clientPrice);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->clientPriceService->saveClientPrice($clientPrice);
            
            $this->addFlash('success', 'Client price created successfully.');
            return $this->redirectToRoute('client_price_index');
        }
        
        return $this->render('client_price/new.html.twig', [
            'form' => $form->createView(),
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
}
