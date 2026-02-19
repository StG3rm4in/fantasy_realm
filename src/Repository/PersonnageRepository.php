<?php

namespace App\Repository;

use App\Entity\Personnage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Personnage>
 */
class PersonnageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personnage::class);
    }

    /**
     * Find shared and authorized characters
     */
    public function findSharedAndAuthorized(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.partage = :partage')
            ->andWhere('p.autorise = :autorise')
            ->setParameter('partage', true)
            ->setParameter('autorise', true)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find characters by user
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.utilisateur = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
