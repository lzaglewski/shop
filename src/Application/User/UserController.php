<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Application\Form\UserType;
use App\Application\Pricing\ClientPriceService;
use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    private UserService $userService;
    private ClientPriceService $clientPriceService;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserService $userService,
        ClientPriceService $clientPriceService,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->userService = $userService;
        $this->clientPriceService = $clientPriceService;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('', name: 'user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $users = $this->userService->getAllUsers();

        // Filter parameters
        $role = $request->query->get('role');
        $search = $request->query->get('search');
        $status = $request->query->get('status');

        // Apply filters if provided
        if ($role || $search || $status !== null) {
            $filteredUsers = [];

            foreach ($users as $user) {
                $matchesRole = !$role || $user->getRole()->value === $role;
                $matchesSearch = !$search ||
                    str_contains(strtolower($user->getEmail()), strtolower($search)) ||
                    str_contains(strtolower($user->getCompanyName()), strtolower($search));
                $matchesStatus = $status === null ||
                    ($status === '1' && $user->isActive()) ||
                    ($status === '0' && !$user->isActive());

                if ($matchesRole && $matchesSearch && $matchesStatus) {
                    $filteredUsers[] = $user;
                }
            }

            $users = $filteredUsers;
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'selectedRole' => $role,
            'searchTerm' => $search,
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Create a new User with required constructor arguments
        // These values will be overwritten by the form data
        $user = new User(
            'user@example.com', // email
            'temporary-password', // password
            'New Company', // companyName
            null, // taxId
            UserRole::CLIENT // role
        );
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);

            // Save the user
            $this->userService->saveUser($user);

            $this->addFlash('success', 'User created successfully.');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // If user is a client, get their client prices
        $clientPrices = [];
        if ($user->getRole() === UserRole::CLIENT) {
            $clientPrices = $this->clientPriceService->getClientPricesForClient($user);
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'clientPrices' => $clientPrices,
        ]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createForm(UserType::class, $user, [
            'require_password' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save the user
            $this->userService->saveUser($user);

            $this->addFlash('success', 'User updated successfully.');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{id}/activate', name: 'user_activate', methods: ['POST'])]
    public function activate(int $id): Response
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $this->userService->activateUser($user);

        $this->addFlash('success', 'User has been activated successfully.');
        return $this->redirectToRoute('user_index');
    }

    #[Route('/{id}/deactivate', name: 'user_deactivate', methods: ['POST'])]
    public function deactivate(int $id): Response
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $this->userService->deactivateUser($user);

        $this->addFlash('success', 'User has been deactivated successfully.');
        return $this->redirectToRoute('user_index');
    }

    #[Route('/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Check CSRF token
        if (!$this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token');
            return $this->redirectToRoute('user_show', ['id' => $id]);
        }

        // Don't allow deleting your own account
        if ($this->getUser() && $user->getId() === $this->getUser()->getId()) {
            $this->addFlash('danger', 'You cannot delete your own account.');
            return $this->redirectToRoute('user_show', ['id' => $id]);
        }

        // Check if user can be deleted
        $errors = $this->userService->canDeleteUser($user);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error);
            }
            return $this->redirectToRoute('user_show', ['id' => $id]);
        }

        // Delete the user
        $this->userService->deleteUser($user);

        $this->addFlash('success', 'User has been deleted successfully.');
        return $this->redirectToRoute('user_index');
    }
}
