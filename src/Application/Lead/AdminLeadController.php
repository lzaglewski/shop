<?php

declare(strict_types=1);

namespace App\Application\Lead;

use App\Domain\Lead\Model\Lead;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/leads', name: 'admin_lead_')]
#[IsGranted('ROLE_ADMIN')]
class AdminLeadController extends AbstractController
{
    public function __construct(
        private readonly LeadService $leadService
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $source = $request->query->get('source', '');
        $search = $request->query->get('search', '');
        $limit = 25; // Leads per page

        $pagination = $this->leadService->getPaginatedLeads($page, $limit, $source, $search);

        return $this->render('admin/lead/index.html.twig', [
            'pagination' => $pagination,
            'currentSource' => $source,
            'currentSearch' => $search,
        ]);
    }

    #[Route('/{id}', name: 'details', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function details(int $id): Response
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            throw $this->createNotFoundException('Lead not found');
        }

        return $this->render('admin/lead/details.html.twig', [
            'lead' => $lead,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id): Response
    {
        $lead = $this->leadService->getLeadById($id);

        if (!$lead) {
            throw $this->createNotFoundException('Lead not found');
        }

        $this->leadService->deleteLead($lead);
        $this->addFlash('success', 'Lead został usunięty pomyślnie.');

        return $this->redirectToRoute('admin_lead_list');
    }
}
