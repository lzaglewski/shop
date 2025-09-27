<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Lead\Model\Lead;
use App\Domain\Lead\Repository\LeadRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

class DoctrineLeadRepository implements LeadRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;
    private PaginatorInterface $paginator;

    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $paginator)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Lead::class);
        $this->paginator = $paginator;
    }

    public function save(Lead $lead): void
    {
        $this->entityManager->persist($lead);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Lead
    {
        return $this->repository->find($id);
    }

    public function findAll(): array
    {
        return $this->repository->findBy([], ['submittedAt' => 'DESC']);
    }

    public function findBySource(string $source): array
    {
        return $this->repository->findBy(['source' => $source], ['submittedAt' => 'DESC']);
    }

    public function findByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->repository->createQueryBuilder('l')
            ->where('l.submittedAt >= :from')
            ->andWhere('l.submittedAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('l.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function remove(Lead $lead): void
    {
        $this->entityManager->remove($lead);
        $this->entityManager->flush();
    }

    public function createAllLeadsQueryBuilder(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(Lead::class, 'l')
            ->orderBy('l.submittedAt', 'DESC');
    }

    public function addSourceFilter(QueryBuilder $queryBuilder, string $source): QueryBuilder
    {
        if (!empty($source)) {
            $queryBuilder
                ->andWhere('l.source = :source')
                ->setParameter('source', $source);
        }

        return $queryBuilder;
    }

    public function addSearchFilter(QueryBuilder $queryBuilder, string $search): QueryBuilder
    {
        if (!empty($search)) {
            $queryBuilder
                ->andWhere('l.name LIKE :search OR l.email LIKE :search OR l.subject LIKE :search OR l.message LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $queryBuilder;
    }

    public function getPaginatedLeads(QueryBuilder $queryBuilder, int $page, int $limit): PaginationInterface
    {
        return $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $page,
            $limit
        );
    }
}