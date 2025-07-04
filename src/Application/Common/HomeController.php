<?php

declare(strict_types=1);

namespace App\Application\Common;

use App\Application\Service\ClientPriceService;
use App\Application\Service\ProductService;
use App\Domain\User\Model\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private ProductService $productService;
    private ClientPriceService $clientPriceService;

    public function __construct(ProductService $productService, ClientPriceService $clientPriceService)
    {
        $this->productService = $productService;
        $this->clientPriceService = $clientPriceService;
    }

    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        $activeProducts = $this->productService->getActiveProducts();
        $user = $this->getUser();
        $isClient = $user && $user->getRole() === UserRole::CLIENT;
        
        // Filter products by visibility if the user is a client
        if ($isClient) {
            $visibleProducts = $this->clientPriceService->getVisibleProductsForClient($user);
            
            // Filter the active products to only include visible products
            $visibleProductIds = array_map(function($product) {
                return $product->getId();
            }, $visibleProducts);
            
            $featuredProducts = array_filter($activeProducts, function($product) use ($visibleProductIds) {
                return in_array($product->getId(), $visibleProductIds);
            });
        } else {
            $featuredProducts = $activeProducts;
        }

        // In a real application, you might want to limit this to just a few featured products
        if (count($featuredProducts) > 8) {
            $featuredProducts = array_slice($featuredProducts, 0, 8);
        }

        return $this->render('home/index.html.twig', [
            'featuredProducts' => $featuredProducts,
        ]);
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }
}
