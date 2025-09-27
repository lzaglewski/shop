<?php

declare(strict_types=1);

namespace App\Application\Common;

use App\Application\Form\ContactFormType;
use App\Application\Form\InfoFormType;
use App\Application\Pricing\ClientPriceService;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\User\Model\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private ProductRepositoryInterface $productRepository;
    private ClientPriceService $clientPriceService;
    private SettingsService $settingsService;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ClientPriceService $clientPriceService,
        SettingsService $settingsService
    ) {
        $this->productRepository = $productRepository;
        $this->clientPriceService = $clientPriceService;
        $this->settingsService = $settingsService;
    }

    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        $user = $this->getUser();
        $isClient = $user && $user->getRole() === UserRole::CLIENT;

        // Get featured category from settings
        $featuredCategory = $this->settingsService->getHomepageCategory();

        if ($isClient) {
            // Get products visible to client
            $visibleProducts = $this->clientPriceService->getVisibleProductsForClient($user);

            // Filter by category if specified in settings
            if ($featuredCategory) {
                $featuredProducts = array_filter($visibleProducts, function($product) use ($featuredCategory) {
                    return $product->getCategory() && $product->getCategory()->getId() === $featuredCategory->getId();
                });
            } else {
                $featuredProducts = $visibleProducts;
            }
        } else {
            // For non-clients, get all active products
            $activeProducts = $this->productRepository->findActive();

            // Filter by category if specified in settings
            if ($featuredCategory) {
                $featuredProducts = array_filter($activeProducts, function($product) use ($featuredCategory) {
                    return $product->getCategory() && $product->getCategory()->getId() === $featuredCategory->getId();
                });
            } else {
                $featuredProducts = $activeProducts;
            }
        }

        // Limit to 8 products
        if (count($featuredProducts) > 8) {
            $featuredProducts = array_slice($featuredProducts, 0, 8);
        }

        // Get banner from settings
        $bannerImage = $this->settingsService->getHomepageBanner();

        return $this->render('home/index.html.twig', [
            'featuredProducts' => $featuredProducts,
            'bannerImage' => $bannerImage,
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
        $form = $this->createForm(ContactFormType::class, null, [
            'action' => $this->generateUrl('contact_submit'),
        ]);

        return $this->render('home/contact.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }

    #[Route('/info', name: 'info')]
    public function info(): Response
    {
        $form = $this->createForm(InfoFormType::class, null, [
            'action' => $this->generateUrl('info_submit'),
        ]);

        return $this->render('home/info.html.twig', [
            'infoForm' => $form->createView(),
        ]);
    }
}
