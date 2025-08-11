<?php

declare(strict_types=1);

namespace App\Application\Admin;

use App\Application\Common\SettingsService;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/settings')]
#[IsGranted('ROLE_ADMIN')]
class SettingsController extends AbstractController
{
    private SettingsService $settingsService;
    private ProductCategoryRepositoryInterface $categoryRepository;

    public function __construct(
        SettingsService $settingsService,
        ProductCategoryRepositoryInterface $categoryRepository
    ) {
        $this->settingsService = $settingsService;
        $this->categoryRepository = $categoryRepository;
    }

    #[Route('', name: 'admin_settings')]
    public function index(): Response
    {
        $categories = $this->categoryRepository->findAll();
        $currentCategory = $this->settingsService->getHomepageCategory();

        return $this->render('admin/settings/index.html.twig', [
            'categories' => $categories,
            'currentCategory' => $currentCategory,
        ]);
    }

    #[Route('/homepage-category', name: 'admin_settings_homepage_category', methods: ['POST'])]
    public function updateHomepageCategory(Request $request): Response
    {
        $categoryId = $request->request->get('category_id');

        $category = null;
        if ($categoryId && $categoryId !== '') {
            $category = $this->categoryRepository->findById((int)$categoryId);
        }

        $this->settingsService->setHomepageCategory($category);

        $this->addFlash('success', 'Ustawienia zostały zapisane.');

        return $this->redirectToRoute('admin_settings');
    }
}
