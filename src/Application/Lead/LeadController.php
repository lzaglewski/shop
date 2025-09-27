<?php

declare(strict_types=1);

namespace App\Application\Lead;

use App\Application\Form\ContactFormType;
use App\Application\Form\InfoFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LeadController extends AbstractController
{
    private LeadService $leadService;

    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    #[Route('/contact/submit', name: 'contact_submit', methods: ['POST'])]
    public function submitContactForm(Request $request): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->leadService->createLead(
                $data['name'],
                $data['email'],
                $data['subject'],
                $data['message'],
                'contact'
            );

            $this->addFlash('success', 'contact.form_success');
            return $this->redirectToRoute('contact');
        }

        $this->addFlash('error', 'contact.form_error');
        return $this->redirectToRoute('contact');
    }

    #[Route('/info/submit', name: 'info_submit', methods: ['POST'])]
    public function submitInfoForm(Request $request): Response
    {
        $form = $this->createForm(InfoFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->leadService->createLead(
                $data['name'],
                $data['email'],
                $data['subject'],
                $data['message'],
                'info'
            );

            $this->addFlash('success', 'info.form_success');
            return $this->redirectToRoute('info');
        }

        $this->addFlash('error', 'info.form_error');
        return $this->redirectToRoute('info');
    }
}