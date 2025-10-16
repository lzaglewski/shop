<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Application\Form\ProductCategoryType;
use App\Domain\Product\Model\ProductCategory;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    private ProductCategoryRepositoryInterface $categoryRepository;

    public function __construct(ProductCategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    #[Route('', name: 'category_list', methods: ['GET'])]
    public function list(): Response
    {
        // Get all root categories (those without parents)
        $rootCategories = $this->categoryRepository->findRootCategories();

        return $this->render('category/list.html.twig', [
            'categories' => $rootCategories,
        ]);
    }

    #[Route('/new', name: 'category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $category = new ProductCategory('New Category');
        $form = $this->createForm(ProductCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryRepository->save($category);
            $this->addFlash('success', 'Category created successfully.');

            return $this->redirectToRoute('category_list');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'category_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $category = $this->categoryRepository->findById($id);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'category_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $category = $this->categoryRepository->findById($id);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        $form = $this->createForm(ProductCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if category is trying to set itself as parent
            if ($category->getParent() && $category->getParent()->getId() === $category->getId()) {
                $this->addFlash('danger', 'Category cannot be its own parent.');
                return $this->redirectToRoute('category_list');
            }

            $this->categoryRepository->save($category);
            $this->addFlash('success', 'Category updated successfully.');

            return $this->redirectToRoute('category_show', ['id' => $category->getId()]);
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'category_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $category = $this->categoryRepository->findById($id);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        // Validate CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-category-'.$id, $submittedToken)) {
            $this->addFlash('danger', 'Invalid CSRF token');
            return $this->redirectToRoute('category_list');
        }

        // Check if category has products or subcategories
        if (count($category->getProducts()) > 0) {
            $this->addFlash('danger', 'Cannot delete category with associated products.');
            return $this->redirectToRoute('category_show', ['id' => $id]);
        }

        if (count($category->getChildren()) > 0) {
            $this->addFlash('danger', 'Cannot delete category with subcategories.');
            return $this->redirectToRoute('category_show', ['id' => $id]);
        }

        $this->categoryRepository->remove($category);
        $this->addFlash('success', 'Category deleted successfully.');

        return $this->redirectToRoute('category_list');
    }

    #[Route('/{id}/new-subcategory', name: 'category_new_subcategory', methods: ['GET', 'POST'])]
    public function newSubcategory(int $id, Request $request): Response
    {
        $parentCategory = $this->categoryRepository->findById($id);

        if (!$parentCategory) {
            throw $this->createNotFoundException('Parent category not found');
        }

        $category = new ProductCategory('New Subcategory', null, $parentCategory);
        $form = $this->createForm(ProductCategoryType::class, $category, [
            'parent_locked' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryRepository->save($category);
            $this->addFlash('success', 'Subcategory created successfully.');

            return $this->redirectToRoute('category_show', ['id' => $parentCategory->getId()]);
        }

        return $this->render('category/new_subcategory.html.twig', [
            'form' => $form->createView(),
            'parent' => $parentCategory,
        ]);
    }
}
