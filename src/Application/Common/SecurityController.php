<?php

declare(strict_types=1);

namespace App\Application\Common;

use App\Application\Form\ChangePasswordType;
use App\Application\Form\ProfileType;
use App\Application\Form\RegistrationType;
use App\Application\User\UserService;
use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private UserService $userService;
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserService $userService,
        UserPasswordHasherInterface $passwordHasher,
        UserRepositoryInterface $userRepository
    ) {
        $this->userService = $userService;
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on your firewall
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('homepage');
        }

        // Create a new user with temporary values that will be overwritten by the form
        $user = new User(
            'temp@example.com', // Will be overwritten by form data
            'temp-password',    // Will be overwritten by form data
            'Temp Company'     // Will be overwritten by form data
        );
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set default values for new user
            $user->setRole(UserRole::CLIENT);
            $user->setIsActive(true);

            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);

            // Save the user using the repository
            $this->userRepository->save($user);

            $this->addFlash('success', 'Your account has been created. You can now log in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // Profile form
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        // Password change form
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->save($user);
            $this->addFlash('success', 'Your profile has been updated');

            return $this->redirectToRoute('app_profile');
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = $passwordForm->get('currentPassword')->getData();
            $newPassword = $passwordForm->get('newPassword')->getData();

            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('danger', 'Current password is incorrect.');
                return $this->redirectToRoute('app_profile');
            }

            // Update password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $this->userRepository->save($user);

            $this->addFlash('success', 'Your password has been updated.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('security/profile.html.twig', [
            'form' => $form->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }


}
