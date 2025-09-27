<?php

declare(strict_types=1);

namespace App\Domain\Lead\Repository;

use App\Domain\Lead\Model\Lead;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;

interface LeadRepositoryInterface
{
    public function save(Lead $lead): void;

    public function findById(int $id): ?Lead;

    public function findAll(): array;

    public function findBySource(string $source): array;

    public function findByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array;

    public function remove(Lead $lead): void;

    public function createAllLeadsQueryBuilder(): QueryBuilder;

    public function addSourceFilter(QueryBuilder $queryBuilder, string $source): QueryBuilder;

    public function addSearchFilter(QueryBuilder $queryBuilder, string $search): QueryBuilder;

    public function getPaginatedLeads(QueryBuilder $queryBuilder, int $page, int $limit): PaginationInterface;
}