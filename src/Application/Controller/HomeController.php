<?php

declare(strict_types=1);

namespace App\Application\Controller;

use App\Application\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        $featuredProducts = $this->productService->getActiveProducts();
        
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
