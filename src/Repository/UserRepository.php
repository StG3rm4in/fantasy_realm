<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find a user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Find a user by pseudo
     */
    public function findByPseudo(string $pseudo): ?User
    {
        return $this->findOneBy(['pseudo' => $pseudo]);
    }

    /**
     * Find non-suspended users
     *
     * @return User[]
     */
    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.suspendu = :suspendu')
            ->setParameter('suspendu', false)
            ->orderBy('u.pseudo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users by role libelle
     *
     * @return User[]
     */
    public function findByRoleLibelle(string $roleLibelle): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.role', 'r')
            ->andWhere('r.libelle = :libelle')
            ->setParameter('libelle', $roleLibelle)
            ->orderBy('u.pseudo', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
