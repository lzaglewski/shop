<?php

declare(strict_types=1);

namespace App\Application\Lead;

use App\Domain\Lead\Model\Lead;
use App\Domain\Lead\Repository\LeadRepositoryInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

class LeadService
{
    private LeadRepositoryInterface $leadRepository;

    public function __construct(LeadRepositoryInterface $leadRepository)
    {
        $this->leadRepository = $leadRepository;
    }

    public function createLead(string $name, string $email, string $subject, string $message, string $source): Lead
    {
        $lead = new Lead($name, $email, $subject, $message, $source);
        $this->leadRepository->save($lead);

        return $lead;
    }

    public function getAllLeads(): array
    {
        return $this->leadRepository->findAll();
    }

    public function getLeadsBySource(string $source): array
    {
        return $this->leadRepository->findBySource($source);
    }

    public function getLeadsByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->leadRepository->findByDateRange($from, $to);
    }

    public function getLeadById(int $id): ?Lead
    {
        return $this->leadRepository->findById($id);
    }

    public function deleteLead(Lead $lead): void
    {
        $this->leadRepository->remove($lead);
    }

    public function getPaginatedLeads(int $page = 1, int $limit = 20, string $source = '', string $search = ''): PaginationInterface
    {
        $queryBuilder = $this->leadRepository->createAllLeadsQueryBuilder();

        if (!empty($source)) {
            $this->leadRepository->addSourceFilter($queryBuilder, $source);
        }

        if (!empty($search)) {
            $this->leadRepository->addSearchFilter($queryBuilder, $search);
        }

        return $this->leadRepository->getPaginatedLeads($queryBuilder, $page, $limit);
    }
}