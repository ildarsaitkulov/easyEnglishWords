<?php

namespace App\Repository\Podcast;

use App\Entity\Podcast\TelegramSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TelegramSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramSubscription[]    findAll()
 * @method TelegramSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramSubscription::class);
    }

    // /**
    //  * @return TelegramSubscription[] Returns an array of TelegramSubscription objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TelegramSubscription
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
