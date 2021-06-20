<?php

namespace App\Repository\Telegram;

use App\Entity\Telegram\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    
    public function saveTelegramUser(\Zanzara\Telegram\Type\User $user)
    {
        $telegramUser = $this->find($user->getId());
        if ($telegramUser) {
            return $telegramUser;
        }

        $telegramUser = new User();
        $telegramUser->setId($user->getId());
        $telegramUser->setUsername($user->getUsername());
        $telegramUser->setFirstName($user->getFirstName());
        $telegramUser->setLastName($user->getLastName());
        $telegramUser->setLanguageCode($user->getLanguageCode());

        $em = $this->getEntityManager();
        $em->persist($telegramUser);
        $em->flush();
        
        return $telegramUser;
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
